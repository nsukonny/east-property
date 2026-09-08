#!/usr/bin/env bash
#
# Operational tools for the EastProperty installations.
#
# Run on the server that hosts both installations:
#   scripts/tools.sh clone-to-stage [-n] [-y] [--keep-dump]
#
# Every path, database name and domain can be overridden through the
# environment, so the same script serves a differently laid out host:
#   STAGE_DB=other_db scripts/tools.sh clone-to-stage
#
set -eu

SCRIPT_NAME=tools

PROD_PATH=${PROD_PATH:-/var/www/eastproperty.com/public}
STAGE_PATH=${STAGE_PATH:-/var/www/stage.eastproperty.com/public}
STAGE_DB=${STAGE_DB:-stage_east}
PROD_DOMAIN=${PROD_DOMAIN:-eastproperty.com}
STAGE_DOMAIN=${STAGE_DOMAIN:-stage.eastproperty.com}
DUMP_DIR=${DUMP_DIR:-$STAGE_PATH}

# The account the deploy runs under, and the group the web server reads as.
DEPLOY_USER=${DEPLOY_USER:-deploy}
WEB_GROUP=${WEB_GROUP:-www-data}

ASSUME_YES=0
DRY_RUN=0
KEEP_DUMP=0

usage() {
	cat <<'USAGE'
Usage: tools.sh <command> [options]

Commands:
  clone-to-stage   Copy the production database onto staging, rewriting the
                   domain on the way. Staging data is destroyed. Ends with
                   lock-stage, because the import re-enables indexing.
  fix-permissions  Hand the repositories and build directories back to the
                   deploy account. Run as root after any git, npm or composer
                   command that was run as root. Idempotent.
  lock-stage       Keep staging out of search results: noindex site-wide and no
                   XML sitemap. Safe to run at any time.
  help             Show this text.

Output:
  All progress is written to stderr, so capture a run with `2>&1 | tee`.

Options:
  -n, --dry-run    Print the commands that change anything, run nothing.
  -y, --yes        Skip the confirmation prompt.
      --keep-dump  Leave the dump file in place after a successful import.

  TOOLS_TRACE=1    Echo every wp-cli invocation.

Environment:
  PROD_PATH               (default /var/www/eastproperty.com/public)
  STAGE_PATH              (default /var/www/stage.eastproperty.com/public)
  STAGE_DB                (default stage_east)
  PROD_DOMAIN             (default eastproperty.com)
  STAGE_DOMAIN            (default stage.eastproperty.com)
  DUMP_DIR                (default: STAGE_PATH)
  DEPLOY_USER             (default deploy)
  WEB_GROUP               (default www-data)
USAGE
}

# Progress goes to stderr, never to stdout. Values are read back through
# command substitution of wp_at, and a log line on stdout would be captured
# along with the value — which is exactly how 'stage_east' once arrived as
# '[tools] Running wp-cli ...\nstage_east' and failed its own comparison.
# Capture a run with `2>&1 | tee`, not with `>`.
log() {
	printf '[%s] %s\n' "$SCRIPT_NAME" "$1" >&2
}

warn() {
	printf '[%s] WARNING: %s\n' "$SCRIPT_NAME" "$1" >&2
}

die() {
	printf '[%s] %s\n' "$SCRIPT_NAME" "$1" >&2
	exit 1
}

# Runs a command, or only describes it while --dry-run is on. Read-only lookups
# are called directly instead, so a dry run still validates against real state.
run() {
	if [ "$DRY_RUN" -eq 1 ]; then
		printf '[%s] would run: %s\n' "$SCRIPT_NAME" "$*" >&2
		return 0
	fi

	"$@"
}

wp_at() {
	install_path=$1
	shift
	if [ "${TOOLS_TRACE:-0}" = '1' ]; then
		log "wp $*  (at $install_path)"
	fi
	log "Running wp-cli at $install_path: $*"
	wp --path="$install_path" "$@" --allow-root
}

confirm() {
	if [ "$ASSUME_YES" -eq 1 ]; then
		return 0
	fi

	printf '[%s] %s [y/N] ' "$SCRIPT_NAME" "$1"
	read -r answer </dev/tty || answer=

	case $answer in
		y|Y|yes|YES) return 0 ;;
		*) die 'aborted' ;;
	esac
}

# Everything that must hold before a single byte is written. The database name
# check is the important one: it is what keeps an accidental run from importing
# the dump back over production.
preflight() {
	command -v wp >/dev/null 2>&1 || die 'wp-cli is required'

	[ -d "$PROD_PATH" ] || die "production path not found: $PROD_PATH"
	[ -d "$STAGE_PATH" ] || die "staging path not found: $STAGE_PATH"
	[ -d "$DUMP_DIR" ] || die "dump directory not found: $DUMP_DIR"
	[ -w "$DUMP_DIR" ] || die "dump directory is not writable: $DUMP_DIR"

	# Resolved, not compared as text: a trailing slash or a symlink would
	# otherwise pass two names for one installation straight through.
	prod_real=$(CDPATH= cd -- "$PROD_PATH" && pwd -P)
	stage_real=$(CDPATH= cd -- "$STAGE_PATH" && pwd -P)
	[ "$prod_real" != "$stage_real" ] || die "PROD_PATH and STAGE_PATH both resolve to $prod_real"

	# Production has to be a working installation — there is nothing to export
	# otherwise.
	wp_at "$PROD_PATH" core is-installed >/dev/null 2>&1 \
		|| die "no WordPress installation at $PROD_PATH"

	# Staging needs only a config and a database it can reach. On a first clone
	# that database is still empty, and asking `core is-installed` there would
	# turn down exactly the job this command exists to do.
	wp_at "$STAGE_PATH" config path >/dev/null 2>&1 \
		|| die "no wp-config.php reachable from $STAGE_PATH"

	stage_db=$(wp_at "$STAGE_PATH" config get DB_NAME)
	[ "$stage_db" = "$STAGE_DB" ] || die "refusing to touch database '$stage_db' at $STAGE_PATH, expected '$STAGE_DB'"

	prod_db=$(wp_at "$PROD_PATH" config get DB_NAME)
	[ "$prod_db" != "$stage_db" ] || die "production and staging share the database '$prod_db'"

	wp_at "$STAGE_PATH" db query 'SELECT 1;' >/dev/null 2>&1 \
		|| die "cannot reach the staging database '$stage_db': check DB_USER and DB_PASSWORD in $STAGE_PATH/wp-config.php, and that the database itself exists"

	prod_prefix=$(wp_at "$PROD_PATH" config get table_prefix)
	stage_prefix=$(wp_at "$STAGE_PATH" config get table_prefix)
	[ "$prod_prefix" = "$stage_prefix" ] \
		|| die "table prefixes differ: production '$prod_prefix', staging '$stage_prefix'"

	if [ -f "$STAGE_PATH/wp-content/object-cache.php" ]; then
		warn "$STAGE_PATH/wp-content/object-cache.php is present: staging will share the object cache backend with production unless it points at its own database"
	fi
}

# Rewrites the domain scheme by scheme rather than replacing the bare host, so
# addresses like info@eastproperty.com and mail.eastproperty.com stay intact.
# GUIDs are left alone: they are identifiers, not links.
rewrite_domain() {
	for prefix in "https://" "http://" "//"; do
	  wp --path="$STAGE_PATH" search-replace \
			"${prefix}${PROD_DOMAIN}" \
			"${prefix}${STAGE_DOMAIN}" \
			--all-tables-with-prefix \
			--skip-columns=guid \
			--report-changed-only \
			--allow-root
	done
}

# Everything that keeps a clone of a live site out of search results.
#
# Re-applied on every clone on purpose: the import brings production's options
# along, which turns indexing back on and re-enables the sitemap. Only the
# server-level X-Robots-Tag survives an import, which is why the snippet in
# scripts/stage-nginx.conf is the part that really guards this.
#
# Note what is deliberately *not* done here: robots.txt is left crawlable. A
# `Disallow: /` would stop crawlers from ever reading the noindex, and a URL
# discovered through a link could then still be listed, just without a snippet.
lock_stage() {
	log 'Discouraging search engines'
	run wp --path="$STAGE_PATH" option update blog_public 0 --allow-root
}

cmd_lock_stage() {
	preflight
	lock_stage
	log 'Staging is set to noindex. Verify with:'
	log "  curl -sI https://${STAGE_DOMAIN}/ | grep -i x-robots-tag"
	log "  curl -s https://${STAGE_DOMAIN}/ | grep -o \"<meta name='robots'[^>]*>\""
}

# Hands the repositories and build directories back to the deploy account.
#
# Every git, npm or composer command run as root leaves files the deploy account
# cannot overwrite, and the next deploy stops on the first of them — the theme's
# .git, then mu-plugins, then node_modules. This puts all of it back and is safe
# to re-run.
cmd_fix_permissions() {
	[ "$(id -u)" = '0' ] || die 'must run as root: this changes ownership'
	command -v git >/dev/null 2>&1 || die 'git is required'
	id -u "$DEPLOY_USER" >/dev/null 2>&1 || die "no such user: $DEPLOY_USER"
	getent group "$WEB_GROUP" >/dev/null 2>&1 || die "no such group: $WEB_GROUP"

	for install in "$PROD_PATH" "$STAGE_PATH"; do
		[ -d "$install" ] || continue

		theme="$install/wp-content/themes/east-property"
		mu="$install/wp-content/mu-plugins"

		log "$install"

		for repo in "$theme" "$mu"; do
			[ -d "$repo" ] || continue

			log "  $(basename "$repo"): ownership to $DEPLOY_USER:$WEB_GROUP"
			run chown -R "$DEPLOY_USER:$WEB_GROUP" "$repo"

			# setgid, so files written later keep the group the web server reads as.
			run find "$repo" -type d -exec chmod 2775 {} +
			run find "$repo" -type f -exec chmod 664 {} +

			if [ -d "$repo/.git" ]; then
				# A deploy clone has no use for reflogs — every run resets hard — and
				# they are exactly the files a root-run git leaves unwritable.
				log "  $(basename "$repo"): reflogs off"
				run sudo -u "$DEPLOY_USER" git -C "$repo" config core.logAllRefUpdates false
			fi
		done

		[ -d "$theme/scripts" ] && run chmod +x "$theme/scripts/auto-deploy.sh" "$theme/scripts/tools.sh"

		# ACF writes its JSON from the browser, so the web server needs it writable.
		if [ -d "$theme/acf-json" ]; then
			log '  acf-json: writable by the web server'
			run chmod -R g+w "$theme/acf-json"
		fi
	done

	log "Verifying as $DEPLOY_USER"

	for install in "$PROD_PATH" "$STAGE_PATH"; do
		for repo in "$install/wp-content/themes/east-property" "$install/wp-content/mu-plugins"; do
			[ -d "$repo/.git" ] || continue

			if sudo -u "$DEPLOY_USER" git -C "$repo" fetch --dry-run origin >/dev/null 2>&1; then
				log "  ok: $repo"
			else
				warn "still unwritable or unreachable: $repo"
			fi
		done
	done
}

cmd_clone_to_stage() {
	preflight

	dump_file="$STAGE_PATH/eastproperty-prod-$(date +%Y%m%d-%H%M%S).sql"

	log "production: $PROD_PATH"
	log "staging:    $STAGE_PATH (database $STAGE_DB)"
	log "domain:     $PROD_DOMAIN -> $STAGE_DOMAIN"
	log "dump:       $dump_file"
	confirm "This destroys every table in '$STAGE_DB'. Continue?"

	log 'Exporting the production database'
	wp --path="$PROD_PATH" db export "$dump_file" --add-drop-table --allow-root
	chmod 600 "$dump_file"

	log "Emptying $STAGE_DB"
	wp --path="$STAGE_PATH" db reset --yes --allow-root

	log 'Importing the dump into staging'
  wp --path="$STAGE_PATH" db import "$dump_file" --allow-root

	log "Rewriting $PROD_DOMAIN to $STAGE_DOMAIN"
	rewrite_domain

	lock_stage

	log 'Flushing staging caches and rewrite rules'
	wp --path="$STAGE_PATH" transient delete --all --allow-root
	wp --path="$STAGE_PATH" cache flush --allow-root
	wp --path="$STAGE_PATH" rewrite flush --hard --allow-root

	if [ "$KEEP_DUMP" -eq 1 ]; then
		warn "the dump stays at $dump_file; under a web root it is downloadable, so move or remove it"
	else
		log 'Removing the dump'
		rm -f "$dump_file"
	fi

	log "Done: https://${STAGE_DOMAIN}/"
}

main() {
	command=${1:-help}
	[ $# -gt 0 ] && shift || true

	while [ $# -gt 0 ]; do
		case $1 in
			-n|--dry-run) DRY_RUN=1 ;;
			-y|--yes) ASSUME_YES=1 ;;
			--keep-dump) KEEP_DUMP=1 ;;
			-h|--help) usage; exit 0 ;;
			*) die "unknown option: $1" ;;
		esac
		shift
	done

	case $command in
		clone-to-stage) cmd_clone_to_stage ;;
		lock-stage) cmd_lock_stage ;;
		fix-permissions) cmd_fix_permissions ;;
		help|-h|--help) usage ;;
		*) usage >&2; die "unknown command: $command" ;;
	esac
}

# Sourcing the script exposes the functions without running anything, which is
# how the guards above are tested.
if [ "${TOOLS_SH_SOURCE_ONLY:-0}" != '1' ]; then
	main "$@"
fi
