#!/usr/bin/env bash
set -eu

REPO_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
DEPLOY_BRANCH=${1:-main}
WP_ROOT=$(CDPATH= cd -- "$REPO_ROOT/../../.." && pwd)

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

	if ! wp --path="$WP_ROOT" cli has-command acf json >/dev/null 2>&1; then
		printf '[auto-deploy] `wp acf json` command is unavailable\n' >&2
		exit 1
	fi

	log "Syncing ACF JSON"
	wp --path="$WP_ROOT" acf json sync
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
	log "Change branch to main";
	git switch main

	log "Updating theme repo"
	git fetch --prune origin "$DEPLOY_BRANCH"
	log "Discarding theme repo changes"
	discard_repo_changes "$REPO_ROOT"
	git reset --hard "origin/$DEPLOY_BRANCH"
	git clean -fd
}

update_mu_plugins() {
	mu_plugins_path="$REPO_ROOT/../../mu-plugins"

	if [ ! -d "$mu_plugins_path/.git" ]; then
		return 0
	fi

	mu_branch=$(git -C "$mu_plugins_path" branch --show-current 2>/dev/null || true)
	case "$mu_branch" in
		main|master)
			;;
		*)
			log "Skipping mu-plugins on branch ${mu_branch:-detached}"
			return 0
			;;
	esac

	old_mu_head=$(git -C "$mu_plugins_path" rev-parse HEAD)

	log "Updating mu-plugins"
	git -C "$mu_plugins_path" fetch --prune origin
	log "Discarding mu-plugins changes"
	discard_repo_changes "$mu_plugins_path"
	git -C "$mu_plugins_path" reset --hard "origin/$mu_branch"
	git -C "$mu_plugins_path" clean -fd

	new_mu_head=$(git -C "$mu_plugins_path" rev-parse HEAD)
	if [ "$old_mu_head" = "$new_mu_head" ]; then
		return 0
	fi

	if ! repo_changed_files_match "$mu_plugins_path" '(^|/)(composer\.json|composer\.lock)$' "$old_mu_head" "$new_mu_head"; then
		return 0
	fi

	if ! command -v composer >/dev/null 2>&1; then
		printf '[auto-deploy] composer is required to refresh mu-plugins dependencies\n' >&2
		exit 1
	fi

	log "Refreshing mu-plugins dependencies"
	composer install --working-dir "$mu_plugins_path" --no-interaction --prefer-dist --optimize-autoloader
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
