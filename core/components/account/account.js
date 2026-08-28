document.addEventListener('DOMContentLoaded', () => {
	'use strict';

	initChangeProjectImages();
	initAvatarUploader();
	initRoleSwitcher();
	initValidationForRegistrationForms();
	initAgreeTerms();
	initAddToFavorites();
	initResendVerification();
	initIsCompleted();
	initLangSwitcher();
});

const initChangeProjectImages = () => {
	let propertyIdInputs = document.querySelectorAll('[data-project-images-switcher] input[type="hidden"]');

	if (!propertyIdInputs) return;

	propertyIdInputs.forEach(propertyIdInput => {
		propertyIdInput.addEventListener('change', () => {
			let propertyId = propertyIdInput.value,
				uploadsBlock = document.querySelector('.uploader-presets .uploader-grid'), formData = new FormData();

			if (!propertyId || !uploadsBlock) return;

			uploadsBlock.classList.add('preloader');

			formData.append('action', 'get_property_presets');
			formData.append('_ajax_nonce', ajax_object._ajax_nonce);
			formData.append('property_id', propertyId);

			fetch(ajax_object.ajax_url, {
				method: 'POST', body: formData, headers: {
					'Accept': 'application/json'
				}
			})
				.then(response => response.json())
				.then(response => {
					if (response.success) {
						uploadsBlock.innerHTML = response.data.html;
					}
					setTimeout(() => {
						uploadsBlock.classList.remove('preloader');
						document.dispatchEvent(new Event('ajaxComplete'));
					}, 700);
				})
				.catch(error => {
					console.log(error);
					setTimeout(() => {
						uploadsBlock.classList.remove('preloader');
					}, 700);
				});
		});
	});
};

const initAvatarUploader = () => {
	const avatarUploader = document.querySelector('.avatar-uploader');
	if (!avatarUploader) return;

	const uploaderWrapper = avatarUploader.querySelector('.avatar-uploader-wrapper');
	if (!uploaderWrapper) return;

	const fileInput = avatarUploader.querySelector('input[type="file"]');
	if (!fileInput) return;
	uploaderWrapper.addEventListener('click', (e) => {
		e.preventDefault();

		fileInput.click();
	});

	const currentAvatarIdInput = avatarUploader.querySelector('input[name="current_avatar_id"]');

	const handleAvatarFile = (file) => {
		if (!file) return;
		if (!file.type || !file.type.startsWith('image/')) return;

		const reader = new FileReader();
		reader.onload = (event) => {
			const img = document.createElement('img');
			img.src = event.target.result;
			img.alt = 'Avatar Preview';
			img.classList.add('avatar-preview');

			uploaderWrapper.innerHTML = '';
			uploaderWrapper.appendChild(img);
		};
		reader.readAsDataURL(file);
		currentAvatarIdInput.value = '';
	};


	fileInput.addEventListener('change', (e) => {
		const file = e.target.files?.[0];
		handleAvatarFile(file);
	});

	// Drag & drop support
	const preventDefaults = (e) => {
		e.preventDefault();
		e.stopPropagation();
	};

	['dragenter', 'dragover'].forEach((eventName) => {
		uploaderWrapper.addEventListener(eventName, (e) => {
			preventDefaults(e);
			uploaderWrapper.classList.add('is-dragover');
		});
	});

	['dragleave', 'dragend', 'drop'].forEach((eventName) => {
		uploaderWrapper.addEventListener(eventName, (e) => {
			preventDefaults(e);
			uploaderWrapper.classList.remove('is-dragover');
		});
	});

	uploaderWrapper.addEventListener('drop', (e) => {
		const file = e.dataTransfer?.files?.[0];
		handleAvatarFile(file);

		// Optional: keep input in sync so form submit includes file
		if (e.dataTransfer?.files?.length) {
			fileInput.files = e.dataTransfer.files;
		}
	});
}

window.updateBoostPoints = function () {
	let boostPointsCounter = document.querySelector('.boost-points .count');

	if (!boostPointsCounter) return;

	let formData = new FormData();
	formData.append('action', 'get_boost_points');
	formData.append('_ajax_nonce', ajax_object._ajax_nonce);

	fetch(ajax_object.ajax_url, {
		method: 'POST', body: formData, headers: {
			'Accept': 'application/json'
		}
	})
		.then(response => response.json())
		.then(response => {
			if (response.success) {
				boostPointsCounter.textContent = response.data.boost_points;
			}
		})
		.catch(error => {
		});

}

const initRoleSwitcher = () => {
	const roleSwitchers = document.querySelectorAll('input[name="user_role"]');
	if (!roleSwitchers) return;

	roleSwitchers.forEach(roleSwitcher => {
		const switcherForm = roleSwitcher.closest('form');
		if (!switcherForm) return;

		const forRoleElements = switcherForm.querySelectorAll('[data-for-role]');
		if (!forRoleElements.length) return;

		forRoleElements.forEach(el => {
			const roles = el.dataset.forRole.split(',').map(r => r.trim());
			if (roles.includes(roleSwitcher.value)) {
				el.style.display = '';
			} else {
				el.style.display = 'none';
			}
		});

		roleSwitcher.addEventListener('change', () => {
			const selectedRole = roleSwitcher.value;

			forRoleElements.forEach(el => {
				const roles = el.dataset.forRole.split(',').map(r => r.trim());
				if (roles.includes(selectedRole)) {
					el.style.display = '';
				} else {
					el.style.display = 'none';
				}
			});
		});
	});
}

const initValidationForRegistrationForms = () => {
	const registrationForms = document.querySelectorAll('.registration-form');
	if (!registrationForms) return;

	registrationForms.forEach(form => {
		form.addEventListener('submit', (e) => {
			const userNameInput = form.querySelector('input[name="first_name"]');
			if (userNameInput && !userNameInput.value.trim()) {
				window.showNotification('Please enter your first name', 'error');

				e.preventDefault();
				return;
			}

			const userEmailInput = form.querySelector('input[name="user_email"]');
			if (userEmailInput && !userEmailInput.value.trim()) {
				window.showNotification('Please enter your email', 'error');
			}

			const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailPattern.test(userEmailInput.value.trim())) {
				window.showNotification('Please enter a valid email address', 'error');
				e.preventDefault();
				return;
			}

			const passwordInput = form.querySelector('input[name="user_password"]'),
				confirmPasswordInput = form.querySelector('input[name="repeat_password"]');

			if (passwordInput.value !== confirmPasswordInput.value) {
				window.showNotification('The password and the password confirmation must match', 'error');

				e.preventDefault();
				return;
			}
		})
	})
}

const initAddToFavorites = () => {
	document.addEventListener('click', (e) => {
		const button = e.target.closest('.button');

		if (!button || !button.classList.contains('toggle-favorite')) return;

		e.preventDefault();

		const unitId = button.dataset.unitId;

		if (!unitId) return;

		let textSpan = button.querySelector('span');

		let formData = new FormData();
		formData.append('action', 'toggle_favorite');
		formData.append('_ajax_nonce', ajax_object._ajax_nonce);
		formData.append('unit_id', unitId);

		fetch(ajax_object.ajax_url, {
			method: 'POST', body: formData, headers: {
				'Accept': 'application/json'
			}
		})
			.then(response => response.json())
			.then(response => {
				if (response.success) {
					if (response.data.is_favorite) {
						button.classList.add('green', 'orange');
						button.classList.remove('gray');
					} else {
						button.classList.add('gray');
						button.classList.remove('green', 'orange');
					}

					if (textSpan) {
						textSpan.textContent = response.data.is_favorite ? 'Saved' : 'Save';
					}
				} else {
					window.showNotification(response.data.message || 'An error occurred 33', 'error');
				}
			})
			.catch(error => {
				window.showNotification(error.message || 'An error occurred', 'error');
			});
	})
}

const initResendVerification = () => {
	const resendVerificationButtons = document.querySelectorAll('.resend-verification-email');
	if (!resendVerificationButtons) return;

	resendVerificationButtons.forEach(btn => {
		btn.addEventListener('click', () => {
			let formData = new FormData();
			formData.append('action', 'resend_verification_email');
			formData.append('_ajax_nonce', ajax_object._ajax_nonce);

			fetch(ajax_object.ajax_url, {
				method: 'POST', body: formData, headers: {
					'Accept': 'application/json'
				}
			})
				.then(response => response.json())
				.then(response => {
					if (response.success) {
						window.showNotification(response.data.message, 'success');
					} else {
						window.showNotification(response.data.message || 'An error occurred', 'error');
					}
				})
				.catch(error => {
					window.showNotification('An error occurred', 'error');
				});
		});
	});
}

const initAgreeTerms = () => {
	const agreeTermsCheckboxes = document.querySelectorAll('[name="accepted_with_policy"]');
	if (!agreeTermsCheckboxes) return;

	agreeTermsCheckboxes.forEach(checkbox => {
		const submitButton = checkbox.closest('form').querySelector('button[type="submit"]');
		if (!submitButton) return;

		checkbox.addEventListener('change', () => {
			submitButton.disabled = !checkbox.checked;
		});
	});
}

const initIsCompleted = () => {
	const form = document.querySelector('form.submit-unit-form');
	if (!form) return;

	// One pair per language fieldset, so pair them up by the fieldset they sit in.
	const isCompletedInputs = form.querySelectorAll('input[data-lang-sync="is_completed"]');
	if (!isCompletedInputs.length) return;

	const applyState = () => {
		isCompletedInputs.forEach(isCompleted => {
			const scope = isCompleted.closest('fieldset') || form;
			const deliveryDate = scope.querySelector('input[data-lang-sync="delivery_date"]');
			if (!deliveryDate) return;

			deliveryDate.disabled = !!isCompleted.checked;
		});
	};

	isCompletedInputs.forEach(isCompleted => {
		isCompleted.addEventListener('change', applyState);
	});

	applyState();
}

const initLangSwitcher = () => {
	const langSwitcher = document.querySelector('.lang-switcher');
	if (!langSwitcher) return;

	const langButtons = document.querySelectorAll('[data-switch-to-lang]');
	if (!langButtons) return;

	const langFields = document.querySelectorAll('[data-show-on-lang]');
	if (!langFields) return;

	let currentLangBtn = document.querySelector('[data-switch-to-lang].current')

	langButtons.forEach(langButton => {
		langButton.addEventListener('click', (e) => {
			e.preventDefault();

			const selectedLangBtn = e.target.closest('a'), lang = selectedLangBtn.dataset.switchToLang;

			currentLangBtn = document.querySelector('[data-switch-to-lang].current');
			currentLangBtn?.classList.remove('current');
			selectedLangBtn.classList.add('current');

			langFields.forEach(langField => {
				if (langField.dataset.showOnLang === selectedLangBtn.dataset.switchToLang) {
					langField.classList.remove('hidden');
				} else {
					langField.classList.add('hidden');
				}
			});

			return false;
		})
	});

	if (currentLangBtn) {
		currentLangBtn.click();
	}

	syncLangValues();
}

/**
 * Clone values on change from same inputs like name="en[price]" to name="ru[price]" and name="ar[price]" and other languages
 *
 * Use data-lang-sync="price" as example
 */
const syncLangValues = () => {
	const syncInputs = document.querySelectorAll('input[data-lang-sync]');

	syncInputs?.forEach(input => {
		input.addEventListener('input', (e) => {
			const value = e.target.value;
			const name = e.target.name;
			const langSyncKey = e.target.dataset.langSync;

			syncInputs.forEach(syncInput => {
				if (syncInput.name === name || syncInput.dataset.langSync !== langSyncKey) {
					return;
				}

				// A checkbox carries its state in `checked`; `value` is the constant
				// it submits when ticked.
				if ('checkbox' === syncInput.type) {
					syncInput.checked = e.target.checked;
					syncInput.dispatchEvent(new Event('change', {bubbles: true}));

					return;
				}

				syncInput.value = value;
			});
		});

		//sync beds and baths buttons
		input.addEventListener('change', (e) => {
			const langSyncKey = e.target.dataset.langSync;

			if ('beds' !== langSyncKey && 'baths' !== langSyncKey) {
				return;
			}

			const value = e.target.value;
			const name = e.target.name;

			syncInputs.forEach(syncInput => {
				if (syncInput.name === name || syncInput.dataset.langSync !== langSyncKey) {
					return;
				}

				let btn = syncInput.parentElement.querySelector('.submit-buttons [data-beds="' + value + '"]');
				btn?.click();

			});
		});
	});

	// Checkbox button groups keep their state in a hidden input and announce it
	// with a change event, so mirror both the value and the active classes.
	const syncCheckboxGroups = document.querySelectorAll('[data-checkbox-buttons][data-lang-sync]');
	syncCheckboxGroups?.forEach(group => {
		const hiddenInput = group.querySelector('input[type="hidden"]');
		if (!hiddenInput) return;

		hiddenInput.addEventListener('change', () => {
			const value = hiddenInput.value;
			const values = value ? value.split(',') : [];
			const langSyncKey = group.dataset.langSync;

			syncCheckboxGroups.forEach(syncGroup => {
				if (syncGroup === group || syncGroup.dataset.langSync !== langSyncKey) {
					return;
				}

				const syncHidden = syncGroup.querySelector('input[type="hidden"]');
				if (syncHidden) syncHidden.value = value;

				syncGroup.querySelectorAll('.checkbox-btn').forEach(btn => {
					btn.classList.toggle('active', values.includes(btn.dataset.value));
				});
			});
		});
	});

	const syncDropdowns = document.querySelectorAll('.dropdown[data-lang-sync]');
	syncDropdowns?.forEach(dropdown => {
		dropdown.addEventListener('change', (e) => {
			const value = e.target.parentElement.querySelector('input[type="hidden"]').value;
			const name = e.target.parentElement.querySelector('input[type="hidden"]').name;
			const langSyncKey = e.target.dataset.langSync;

			syncDropdowns.forEach(syncDropdown => {
				let syncInput = syncDropdown.parentElement.querySelector('input[type="hidden"]');
				if (syncInput.name === name || syncDropdown.dataset.langSync !== langSyncKey) {
					return;
				}

				syncInput.value = value;
				let syncTitle = syncDropdown.querySelector('.dropdown-title');
				syncTitle.textContent = syncDropdown.querySelector('.dropdown-option[data-value="' + value + '"]')?.textContent;
			});
		});
	});

	const bedsBathsButtons = document.querySelectorAll('.submit-buttons-wrapper[data-lang-sync] .beds-baths-btn');
	bedsBathsButtons?.forEach(button => {
		button.addEventListener('click', (e) => {
			const value = e.target.dataset.beds || e.target.dataset.baths;
			console.log(value);
			const langSyncKey = e.target.closest('.submit-buttons-wrapper').dataset.langSync;

			bedsBathsButtons.forEach(syncButton => {
				if (syncButton === button || syncButton.closest('.submit-buttons-wrapper').dataset.langSync !== langSyncKey) {
					return;
				}

				syncButton.classList.remove('active');
				if (syncButton.dataset.beds === value || syncButton.dataset.baths === value) {
					syncButton.classList.add('active');
				}
			});
		});
	});
}