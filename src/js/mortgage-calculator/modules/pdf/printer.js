export function printHtml(html) {
	const iframe = document.createElement('iframe');
	iframe.style.position = 'fixed';
	iframe.style.right = '0';
	iframe.style.bottom = '0';
	iframe.style.width = '0';
	iframe.style.height = '0';
	iframe.style.border = 'none';
	iframe.style.visibility = 'hidden';

	document.body.appendChild(iframe);

	const doc = iframe.contentWindow?.document;
	if (!doc) {
		document.body.removeChild(iframe);
		return;
	}

	doc.open();
	doc.write(html);
	doc.close();

	iframe.contentWindow?.focus();

	setTimeout(() => {
		iframe.contentWindow?.print();
		setTimeout(() => {
			if (iframe.parentNode) {
				iframe.parentNode.removeChild(iframe);
			}
		}, 1500);
	}, 200);
}