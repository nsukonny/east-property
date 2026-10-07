/**
 * Google Analytics events
 */

/**
 * Section of the site the current page belongs to
 *
 * @return {string}
 */
const getPageType = () => {
	const segments = window.location.pathname.split('/').filter(Boolean);
	const languages = window.ajax_object?.languages || [];

	if (languages.includes(segments[0])) {
		segments.shift();
	}

	return segments[0] || 'home';
};

document.addEventListener('click', function (event) {
	if (typeof window.gtag !== 'function') {
		return;
	}

	const pageType = getPageType();

	const brokerModalBtn = event.target.closest('button[data-modal-open="broker-modal"]');
	if (brokerModalBtn) {
		window.gtag('event', 'open_contact_broker_modal', {
			page_type: pageType,
			contact_type: 'broker_modal',
			property_id: brokerModalBtn.dataset.propertyId || '',
			property_name: brokerModalBtn.dataset.propertyTitle || ''
		});

		const openedModal = document.querySelector('.modal-wrapper[data-modal-id="broker-modal"]');
		if (openedModal) {
			openedModal.dataset.propertyId = brokerModalBtn.dataset.propertyId || '';
			openedModal.dataset.propertyTitle = brokerModalBtn.dataset.propertyTitle || '';
		}
	}

	const whatsAppLink = event.target.closest('#bm_whatsapp');
	if (whatsAppLink) {
		const brokerModal = whatsAppLink.closest('.modal-wrapper[data-modal-id="broker-modal"]');
		window.gtag('event', 'contact_whatsapp', {
			page_type: pageType,
			contact_type: 'whatsapp',
			property_id: brokerModal?.dataset.propertyId || '',
			property_name: brokerModal?.dataset.propertyTitle || ''
		});
	}

	const phoneLink = event.target.closest('#bm_phone');
	if (phoneLink) {
		const brokerModal = phoneLink.closest('.modal-wrapper[data-modal-id="broker-modal"]');
		window.gtag('event', 'contact_phone', {
			page_type: pageType,
			contact_type: 'phone',
			property_id: brokerModal?.dataset.propertyId || '',
			property_name: brokerModal?.dataset.propertyTitle || ''
		});
	}

	const sellModalBtn = event.target.closest('button[data-modal-open="contact-manager-modal"]');
	if (sellModalBtn) {
		window.gtag('event', 'opened_sell_my_distress_modal', {
			page_type: pageType,
			contact_type: 'contact_manager_modal'
		});
	}

	const sellWhatsAppLink = event.target.closest('.modal-wrapper[data-modal-id="contact-manager-modal"] .ccm-btn-primary');
	if (sellWhatsAppLink) {
		window.gtag('event', 'sell_my_distress_whatsapp', {
			page_type: pageType,
			contact_type: 'whatsapp'
		});
	}
});

document.addEventListener('subscribe_form_success', function () {
	if (typeof window.gtag !== 'function') {
		return;
	}

	window.gtag('event', 'subscribe_newsletter', {
		page_type: getPageType(),
		contact_type: 'newsletter',
		form_name: 'subscribe_form'
	});
});
