document.addEventListener('DOMContentLoaded', () => {
	'use strict';

	initSubscriptionForm();
});

const initSubscriptionForm = () => {
	let form = document.querySelector('form[name="subscribe_form"]');

	if (!form) return;

	form.addEventListener('submit', (e) => {
		e.preventDefault();

		let emailInput = form.querySelector('input[name="email"]');
		if (0 >= emailInput.value.length) {
			window.showNotification('Wrong email address', 'error');
			return;
		}

		let submitBtn = form.querySelector('button[type="submit"]'),
			submitText = submitBtn.querySelector('span');

		if (submitBtn) {
			submitBtn.disabled = true;
			submitText.textContent = 'Subscribing...';
		}

		let formData = new FormData();

		formData.append('action', 'add_subscriber');
		formData.append('_ajax_nonce', ajax_object._ajax_nonce);
		formData.append('email', emailInput.value);

		fetch(ajax_object.ajax_url, {
			method: 'POST', body: formData, headers: {
				'Accept': 'application/json'
			}
		})
			.then(response => response.json())
			.then(response => {
				setTimeout(() => {
					if (response.success) {
						window.showNotification(response.data.message, 'success');
					} else {
						window.showNotification(response.data.message, 'error');
					}

					emailInput.value = '';
					submitBtn.disabled = false;
					submitText.textContent = 'Subscribe';
				}, 700);
			})
			.catch(error => {
				setTimeout(() => {
					window.showNotification(error, 'error');
					emailInput.value = '';
					submitBtn.disabled = false;
					submitText.textContent = 'Subscribe';
				}, 700);
			});

	});
}