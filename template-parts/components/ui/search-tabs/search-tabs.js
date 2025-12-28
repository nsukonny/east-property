import {loadSearchData} from "../../../../js/common/common.js";

document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	void initSearchTabs()
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

	let searchData

	try {
		searchData = await loadSearchData()
	} catch {
		return
	}

	containers.forEach((container) => {
		const tabs = Array.from(container.querySelectorAll('[data-search-tab]'))
		const typeField = container.querySelector('[data-search-type]')
		const availableText = container.querySelector('[data-search-available-text]')
		const priceText = container.querySelector('[data-search-price-text]')
		const availableValue = container.querySelector('[data-search-available-value]')
		const priceValue = container.querySelector('[data-search-price-value]')
		const panel = container.querySelector('[role="tabpanel"]')
		const selectors = Array.from(container.querySelectorAll('[data-search-selector]'))

		if (!tabs.length || !typeField || !availableText || !priceText || !availableValue || !priceValue || !panel) return

		const filterBindings = {
			available: {
				text: availableText,
				value: availableValue
			},
			price: {
				text: priceText,
				value: priceValue
			}
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
				check.src = dropdown.dataset.checkIcon || '/img/check.svg'
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

			if (typeof defaults.available === 'string') setFilterValue('available', defaults.available)

			if (typeof defaults.price === 'string') setFilterValue('price', defaults.price)
		}

		renderDropdown('available')
		renderDropdown('price')

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

				if (isOpen) {
					closeAllDropdowns()
					return
				}

				openDropdown(filterKey, selector)
			})

			const dropdown = container.querySelector(`[data-search-dropdown="${filterKey}"]`)

			if (dropdown) {
				dropdown.addEventListener('click', (e) => {
					const target = e.target ? e.target.closest('.tab-option') : null

					if (!target) {return}

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
	})
}