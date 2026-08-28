export const RANGES = Object.freeze({
	price: Object.freeze({
		min: 300000,
		max: 20000000,
		step: 50000,
	}),
	downResident: Object.freeze({
		min: 20,
		max: 80,
		step: 1,
	}),
	downNonResident: Object.freeze({
		min: 40,
		max: 80,
		step: 1,
	}),
	term: Object.freeze({
		min: 1,
		max: 25,
		step: 1,
	}),
	rate: Object.freeze({
		min: 2.5,
		max: 8.0,
		step: 0.05,
	}),
});