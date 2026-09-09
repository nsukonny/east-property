const {__} = window.wp.i18n;

export const getBedsBathsText = (selectedBeds, selectedBaths) => {
	const bedsArray = Array.from(selectedBeds).sort()
	//const bathsArray = Array.from(selectedBaths).sort()

	let text = ''
	if (bedsArray.length > 0) {
		console.log(bedsArray);
		if (!bedsArray.includes('studio')) {
			text += bedsArray.join(',') + ' ' + (bedsArray.length > 1 ? __('Beds', 'east-property') : __('Bed', 'east-property'));
		} else {
			text += bedsArray.join(',');
		}

		text = text.replace('studio', __('Studio', 'east-property'));
	}

	return text || __('Select', 'east-property')
}

export const updateBedsBathsButtons = (container, tempBeds, tempBaths) => {
	const bedButtons = container.querySelectorAll('[data-beds]')
	const bathButtons = container.querySelectorAll('[data-baths]')

	bedButtons.forEach(btn => {
		const value = btn.dataset.beds
		btn.classList.toggle('active', tempBeds.has(value))
	})

	bathButtons.forEach(btn => {
		const value = btn.dataset.baths
		btn.classList.toggle('active', tempBaths.has(value))
	})
}

export const syncTempBedsBaths = (selectedBeds, selectedBaths) => {
	return {
		tempBeds: new Set(selectedBeds),
		tempBaths: new Set(selectedBaths)
	}
}
