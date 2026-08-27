import {loadSearchData} from "./common/common.js";
import {getBedsBathsText, updateBedsBathsButtons, syncTempBedsBaths} from "./common/beds-baths.js";

document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	void initSearchTabs()
	void initSearchInDropdowns()
})

const containerClosers = new Map()
let globalHandlersAttached = false

const attachGlobalHandlers = () => {
	if (globalHandlersAttached) return

	globalHandlersAttached = true

	document.addEventListener('click', (e) => {
		const target = e.target
		if (!target) return

		for (const [container, close] of containerClosers) {
			if (!container.contains(target)) close()
		}
	})

	document.addEventListener('keydown', (e) => {
		if (e.key !== 'Escape') return

		for (const close of containerClosers.values()) close()
	})
}

const initSearchTabs = async () => {
	const containers = document.querySelectorAll('[data-search-tabs]')
	if (!containers.length) return

	attachGlobalHandlers()

	let searchData = searchTabsData;

	containers.forEach((container) => {
		const tabs = Array.from(container.querySelectorAll('[data-search-tab]'))
		const typeField = container.querySelector('[data-search-type]')
		const developerText = container.querySelector('[data-search-developer-text]')
		const locationText = container.querySelector('[data-search-location-text]')
		const developerValue = container.querySelector('[data-search-developer-value]')
		const locationValue = container.querySelector('[data-search-location-value]')
		const panel = container.querySelector('[role="tabpanel"]')
		const selectors = Array.from(container.querySelectorAll('[data-search-selector]'))

		if (!tabs.length || !typeField || !locationText || !developerText || !locationValue || !developerValue || !panel) return

		const filterBindings = {
			location: {
				text: locationText,
				value: locationValue
			},
			developer: {
				text: developerText,
				value: developerValue
			},
		}

		const getOptionLabel = (filterKey, value) => {
			const filter = searchData?.filters?.[filterKey]
			const option = filter?.options?.find((opt) => opt?.value === value)
			return option?.label ?? value
		}

		const updateDropdownSelection = (filterKey, selectedValue) => {
			const dropdown = container.querySelector(`[data-search-dropdown="${filterKey}"]`)
			if (!dropdown) return

			Array.from(dropdown.querySelectorAll('.tab-option')).forEach((btn) => {
				const btnValue = btn.getAttribute('data-value')
				const isSelected = btnValue === selectedValue
				btn.classList.toggle('is-selected', isSelected)
				btn.setAttribute('aria-selected', isSelected ? 'true' : 'false')
			})
		}

		const setFilterValue = (filterKey, selectedValue) => {
			const binding = filterBindings[filterKey]
			if (!binding) return

			binding.value.value = selectedValue
			binding.text.textContent = getOptionLabel(filterKey, selectedValue)
			updateDropdownSelection(filterKey, selectedValue)
		}

		const closeAllDropdowns = () => {
			selectors.forEach((selector) => {
				selector.classList.remove('is-open')
				selector.setAttribute('aria-expanded', 'false')
				const key = selector.getAttribute('data-search-selector')

				if (!key) return

				const dropdown = container.querySelector(`[data-search-dropdown="${key}"]`)
				if (dropdown) dropdown.hidden = true
			})
		}

		containerClosers.set(container, closeAllDropdowns)

		container.addEventListener('click', (e) => {
			const target = e.target
			if (!target) return

			if (target.closest('[data-search-selector]') || target.closest('.tab-dropdown')) return

			closeAllDropdowns()
		})

		const openDropdown = (filterKey, selectorButton) => {
			closeAllDropdowns()
			selectorButton.classList.add('is-open')
			selectorButton.setAttribute('aria-expanded', 'true')
			const dropdown = container.querySelector(`[data-search-dropdown="${filterKey}"]`)

			if (dropdown) dropdown.hidden = false
		}

		const renderDropdown = (filterKey) => {
			const dropdown = container.querySelector(`[data-search-dropdown="${filterKey}"]`)
			const filter = searchData?.filters?.[filterKey]

			if (!dropdown || !filter?.options?.length) return

			dropdown.replaceChildren()
			filter.options.forEach((opt) => {
				const btn = document.createElement('button')
				btn.type = 'button'
				btn.className = 'tab-option'
				btn.setAttribute('role', 'option')
				btn.setAttribute('data-value', opt.value)
				btn.setAttribute('aria-selected', 'false')

				const text = document.createElement('span')
				text.textContent = opt.label

				const check = document.createElement('img')
				check.className = 'tab-option-check'
				check.src = dropdown.dataset.checkIcon || '/wp-content/themes/east-property/assets/img/check.svg'
				check.width = 16
				check.height = 16
				check.alt = 'Selected'

				btn.append(text, check)
				dropdown.append(btn)
			})

			const currentValue = filterBindings[filterKey]?.value?.value ?? ''
			updateDropdownSelection(filterKey, currentValue)
		}

		const applyCategoryDefaults = (type) => {
			const category = searchData?.categories?.find((c) => c?.slug === type)
			const defaults = category?.defaults

			if (!defaults) return

			if (typeof defaults.developer === 'string') setFilterValue('developer', defaults.developer)
			if (typeof defaults.location === 'string') setFilterValue('location', defaults.location)
		}

		renderDropdown('developer')
		renderDropdown('location')

		const activeTab = tabs.find((t) => t.classList.contains('is-active'))
		const initialType = activeTab?.dataset?.type ?? typeField.value

		if (typeof initialType === 'string' && initialType) {
			typeField.value = initialType
			applyCategoryDefaults(initialType)
		}

		selectors.forEach((selector) => {
			const filterKey = selector.getAttribute('data-search-selector')
			if (!filterKey) return

			selector.addEventListener('click', (e) => {
				e.preventDefault()
				const isOpen = selector.classList.contains('is-open')

				if (isOpen && !e.target.classList.contains('dropdown-search-input')) {
					closeAllDropdowns()
					return
				}

				openDropdown(filterKey, selector)
			})

			const dropdown = container.querySelector(`[data-search-dropdown="${filterKey}"]`)

			if (dropdown) {
				dropdown.addEventListener('click', (e) => {
					const target = e.target ? e.target.closest('.tab-option') : null

					if (!target) {
						return
					}

					const selected = target.getAttribute('data-value')

					if (!selected) return

					setFilterValue(filterKey, selected)
					closeAllDropdowns()
				})
			}
		})

		tabs.forEach((tab) => {
			tab.addEventListener('click', () => {

				if (tab.classList.contains('is-active')) return

				tabs.forEach((item) => {
					item.classList.remove('is-active')
					item.setAttribute('aria-selected', 'false')
					item.setAttribute('tabindex', '-1')
				})

				tab.classList.add('is-active')
				tab.setAttribute('aria-selected', 'true')
				tab.setAttribute('tabindex', '0')
				panel.setAttribute('aria-labelledby', tab.id)

				const type = tab.dataset.type ?? ''
				typeField.value = type
				applyCategoryDefaults(type)
			})
		})

		const bedsBathsSelector = container.querySelector('[data-search-selector="beds_baths"]')
		const bedsBathsDropdown = container.querySelector('[data-search-dropdown="beds_baths"]')
		const bedsBathsText = container.querySelector('[data-search-beds-baths-text]')
		const bedsValueInput = container.querySelector('[data-search-beds-value]')

		if (bedsBathsSelector && bedsBathsDropdown && bedsBathsText && bedsValueInput) {
			let selectedBeds = new Set()
			let selectedBaths = new Set(['2'])
			let tempBeds = new Set(selectedBeds)
			let tempBaths = new Set(selectedBaths)

			const updateDisplayText = () => {
				bedsBathsText.textContent = getBedsBathsText(selectedBeds, selectedBaths)
				bedsValueInput.value = Array.from(selectedBeds).join(',')
			}

			const updateButtonStates = () => {
				updateBedsBathsButtons(bedsBathsDropdown, tempBeds, tempBaths)
			}

			const originalOpen = () => {
				const synced = syncTempBedsBaths(selectedBeds, selectedBaths)
				tempBeds = synced.tempBeds
				tempBaths = synced.tempBaths
				updateButtonStates()
			}

			bedsBathsSelector.addEventListener('click', (e) => {
				if (!bedsBathsSelector.classList.contains('is-open')) {
					originalOpen()
				}
			}, true)

			bedsBathsDropdown.addEventListener('click', (e) => {
				const bedBtn = e.target.closest('[data-beds]')
				const bathBtn = e.target.closest('[data-baths]')

				if (bedBtn) {
					e.stopPropagation()

					const value = bedBtn.dataset.beds

					if (tempBeds.has(value)) {
						tempBeds.delete(value)
					} else {
						tempBeds.add(value)
					}

					updateButtonStates()
				}

				if (bathBtn) {
					e.stopPropagation()

					const value = bathBtn.dataset.baths

					if (tempBaths.has(value)) {
						tempBaths.delete(value)
					} else {
						tempBaths.add(value)
					}
					updateButtonStates()
				}
			})

			const cancelBtn = bedsBathsDropdown.querySelector('.beds-baths-cancel')

			if (cancelBtn) {
				cancelBtn.addEventListener('click', (e) => {
					e.preventDefault()
					e.stopPropagation()
					tempBeds = new Set(selectedBeds)
					tempBaths = new Set(selectedBaths)
					updateButtonStates()
					closeAllDropdowns()
				})
			}

			const applyBtn = bedsBathsDropdown.querySelector('.beds-baths-apply')
			if (applyBtn) {
				applyBtn.addEventListener('click', (e) => {
					e.preventDefault()
					e.stopPropagation()
					selectedBeds = new Set(tempBeds)
					selectedBaths = new Set(tempBaths)
					updateDisplayText()
					closeAllDropdowns()
				})
			}

			updateDisplayText()
		}
	})
}

const initSearchInDropdowns = () => {
	const searchInputs = document.querySelectorAll('.tab-selector .dropdown-search-input')
	if (!searchInputs.length) return

	searchInputs.forEach(searchInput => {
		const tab = searchInput.closest('.tab-field')
		if (!tab) return

		const dropdown = tab.querySelector('.tab-dropdown');
		if (!dropdown) return

		let options = null;

		searchInput.addEventListener('input', () => {
			if (options === options || !options.length) {
				options = dropdown.querySelectorAll('.tab-option')
			}

			const searchValue = searchInput.value.toLowerCase()

			options.forEach(option => {
				const span = option.querySelector('span')
				const optionText = span.textContent.toLowerCase()

				if (0 === searchValue.length) {
					option.classList.remove('hidden')
				}

				if (optionText.includes(searchValue)) {
					option.classList.remove('hidden')

					const regex = new RegExp(`(${searchValue})`, 'gi')
					span.innerHTML = span.textContent.replace(regex, '<b>$1</b>')
				} else {
					span.innerHTML = span.textContent.replace(/<b>|<\/b>/g, '')
					option.classList.add('hidden')
				}
			})

		})
	})
}