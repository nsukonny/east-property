import { CHART_CONFIG } from '../config/index.js';

export class DonutChart {
	constructor(canvas) {
		this.canvas = canvas;
		this.ctx = canvas?.getContext('2d') || null;
		this.dpr = Math.max(2, window.devicePixelRatio || 2);

		this.currentPct = 0;
		this.targetPct = 69;
		this.animFrameId = null;

		this.init();
	}

	init() {
		if (!this.canvas || !this.ctx) return;

		const { size } = CHART_CONFIG;
		this.canvas.width = size * this.dpr;
		this.canvas.height = size * this.dpr;
		this.canvas.style.width = `${size}px`;
		this.canvas.style.height = `${size}px`;
		this.ctx.scale(this.dpr, this.dpr);
	}

	animateTo(targetPrincipalPct) {
		this.targetPct = targetPrincipalPct;
		if (this.animFrameId) return;

		const { springFactor, convergenceThreshold } = CHART_CONFIG.animation;

		const step = () => {
			const diff = this.targetPct - this.currentPct;

			if (Math.abs(diff) > convergenceThreshold) {
				this.currentPct += diff * springFactor;
				this.draw(this.currentPct);
				this.animFrameId = requestAnimationFrame(step);
			} else {
				this.currentPct = this.targetPct;
				this.draw(this.currentPct);
				this.animFrameId = null;
			}
		};

		this.animFrameId = requestAnimationFrame(step);
	}

	draw(principalPct) {
		if (!this.ctx) return;

		const ctx = this.ctx;
		const { size, rOuter, rInner, whiteBorder, slitWidth, colors } = CHART_CONFIG;
		const cx = size / 2;
		const cy = size / 2;

		ctx.clearRect(0, 0, size, size);
		ctx.beginPath();
		ctx.arc(cx, cy, rOuter + whiteBorder, 0, Math.PI * 2, false);
		ctx.arc(cx, cy, Math.max(0, rInner - whiteBorder), 0, Math.PI * 2, true);
		ctx.closePath();
		ctx.fillStyle = colors.border;
		ctx.fill();

		const p = Math.min(99, Math.max(1, principalPct));
		const total = 2 * Math.PI;
		const midRadius = (rOuter + rInner) / 2;
		const gapRad = slitWidth / midRadius;
		const splitRad = (p / 100) * total;
		const top = -Math.PI / 2;

		const orangeStart = top + gapRad / 2;
		const orangeEnd = top + splitRad - gapRad / 2;
		const darkStart = top + splitRad + gapRad / 2;
		const darkEnd = top + total - gapRad / 2;

		this.drawRingSector(cx, cy, rInner, rOuter, orangeStart, orangeEnd, colors.principal);

		this.drawRingSector(cx, cy, rInner, rOuter, darkStart, darkEnd, colors.interest);
	}

	drawRingSector(cx, cy, rInner, rOuter, startRad, endRad, color) {
		if (startRad >= endRad) return;

		const ctx = this.ctx;

		ctx.beginPath();
		ctx.arc(cx, cy, rOuter, startRad, endRad, false);
		ctx.arc(cx, cy, rInner, endRad, startRad, true);
		ctx.closePath();
		ctx.fillStyle = color;
		ctx.fill();
	}

	destroy() {
		if (this.animFrameId) {
			cancelAnimationFrame(this.animFrameId);
			this.animFrameId = null;
		}
	}
}