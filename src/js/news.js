document.addEventListener('DOMContentLoaded', () => {
	initNewsLoadMore()
	initNewsShare()
})

const copyTextToClipboard = async (text) => {
	if (navigator.clipboard && window.isSecureContext) {
		try {
			await navigator.clipboard.writeText(text)
			return true
		} catch (e) {}
	}

	const textArea = document.createElement('textarea')
	textArea.value = text
	textArea.style.position = 'fixed'
	textArea.style.left = '-999999px'
	textArea.style.top = '-999999px'
	textArea.setAttribute('readonly', '')
	document.body.appendChild(textArea)
	textArea.select()

	let success = false
	try {
		success = document.execCommand('copy')
	} catch (err) {
		success = false
	}
	document.body.removeChild(textArea)
	return success
}

const initNewsLoadMore = () => {
	const loadMoreBtn = document.getElementById('news-load-more')
	const newsGrid = document.getElementById('news-grid')

	if (!loadMoreBtn || !newsGrid) return

	loadMoreBtn.addEventListener('click', async () => {
		const nextPage = parseInt(loadMoreBtn.dataset.nextPage, 10) || 1
		const totalPages = parseInt(loadMoreBtn.dataset.totalPages, 10) || 1
		const initialText = loadMoreBtn.innerText

		loadMoreBtn.disabled = true
		loadMoreBtn.innerText = 'Loading...'

		try {
			const ajaxUrl = typeof ajax_object !== 'undefined' ? ajax_object.ajax_url : '/wp-admin/admin-ajax.php'
			const formData = new FormData()
			formData.append('action', 'load_more_news')
			formData.append('page', nextPage)

			const response = await fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
			})

			const result = await response.json()

			if (result.success && result.data.html) {
				newsGrid.insertAdjacentHTML('beforeend', result.data.html)

				if (result.data.has_more && result.data.next_page <= totalPages) {
					loadMoreBtn.dataset.nextPage = result.data.next_page
					loadMoreBtn.disabled = false
					loadMoreBtn.innerText = initialText
				} else {
					loadMoreBtn.closest('.news-archive-more')?.remove()
				}
			} else {
				loadMoreBtn.closest('.news-archive-more')?.remove()
			}
		} catch (error) {
			console.error('Error loading news:', error)
			loadMoreBtn.disabled = false
			loadMoreBtn.innerText = initialText
		}
	})
}

const initNewsShare = () => {
	const shareToggle = document.getElementById('news-share-toggle')
	const sharePopover = document.getElementById('news-share-popover')
	const copyBtn = document.getElementById('news-copy-btn')
	const copyNotice = document.getElementById('news-copy-notice')

	if (shareToggle && sharePopover) {
		shareToggle.addEventListener('click', (e) => {
			e.stopPropagation()
			const isOpen = sharePopover.classList.toggle('is-open')
			shareToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false')
		})

		document.addEventListener('click', (e) => {
			if (!sharePopover.contains(e.target) && e.target !== shareToggle) {
				sharePopover.classList.remove('is-open')
				shareToggle.setAttribute('aria-expanded', 'false')
				if (copyNotice) {
					copyNotice.classList.remove('is-visible')
				}
			}
		})
	}

	if (copyBtn) {
		copyBtn.addEventListener('click', async (e) => {
			e.preventDefault()
			e.stopPropagation()
			const url = copyBtn.dataset.url || window.location.href
			
			await copyTextToClipboard(url)

			copyBtn.classList.add('copied')

			if (copyNotice) {
				copyNotice.classList.add('is-visible')
			}

			setTimeout(() => {
				copyBtn.classList.remove('copied')
				if (copyNotice) {
					copyNotice.classList.remove('is-visible')
				}
			}, 2500)
		})
	}
}
