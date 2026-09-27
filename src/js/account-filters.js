document.addEventListener('change', (event) => {
	const form = event.target.closest('[data-account-filters]')

	if (!form || !['hidden', 'search'].includes(event.target.type)) return

	form.requestSubmit()
})

document.addEventListener('submit', (event) => {
	const form = event.target.closest('[data-account-filters]')

	if (!form) return

	event.preventDefault()

	const params = new URLSearchParams()

	new FormData(form).forEach((value, name) => {
		const trimmed = String(value).trim()

		if ('' !== trimmed && 'all' !== trimmed) {
			params.append(name, trimmed)
		}
	})

	const query = params.toString()

	window.location.assign(query ? `${form.action}?${query}` : form.action)
})
