document.addEventListener('DOMContentLoaded', () => {
	'use strict';

	initCookiesNotification();
});

const initCookiesNotification = () => {
	const cookieNotice = document.getElementById('cookie-notice');

	if (!cookieNotice) return;

	const savedChoice = localStorage.getItem('eastproperty_cookie_consent');

	if (savedChoice !== 'accepted' && savedChoice !== 'declined') {
		cookieNotice.style.display = 'flex';
	}

	document.getElementById('cookie-accept')?.addEventListener('click', function () {
		localStorage.setItem('eastproperty_cookie_consent', 'accepted');
		cookieNotice.style.display = 'none';
	});

	document.getElementById('cookie-decline')?.addEventListener('click', function () {
		localStorage.setItem('eastproperty_cookie_consent', 'declined');
		cookieNotice.style.display = 'none';
	});
}
