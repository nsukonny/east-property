import {FORMATTING} from '../config/index.js';

const numberFormatter = new Intl.NumberFormat(FORMATTING.locale, {
	maximumFractionDigits: 0,
});

const rateFormatter = new Intl.NumberFormat(FORMATTING.locale, {
	minimumFractionDigits: 2,
	maximumFractionDigits: 2,
});

/**
 * Разделители, которые Intl ставит в этом языке.
 *
 * Читаются у самого форматтера, а не задаются списком: иначе разбор ввода
 * разошёлся бы с выводом на первом же новом языке.
 *
 * @return {{group: string, decimal: string}}
 */
const separators = () => {
	const parts = new Intl.NumberFormat(FORMATTING.locale).formatToParts(12345.6);
	const of = (type) => parts.find(part => part.type === type)?.value || '';

	return {
		group: of('group'),
		decimal: of('decimal') || '.',
	};
};

const SEPARATORS = separators();

export function formatCurrency(value) {
	if (!Number.isFinite(value)) return '0';

	return numberFormatter.format(Math.round(value));
}

/**
 * Разбор значения, которое пришло от элемента управления, а не от человека.
 *
 * `value` у range-инпута всегда канонический: точка как десятичный разделитель,
 * без группировки, независимо от языка страницы. Пропускать его через разбор
 * локализованного текста нельзя — в немецком точка считается разделителем
 * тысяч, и ставка 4.5 превращалась в 45.
 *
 * @param {string|number} rawValue
 *
 * @return {number}
 */
export function parseControlValue(rawValue) {
	const parsed = typeof rawValue === 'number' ? rawValue : parseFloat(String(rawValue));

	return Number.isFinite(parsed) ? parsed : 0;
}

/**
 * Где в тексте десятичный разделитель.
 *
 * Отделять его от группировки надо до удаления символов: удаление всех точек
 * по признаку «в немецком точка группирует» превращало ставку 4.5 в 45, а
 * обработчик дальше обрезал её до максимума в 8%.
 *
 * @param {string} text
 *
 * @return {number} -1, если в тексте только группировка.
 */
const decimalSeparatorPosition = (text) => {
	const dot = text.lastIndexOf('.');
	const comma = text.lastIndexOf(',');

	// Есть оба: десятичный тот, что правее, второй группирует.
	if (dot !== -1 && comma !== -1) {
		return Math.max(dot, comma);
	}

	const sole = dot !== -1 ? '.' : (comma !== -1 ? ',' : '');

	if (sole === '') return -1;

	// Повторяется — значит группировка: 1.200.000.
	if (text.split(sole).length > 2) return -1;

	// Ровно три цифры следом и это разделитель групп языка — тоже группировка.
	const at = text.lastIndexOf(sole);

	return text.slice(at + 1).length === 3 && sole === SEPARATORS.group ? -1 : at;
};

/**
 * Разбор того, что человек видит в поле.
 *
 * Принимается и разделитель этого языка, и точка с запятой: поле заполняют
 * вручную и копированием из других мест. Пробелы любого вида — неразрывный и
 * узкий неразрывный в том числе — считаются группировкой.
 *
 * @param {string|number} rawValue
 *
 * @return {number}
 */
export function parseNumericInput(rawValue) {
	if (typeof rawValue === 'number') {
		return Number.isFinite(rawValue) ? rawValue : 0;
	}
	if (!rawValue) return 0;

	const text = String(rawValue).replace(/[\s   ]/g, '');
	const at = decimalSeparatorPosition(text);
	const whole = (at === -1 ? text : text.slice(0, at)).replace(/[^0-9\-]/g, '');
	const fraction = at === -1 ? '' : text.slice(at + 1).replace(/[^0-9]/g, '');

	const parsed = parseFloat(fraction === '' ? whole : `${whole}.${fraction}`);

	return Number.isFinite(parsed) ? parsed : 0;
}

/**
 * Ставка выводится с той же точностью, с какой её можно выбрать: шаг слайдера
 * 0.05, поэтому округление до одного знака показывало «4» там, где считалось
 * по 4.05.
 *
 * Знаков всегда два, включая незначащий ноль: иначе длина строки прыгает между
 * «4,5» и «4,55», и поле дёргается, пока тянешь ползунок.
 *
 * @param {number} value Годовая ставка в процентах.
 *
 * @return {string}
 */
export function formatRate(value) {
	if (!Number.isFinite(value)) return rateFormatter.format(0);

	return rateFormatter.format(Number(value.toFixed(2)));
}
