document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	showContactPanel()
})

const showContactPanel = () => {
	const panel = document.querySelector('.contact-panel')
	const footer = document.querySelector('.footer')

	if (!panel || !footer) return

	window.addEventListener('scroll', () => {
		const footerRect = footer.getBoundingClientRect()

		if (window.scrollY > 100) {
			panel.classList.add('showed')
		} else {
			panel.classList.remove('showed')
		}

		if (footerRect.top <= window.innerHeight) panel.classList.remove('showed')
	})
}