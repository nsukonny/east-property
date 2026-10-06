/**
 * Google Analytics events
 */
document.addEventListener('click', function (event) {
	if (typeof window.gtag !== 'function') {
		return;
	}

	const pageType = window.location.href.split('/')[3];

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
});
