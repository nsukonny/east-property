document.addEventListener('DOMContentLoaded', () => {
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
