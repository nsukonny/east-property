#!/usr/bin/env bash
set -eu

REPO_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd -P)
DEPLOY_BRANCH=${1:-main}
WP_ROOT=$(CDPATH= cd -- "$REPO_ROOT/../../.." && pwd -P)

case "$DEPLOY_BRANCH" in
	dev)
		EXPECTED_WP_ROOT="/var/www/stage.eastproperty.com/public"
		;;
	main)
		EXPECTED_WP_ROOT="/var/www/eastproperty.com/public"
		;;
	*)
		printf '[auto-deploy] ERROR: branch %s has no target, expected dev or main\n' \
			"$DEPLOY_BRANCH" >&2
		exit 1
		;;
esac

if [ "$WP_ROOT" != "$EXPECTED_WP_ROOT" ]; then
	printf '[auto-deploy] ERROR: branch %s cannot be deployed to %s\n' \
		"$DEPLOY_BRANCH" "$WP_ROOT" >&2
	exit 1
fi

log() {
	printf '[auto-deploy] %s\n' "$1"
}

discard_repo_changes() {
	repo_path=$1
	git -C "$repo_path" reset --hard HEAD
	git -C "$repo_path" clean -fd
}

resolve_diff_range() {
	repo_path=$1
	old_ref=${2:-}
	new_ref=${3:-}

	if [ -n "$old_ref" ] && [ -n "$new_ref" ] \
		&& git -C "$repo_path" rev-parse -q --verify "${old_ref}^{commit}" >/dev/null 2>&1 \
		&& git -C "$repo_path" rev-parse -q --verify "${new_ref}^{commit}" >/dev/null 2>&1; then
		printf '%s %s' "$old_ref" "$new_ref"
		return 0
	fi

	return 1
}

repo_changed_files_match() {
	repo_path=$1
	pattern=$2
	old_ref=${3:-}
	new_ref=${4:-}

	if range=$(resolve_diff_range "$repo_path" "$old_ref" "$new_ref"); then
		# shellcheck disable=SC2086
		git -C "$repo_path" diff --name-only $range -- | grep -Eq "$pattern"
		return $?
	fi

	return 1
}

install_dependencies() {
	  cd "$REPO_ROOT"
    log "Refreshing npm dependencies"
		npm ci --no-audit --no-fund
}

sync_acf_json() {

	if ! command -v wp >/dev/null 2>&1; then
		printf '[auto-deploy] wp-cli is required to sync ACF JSON\n' >&2
		exit 1
	fi

	if wp --path="$WP_ROOT" cli has-command acf sync --allow-root >/dev/null 2>&1; then
		log "Syncing ACF JSON with \`wp acf sync\`"
		wp --path="$WP_ROOT" acf sync --all --allow-root
		return 0
	fi

	if wp --path="$WP_ROOT" cli has-command acf json --allow-root >/dev/null 2>&1; then
		log "Syncing ACF JSON with \`wp acf json sync\`"
		wp --path="$WP_ROOT" acf json sync --allow-root
		return 0
	fi

	printf '[auto-deploy] no supported ACF sync command found (`wp acf sync` or `wp acf json sync`)\n' >&2
	exit 1
}

reset_transients() {
  if ! command -v wp >/dev/null 2>&1; then
    printf '[auto-deploy] wp-cli is required to reset all transients\n' >&2
    exit 1
  fi

  wp --path="$WP_ROOT" transient delete --all --allow-root
  log "All transients have been reset"

  wp --path="$WP_ROOT" rewrite flush --hard --allow-root
  log "Permalinks rules have been flushed"
}

update_theme_repo() {
	log "Updating theme repo to $DEPLOY_BRANCH"

	git fetch --prune origin "$DEPLOY_BRANCH"
	log "Discarding theme repo changes"
	discard_repo_changes "$REPO_ROOT"
	git switch --force-create "$DEPLOY_BRANCH" "origin/$DEPLOY_BRANCH"
	git reset --hard "origin/$DEPLOY_BRANCH"
	git clean -fd
}

update_mu_plugins() {
	mu_plugins_path="$REPO_ROOT/../../mu-plugins"

	if [ ! -d "$mu_plugins_path/.git" ]; then
		return 0
	fi

	old_mu_head=$(git -C "$mu_plugins_path" rev-parse HEAD)

	log "Updating mu-plugins to $DEPLOY_BRANCH"

	git -C "$mu_plugins_path" fetch --prune origin "$DEPLOY_BRANCH"

	discard_repo_changes "$mu_plugins_path"

	git -C "$mu_plugins_path" checkout -B \
		"$DEPLOY_BRANCH" \
		"origin/$DEPLOY_BRANCH"

	git -C "$mu_plugins_path" reset --hard "origin/$DEPLOY_BRANCH"
	git -C "$mu_plugins_path" clean -fd

	new_mu_head=$(git -C "$mu_plugins_path" rev-parse HEAD)

	if [ "$old_mu_head" = "$new_mu_head" ]; then
		return 0
	fi

	if ! repo_changed_files_match \
		"$mu_plugins_path" \
		'(^|/)(composer\.json|composer\.lock)$' \
		"$old_mu_head" \
		"$new_mu_head"; then
		return 0
	fi

	if ! command -v composer >/dev/null 2>&1; then
		printf '[auto-deploy] composer is required to refresh mu-plugins dependencies\n' >&2
		exit 1
	fi

	log "Refreshing mu-plugins dependencies"

	composer install \
		--working-dir "$mu_plugins_path" \
		--no-interaction \
		--prefer-dist \
		--optimize-autoloader
}

main() {
	cd "$REPO_ROOT"
	log "Running deploy for $DEPLOY_BRANCH in $REPO_ROOT"
	update_theme_repo
	update_mu_plugins
	install_dependencies
	sync_acf_json
	reset_transients
	log "Building assets"
	npm run build
	log "Deploy completed"
}

main
