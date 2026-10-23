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

/**
 * Параметры, которые уходят с каждым событием.
 *
 * Список полный намеренно: модель данных GTM накапливает значения, и ключ,
 * однажды попавший в dataLayer, виден всем следующим событиям. Из-за этого
 * подписка на рассылку уезжала в GA4 с property_id объекта, который посетитель
 * открывал до неё. Каждое событие перезаписывает весь набор, ненужное ему —
 * пустым значением.
 */
const EVENT_PARAMS = ['page_type', 'contact_type', 'property_id', 'property_name', 'form_name'];

/**
 * Событие в dataLayer для Google Tag Manager.
 *
 * Не через gtag: тот кладёт в dataLayer объект arguments, и в контейнере
 *
 * @param {string} name Имя события.
 * @param {Object} params Параметры события.
 *
 * @return {void}
 */
const pushDataLayerEvent = (name, params = {}) => {
	window.dataLayer = window.dataLayer || [];

	const payload = {event: name};

	EVENT_PARAMS.forEach(key => {
		payload[key] = params[key] ?? '';
	});

	window.dataLayer.push(payload);
};

document.addEventListener('click', function (event) {
	const pageType = getPageType();

	const offMarketCta = event.target.closest('[data-offmarket-cta]');
	if (offMarketCta) {
		pushDataLayerEvent('click_unlock_offmarket_whatsapp', {
			page_type: pageType,
			contact_type: 'whatsapp'
		});
	}

	const brokerModalBtn = event.target.closest('button[data-modal-open="broker-modal"]');
	if (brokerModalBtn) {
		pushDataLayerEvent('open_contact_broker_modal', {
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
		pushDataLayerEvent('contact_whatsapp', {
			page_type: pageType,
			contact_type: 'whatsapp',
			property_id: brokerModal?.dataset.propertyId || '',
			property_name: brokerModal?.dataset.propertyTitle || ''
		});
	}

	const phoneLink = event.target.closest('#bm_phone');
	if (phoneLink) {
		const brokerModal = phoneLink.closest('.modal-wrapper[data-modal-id="broker-modal"]');
		pushDataLayerEvent('contact_phone', {
			page_type: pageType,
			contact_type: 'phone',
			property_id: brokerModal?.dataset.propertyId || '',
			property_name: brokerModal?.dataset.propertyTitle || ''
		});
	}

	const sellModalBtn = event.target.closest('button[data-modal-open="contact-manager-modal"]');
	if (sellModalBtn) {
		pushDataLayerEvent('opened_sell_my_distress_modal', {
			page_type: pageType,
			contact_type: 'contact_manager_modal'
		});
	}

	const sellWhatsAppLink = event.target.closest('.modal-wrapper[data-modal-id="contact-manager-modal"] .ccm-btn-primary');
	if (sellWhatsAppLink) {
		pushDataLayerEvent('sell_my_distress_whatsapp', {
			page_type: pageType,
			contact_type: 'whatsapp'
		});
	}
});

document.addEventListener('subscribe_form_success', function () {
	pushDataLayerEvent('subscribe_newsletter', {
		page_type: getPageType(),
		contact_type: 'newsletter',
		form_name: 'subscribe_form'
	});
});
