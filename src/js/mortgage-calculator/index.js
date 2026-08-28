export * from './config/index.js';
export { calculateMortgage } from './modules/math.js';
export { formatCurrency, parseNumericInput } from './modules/formatter.js';
export { DonutChart } from './modules/donut-chart.js';
export { generateMortgagePdf } from './modules/pdf/index.js';
export { MortgageCalculator } from './modules/controller.js';
import { MortgageCalculator } from './modules/controller.js';

export function initMortgageCalculator(target = '[data-mortgage-calculator]') {
	const root = typeof target === 'string'
		? document.querySelector(target) || document.getElementById('mortgage-calculator')
		: target;

	if (root && root instanceof HTMLElement) {
		return new MortgageCalculator(root);
	}

	return null;
}