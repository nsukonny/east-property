export const RESIDENCY = Object.freeze({
	resident: Object.freeze({
		minDownPct: 20,
		maxDownPct: 80,
		maxFinancingPct: 80,
		minDownLabel: 'from 20',
		metaIncome: '15,000 AED / mo',
		metaFinancing: 'up to 80%',
	}),
	nonResident: Object.freeze({
		minDownPct: 40,
		maxDownPct: 80,
		maxFinancingPct: 60,
		minDownLabel: 'from 40',
		metaIncome: '10,000 USD / mo',
		metaFinancing: 'up to 60%',
	}),
});