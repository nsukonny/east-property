export const getBedsBathsText = (selectedBeds, selectedBaths) => {
	const bedsArray = Array.from(selectedBeds).sort()
	//const bathsArray = Array.from(selectedBaths).sort()

	let text = ''
	if (bedsArray.length > 0) {
		if (!bedsArray.includes('studio')) {
			text += bedsArray.join(',') + ' bed' + (bedsArray.length > 1 || bedsArray.includes('5+') ? 's' : '')
		} else {
			text += bedsArray.join(',');
		}

	}
	// if (bathsArray.length > 0) {
	//     if (text) text += ', '
	//     text += bathsArray.join(',') + ' bath' + (bathsArray.length > 1 || bathsArray.includes('5+') ? 's' : '')
	// }

	return text || 'Select'
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
