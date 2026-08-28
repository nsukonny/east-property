import {reCalculateDropdownHeight} from "./common/common.js";

document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	toggleDropdown()
})

const toggleDropdown = () => {
	const dropdownButtons = document.querySelectorAll('.dropdown-button')
	const openedDropdowns = document.querySelectorAll('.dropdown.opened')

	if (openedDropdowns.length) {
		openedDropdowns.forEach(dropdown => reCalculateDropdownHeight(dropdown))
	}

	if (!dropdownButtons.length) return

	dropdownButtons.forEach(button => {
		button.addEventListener('click', () => {
			const dropdown = button.closest('.dropdown')
			const dropdownOpen = dropdown.querySelector('.dropdown-content')

			if (!dropdownOpen) return

			if (!dropdown.classList.contains('opened')) {
				dropdown.classList.add('opened')

				reCalculateDropdownHeight(dropdown)
			} else {
				dropdown.classList.remove('opened')
				dropdownOpen.style.height = '0'
			}
		})

		initSearch(button);
	})

	document.addEventListener('click', (e) => {
		const option = e.target.closest('.dropdown-option')
		if (!option) return

		const dropdown = option.closest('.dropdown')
		const button = dropdown.querySelector('.dropdown-button')
		const title = button.querySelector('.dropdown-title')
		const dropdownOpen = dropdown.querySelector('.dropdown-content')
		const hiddenInput = dropdown.closest('.input-wrapper')?.querySelector('input[type="hidden"]')

		if (title) title.textContent = option.textContent
		if (hiddenInput) {
			hiddenInput.value = option.getAttribute('data-value') || option.textContent

			hiddenInput.dispatchEvent(new Event('input', {bubbles: true}))
			hiddenInput.dispatchEvent(new Event('change', {bubbles: true}))
		}

		dropdown.classList.remove('opened')
		if (dropdownOpen) dropdownOpen.style.height = '0'

		dropdown.dispatchEvent(new CustomEvent('change', {detail: {value: option.textContent}}))
	})
}

const initSearch = (button) => {
	const dropdown = button.closest('.dropdown')
	const searchInput = dropdown.querySelector('.dropdown-search input')
	if (!searchInput) return

	const options = dropdown.querySelectorAll('.dropdown-option')
	if (!options.length) return

	searchInput.addEventListener('input', () => {
		const searchValue = searchInput.value.toLowerCase()

		options.forEach(option => {
			const optionText = option.textContent.toLowerCase()

			if (optionText.includes(searchValue)) {
				option.classList.remove('hidden')

				const regex = new RegExp(`(${searchValue})`, 'gi')
				option.innerHTML = option.textContent.replace(regex, '<b>$1</b>')
			} else {
				option.innerHTML = option.textContent.replace(/<b>|<\/b>/g, '')
				option.classList.add('hidden')
			}
		})

		reCalculateDropdownHeight(dropdown)
	})
}

window.addEventListener('resize', () => {
	const dropdowns = document.querySelectorAll('.dropdown.opened')

	if (!dropdowns.length) return

	dropdowns.forEach(dropdown => reCalculateDropdownHeight(dropdown))
})