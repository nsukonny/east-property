document.addEventListener('DOMContentLoaded', () => {
	'use strict';

	initCheckboxButtons();
	initDeleteApproves();
	initAccordion();
});

const initCheckboxButtons = () => {
	const checkboxButtonsGroups = document.querySelectorAll('[data-checkbox-buttons]');
	if (!checkboxButtonsGroups.length) return;

	checkboxButtonsGroups.forEach(group => {
		const buttons = group.querySelectorAll('.checkbox-btn');
		const hiddenInput = group.querySelector('input[type="hidden"]');
		const multiply = group.dataset.multiply === 'true';

		buttons.forEach(btn => {
			btn.addEventListener('click', () => {
				if (!multiply) {
					buttons.forEach(b => b.classList.remove('active'));
				}

				if (btn.classList.contains('active')) {
					btn.classList.remove('active');
				} else {
					btn.classList.add('active');
				}

				const activeButtons = group.querySelectorAll('.checkbox-btn.active');

				hiddenInput.value = Array.from(activeButtons).map(activeBtn => activeBtn.dataset.value).join(',');
				hiddenInput.dispatchEvent(new Event('change', {bubbles: true}));
			});
		});
	});
};

const initDeleteApproves = () => {
	document.addEventListener('click', (e) => {
		const target = e.target;
		if (target.classList.contains('delete_approve_call') || target.classList.contains('cancel')) {
			e.preventDefault();

			let wrapper = target.closest('.delete_wrapper');

			if (wrapper) {
				wrapper.classList.toggle('showed');
			}
		}
	});
}

const initAccordion = () => {
	const accordionGroups = document.querySelectorAll('.accordion');
	if (!accordionGroups.length) return;

	accordionGroups.forEach(accordionGroup => {
		const buttons = accordionGroup.querySelectorAll('button');
		buttons.forEach(button => {
			button.addEventListener('click', () => {
				accordionGroup.classList.toggle('opened');
			});
		});
	});
}
