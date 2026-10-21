#!/usr/bin/env bash
#
# Operational tools for the EastProperty installations.
#
# Run on the server that hosts both installations:
#   scripts/tools.sh clone-to-stage [-n] [-y] [--keep-dump]
#
# Run on the workstation, against the Local by WP Engine site:
#   PROD_SSH=<host> scripts/tools.sh clone-to-local [-n] [-y] [--keep-dump]
#   scripts/tools.sh clone-to-local --dump=<file>
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

# The local clone runs on the workstation, where Local by WP Engine keeps both
# the MySQL socket and a php.ini of its own. Resolved in local_preflight.
LOCAL_PATH=${LOCAL_PATH:-}
LOCAL_DOMAIN=${LOCAL_DOMAIN:-eastproperty.local}
LOCAL_SCHEME=${LOCAL_SCHEME:-http}
LOCAL_SITE_ID=${LOCAL_SITE_ID:-}
LOCAL_PHP=${LOCAL_PHP:-}
LOCAL_PHP_INI=${LOCAL_PHP_INI:-}
LOCAL_MYSQL=${LOCAL_MYSQL:-}
LOCAL_SOCKET=${LOCAL_SOCKET:-}
LOCAL_RUN_DIR=${LOCAL_RUN_DIR:-$HOME/Library/Application Support/Local/run}
LOCAL_SERVICES_DIR=${LOCAL_SERVICES_DIR:-$HOME/Library/Application Support/Local/lightning-services}

# Production is reachable over ssh from the workstation, or the dump is handed
# over with --dump when it is not.
PROD_SSH=${PROD_SSH:-}
PROD_WP=${PROD_WP:-wp}

ASSUME_YES=0
DRY_RUN=0
KEEP_DUMP=0
WP_LOCAL=0
DUMP_FILE=

usage() {
	cat <<'USAGE'
Usage: tools.sh <command> [options]

Commands:
  clone-to-stage   Copy the production database onto staging, rewriting the
                   domain on the way. Staging data is destroyed. Ends with
                   lock-stage, because the import re-enables indexing.
  clone-to-local   Copy the production database onto the Local by WP Engine site
                   on this workstation, rewriting the domain on the way. Local
                   data is destroyed. Runs on the workstation, not the server.
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
      --dump=FILE  clone-to-local: import this dump instead of exporting over
                   ssh. Use it when production is reachable only from your own
                   terminal: export there, copy the file down, pass it here.

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

Environment, clone-to-local only:
  LOCAL_PATH              (default: the WordPress root this script sits in)
  LOCAL_DOMAIN            (default eastproperty.local)
  LOCAL_SCHEME            (default http) scheme the Local site is pinned to
  LOCAL_SITE_ID           (default: the Local site whose nginx root is LOCAL_PATH)
  LOCAL_PHP               (default: newest PHP that Local ships)
  LOCAL_PHP_INI           (default: the php.ini of that Local site)
  PROD_SSH                ssh destination of production; required unless --dump
  PROD_WP                 (default wp) wp-cli on the production host

Examples:
  PROD_SSH=eastproperty scripts/tools.sh clone-to-local
  scripts/tools.sh clone-to-local --dump=~/Downloads/prod.sql.gz -y
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

# One way in to wp-cli for both machines. On the workstation the system `wp`
# cannot reach the database at all: Local keeps the socket under its own run
# directory, and wp-config says DB_HOST=localhost. Hence Local's own PHP with
# its php.ini, and the silenced deprecations — on PHP 8.5 they bury the value a
# caller reads back through command substitution.
wp_cli() {
	install_path=$1
	shift

	if [ "$WP_LOCAL" -eq 1 ]; then
		"$LOCAL_PHP" -c "$LOCAL_PHP_INI" -d error_reporting=0 -d display_errors=0 \
			"$WP_BIN" --path="$install_path" "$@"
		return
	fi

	wp --path="$install_path" "$@" --allow-root
}

wp_at() {
	install_path=$1
	shift
	if [ "${TOOLS_TRACE:-0}" = '1' ]; then
		log "wp $*  (at $install_path)"
	fi
	log "Running wp-cli at $install_path: $*"
	wp_cli "$install_path" "$@"
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

# Pins the scheme of the local site.
#
# Production is on https and the rewrite keeps the scheme it finds, so a clone
# would leave the Local site on https and redirect every plain request to it.
# Local answers on both, but the site was on http before the clone and every
# link we hand around is http.
pin_local_scheme() {
	other=https
	[ "$LOCAL_SCHEME" = 'https' ] && other=http

	wp_cli "$LOCAL_PATH" search-replace \
		"${other}://${LOCAL_DOMAIN}" \
		"${LOCAL_SCHEME}://${LOCAL_DOMAIN}" \
		--all-tables-with-prefix \
		--skip-columns=guid \
		--report-changed-only
}

# Rewrites the domain scheme by scheme rather than replacing the bare host, so
# addresses like info@eastproperty.com and mail.eastproperty.com stay intact.
# GUIDs are left alone: they are identifiers, not links.
rewrite_domain() {
	install_path=$1
	from_domain=$2
	to_domain=$3

	for prefix in "https://" "http://" "//"; do
	  wp_cli "$install_path" search-replace \
			"${prefix}${from_domain}" \
			"${prefix}${to_domain}" \
			--all-tables-with-prefix \
			--skip-columns=guid \
			--report-changed-only
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

# The WordPress root this script sits in: <root>/wp-content/themes/<theme>/scripts.
wp_root_of_script() {
	# BASH_SOURCE, not $0: sourcing the script for its functions would otherwise
	# resolve against the shell and land one directory short.
	CDPATH= cd -- "$(dirname -- "${BASH_SOURCE[0]:-$0}")/../../../.." && pwd -P
}

# The Local site that serves a path, by its own nginx root. Every Local site
# names its database `local`, so a connection that succeeds proves nothing about
# which site answered — the path is the only thing that identifies one.
local_site_id_for() {
	target=$1

	for conf in "$LOCAL_RUN_DIR"/*/conf/nginx/site.conf; do
		[ -f "$conf" ] || continue

		if grep -Fq "root   \"$target\"" "$conf" || grep -Fq "root \"$target\"" "$conf"; then
			basename "$(dirname "$(dirname "$(dirname "$conf")")")"
			return 0
		fi
	done

	return 1
}

# Newest PHP that Local ships. The binary only has to run wp-cli; the database
# socket comes from the php.ini of the site, not from the build.
local_php_binary() {
	newest_under "$LOCAL_SERVICES_DIR"/php-*/bin/*/bin/php
}

# Newest mysql client Local ships. Needed because wp-cli cannot import here: its
# db commands shell out to a client that reads the default socket, and the
# Homebrew one looks in /tmp. Local's own client takes --socket and connects.
local_mysql_binary() {
	newest_under "$LOCAL_SERVICES_DIR"/mysql-*/bin/*/bin/mysql
}

# Highest versioned path out of a glob of lightning-services binaries.
#
# Globbed, never listed: the path carries "Application Support", and word
# splitting on a command substitution loses the binary at the space.
newest_under() {
	best=
	best_version=

	for candidate in "$@"; do
		[ -x "$candidate" ] || continue

		version=${candidate#*/lightning-services/}
		version=${version#*-}
		version=${version%%/*}

		if [ -z "$best" ] \
			|| [ "$(printf '%s\n%s\n' "$best_version" "$version" | sort -V | tail -1)" = "$version" ]; then
			best=$candidate
			best_version=$version
		fi
	done

	[ -n "$best" ] || return 1

	printf '%s' "$best"
}

# The Local MySQL socket, as the site's own php.ini spells it.
local_socket_from_ini() {
	sed -n 's/^[[:space:]]*mysqli.default_socket[[:space:]]*=[[:space:]]*"\(.*\)"/\1/p' "$1" | head -1
}

# Local's mysql client against the site database. The password goes through the
# environment, never the command line, where ps would show it.
local_mysql() {
	MYSQL_PWD="$LOCAL_DB_PASS" "$LOCAL_MYSQL" \
		--socket="$LOCAL_SOCKET" \
		-u"$LOCAL_DB_USER" \
		"$@"
}

# Collations MySQL has no idea about, renamed to the nearest one it does.
#
# Production runs MariaDB 11, which stamps its own UCA collations into the dump
# — utf8mb3_uca1400_ai_ci on the two Yoast tables — and the MySQL that Local
# runs stops on them mid-import: "Unknown collation". Only the name after
# COLLATE is touched, so a row that happens to carry the same text survives.
# LC_ALL=C because BSD sed gives up on a UTF-8 dump under any other locale.
mysql_compatible_collations() {
	LC_ALL=C sed -E 's/(COLLATE[= ])([a-z0-9]+)_uca1400_[a-z_]+/\1\2_unicode_ci/g'
}

# Feeds a dump into the site database, gzipped or plain. A function rather than
# an inline pipeline so --dry-run prints the call and not the expanded
# MYSQL_PWD: a run is meant to be captured with tee.
local_mysql_import() {
	case $1 in
		*.gz) gunzip -c "$1" ;;
		*) cat "$1" ;;
	esac | mysql_compatible_collations | local_mysql -D"$LOCAL_DB_NAME"
}

# Streams the production database down over ssh, compressed on the far side.
#
# pipefail in a subshell, because the status of a pipeline is the status of its
# last command: gzip writes a valid, empty archive and reports success even
# after ssh refused the connection, and the next step drops the local database
# for that empty dump. The subshell keeps the option off for the rest of the
# script, where a pipeline closed early by head is normal.
fetch_prod_dump() {
	(
		set -o pipefail
		ssh "$PROD_SSH" "$PROD_WP --path=$PROD_PATH db export - --add-drop-table --allow-root" \
			| gzip -c > "$1"
	)
}

# How much of a dump is read looking for the first statement.
DUMP_PROBE_BYTES=${DUMP_PROBE_BYTES:-2000000}

# Refuses to go on until the file looks like a database dump.
#
# Stands before DROP DATABASE and covers both ways a dump arrives: streamed
# over ssh, and handed over with --dump. head closes the pipe early, so the
# status read here is grep's, which is the point.
assert_usable_dump() {
	[ -s "$1" ] || die "the dump is empty, refusing to drop the database: $1"

	case $1 in
		*.gz) gunzip -c "$1" 2>/dev/null ;;
		*) cat "$1" ;;
	esac | head -c "$DUMP_PROBE_BYTES" \
		| grep -qiE 'CREATE TABLE|INSERT INTO|MySQL dump|MariaDB dump' \
		|| die "the file does not look like a database dump, refusing to drop the database: $1"
}

# Everything that must hold before the local database is dropped.
local_preflight() {
	WP_LOCAL=1

	[ -n "$LOCAL_PATH" ] || LOCAL_PATH=$(wp_root_of_script)

	# Checked before the directory exists, so the refusal is the same whether the
	# command was run on the server by mistake or pointed there by hand.
	case $LOCAL_PATH in
		/var/www/*) die "$LOCAL_PATH looks like a server root: clone-to-local runs on the workstation" ;;
	esac

	[ -d "$LOCAL_PATH" ] || die "local path not found: $LOCAL_PATH"
	LOCAL_PATH=$(CDPATH= cd -- "$LOCAL_PATH" && pwd -P)

	WP_BIN=${WP_BIN:-$(command -v wp || true)}
	[ -n "$WP_BIN" ] || die 'wp-cli is required'

	[ -d "$LOCAL_RUN_DIR" ] || die "Local run directory not found: $LOCAL_RUN_DIR"

	if [ -z "$LOCAL_SITE_ID" ]; then
		LOCAL_SITE_ID=$(local_site_id_for "$LOCAL_PATH") \
			|| die "no Local site serves $LOCAL_PATH — set LOCAL_SITE_ID by hand"
	fi

	[ -n "$LOCAL_PHP_INI" ] || LOCAL_PHP_INI="$LOCAL_RUN_DIR/$LOCAL_SITE_ID/conf/php/php.ini"
	[ -f "$LOCAL_PHP_INI" ] || die "php.ini of the Local site not found: $LOCAL_PHP_INI"

	if [ -z "$LOCAL_PHP" ]; then
		LOCAL_PHP=$(local_php_binary) || die "no PHP binary under $LOCAL_SERVICES_DIR"
	fi
	[ -x "$LOCAL_PHP" ] || die "PHP binary is not executable: $LOCAL_PHP"

	if [ -z "$LOCAL_MYSQL" ]; then
		LOCAL_MYSQL=$(local_mysql_binary) || die "no mysql client under $LOCAL_SERVICES_DIR"
	fi
	[ -x "$LOCAL_MYSQL" ] || die "mysql client is not executable: $LOCAL_MYSQL"

	[ -n "$LOCAL_SOCKET" ] || LOCAL_SOCKET=$(local_socket_from_ini "$LOCAL_PHP_INI")
	[ -S "$LOCAL_SOCKET" ] || die "MySQL socket of the Local site is not there: ${LOCAL_SOCKET:-<none in php.ini>} — is the site running in Local?"

	LOCAL_DB_NAME=$(wp_cli "$LOCAL_PATH" config get DB_NAME)
	LOCAL_DB_USER=$(wp_cli "$LOCAL_PATH" config get DB_USER)
	LOCAL_DB_PASS=$(wp_cli "$LOCAL_PATH" config get DB_PASSWORD)
	[ -n "$LOCAL_DB_NAME" ] || die "cannot read DB_NAME from $LOCAL_PATH/wp-config.php"

	local_mysql -D"$LOCAL_DB_NAME" -e 'SELECT 1;' >/dev/null 2>&1 \
		|| die "cannot reach the Local database '$LOCAL_DB_NAME' over $LOCAL_SOCKET"

	# What identifies the installation is the path: LOCAL_SITE_ID came from the
	# nginx root of the site that serves it, and php.ini and the socket follow
	# from there. Every Local site names its database `local`, so a connection
	# that succeeds proves nothing on its own.
	#
	# home only confirms it, and it cannot be required: a clone that broke
	# halfway leaves production's domain behind, and insisting on the local one
	# would lock the command out of the repair run.
	local_home=$(wp_cli "$LOCAL_PATH" option get home 2>/dev/null || true)
	case $local_home in
		*"$LOCAL_DOMAIN"*) : ;;
		*"$PROD_DOMAIN"*) warn "home here is already '$local_home': a clone landed in this database before, re-cloning over it" ;;
		*) die "home of $LOCAL_PATH is '$local_home', expected '$LOCAL_DOMAIN' or '$PROD_DOMAIN' — wrong site or wrong LOCAL_SITE_ID" ;;
	esac

	if [ -n "$DUMP_FILE" ]; then
		[ -f "$DUMP_FILE" ] || die "dump not found: $DUMP_FILE"
	elif [ -n "$PROD_SSH" ]; then
		command -v ssh >/dev/null 2>&1 || die 'ssh is required to export from production'

		# ssh's own words, not just "cannot reach": the reason is almost always
		# the account, and a bare host silently means the local user name.
		if ! ssh_error=$(ssh -o BatchMode=yes -o ConnectTimeout=10 "$PROD_SSH" true 2>&1); then
			case $PROD_SSH in
				*@*) hint= ;;
				*) hint=" — PROD_SSH carries no user, so ssh tried '$(id -un)@$PROD_SSH'" ;;
			esac

			die "cannot reach $PROD_SSH over ssh: $(printf '%s' "$ssh_error" | tail -1)$hint"
		fi
	else
		die 'set PROD_SSH to export from production, or pass --dump=<file> with a dump exported there by hand'
	fi

	if [ -f "$LOCAL_PATH/wp-content/object-cache.php" ]; then
		warn "$LOCAL_PATH/wp-content/object-cache.php is present: production brings Redis settings along and there is no Redis here"
	fi
}

# Copies the production database onto the Local site, rewriting the domain.
#
# Search engines are left alone on purpose: unlike staging, a Local site answers
# on the loopback only, and blog_public 0 would switch off the Yoast sitemap
# that is often the thing being tested.
cmd_clone_to_local() {
	local_preflight

	dump="$DUMP_FILE"
	fetched=0

	if [ -z "$dump" ]; then
		dump="$(dirname "$LOCAL_PATH")/eastproperty-prod-$(date +%Y%m%d-%H%M%S).sql.gz"
		fetched=1
	fi

	log "production: ${PROD_SSH:-<dump>}:$PROD_PATH"
	log "local:      $LOCAL_PATH (Local site $LOCAL_SITE_ID, database $LOCAL_DB_NAME)"
	log "domain:     $PROD_DOMAIN -> $LOCAL_DOMAIN"
	log "dump:       $dump"
	confirm "This destroys every table in '$LOCAL_DB_NAME' of $LOCAL_DOMAIN. Continue?"

	if [ "$fetched" -eq 1 ]; then
		log 'Exporting the production database over ssh'
		run fetch_prod_dump "$dump"
		run chmod 600 "$dump"
	fi

	assert_usable_dump "$dump"

	log "Emptying $LOCAL_DB_NAME"
	run local_mysql -e "DROP DATABASE IF EXISTS \`$LOCAL_DB_NAME\`; CREATE DATABASE \`$LOCAL_DB_NAME\` DEFAULT CHARACTER SET utf8mb4;"

	log 'Importing the dump'
	run local_mysql_import "$dump"

	log "Rewriting $PROD_DOMAIN to $LOCAL_DOMAIN"
	run rewrite_domain "$LOCAL_PATH" "$PROD_DOMAIN" "$LOCAL_DOMAIN"

	log "Pinning the scheme to $LOCAL_SCHEME"
	run pin_local_scheme

	log 'Flushing caches and rewrite rules'
	run wp_cli "$LOCAL_PATH" transient delete --all
	run wp_cli "$LOCAL_PATH" cache flush
	run wp_cli "$LOCAL_PATH" rewrite flush --hard

	if [ "$fetched" -eq 1 ] && [ "$KEEP_DUMP" -eq 0 ]; then
		log 'Removing the dump'
		run rm -f "$dump"
	elif [ "$fetched" -eq 1 ]; then
		log "the dump stays at $dump"
	fi

	log "Done: http://${LOCAL_DOMAIN}/"
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
	rewrite_domain "$STAGE_PATH" "$PROD_DOMAIN" "$STAGE_DOMAIN"

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
			--dump=*) DUMP_FILE=${1#--dump=} ;;
			--dump) shift; DUMP_FILE=${1:-} ;;
			-h|--help) usage; exit 0 ;;
			*) die "unknown option: $1" ;;
		esac
		shift
	done

	case $command in
		clone-to-stage) cmd_clone_to_stage ;;
		clone-to-local) cmd_clone_to_local ;;
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
