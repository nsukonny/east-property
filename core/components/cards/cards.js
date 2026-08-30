document.addEventListener('DOMContentLoaded', () => {
	'use strict'

	initCardsActions();
})

const initCardsActions = () => {
	document.addEventListener('click', (e) => {
		const swiperButtons = e.target.closest('.swiper-buttons');
		if (swiperButtons) {
			return;
		}

		if (e.target.closest('button') || e.target.closest('a')) {
			return;
		}

		const card = e.target.closest('.unit-card');
		if (card) {
			e.stopPropagation();
			let cardDetailsBtn = card.querySelector('.unit-card-info-buttons .view_details');
			if (!cardDetailsBtn) return;

			cardDetailsBtn.click();
		}

		const largeCard = e.target.closest('.large-card');
		if (largeCard) {
			e.stopPropagation();
			let propertyDetailsLnk = largeCard.querySelector('.property-details-lnk');
			if (!propertyDetailsLnk) return;

			propertyDetailsLnk.click();
		}


	})
}