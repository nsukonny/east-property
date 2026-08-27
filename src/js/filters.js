const {__, _n, _x, sprintf} = window.wp.i18n;

document.addEventListener('DOMContentLoaded', () => {
	'use strict'
	void initSearchResultsFilters()
	initPropertiesFilters()
})

const getBedsBathsText = (selectedBeds, selectedBaths) => {
	const bedsArray = Array.from(selectedBeds).sort()
	const bathsArray = Array.from(selectedBaths).sort()

	let text = ''
	if (bedsArray.length > 0) {
		text += bedsArray.join(',') + ' ';
		text += bedsArray.length > 1 || bedsArray.includes('7+') ? __('beds', 'east-property') : __('bed', 'east-property');
	}
	if (bathsArray.length > 0) {
		if (text) text += ', '
		text += bathsArray.join(',') + ' ';
		text += (bathsArray.length > 1 || bathsArray.includes('5+') ? __('baths', 'east-property') : __('bath', 'east-property'))
	}

	return text || __('Select', 'east-property')
}

const updateBedsBathsButtons = (container, tempBeds, tempBaths) => {
	container.querySelectorAll('[data-beds]').forEach(btn => btn.classList.toggle('active', tempBeds.has(btn.dataset.beds)))
	container.querySelectorAll('[data-baths]').forEach(btn => btn.classList.toggle('active', tempBaths.has(btn.dataset.baths)))
}

const initSearchResultsFilters = async () => {
	const buttons = Array.from(document.querySelectorAll('.result-filter[data-filter]'))
	if (!buttons.length) return

	let searchData = searchTabsData;

	const filters = searchData?.filters
	if (!filters) return

	const getLabelByValue = (filterKey, value) => {
		const options = filters?.[filterKey]?.options
		if (!Array.isArray(options)) return value
		return options.find((opt) => opt?.value === value)?.label ?? value
	}

	const applySelection = (button, filterKey, selectedValue) => {
		button.dataset.selectedValue = selectedValue
		button.dispatchEvent(new Event('change', {bubbles: true}));

		const valueEl = button.querySelector('.result-value')
		if (!valueEl) return

		let textSpan = valueEl.querySelector('[data-value-text]')
		if (!textSpan) {
			const img = valueEl.querySelector('img')
			textSpan = document.createElement('span')
			textSpan.setAttribute('data-value-text', 'true')
			valueEl.replaceChildren(textSpan)
			if (img) valueEl.append(img)
		}
		textSpan.textContent = getLabelByValue(filterKey, selectedValue)
	}

	const renderDropdown = (button) => {
		const filterKey = button.dataset.filter
		const options = filters?.[filterKey]?.options
		const dropdown = button.querySelector('.result-dropdown')

		if (!dropdown || !Array.isArray(options)) return

		dropdown.replaceChildren()
		const selectedValue = button.dataset.selectedValue ?? ''

		options.forEach((opt) => {
			const optBtn = document.createElement('button')
			optBtn.type = 'button'
			optBtn.className = `result-option${opt.value === selectedValue ? ' is-selected' : ''}`
			optBtn.setAttribute('data-value', opt.value)

			const text = document.createElement('span')
			text.textContent = opt.label

			const check = document.createElement('img')
			check.className = 'result-option-check'
			check.src = '/wp-content/themes/east-property/assets/img/check.svg'
			check.width = 16
			check.height = 16
			check.alt = 'Selected'

			optBtn.append(text, check)
			dropdown.append(optBtn)
		})
		dropdown.hidden = true
	}

	buttons.forEach((button) => {
		const filterKey = button.dataset.filter
		if (filterKey === 'beds_baths') return

		const options = filters?.[filterKey]?.options
		if (!Array.isArray(options) || !options.length) return

		const valueEl = button.querySelector('.result-value')
		if (!valueEl) return

		const currentLabel = (valueEl.querySelector('[data-value-text]')?.textContent ?? valueEl.textContent ?? '').trim().toLowerCase()
		const initial = options.find((opt) => (opt?.label ?? '').trim().toLowerCase() === currentLabel)
		const initialValue = (!button.dataset.selectedValue && initial?.value) ? options[0]?.value : button.dataset.selectedValue

		applySelection(button, filterKey, initialValue)
		renderDropdown(button)
	})

	let openButton = null

	const closeDropdown = (btn) => {
		if (!btn) return
		const dropdown = btn.querySelector('.result-dropdown') || document.querySelector(`[data-result-dropdown="${btn.dataset.filter}"]`)
		if (dropdown) dropdown.hidden = true
		btn.classList.remove('is-open')
	}

	const openDropdown = (btn) => {
		if (!btn) return
		if (openButton && openButton !== btn) closeDropdown(openButton)
		openButton = btn
		const dropdown = btn.querySelector('.result-dropdown') || document.querySelector(`[data-result-dropdown="${btn.dataset.filter}"]`)
		if (dropdown) dropdown.hidden = false
		btn.classList.add('is-open')
	}

	buttons.forEach((button) => {
		button.addEventListener('click', (e) => {
			if (e.target.classList.contains('dropdown-search-input')) return

			const option = e.target.closest('.result-option')
			const filterKey = button.dataset.filter

			if (!option) {
				button.classList.contains('is-open') ? (closeDropdown(button), openButton = null) : openDropdown(button)
				return
			}

			const selectedValue = option.getAttribute('data-value')
			if (filterKey && selectedValue) {
				applySelection(button, filterKey, selectedValue)
				renderDropdown(button)
			}
			closeDropdown(button)
			openButton = null
		})
	})

	document.addEventListener('click', (e) => {
		if (!openButton || openButton.contains(e.target) || e.target.closest('[data-result-dropdown]')) return
		closeDropdown(openButton)
		openButton = null
	})

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && openButton) {
			closeDropdown(openButton)
			openButton = null
		}
	})

	const bbSelector = document.querySelector('.result-filter[data-filter="beds_baths"]')
	const bbDropdown = document.querySelector('[data-result-dropdown="beds_baths"]')
	const bbText = document.querySelector('[data-result-beds-baths-text]')
	const bbBedsInp = document.querySelector('[data-result-beds-value]')
	const bbBathsInp = document.querySelector('[data-result-baths-value]')

	if (bbSelector && bbDropdown && bbText && bbBedsInp && bbBathsInp) {
		let selectedBeds = new Set(), selectedBaths = new Set()
		let tempBeds = new Set(), tempBaths = new Set()

		if (filters.beds) {
			filters.beds.options.forEach(opt => {
				if (opt.active) {
					selectedBeds.add(opt.value)
					tempBeds.add(opt.value)
				}
			});
		}

		if (filters.baths) {
			filters.baths.options.forEach(opt => {
				if (opt.active) {
					selectedBaths.add(opt.value)
					tempBaths.add(opt.value)
				}
			});
		}

		const updateDisplay = () => {
			bbText.textContent = getBedsBathsText(selectedBeds, selectedBaths)
			bbBedsInp.value = Array.from(selectedBeds).join(',')
			bbBathsInp.value = Array.from(selectedBaths).sort().join(',')
		}

		bbDropdown.addEventListener('click', (e) => {
			e.stopPropagation()
			const bedBtn = e.target.closest('[data-beds]')
			const bathBtn = e.target.closest('[data-baths]')
			if (bedBtn) {
				const val = bedBtn.dataset.beds
				tempBeds.has(val) ? tempBeds.delete(val) : tempBeds.add(val)
				updateBedsBathsButtons(bbDropdown, tempBeds, tempBaths)
			}
			if (bathBtn) {
				const val = bathBtn.dataset.baths
				tempBaths.has(val) ? tempBaths.delete(val) : tempBaths.add(val)
				updateBedsBathsButtons(bbDropdown, tempBeds, tempBaths)
			}

			if (e.target.closest('.beds-baths-cancel')) {
				tempBeds = new Set(selectedBeds)
				tempBaths = new Set(selectedBaths)
				updateBedsBathsButtons(bbDropdown, tempBeds, tempBaths)
				closeDropdown(bbSelector)
				openButton = null
			}
			if (e.target.closest('.beds-baths-apply')) {
				selectedBeds = new Set(tempBeds)
				selectedBaths = new Set(tempBaths)
				updateDisplay()
				closeDropdown(bbSelector)
				openButton = null
				bbSelector.dispatchEvent(new Event('change', {bubbles: true}));
			}
		})
		updateDisplay()
	}
	initSearchInFilters()
}

const initPropertiesFilters = () => {
	const filterItem = document.querySelector('.results-filters-items');
	if (!filterItem) return

	let filterButtons = filterItem.querySelectorAll('button.result-filter');
	if (!filterButtons) return

	filterButtons.forEach(button => {
		button.addEventListener('change', () => {
			updatePropertiesList();
		});
	});
}

const updatePropertiesList = () => {
	const filterItem = document.querySelector('.results-filters-items');
	let filterButtons = filterItem.querySelectorAll('button.result-filter'),
		formData = new FormData(),
		resultsBlock = document.getElementById('result-tabs-list-panel'),
		contentList = resultsBlock.querySelector('.content-list'),
		resultTabs = document.querySelector('.result-tabs'),
		h2Block = resultsBlock.querySelector('.title-top h2'),
		h2BlockSpan = h2Block.querySelector('span'),
		action = filterItem.querySelector('input[name="action"]'),
		mapInstances = document.querySelectorAll('.js-map-instance'),
		allInputs = filterItem?.querySelectorAll('input');

	if (h2BlockSpan) {
		h2Block = h2BlockSpan;
	}

	resultTabs.classList.add('preloader');

	formData.append('action', action.value ?? 'get_properties');
	formData.append('_ajax_nonce', ajax_object._ajax_nonce);
	filterButtons.forEach(button => {
		if (!button.dataset.selectedValue) {
			return;
		}
		formData.append(button.dataset.filter, button.dataset.selectedValue);
	});

	allInputs.forEach(input => {
		if (0 < input.name.length && !formData.has(input.name)) {
			let value = input.value;
			if ('min_price' === input.name || 'max_price' === input.name) {
				value = value.replace(/[^0-9.]/g, '');
			}
			formData.append(input.name, value);
		}
	});

	let urlWithFilters = getUrlWithFilters(formData);
	formData.append('current_href', urlWithFilters);

	fetch(ajax_object.ajax_url, {
		method: 'POST',
		body: formData,
		headers: {
			'Accept': 'application/json'
		}
	})
		.then(response => response.json())
		.then(response => {
			if (response.success) {
				contentList.innerHTML = response.data.properties;
				if (mapInstances.length) {
					mapInstances.forEach(instance => {
						instance.propertyMap.updateProperties(response.data.map_properties);
					});
				}
				h2Block.innerHTML = response.data.properties_found;
				history.replaceState({}, '', urlWithFilters);
			}
			setTimeout(() => {
				resultTabs.classList.remove('preloader');
				document.dispatchEvent(new Event('ajaxComplete'));
			}, 600);
		})
		.catch(error => {
			console.log(error);
			resultTabs.classList.remove('preloader');
		});
}

/**
 * Remove from URl pagination like /page-2/ and add to URL all filter parameters as GET params
 */
const getUrlWithFilters = (formData) => {
	let currentHref = removePaginationFromUrl();

	currentHref += '?filtered=true';

	formData.forEach((value, key) => {
		if (0 === value.length) {
			return;
		}

		if (key !== 'action' && key !== '_ajax_nonce' && key !== '_wp_http_referer' && key !== 'current_href') {
			currentHref += `&${key}=${value}`;
		}
	});

	return currentHref;
}

function removePaginationFromUrl(urlString = window.location.href) {
	const url = new URL(urlString);

	url.pathname = url.pathname
		.replace(/\/page-\d+\/?$/i, '/')
		.replace(/\/page\/\d+\/?$/i, '/')
		.replace(/\/{2,}/g, '/');

	url.searchParams.delete('page');
	url.searchParams.delete('paged');
	url.searchParams.delete('pagination');

	return url.origin + url.pathname;
}

const initSearchInFilters = () => {
	const searchInputs = document.querySelectorAll('.dropdown-search-input')
	if (!searchInputs.length) return

	searchInputs.forEach(searchInput => {
		const button = searchInput.closest('.result-filter')
		if (!button) return

		const dropdown = button.querySelector('.result-dropdown');
		if (!dropdown) return

		let options = null;

		searchInput.addEventListener('input', () => {
			if (options === options || !options.length) {
				options = dropdown.querySelectorAll('.result-option, .tab-option')
			}
			const searchValue = searchInput.value.toLowerCase()

			options.forEach(option => {
				option.addEventListener('click', () => {
					searchInput.value = '';
				});

				const optionText = option.textContent.toLowerCase()
				if (0 === searchValue.length) {
					option.classList.remove('hidden')
					return;
				}

				if (optionText.includes(searchValue)) {
					option.classList.remove('hidden')

					const regex = new RegExp(`(${searchValue})`, 'gi')
					option.innerHTML = option.textContent.replace(regex, '<b>$1</b>')
				} else {
					option.innerHTML = option.textContent.replace(/<b>|<\/b>/g, '')
					option.classList.add('hidden')
				}
			})

		})
	})
}