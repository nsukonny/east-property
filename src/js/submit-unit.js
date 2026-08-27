document.addEventListener('DOMContentLoaded', () => {
	'use strict';

	initFormInteractions();
	initFormValidation();
});

const initFormInteractions = () => {
	const form = document.querySelector('.submit-unit-form');
	if (!form) return;

	const priceInputs = form.querySelectorAll('input[data-lang-sync="price"]');
	const areaInputs = form.querySelectorAll('input[data-lang-sync="area"]');
	const originalPriceInputs = form.querySelectorAll('input[data-lang-sync="original_price"]');
	const resultSpans = form.querySelectorAll('[data-sqrt-value] span');
	const discountSpans = form.querySelectorAll('[data-discount-value] span');
	const distressBlocks = form.querySelectorAll('[data-distress-fields]');
	const listingTypeInputs = form.querySelectorAll('input[name$="[listing_type]"]');
	const submitBtn = form.querySelector('button[type="submit"]');

	if (!priceInputs.length || !areaInputs.length || !resultSpans.length || !submitBtn) return;

	const formatNumber = (num) => new Intl.NumberFormat('en-US').format(Math.round(num));
	const cleanNumericValue = (val, allowDecimal = false) => {
		if (!allowDecimal) {
			return val.replace(/\D/g, '').replace(/^0+/, '');
		}

		let clean = val.replace(/,/g, '.').replace(/[^\d.]/g, '');
		const firstDot = clean.indexOf('.');

		if (firstDot !== -1) {
			clean = clean.slice(0, firstDot + 1) + clean.slice(firstDot + 1).replace(/\./g, '');
		}

		return clean.replace(/^0+(?=\d)/, '');
	};

	const numberFrom = (root, key) => parseFloat(root.querySelector(`input[data-lang-sync="${key}"]`)?.value) || 0;
	const paint = (spans, text) => spans.forEach(span => {
		span.textContent = text;
	});

	// Keep in sync with the server side in update_unit_translation().
	const discountPercent = (originalPrice, price) => Math.round(((originalPrice - price) / originalPrice) * 100);

	/**
	 * Recalculate the price per square foot and the distress discount for every
	 * locale at once.
	 *
	 * Each language has its own fieldset with its own price, area, original price
	 * and result spans. Values are mirrored between locales by syncLangValues(),
	 * but the mirrored inputs receive no input event, so read the values from the
	 * fieldset being edited and paint the results into all of them.
	 *
	 * @param {HTMLElement|null} scope Fieldset of the edited locale. Falls back
	 *                                 to the first one on the initial render.
	 */
	const updateCalculations = (scope) => {
		const root = scope || form;
		const price = numberFrom(root, 'price');
		const area = numberFrom(root, 'area');
		const originalPrice = numberFrom(root, 'original_price');

		paint(resultSpans, (price > 0 && area > 0) ? ` ~${formatNumber(price / area)} AED / sq ft` : '');
		paint(discountSpans, (price > 0 && originalPrice > price) ? ` ${discountPercent(originalPrice, price)}%` : '');
	};

	/**
	 * Show the original price only for the distress listing type.
	 *
	 * Applies to every locale at once: syncLangValues() mirrors the picked value
	 * into the other dropdowns without firing an event of its own.
	 *
	 * @param {string} listingType Selected listing type slug.
	 */
	const applyListingType = (listingType) => {
		const isDistress = 'distress' === listingType;

		distressBlocks.forEach(block => block.classList.toggle('hidden', !isDistress));

		// Drop a leftover original price so a non-distress unit is not saved with one.
		if (!isDistress) {
			originalPriceInputs.forEach(inp => {
				inp.value = '';
			});
		}

		updateCalculations();
	};

	const checkValidity = () => {
		const requiredElements = form.querySelectorAll('[required], [data-required]');
		const isFormValid = Array.from(requiredElements).every(el =>
			el.type === 'checkbox' ? el.checked : el.value.trim() !== ''
		);
		submitBtn.disabled = !isFormValid;
	};

	const handleNumericInput = (allowDecimal) => (e) => {
		if (e.key.length !== 1 || e.ctrlKey || e.metaKey || /[0-9]/.test(e.key)) {
			return;
		}

		const isSeparator = '.' === e.key || ',' === e.key;

		// Allow a single separator only. On number inputs value is empty while the
		// text is not a valid number yet, so an unknown state blocks the separator.
		if (allowDecimal && isSeparator && e.target.value && !e.target.value.includes('.')) {
			return;
		}

		e.preventDefault();
	};

	const initButtonGroupToggles = () => {
		form.querySelectorAll('.submit-buttons-wrapper').forEach(wrapper => {
			const buttons = wrapper.querySelectorAll('.beds-baths-btn');
			const hiddenInput = wrapper.querySelector('input[type="hidden"]');

			buttons.forEach(btn => {
				btn.addEventListener('click', () => {
					buttons.forEach(b => b.classList.remove('active'));
					btn.classList.add('active');
					hiddenInput.value = btn.dataset.beds || btn.dataset.baths;
					hiddenInput.dispatchEvent(new Event('change', {bubbles: true}));
				});
			});
		});
	};

	const initEvents = () => {
		[[priceInputs, false], [areaInputs, true], [originalPriceInputs, false]].forEach(([inputs, allowDecimal]) => {
			inputs.forEach(inp => {
				inp.addEventListener('keydown', handleNumericInput(allowDecimal));
				inp.addEventListener('input', () => {

					const cleaned = cleanNumericValue(inp.value, allowDecimal);
					if (cleaned !== inp.value) {
						inp.value = cleaned;
					}

					updateCalculations(inp.closest('fieldset'));
				});
			});
		});

		listingTypeInputs.forEach(inp => {
			inp.addEventListener('change', () => applyListingType(inp.value));
		});

		initButtonGroupToggles();
	};

	initEvents();
	updateCalculations();
};


// Matches the minimum the description check has always used.
const DESCRIPTION_MIN_LENGTH = 40;

/**
 * wp_editor() renders one instance per language, so collect them all.
 *
 * @param {HTMLFormElement} form Submit form.
 *
 * @return {string[]} Editor ids present in the form.
 */
const descriptionEditorIds = (form) =>
	Array.from(form.querySelectorAll('textarea[id^="s-desc"]')).map(el => el.id);

/**
 * @param {string} editorId TinyMCE instance id.
 *
 * @return {boolean} Whether that description is below the minimum.
 */
const isDescriptionTooShort = (editorId) => {
	const editor = window.tinymce?.get(editorId);
	if (!editor) return false;

	return editor.getContent().length < DESCRIPTION_MIN_LENGTH;
};

/**
 * Flag the language tabs whose own fieldset is still incomplete, so the broker
 * can see that a hidden tab is holding the submit back.
 *
 * @return {Function|null} Callback that refreshes the flags, null when the form
 *                        has no language switcher.
 */
const initLangValidation = () => {
	const form = document.querySelector('.submit-unit-form');
	if (!form) return null;

	const langLinks = Array.from(document.querySelectorAll('.lang-switcher [data-switch-to-lang]'));
	if (!langLinks.length) return null;

	const isLangIncomplete = (slug) => {
		// Only the fieldset: the submit block carries data-show-on-lang too.
		const fieldset = form.querySelector(`fieldset[data-show-on-lang="${slug}"]`);
		if (!fieldset) return false;

		const incomplete = Array.from(fieldset.querySelectorAll('[required], [data-required]'))
			.some(el => ('checkbox' === el.type ? !el.checked : '' === String(el.value).trim()));

		if (incomplete) return true;

		return isDescriptionTooShort(`s-desc_${slug}`);
	};

	return () => {
		langLinks.forEach(link => {
			link.classList.toggle('has-errors', isLangIncomplete(link.dataset.switchToLang));
		});
	};
};

const initFormValidation = () => {
	const form = document.querySelector('.submit-unit-form');
	if (!form) return;

	const refreshLangErrors = initLangValidation();
	let submitAttempted = false;

	const markSubmitAttempted = () => {
		submitAttempted = true;
		refreshLangErrors?.();
	};

	// The form has no novalidate, so the browser runs its own check first and
	// never fires submit when a required field is empty — and that field may sit
	// in a hidden fieldset, which is exactly the case worth flagging. A click on
	// the submit button happens before that check, so hook it too.
	form.querySelectorAll('button[type="submit"]').forEach(button => {
		button.addEventListener('click', markSubmitAttempted);
	});

	// Only start flagging tabs once the broker has tried to submit: a brand new
	// form is incomplete by definition and would open all red.
	['input', 'change'].forEach(eventName => {
		form.addEventListener(eventName, () => {
			if (submitAttempted) refreshLangErrors?.();
		});
	});

	form.addEventListener('submit', (e) => {
		const requiredElements = form.querySelectorAll('[required], [data-required]');
		let errors = 0;

		requiredElements.forEach(el => {
			let wrapper = el.closest('.submit-buttons-wrapper');

			if (!wrapper) {
				wrapper = el.closest('.checkbox-buttons-wrapper');
			}

			if (!wrapper) {
				wrapper = el.closest('.input-wrapper');
			}

			if (!wrapper) return;

			if (!el.value) {
				wrapper.classList.add('error-field');
				errors++;
			} else {
				wrapper.classList.remove('error-field');
			}
		});

		// A distress unit is pointless without a higher original price to discount from.
		const listingType = form.querySelector('input[name$="[listing_type]"]')?.value || '';
		if ('distress' === listingType) {
			const price = parseFloat(form.querySelector('input[data-lang-sync="price"]')?.value) || 0;
			const originalPriceInputs = form.querySelectorAll('input[data-lang-sync="original_price"]');
			const originalPrice = parseFloat(originalPriceInputs[0]?.value) || 0;
			const isValid = price > 0 && originalPrice > price;

			originalPriceInputs.forEach(inp => {
				inp.closest('.input-group')?.classList.toggle('error-field', !isValid);
			});

			if (!isValid) {
				errors++;
			}
		}

		// One editor per language, and each one is flagged on its own.
		descriptionEditorIds(form).forEach(editorId => {
			const wrapper = document.getElementById(`wp-${editorId}-wrap`)
				?.querySelector('.wp-editor-container');
			if (!wrapper) return;

			if (isDescriptionTooShort(editorId)) {
				wrapper.classList.add('error-field');
				errors++;
			} else {
				wrapper.classList.remove('error-field');
			}
		});

		const finalImageSelectionInp = form.querySelector('input[name="final_image_selection"]'),
			imagesUploaderBlock = form.querySelector('.submit-unit-right .uploader');

		if (finalImageSelectionInp) {
			const finalImageSelection = JSON.parse(finalImageSelectionInp.value);

			if (finalImageSelection.fullOrder.length <= 0) {
				errors++;
				imagesUploaderBlock.classList.add('error-field');
			} else {
				imagesUploaderBlock.classList.remove('error-field');
			}
		}

		markSubmitAttempted();

		if (errors > 0) {
			e.preventDefault();
			return false;
		}

		return true;
	});
}
