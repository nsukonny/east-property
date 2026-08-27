import './modal';

import {SEARCH_DATA_URL} from './global.js';

document.addEventListener('DOMContentLoaded', () => {
	'use strict'
})

export const loadSearchData = (() => {
	let cache = null
	return async () => {
		if (cache) return cache

		cache = fetch(SEARCH_DATA_URL, {cache: 'no-cache'}).then(async (res) => {
			if (!res.ok) throw new Error(`Failed to load search data: ${res.status}`)

			return res.json()
		})
		return cache
	}
})()

export const reCalculateDropdownHeight = dropdown => {
	const dropdownOpen = dropdown.querySelector('.dropdown-content'),
		dropdownInner = dropdown.querySelector('.dropdown-inner')

	if (!dropdownOpen || !dropdownInner) return

	dropdownOpen.style.height = `${dropdownInner.getBoundingClientRect().height}px`
}

document.addEventListener('click', (e) => {
	const trigger = e.target.closest('[data-modal-open="plan-modal"]');
	if (!trigger) return;

	const modal = document.querySelector('.modal-wrapper[data-modal-id="plan-modal"]');
	const imgTag = modal?.querySelector('[data-modal-img]');
	if (!imgTag) return;

	const src = trigger.getAttribute('data-plan-src')
		|| trigger.getAttribute('src')
		|| trigger.querySelector('img')?.getAttribute('src');

	if (src) imgTag.src = src;
});

document.addEventListener('click', (e) => {
	const brokerModalBtn = e.target.closest('[data-modal-open="broker-modal"]');
	if (!brokerModalBtn) return;

	const modal = document.querySelector('.modal-wrapper[data-modal-id="broker-modal"]'),
		phoneLink = modal?.querySelector('#bm_phone'),
		whatsAppLink = modal?.querySelector('#bm_whatsapp'),
		brokerPhone = brokerModalBtn.getAttribute('data-broker-phone'),
		brokerWhatsApp = brokerModalBtn.getAttribute('data-broker-whatsapp');

	if (phoneLink && brokerPhone) {
		phoneLink.href = brokerPhone;
	}

	if (whatsAppLink && brokerWhatsApp) {
		whatsAppLink.href = brokerWhatsApp;
	}
});