document.addEventListener('DOMContentLoaded', () => {
	'use strict';

	initDefaultTabs();
});

const initDefaultTabs = () => {
	const containers = document.querySelectorAll('[data-tabs]');

	containers.forEach((container) => {
		const buttons = Array.from(container.querySelectorAll('[data-tab-button]'));
		const panels = Array.from(container.querySelectorAll('[data-tab-panel]'));

		if (!buttons.length || !panels.length) return;

		const buttonByKey = new Map();
		const panelByKey = new Map();

		buttons.forEach((button) => {
			const key = button.dataset.tab;

			if (key) buttonByKey.set(key, button);
		});

		panels.forEach((panel) => {
			const key = panel.dataset.tab;

			if (key) panelByKey.set(key, panel);
		});

		const togglePanel = (panel, isActive) => {
			panel.hidden = !isActive;
			panel.style.display = isActive ? 'flex' : 'none';
		};

		const activate = (key) => {
			if (!key || !buttonByKey.has(key) || !panelByKey.has(key)) return;

			buttons.forEach((button) => {
				const isActive = button.dataset.tab === key;

				button.classList.toggle('active', isActive);
				button.setAttribute('aria-selected', isActive ? 'true' : 'false');
				button.setAttribute('tabindex', isActive ? '0' : '-1');
			});

			panels.forEach((panel) => {
				const isActive = panel.dataset.tab === key;

				togglePanel(panel, isActive);

				if (isActive && panel.id && buttonByKey.get(key)) {
					const controlId = buttonByKey.get(key).id;

					if (controlId) panel.setAttribute('aria-labelledby', controlId);
				}
			});
		};

		buttons.forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();

				const key = button.dataset.tab;

				if (!key) return;

				activate(key);

				let currentUrl = window.location.href,
					newUrl = currentUrl;
				if (currentUrl.includes('tab=')) {
					newUrl = currentUrl.replace(/tab=[^&]*/, 'tab=' + key);
				} else {
					const separator = currentUrl.includes('?') ? '&' : '?';
					newUrl = currentUrl + separator + 'tab=' + key;
				}

				window.history.replaceState(null, '', newUrl);
			});
		});

		const initialKey =
			buttons.find((button) => button.classList.contains('active'))?.dataset.tab ?? buttons[0]?.dataset.tab;

		panels.forEach((panel) => {
			const isActive = panel.dataset.tab === initialKey;
			togglePanel(panel, isActive);
		});

		activate(initialKey);
	});
};
