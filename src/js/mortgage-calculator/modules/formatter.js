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