import { DEFAULTS, RESIDENCY, SLIDER_CONFIG, FORMATTING } from '../config/index.js';
import { calculateMortgage, calculateEntryCosts } from './math.js';
import { formatCurrency, parseNumericInput } from './formatter.js';
import { DonutChart } from './donut-chart.js';
import { generateMortgagePdf } from './pdf/index.js';

export class MortgageCalculator {
	constructor(rootContainer) {
		if (!rootContainer || !(rootContainer instanceof HTMLElement)) {
			throw new Error('[MortgageCalculator] A valid DOM element is required.');
		}

		this.root = rootContainer;
		this.isRafScheduled = false;

		this.state = {
			resident: DEFAULTS.resident,
			price: DEFAULTS.price,
			downPct: DEFAULTS.downPct,
			years: DEFAULTS.years,
			rate: DEFAULTS.rate,
		};

		this.elements = this.queryElements();
		if (!this.isValid()) return;

		this.chart = new DonutChart(this.elements.donutCanvas);
		this.initEventListeners();
		this.render();
	}

	queryElements() {
		const $ = (selector) => this.root.querySelector(selector);
		const $doc = (selector) => document.querySelector(selector);

		return {
			resBtn: $('[data-mc-toggle="resident"]') || $('#mcResidentBtn'),
			nonResBtn: $('[data-mc-toggle="non-resident"]') || $('#mcNonResidentBtn'),

			metaIncome: $('[data-mc-display="income"]') || $('#mcMetaIncome'),
			metaFinancing: $('[data-mc-display="financing"]') || $('#mcMetaFinancing'),
			downMinLabel: $('[data-mc-display="down-min-label"]') || $('#mcDownMinLabel'),

			priceInput: $('[data-mc-input="price"]') || $('#mcPriceInput'),
			priceSlider: $('[data-mc-slider="price"]') || $('#mcPriceSlider'),

			downInput: $('[data-mc-input="down"]') || $('#mcDownInput'),
			downSlider: $('[data-mc-slider="down"]') || $('#mcDownSlider'),

			termInput: $('[data-mc-input="term"]') || $('#mcTermInput'),
			termSlider: $('[data-mc-slider="term"]') || $('#mcTermSlider'),

			rateInput: $('[data-mc-input="rate"]') || $('#mcRateInput'),
			rateSlider: $('[data-mc-slider="rate"]') || $('#mcRateSlider'),

			monthlyPayment: $('[data-mc-display="monthly-payment"]') || $('#mcMonthlyPayment'),
			statLoan: $('[data-mc-display="loan"]') || $('#mcStatLoan'),
			statDown: $('[data-mc-display="down"]') || $('#mcStatDown'),
			statInterest: $('[data-mc-display="interest"]') || $('#mcStatInterest'),

			donutCanvas: $('[data-mc-canvas]') || $('#mcDonutCanvas'),
			principalPct: $('[data-mc-display="principal-pct"]') || $('#mcPrincipalPct'),
			interestPct: $('[data-mc-display="interest-pct"]') || $('#mcInterestPct'),

			entryDown: $doc('[data-mc-entry="down"]'),
			entryDld: $doc('[data-mc-entry="dld"]'),
			entryAgency: $doc('[data-mc-entry="agency"]'),
			entryBankFee: $doc('[data-mc-entry="bank-fee"]'),
			entryValuation: $doc('[data-mc-entry="valuation"]'),
			entryMortgageReg: $doc('[data-mc-entry="mortgage-reg"]'),
			entryTotal: $doc('[data-mc-entry="total"]'),

			downloadBtn: $('[data-mc-action="download"]') || $('#mcDownloadBtn'),
		};
	}

	isValid() {
		return Boolean(
			this.elements.priceSlider &&
			this.elements.monthlyPayment &&
			this.elements.donutCanvas
		);
	}

	initEventListeners() {
		const { elements } = this;

		elements.resBtn?.addEventListener('click', () => this.setResidency(true));
		elements.nonResBtn?.addEventListener('click', () => this.setResidency(false));

		this.bindSlider(elements.priceSlider, (val) => { this.state.price = val; });
		this.bindSlider(elements.downSlider, (val) => { this.state.downPct = val; });
		this.bindSlider(elements.termSlider, (val) => { this.state.years = val; });
		this.bindSlider(elements.rateSlider, (val) => { this.state.rate = val; });

		this.bindTextInput(elements.priceInput, elements.priceSlider, (val) => { this.state.price = val; });
		this.bindTextInput(elements.downInput, elements.downSlider, (val) => { this.state.downPct = val; });
		this.bindTextInput(elements.termInput, elements.termSlider, (val) => { this.state.years = val; });
		this.bindTextInput(elements.rateInput, elements.rateSlider, (val) => { this.state.rate = val; });

		elements.downloadBtn?.addEventListener('click', () => {
			generateMortgagePdf(this.state);
		});
	}

	bindSlider(sliderEl, stateUpdater) {
		if (!sliderEl) return;
		sliderEl.addEventListener('input', (event) => {
			const value = parseNumericInput(event.target.value);
			stateUpdater(value);
			this.scheduleRender();
		});
	}

	bindTextInput(inputEl, sliderEl, stateUpdater) {
		if (!inputEl || !sliderEl) return;

		inputEl.addEventListener('focus', () => {
			inputEl.closest('.calc-input-box')?.classList.remove('is-clamped');
		});

		inputEl.addEventListener('change', () => {
			const min = parseFloat(sliderEl.min);
			const max = parseFloat(sliderEl.max);
			const rawVal = parseNumericInput(inputEl.value);

			const isClamped = rawVal < min || rawVal > max;
			const clampedVal = Math.min(max, Math.max(min, rawVal));

			stateUpdater(clampedVal);
			sliderEl.value = String(clampedVal);

			if (isClamped) {
				inputEl.closest('.calc-input-box')?.classList.add('is-clamped');
			}

			this.scheduleRender();
		});
	}

	setResidency(isResident) {
		this.state.resident = isResident;
		const cfg = isResident ? RESIDENCY.resident : RESIDENCY.nonResident;
		const { elements } = this;

		elements.resBtn?.classList.toggle('is-active', isResident);
		elements.resBtn?.setAttribute('aria-selected', String(isResident));
		elements.nonResBtn?.classList.toggle('is-active', !isResident);
		elements.nonResBtn?.setAttribute('aria-selected', String(!isResident));

		if (elements.metaIncome) elements.metaIncome.textContent = cfg.metaIncome;
		if (elements.metaFinancing) elements.metaFinancing.textContent = cfg.metaFinancing;
		if (elements.downMinLabel) elements.downMinLabel.textContent = cfg.minDownLabel;

		if (elements.downSlider) {
			elements.downSlider.min = String(cfg.minDownPct);

			if (this.state.downPct < cfg.minDownPct) {
				this.state.downPct = cfg.minDownPct;
				elements.downSlider.value = String(cfg.minDownPct);
			}
		}

		this.scheduleRender();
	}

	updateTrackGradient(sliderEl) {
		if (!sliderEl) return;
		const min = parseFloat(sliderEl.min) || 0;
		const max = parseFloat(sliderEl.max) || 100;
		const val = parseFloat(sliderEl.value) || 0;
		const pct = Math.min(100, Math.max(0, ((val - min) / (max - min)) * 100));
		const { filledTrackColor, unfilledTrackColor } = SLIDER_CONFIG;
		sliderEl.style.background = `linear-gradient(to right, ${filledTrackColor} 0%, ${filledTrackColor} ${pct}%, ${unfilledTrackColor} ${pct}%, ${unfilledTrackColor} 100%)`;
	}

	scheduleRender() {
		if (this.isRafScheduled) return;
		this.isRafScheduled = true;
		requestAnimationFrame(() => {
			this.render();
			this.isRafScheduled = false;
		});
	}

	render() {
		const metrics = calculateMortgage(this.state);
		const entryCosts = calculateEntryCosts(this.state.price, this.state.downPct);
		const { elements } = this;

		if (elements.priceInput && document.activeElement !== elements.priceInput) {
			elements.priceInput.value = formatCurrency(metrics.price);
		}

		if (elements.priceSlider) {
			elements.priceSlider.value = String(metrics.price);
			this.updateTrackGradient(elements.priceSlider);
		}

		if (elements.downInput && document.activeElement !== elements.downInput) {
			elements.downInput.value = String(metrics.downPct);
		}

		if (elements.downSlider) {
			elements.downSlider.value = String(metrics.downPct);
			this.updateTrackGradient(elements.downSlider);
		}

		if (elements.termInput && document.activeElement !== elements.termInput) {
			elements.termInput.value = String(metrics.years);
		}

		if (elements.termSlider) {
			elements.termSlider.value = String(metrics.years);
			this.updateTrackGradient(elements.termSlider);
		}

		if (elements.rateInput && document.activeElement !== elements.rateInput) {
			elements.rateInput.value = metrics.rate.toFixed(1).replace('.0', '').replace('.', ',');
		}

		if (elements.rateSlider) {
			elements.rateSlider.value = String(metrics.rate);
			this.updateTrackGradient(elements.rateSlider);
		}

		if (elements.monthlyPayment) {
			elements.monthlyPayment.textContent = `${formatCurrency(metrics.monthlyPayment)} ${FORMATTING.currencyUnit}`;
		}
		if (elements.statLoan) {
			elements.statLoan.textContent = formatCurrency(metrics.loanAmount);
		}
		if (elements.statDown) {
			elements.statDown.textContent = formatCurrency(metrics.downAmount);
		}
		if (elements.statInterest) {
			elements.statInterest.textContent = formatCurrency(metrics.totalInterest);
		}

		this.chart?.animateTo(metrics.principalPct);

		if (elements.principalPct) {
			elements.principalPct.textContent = `${metrics.principalPct}%`;
		}
		if (elements.interestPct) {
			elements.interestPct.textContent = `${metrics.interestPct}%`;
		}

		if (elements.entryDown) {
			elements.entryDown.textContent = `${formatCurrency(entryCosts.downAmount)} ${FORMATTING.currencyUnit}`;
		}

		if (elements.entryDld) {
			elements.entryDld.textContent = `${formatCurrency(entryCosts.dldFee)} ${FORMATTING.currencyUnit}`;
		}

		if (elements.entryAgency) {
			elements.entryAgency.textContent = `${formatCurrency(entryCosts.agencyFee)} ${FORMATTING.currencyUnit}`;
		}

		if (elements.entryBankFee) {
			elements.entryBankFee.textContent = `${formatCurrency(entryCosts.bankFee)} ${FORMATTING.currencyUnit}`;
		}

		if (elements.entryValuation) {
			elements.entryValuation.textContent = `${formatCurrency(entryCosts.valuationFee)} ${FORMATTING.currencyUnit}`;
		}

		if (elements.entryMortgageReg) {
			elements.entryMortgageReg.textContent = `${formatCurrency(entryCosts.mortgageRegFee)} ${FORMATTING.currencyUnit}`;
		}

		if (elements.entryTotal) {
			elements.entryTotal.textContent = `${formatCurrency(entryCosts.totalEntryCost)} ${FORMATTING.currencyUnit}`;
		}
	}

	destroy() {
		this.chart?.destroy();
	}
}