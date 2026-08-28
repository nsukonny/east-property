import {disableBodyScroll, enableBodyScroll} from 'body-scroll-lock';
import {WINDOW_WIDTH_LG} from './common/global.js';

document.addEventListener('DOMContentLoaded', () => {
	'use strict';
	initHeaderMenu();
});

const initHeaderMenu = () => {
	const header = document.querySelector('.header');
	const burgerButton = document.querySelector('.burger-button');
	const navElement = document.querySelector('.header-nav');
	const drawerElement = navElement?.querySelector('.header-nav-drawer');
	const closeButton = navElement?.querySelector('.header-nav-close');

	if (!header || !burgerButton || !navElement || !drawerElement) return;

	const closeMenu = () => {
		header.classList.remove('menu-opened');
		burgerButton.setAttribute('aria-expanded', 'false');
		enableBodyScroll(navElement);
	};

	const openMenu = () => {
		header.classList.add('menu-opened');
		burgerButton.setAttribute('aria-expanded', 'true');
		disableBodyScroll(navElement);
	};

	burgerButton.addEventListener('click', (e) => {
		e.stopPropagation();
		if (header.classList.contains('menu-opened')) {
			closeMenu();
		} else {
			openMenu();
		}
	});

	if (closeButton) {
		closeButton.addEventListener('click', (e) => {
			e.stopPropagation();
			closeMenu();
		});
	}

	navElement.addEventListener('click', (e) => {
		if (e.target === navElement) {
			closeMenu();
		}
	});

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && header.classList.contains('menu-opened')) {
			closeMenu();
		}
	});

	let resizeTimer;
	window.addEventListener('resize', () => {
		document.body.classList.add('resize-transitions-disabled');
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(() => {
			document.body.classList.remove('resize-transitions-disabled');
		}, 150);

		if (window.innerWidth >= WINDOW_WIDTH_LG) {
			closeMenu();
		}
	});
};
