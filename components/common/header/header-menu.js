import {disableBodyScroll, enableBodyScroll} from 'body-scroll-lock'
import {WINDOW_WIDTH_LG} from '../../../../js/common/global.js'

document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	initHeaderMenu()
})

const initHeaderMenu = () => {
	const header = document.querySelector('.header')
	const burgerButton = document.querySelector('.burger-button')
	const targetElement = document.querySelector('.header-nav')
	const menuElement = targetElement?.querySelector('ul')

	if (!header || !burgerButton || !targetElement || !menuElement) return

	const closeMenu = () => {
		header.classList.remove('menu-opened')
		burgerButton.setAttribute('aria-expanded', 'false')
		enableBodyScroll(targetElement)
	}

	burgerButton.addEventListener('click', () => {
		header.classList.toggle('menu-opened')
		burgerButton.setAttribute('aria-expanded', header.classList.contains('menu-opened') ? 'true' : 'false')
		header.classList.contains('menu-opened') ? disableBodyScroll(targetElement) : enableBodyScroll(targetElement)
	})

	targetElement.addEventListener('click', (e) => {
		const target = e.target
		if (!(target instanceof Node)) return

		if (!menuElement.contains(target)) closeMenu()
	})

	window.addEventListener('resize', () => {
		if (window.innerWidth >= WINDOW_WIDTH_LG) {
			closeMenu()
		}
	})
}