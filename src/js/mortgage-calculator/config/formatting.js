/**
 * Отображение чисел следует языку страницы: группировка тысяч и десятичный
 * разделитель у ru, en и zh разные, а раньше здесь стоял жёсткий ru-RU.
 *
 * @return {string} BCP 47 тег текущего языка.
 */
const currentLocale = () => {
	const tag = window.ajax_object?.tag;

	if (typeof tag === 'string' && tag !== '') return tag;

	return document.documentElement.getAttribute('lang') || 'en-US';
};

export const FORMATTING = Object.freeze({
	locale: currentLocale(),
	currencyUnit: 'AED',
});
