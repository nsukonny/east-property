#!/bin/sh
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

install_dependencies_if_needed() {
	old_ref=${1:-}
	new_ref=${2:-}

	if [ ! -d node_modules ]; then
		log "Installing npm dependencies"
		npm ci --no-audit --no-fund
		return 0
	fi

	if repo_changed_files_match "$REPO_ROOT" '(^|/)(package\.json|package-lock\.json)$' "$old_ref" "$new_ref"; then
		log "Refreshing npm dependencies"
		npm ci --no-audit --no-fund
	fi
}

sync_acf_json() {
	if ! repo_changed_files_match "$REPO_ROOT" '(^|/)acf-json/.+\.json$' "$old_theme_head" "$new_theme_head"; then
		return 0
	fi

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

discard_submodule_changes() {
	if [ ! -f .gitmodules ]; then
		return 0
	fi

	git submodule foreach --quiet --recursive '
		git reset --hard HEAD
		git clean -fd
	'
}

update_submodules() {
	if [ ! -f .gitmodules ]; then
		return 0
	fi

	log "Discarding submodule changes"
	discard_submodule_changes
	log "Syncing submodules"
	git submodule sync --recursive
	git submodule update --init --recursive --force --jobs 4
}

update_theme_repo() {
	current_branch=$(git branch --show-current 2>/dev/null || true)
	if [ "$current_branch" != "$DEPLOY_BRANCH" ]; then
		log "Skipping deploy on branch ${current_branch:-detached}"
		exit 0
	fi

	old_theme_head=$(git rev-parse HEAD)

	log "Updating theme repo"
	git fetch --prune origin
	log "Discarding theme repo changes"
	discard_repo_changes "$REPO_ROOT"
	git reset --hard "origin/$DEPLOY_BRANCH"
	git clean -fd

	new_theme_head=$(git rev-parse HEAD)
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
	log "Running deploy for $DEPLOY_BRANCH"
	update_theme_repo
	update_submodules
	update_mu_plugins
	install_dependencies_if_needed "$old_theme_head" "$new_theme_head"
	sync_acf_json
	log "Building assets"
	npm run build
	log "Deploy completed"
}

main
