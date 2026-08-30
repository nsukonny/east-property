document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	initBoostModal();
	initForgotModal();
})

const initBoostModal = () => {
	document.addEventListener('submit', e => {
		const form = e.target;

		if (form.name === 'boost_form') {
			e.preventDefault();
			const modalWrapper = form.closest('.modal-wrapper'), modal = modalWrapper?.querySelector('.modal'),
				closeModal = modal?.querySelector('.modal-close');

			if (!modal) return;

			let unitId = modalWrapper.dataset.unit_id;

			if (!unitId) {
				closeModal?.click();
				window.showNotification('Error, please try again', 'error');
				return;
			}

			modal.classList.add('preloader');

			let formData = new FormData();

			const inputs = form.querySelectorAll('input, select, textarea');
			inputs.forEach(input => {
				formData.append(input.name, input.value);
			});

			formData.append('unit_id', unitId);

			fetch(ajax_object.ajax_url, {
				method: 'POST', body: formData, headers: {
					'Accept': 'application/json'
				}
			})
				.then(response => response.json())
				.then(response => {
					setTimeout(() => {
						modal.classList.remove('preloader');
						closeModal?.click();

						if (response.success) {
							window.showNotification(response.data.message, 'success');
						} else {
							window.showNotification(response.data.message, 'error');
						}

						window.updateBoostPoints();
						document.dispatchEvent(new Event('ajaxComplete'));
					}, 700);
				})
				.catch(error => {
					console.log(error);
					setTimeout(() => {
						modal.classList.remove('preloader');
						closeModal?.click();
					}, 700);
				});
		}
	});
}

const initForgotModal = () => {
	const forgotForm = document.querySelector('form[name="forgot-password-form"]');
	if (!forgotForm) return;

	const signInModal = document.querySelector('.signin-modal'),
		signInModalClose = signInModal?.querySelector('.modal-close');

	forgotForm.addEventListener('submit', e => {
		e.preventDefault();

		const modalWrapper = forgotForm.closest('.modal-wrapper'),
			emailInput = modalWrapper.querySelector('input[name="email"]'),
			modal = modalWrapper?.querySelector('.modal'),
			closeModal = modal?.querySelector('.modal-close');

		if (!modal || !emailInput) return;

		if (!emailInput.value) {
			window.showNotification('Please enter your email', 'error');
			return;
		}

		modal.classList.add('preloader');

		let formData = new FormData(forgotForm);
		formData.set('action', 'reset_password');
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
					modal.classList.remove('preloader');
					closeModal?.click();
					signInModalClose?.click();

					if (response.success) {
						window.showNotification(response.data.message, 'success');
					} else {
						window.showNotification(response.data.message, 'error');
					}

					document.dispatchEvent(new Event('ajaxComplete'));
				}, 700);
			})
			.catch(error => {
				setTimeout(() => {
					modal.classList.remove('preloader');
					closeModal?.click();
					signInModalClose?.click();
					window.showNotification(error, 'error');
				}, 700);
			});
	});
}