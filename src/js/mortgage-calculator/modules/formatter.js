import { FORMATTING } from '../config/index.js';

const numberFormatter = new Intl.NumberFormat(FORMATTING.locale, {
	maximumFractionDigits: 0,
});

export function formatCurrency(value) {
	if (!Number.isFinite(value)) return '0';
	return numberFormatter.format(Math.round(value)).replace(/,/g, ' ');
}

export function parseNumericInput(rawValue) {
	if (typeof rawValue === 'number') {
		return Number.isFinite(rawValue) ? rawValue : 0;
	}
	if (!rawValue) return 0;
	const sanitized = String(rawValue)
		.replace(/\s+/g, '')
		.replace(/,/g, '.')
		.replace(/[^0-9.]/g, '');
	const parsed = parseFloat(sanitized);
	return Number.isFinite(parsed) ? parsed : 0;
}

/**
 * Ставка выводится с той же точностью, с какой её можно выбрать: шаг слайдера
 * 0.05, поэтому округление до одного знака показывало «4» там, где считалось
 * по 4.05.
 *
 * @param {number} value Годовая ставка в процентах.
 *
 * @return {string}
 */
export function formatRate(value) {
	if (!Number.isFinite(value)) return '0';

	return String(Number(value.toFixed(2))).replace('.', ',');
}
