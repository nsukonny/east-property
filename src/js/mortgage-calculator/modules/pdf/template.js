import { LOGO_SVG } from './logo.js';
import { getPdfStyles } from './styles.js';
import { formatCurrency, formatRate } from '../formatter.js';
import { FORMATTING } from '../../config/index.js';

const { __, sprintf } = window.wp.i18n;

/**
 * Печатный отчёт на языке страницы.
 *
 * Валюта, ставки и формулы к языку не относятся и остаются как есть; меняются
 * подписи, тег lang документа и разделители чисел — их даёт formatCurrency.
 */
export function buildPdfHtml({ state, metrics, entryCosts, refId, dateStr }) {
	const currency = FORMATTING.currencyUnit;
	const residencyText = state.resident
		? __('UAE Resident', 'east-property')
		: __('Non-Resident', 'east-property');

	return `<!DOCTYPE html>
<html lang="${FORMATTING.locale}">
<head>
<meta charset="UTF-8">
<title>${__('Mortgage Calculation — East Property', 'east-property')} (${refId})</title>
<style>${getPdfStyles()}</style>
</head>
<body>

<div class="doc-wrap">

	<header class="doc-header">
		<div class="doc-brand">
			<div class="doc-brand-mark">${LOGO_SVG}</div>
		</div>
		<div class="doc-meta">
			<div class="doc-meta-title">${__('East Property Advisory', 'east-property')}</div>
			<div>${__('Ref:', 'east-property')} <strong>${refId}</strong></div>
			<div>${__('Date:', 'east-property')} ${dateStr}</div>
			<div>${__('Dubai, United Arab Emirates', 'east-property')}</div>
		</div>
	</header>

	<div class="doc-headline">
		<h1 class="doc-title">${__('UAE Mortgage Calculation Report', 'east-property')}</h1>
		<div class="doc-sub">${__('Property financing estimate and upfront acquisition breakdown · East Property Advisory', 'east-property')}</div>
	</div>

	<div class="hero-card">
		<div class="hero-top">
			<div>
				<div class="hero-label">${__('Estimated Monthly Payment', 'east-property')}</div>
				<div class="hero-amount">${formatCurrency(metrics.monthlyPayment)} <span class="currency">${currency}</span><span class="period">${__('/ month', 'east-property')}</span></div>
			</div>
			<div class="hero-badges">
				<div class="hero-badge">
					<small>${__('Profile', 'east-property')}</small>
					${residencyText}
				</div>
				<div class="hero-badge">
					<small>${__('Tenure', 'east-property')}</small>
					${sprintf(__('%s Years', 'east-property'), metrics.years)}
				</div>
				<div class="hero-badge">
					<small>${__('Interest Rate', 'east-property')}</small>
					${formatRate(metrics.rate)}%
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
					<span>${__('Principal Loan:', 'east-property')} <strong>${metrics.principalPct}%</strong> (${formatCurrency(metrics.loanAmount)} ${currency})</span>
				</div>
				<div class="ratio-legend-item">
					<div class="ratio-dot interest"></div>
					<span>${__('Total Interest:', 'east-property')} <strong>${metrics.interestPct}%</strong> (${formatCurrency(metrics.totalInterest)} ${currency})</span>
				</div>
			</div>
		</div>
	</div>

	<div class="grid-2">

		<div class="panel-box">
			<div class="panel-title">${__('Loan Parameters', 'east-property')}</div>
			<table class="data-table">
				<tbody>
					<tr>
						<td>${__('Property Price', 'east-property')}</td>
						<td>${formatCurrency(metrics.price)} ${currency}</td>
					</tr>
					<tr>
						<td>${sprintf(__('Down Payment (%s%%)', 'east-property'), metrics.downPct)}</td>
						<td>${formatCurrency(metrics.downAmount)} ${currency}</td>
					</tr>
					<tr>
						<td>${__('Bank Loan Amount', 'east-property')}</td>
						<td>${formatCurrency(metrics.loanAmount)} ${currency}</td>
					</tr>
					<tr>
						<td>${__('Loan Tenure', 'east-property')}</td>
						<td>${sprintf(__('%1$s years (%2$s mos)', 'east-property'), metrics.years, metrics.years * 12)}</td>
					</tr>
					<tr>
						<td>${__('Interest Rate', 'east-property')}</td>
						<td>${sprintf(__('%s%% p.a.', 'east-property'), formatRate(metrics.rate))}</td>
					</tr>
					<tr>
						<td>${__('Total Interest (Overpayment)', 'east-property')}</td>
						<td>${formatCurrency(metrics.totalInterest)} ${currency}</td>
					</tr>
					<tr class="row-total">
						<td>${__('Total Payable over Term', 'east-property')}</td>
						<td>${formatCurrency(metrics.totalPayable)} ${currency}</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="panel-box">
			<div class="panel-title">${__('Full Cost of Entry', 'east-property')}</div>
			<table class="data-table">
				<tbody>
					<tr>
						<td>${sprintf(__('Down Payment (%s%%)', 'east-property'), metrics.downPct)}</td>
						<td>${formatCurrency(entryCosts.downAmount)} ${currency}</td>
					</tr>
					<tr>
						<td>${__('DLD Fee (4% + 580)', 'east-property')}</td>
						<td>${formatCurrency(entryCosts.dldFee)} ${currency}</td>
					</tr>
					<tr>
						<td>${__('Agency Commission (2%)', 'east-property')}</td>
						<td>${formatCurrency(entryCosts.agencyFee)} ${currency}</td>
					</tr>
					<tr>
						<td>${__('Bank Fee (1% of loan)', 'east-property')}</td>
						<td>${formatCurrency(entryCosts.bankFee)} ${currency}</td>
					</tr>
					<tr>
						<td>${__('Property Valuation', 'east-property')}</td>
						<td>${formatCurrency(entryCosts.valuationFee)} ${currency}</td>
					</tr>
					<tr>
						<td>${__('Mortgage Registration', 'east-property')}</td>
						<td>${formatCurrency(entryCosts.mortgageRegFee)} ${currency}</td>
					</tr>
					<tr class="row-total">
						<td>${__('Total Required at Entry', 'east-property')}</td>
						<td>${formatCurrency(entryCosts.totalEntryCost)} ${currency}</td>
					</tr>
				</tbody>
			</table>
		</div>

	</div>

	<div class="steps-box">
		<div class="steps-header">${__('The Deal in 6 Steps (Timeline: 4–8 Weeks from MOU to Keys)', 'east-property')}</div>
		<div class="steps-grid">
			<div class="step-item">
				<div class="step-num">01</div>
				<div class="step-name">${__('Pre-approval', 'east-property')}</div>
				<div class="step-desc">${__('Bank eligibility', 'east-property')}</div>
			</div>
			<div class="step-item">
				<div class="step-num">02</div>
				<div class="step-name">${__('MOU (Form F)', 'east-property')}</div>
				<div class="step-desc">${__('~10% deposit', 'east-property')}</div>
			</div>
			<div class="step-item">
				<div class="step-num">03</div>
				<div class="step-name">${__('Valuation', 'east-property')}</div>
				<div class="step-desc">${__('Property appraisal', 'east-property')}</div>
			</div>
			<div class="step-item">
				<div class="step-num">04</div>
				<div class="step-name">${__('Final approval', 'east-property')}</div>
				<div class="step-desc">${__('Loan offer letter', 'east-property')}</div>
			</div>
			<div class="step-item">
				<div class="step-num">05</div>
				<div class="step-name">${__('DLD Transfer', 'east-property')}</div>
				<div class="step-desc">${__('Title deed', 'east-property')}</div>
			</div>
			<div class="step-item">
				<div class="step-num">06</div>
				<div class="step-name">${__('Handover', 'east-property')}</div>
				<div class="step-desc">${__('Funds & keys', 'east-property')}</div>
			</div>
		</div>
	</div>

	<footer class="doc-footer">
		<div class="doc-disclaimer">
			<strong>${__('Disclaimer:', 'east-property')}</strong> This calculation is for informational purposes based on UAE Central Bank standards. Final interest rates and mortgage terms depend on bank underwriting and valuation.
		</div>
		<div class="doc-contacts">
			<strong>${__('East Property Brokerage LLC', 'east-property')}</strong><br>
			+971 56 680 9684 · Dubai, UAE<br>
			eastproperty.com
		</div>
	</footer>

</div>

</body>
</html>`;
}