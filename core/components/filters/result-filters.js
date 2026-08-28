document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	initMinMaxFields();
	initPrettyNumbers();
});

const initMinMaxFields = () => {
	const minMaxWrappers = document.querySelectorAll('div[data-filter-field-type="min_max"]');
	if (!minMaxWrappers.length) {
		return;
	}

	minMaxWrappers.forEach(wrapper => {
		const minInput = wrapper.querySelector('.field_min');
		const maxInput = wrapper.querySelector('.field_max');

		if (!minInput || !maxInput) {
			return;
		}

		const filterButton = wrapper.querySelector('button.result-filter');
		if (!filterButton) {
			return;
		}

		const minMaxText = wrapper.querySelector('span[data-result-min-max-text]');

		let minValue = parseFloat(minInput.value.replace(/,/g, ''));
		let maxValue = parseFloat(maxInput.value.replace(/,/g, ''));

		const apply = wrapper.querySelector('button.min-max-apply');
		apply?.addEventListener('click', () => {
			minValue = parseFloat(minInput.value.replace(/,/g, ''));
			maxValue = parseFloat(maxInput.value.replace(/,/g, ''));
			closeDropdown(filterButton);

			const drawMinValue = new Intl.NumberFormat('en-AE', {
				maximumFractionDigits: 0
			}).format(Number(Math.floor(minValue)));
			const drawMaxValue = new Intl.NumberFormat('en-AE', {
				maximumFractionDigits: 0
			}).format(Number(Math.floor(maxValue)));

			minMaxText.textContent = `${drawMinValue} - ${drawMaxValue}`;
			filterButton.dispatchEvent(new Event('change', {bubbles: true}));
		});

		const cancel = wrapper.querySelector('button.min-max-cancel');
		cancel?.addEventListener('click', () => {
			minInput.value = minValue;
			maxInput.value = maxValue;
			closeDropdown(filterButton);
		});
	});

	const closeDropdown = (btn) => {
		if (!btn) return
		const dropdown = btn.querySelector('.result-dropdown') || document.querySelector(`[data-result-dropdown="${btn.dataset.filter}"]`)
		if (dropdown) dropdown.hidden = true
		btn.classList.remove('is-open')
	}
}

const initPrettyNumbers = () => {
	const numericInputs = document.querySelectorAll('input[inputmode="numeric"]');
	if (!numericInputs.length) {
		return;
	}

	numericInputs.forEach(input => {
		input.addEventListener('input', (e) => {
			const raw = e.target.value.replace(/[^\d]/g, '');

			if (!raw) {
				e.target.value = '';
				return;
			}

			e.target.value = new Intl.NumberFormat('en-AE', {
				maximumFractionDigits: 0
			}).format(Number(raw));
		});

		input.dispatchEvent(new Event('input'));
	});
}