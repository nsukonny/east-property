/**
 * Language dropdown in the header and in the mobile menu.
 *
 * The markup ships with the list open and the toggle hidden, so the languages
 * are reachable as plain links when this never runs. Collapsing into a dropdown
 * is the enhancement: it happens here, and only for switchers that have both a
 * toggle and a list.
 */
const LANG_SWITCH = '[data-lang-switch]';

/**
 * Close one switcher.
 *
 * @param {Element} root
 *
 * @return {void}
 */
const closeSwitch = (root) => {
	const toggle = root.querySelector('.lang-switch-toggle');
	const list = root.querySelector('.lang-switch-list');

	if (!toggle || !list) return;

	toggle.setAttribute('aria-expanded', 'false');
	list.hidden = true;
};

/**
 * Close every switcher apart from the one given.
 *
 * @param {Element|null} except
 *
 * @return {void}
 */
const closeOthers = (except) => {
	document.querySelectorAll(LANG_SWITCH).forEach(root => {
		if (root !== except) closeSwitch(root);
	});
};

/**
 * Move focus between the options of an open list.
 *
 * Entering the list from the toggle starts at the language being read, so the
 * first arrow press lands on the selected option rather than the top of the list.
 *
 * @param {Element} root
 * @param {number} step
 *
 * @return {void}
 */
const moveFocus = (root, step) => {
	const options = Array.from(root.querySelectorAll('.lang-switch-option'));

	if (!options.length) return;

	const at = options.indexOf(document.activeElement);

	if (at < 0) {
		const selected = options.findIndex(option => option.classList.contains('is-selected'));

		options[selected < 0 ? 0 : selected].focus();

		return;
	}

	options[(at + step + options.length) % options.length].focus();
};

/**
 * Turn a plain list of links into a collapsed dropdown.
 *
 * @param {Element} root
 *
 * @return {void}
 */
const enhance = (root) => {
	const toggle = root.querySelector('.lang-switch-toggle');
	const list = root.querySelector('.lang-switch-list');

	if (!toggle || !list || root.classList.contains('is-ready')) return;

	root.classList.add('is-ready');
	toggle.setAttribute('aria-expanded', 'false');
	list.hidden = true;
};

/**
 * Enhance every switcher on the page.
 *
 * @return {void}
 */
const enhanceAll = () => document.querySelectorAll(LANG_SWITCH).forEach(enhance);

enhanceAll();
document.addEventListener('DOMContentLoaded', enhanceAll);

document.addEventListener('click', (event) => {
	const target = event.target;

	if (!target || 'function' !== typeof target.closest) return;

	const toggle = target.closest('.lang-switch-toggle');

	if (toggle) {
		const root = toggle.closest(LANG_SWITCH);
		const list = root?.querySelector('.lang-switch-list');

		if (!list) return;

		const opening = list.hidden;

		closeOthers(root);
		toggle.setAttribute('aria-expanded', String(opening));
		list.hidden = !opening;

		return;
	}

	if (!target.closest(LANG_SWITCH)) closeOthers(null);
});

document.addEventListener('keydown', (event) => {
	const target = event.target;

	if (!target || 'function' !== typeof target.closest) return;

	const root = target.closest(LANG_SWITCH);

	if (!root) return;

	const list = root.querySelector('.lang-switch-list');

	if (!list) return;

	if ('Escape' === event.key) {
		closeSwitch(root);
		root.querySelector('.lang-switch-toggle')?.focus();

		return;
	}

	if ('ArrowDown' !== event.key && 'ArrowUp' !== event.key) return;

	event.preventDefault();

	if (list.hidden) {
		closeOthers(root);
		root.querySelector('.lang-switch-toggle')?.setAttribute('aria-expanded', 'true');
		list.hidden = false;
	}

	moveFocus(root, 'ArrowDown' === event.key ? 1 : -1);
});
