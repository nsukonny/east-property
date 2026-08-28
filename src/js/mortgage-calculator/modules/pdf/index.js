import { calculateMortgage, calculateEntryCosts } from '../math.js';
import { buildPdfHtml } from './template.js';
import { printHtml } from './printer.js';

export function generateMortgagePdf(state) {
	const metrics = calculateMortgage(state);
	const entryCosts = calculateEntryCosts(state.price, state.downPct);

	const dateStr = new Intl.DateTimeFormat('en-GB', {
		day: 'numeric',
		month: 'long',
		year: 'numeric',
	}).format(new Date());

	const refId = `EP-${Date.now().toString(36).toUpperCase()}`;

	const html = buildPdfHtml({
		state,
		metrics,
		entryCosts,
		refId,
		dateStr,
	});

	printHtml(html);
}