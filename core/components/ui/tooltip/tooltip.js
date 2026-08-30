export function initTooltips() {
	const tooltips = document.querySelectorAll('.tooltip');

	if (!tooltips.length) return;

	const closeAllTooltips = () => {
		tooltips.forEach((tooltip) => {
			tooltip.classList.remove('is-open');
			const btn = tooltip.querySelector('.tooltip-btn');

			if (btn) {
				btn.classList.remove('is-active');
				btn.setAttribute('aria-expanded', 'false');
			}
		});
	};

	tooltips.forEach((tooltip) => {
		const btn = tooltip.querySelector('.tooltip-btn');

		if (!btn) return;

		btn.addEventListener('click', (event) => {
			event.stopPropagation();
			const isOpen = tooltip.classList.contains('is-open');
			closeAllTooltips();

			if (!isOpen) {
				tooltip.classList.add('is-open');
				btn.classList.add('is-active');
				btn.setAttribute('aria-expanded', 'true');
			}
		});
	});

	document.addEventListener('click', (event) => {
		if (!event.target.closest('.tooltip')) {
			closeAllTooltips();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			closeAllTooltips();
		}
	});
}

document.addEventListener('DOMContentLoaded', () => {
	initTooltips();
});