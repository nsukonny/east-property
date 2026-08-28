import { CALC_COLORS } from './defaults.js';

export const CHART_CONFIG = Object.freeze({
	size: 71,
	rOuter: 33.5,
	rInner: 27.5,
	whiteBorder: 4.0,
	slitWidth: 2.0,
	colors: Object.freeze({
		principal: CALC_COLORS.orange,
		interest: CALC_COLORS.dark,
		border: CALC_COLORS.white,
	}),
	animation: Object.freeze({
		springFactor: 0.14,
		convergenceThreshold: 0.08,
	}),
});

export const SLIDER_CONFIG = Object.freeze({
	filledTrackColor: CALC_COLORS.orange,
	unfilledTrackColor: CALC_COLORS.wheat
});