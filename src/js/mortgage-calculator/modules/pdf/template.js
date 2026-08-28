import { LOGO_SVG } from './logo.js';
import { getPdfStyles } from './styles.js';
import { formatCurrency } from '../formatter.js';

export function buildPdfHtml({ state, metrics, entryCosts, refId, dateStr }) {
	const currency = 'AED';
	const residencyText = state.resident ? 'UAE Resident' : 'Non-Resident';

	return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mortgage Calculation — East Property (${refId})</title>
<style>${getPdfStyles()}</style>
</head>
<body>

<div class="doc-wrap">

	<header class="doc-header">
		<div class="doc-brand">
			<div class="doc-brand-mark">${LOGO_SVG}</div>
		</div>
		<div class="doc-meta">
			<div class="doc-meta-title">East Property Advisory</div>
			<div>Ref: <strong>${refId}</strong></div>
			<div>Date: ${dateStr}</div>
			<div>Dubai, United Arab Emirates</div>
		</div>
	</header>

	<div class="doc-headline">
		<h1 class="doc-title">UAE Mortgage Calculation Report</h1>
		<div class="doc-sub">Property financing estimate and upfront acquisition breakdown · East Property Advisory</div>
	</div>

	<div class="hero-card">
		<div class="hero-top">
			<div>
				<div class="hero-label">Estimated Monthly Payment</div>
				<div class="hero-amount">${formatCurrency(metrics.monthlyPayment)} <span class="currency">${currency}</span><span class="period">/ month</span></div>
			</div>
			<div class="hero-badges">
				<div class="hero-badge">
					<small>Profile</small>
					${residencyText}
				</div>
				<div class="hero-badge">
					<small>Tenure</small>
					${metrics.years} Years
				</div>
				<div class="hero-badge">
					<small>Interest Rate</small>
					${metrics.rate.toFixed(2)}%
				</div>
			</div>
		</div>

		<div class="hero-ratio">
			<div class="ratio-bar">
				<div class="ratio-bar-fill" style="width: ${metrics.principalPct}%;"></div>
			</div>
			<div class="ratio-legend">
				<div class="ratio-legend-item">
					<div class="ratio-dot principal"></div>
					<span>Principal Loan: <strong>${metrics.principalPct}%</strong> (${formatCurrency(metrics.loanAmount)} ${currency})</span>
				</div>
				<div class="ratio-legend-item">
					<div class="ratio-dot interest"></div>
					<span>Total Interest: <strong>${metrics.interestPct}%</strong> (${formatCurrency(metrics.totalInterest)} ${currency})</span>
				</div>
			</div>
		</div>
	</div>

	<div class="grid-2">

		<div class="panel-box">
			<div class="panel-title">Loan Parameters</div>
			<table class="data-table">
				<tbody>
					<tr>
						<td>Property Price</td>
						<td>${formatCurrency(metrics.price)} ${currency}</td>
					</tr>
					<tr>
						<td>Down Payment (${metrics.downPct}%)</td>
						<td>${formatCurrency(metrics.downAmount)} ${currency}</td>
					</tr>
					<tr>
						<td>Bank Loan Amount</td>
						<td>${formatCurrency(metrics.loanAmount)} ${currency}</td>
					</tr>
					<tr>
						<td>Loan Tenure</td>
						<td>${metrics.years} years (${metrics.years * 12} mos)</td>
					</tr>
					<tr>
						<td>Interest Rate</td>
						<td>${metrics.rate.toFixed(2)}% p.a.</td>
					</tr>
					<tr>
						<td>Total Interest (Overpayment)</td>
						<td>${formatCurrency(metrics.totalInterest)} ${currency}</td>
					</tr>
					<tr class="row-total">
						<td>Total Payable over Term</td>
						<td>${formatCurrency(metrics.totalPayable)} ${currency}</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="panel-box">
			<div class="panel-title">Full Cost of Entry</div>
			<table class="data-table">
				<tbody>
					<tr>
						<td>Down Payment (${metrics.downPct}%)</td>
						<td>${formatCurrency(entryCosts.downAmount)} ${currency}</td>
					</tr>
					<tr>
						<td>DLD Fee (4% + 580)</td>
						<td>${formatCurrency(entryCosts.dldFee)} ${currency}</td>
					</tr>
					<tr>
						<td>Agency Commission (2%)</td>
						<td>${formatCurrency(entryCosts.agencyFee)} ${currency}</td>
					</tr>
					<tr>
						<td>Bank Fee (1% of loan)</td>
						<td>${formatCurrency(entryCosts.bankFee)} ${currency}</td>
					</tr>
					<tr>
						<td>Property Valuation</td>
						<td>${formatCurrency(entryCosts.valuationFee)} ${currency}</td>
					</tr>
					<tr>
						<td>Mortgage Registration</td>
						<td>${formatCurrency(entryCosts.mortgageRegFee)} ${currency}</td>
					</tr>
					<tr class="row-total">
						<td>Total Required at Entry</td>
						<td>${formatCurrency(entryCosts.totalEntryCost)} ${currency}</td>
					</tr>
				</tbody>
			</table>
		</div>

	</div>

	<div class="steps-box">
		<div class="steps-header">The Deal in 6 Steps (Timeline: 4–8 Weeks from MOU to Keys)</div>
		<div class="steps-grid">
			<div class="step-item">
				<div class="step-num">01</div>
				<div class="step-name">Pre-approval</div>
				<div class="step-desc">Bank eligibility</div>
			</div>
			<div class="step-item">
				<div class="step-num">02</div>
				<div class="step-name">MOU (Form F)</div>
				<div class="step-desc">~10% deposit</div>
			</div>
			<div class="step-item">
				<div class="step-num">03</div>
				<div class="step-name">Valuation</div>
				<div class="step-desc">Property appraisal</div>
			</div>
			<div class="step-item">
				<div class="step-num">04</div>
				<div class="step-name">Final approval</div>
				<div class="step-desc">Loan offer letter</div>
			</div>
			<div class="step-item">
				<div class="step-num">05</div>
				<div class="step-name">DLD Transfer</div>
				<div class="step-desc">Title deed</div>
			</div>
			<div class="step-item">
				<div class="step-num">06</div>
				<div class="step-name">Handover</div>
				<div class="step-desc">Funds & keys</div>
			</div>
		</div>
	</div>

	<footer class="doc-footer">
		<div class="doc-disclaimer">
			<strong>Disclaimer:</strong> This calculation is for informational purposes based on UAE Central Bank standards. Final interest rates and mortgage terms depend on bank underwriting and valuation.
		</div>
		<div class="doc-contacts">
			<strong>East Property Brokerage LLC</strong><br>
			+971 56 680 9684 · Dubai, UAE<br>
			eastproperty.com
		</div>
	</footer>

</div>

</body>
</html>`;
}