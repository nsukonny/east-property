/**
 * Google Analytics events
 */
document.addEventListener('click', function (event) {
	if (typeof window.gtag !== 'function') {
		return;
	}

	const brokerModalBtn = event.target.closest('button[data-modal-open="broker-modal"]');
	if (brokerModalBtn) {
		window.gtag('event', 'open_contact_broker_modal', {
			page_type: 'catalog',
			contact_type: 'broker_modal',
			property_id: brokerModalBtn.dataset.propertyId || '',
			property_name: brokerModalBtn.dataset.propertyTitle || ''
		});
	}
});