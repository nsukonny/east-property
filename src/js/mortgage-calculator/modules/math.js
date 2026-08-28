export function calculateMortgage({price, downPct, years, rate}) {
	const safePrice = Math.max(0, Number(price) || 0);
	const safeDownPct = Math.min(100, Math.max(0, Number(downPct) || 0));
	const safeYears = Math.max(1, Math.min(30, Number(years) || 1));
	const safeRate = Math.max(0, Number(rate) || 0);

	const downAmount = Math.round(safePrice * (safeDownPct / 100));
	const loanAmount = Math.max(0, safePrice - downAmount);
	const totalMonths = safeYears * 12;
	const monthlyRate = (safeRate / 100) / 12;

	let exactMonthlyPayment = 0;
	if (loanAmount > 0 && totalMonths > 0) {
		if (monthlyRate === 0) {
			exactMonthlyPayment = loanAmount / totalMonths;
		} else {
			const compoundFactor = Math.pow(1 + monthlyRate, totalMonths);
			exactMonthlyPayment = loanAmount * (monthlyRate * compoundFactor) / (compoundFactor - 1);
		}
	}

	const monthlyPayment = Math.round(exactMonthlyPayment);
	const totalPayable = Math.round(exactMonthlyPayment * totalMonths);
	const totalInterest = Math.max(0, totalPayable - loanAmount);

	let principalPct = 69;
	let interestPct = 31;

	if (totalPayable > 0) {
		principalPct = Math.min(100, Math.max(1, Math.round((loanAmount / totalPayable) * 100)));
		interestPct = Math.max(0, 100 - principalPct);
	}

	return {
		price: safePrice,
		downPct: safeDownPct,
		years: safeYears,
		rate: safeRate,
		downAmount,
		loanAmount,
		monthlyPayment,
		totalPayable,
		totalInterest,
		principalPct,
		interestPct,
	};
}

export function calculateEntryCosts(price, downPct) {
	const safePrice = Math.max(0, Number(price) || 0);
	const safeDownPct = Math.min(100, Math.max(0, Number(downPct) || 0));

	const downAmount = Math.round(safePrice * (safeDownPct / 100));
	const loanAmount = Math.max(0, safePrice - downAmount);

	const dldFee = Math.round(safePrice * 0.04 + 580);
	const agencyFee = Math.round(safePrice * 0.02);
	const bankFee = Math.round(loanAmount * 0.01);
	const valuationFee = 3150;
	const mortgageRegFee = Math.round(loanAmount * 0.0025 + 290);

	const totalEntryCost = downAmount + dldFee + agencyFee + bankFee + valuationFee + mortgageRegFee;

	return {
		downAmount,
		loanAmount,
		dldFee,
		agencyFee,
		bankFee,
		valuationFee,
		mortgageRegFee,
		totalEntryCost,
	};
}