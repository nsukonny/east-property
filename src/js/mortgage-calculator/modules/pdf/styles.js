export function getPdfStyles() {
	return `
	@page {
		size: A4 portrait;
		margin: 12mm 15mm;
	}
	* {
		box-sizing: border-box;
		margin: 0;
		padding: 0;
		-webkit-print-color-adjust: exact !important;
		print-color-adjust: exact !important;
	}
	body {
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
		color: #16181D;
		background: #FFFFFF;
		font-size: 11px;
		line-height: 1.45;
		-webkit-font-smoothing: antialiased;
	}
	.doc-wrap {
		width: 100%;
		max-width: 740px;
		margin: 0 auto;
	}

	.doc-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-bottom: 12px;
		border-bottom: 1.5px solid #16181D;
		margin-bottom: 16px;
	}
	.doc-brand {
		display: flex;
		align-items: center;
		gap: 14px;
	}
	.doc-brand-mark svg {
		display: block;
		width: 115px;
		height: 40px;
	}
	.doc-meta {
		text-align: right;
		font-size: 9.5px;
		color: #6B7280;
		line-height: 1.35;
	}
	.doc-meta strong {
		color: #16181D;
		font-size: 10.5px;
		font-weight: 700;
	}

	.doc-headline {
		margin-bottom: 14px;
	}
	.doc-title {
		font-size: 20px;
		font-weight: 800;
		color: #16181D;
		letter-spacing: -0.02em;
		line-height: 1.2;
	}
	.doc-sub {
		font-size: 10.5px;
		color: #6B7280;
		margin-top: 2px;
	}

	.hero-card {
		background: #F8F9FB;
		border: 1px solid #ECEEF2;
		border-radius: 12px;
		padding: 16px 20px 14px;
		margin-bottom: 16px;
		box-shadow: 0 2px 8px rgba(24, 26, 32, 0.04);
	}
	
	.hero-top {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 14px;
	}
	
	.hero-label {
		font-size: 10px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.06em;
		color: #6B7280;
		margin-bottom: 2px;
	}
	
	.hero-amount {
		font-size: 32px;
		font-weight: 800;
		color: #16181D;
		letter-spacing: -0.02em;
		line-height: 1.1;
		font-variant-numeric: tabular-nums;
	}
	
	.hero-amount span.currency {
		font-size: 20px;
		font-weight: 700;
		color: #16181D;
		margin-left: 4px;
	}
	
	.hero-amount span.period {
		font-size: 13px;
		font-weight: 500;
		color: #6B7280;
		margin-left: 2px;
	}
	
	.hero-badges {
		display: flex;
		gap: 8px;
	}
	
	.hero-badge {
		background: #FFFFFF;
		border: 1px solid #ECEEF2;
		box-shadow: 0 1px 3px rgba(24, 26, 32, 0.03);
		padding: 6px 12px;
		border-radius: 8px;
		font-size: 10px;
		font-weight: 700;
		color: #16181D;
		text-align: center;
		min-width: 75px;
	}
	
	.hero-badge small {
		display: block;
		font-size: 8px;
		font-weight: 600;
		color: #8E95A2;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		margin-bottom: 2px;
	}

	.hero-ratio {
		border-top: 1px solid #ECEEF2;
		padding-top: 12px;
	}
	
	.ratio-bar {
		height: 6px;
		border-radius: 3px;
		background: #FFA41B;
		overflow: hidden;
		display: flex;
		margin-bottom: 8px;
	}
	
	.ratio-bar-fill {
		background: #16181D;
		height: 100%;
	}
	
	.ratio-legend {
		display: flex;
		justify-content: space-between;
		align-items: center;
		font-size: 10px;
		color: #4B5563;
	}
	
	.ratio-legend-item {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	
	.ratio-dot {
		width: 8px;
		height: 8px;
		border-radius: 2px;
		flex-shrink: 0;
	}
	
	.ratio-dot.principal {
		background: #16181D;
	}
	
	.ratio-dot.interest {
		background: #FFA41B;
	}
	
	.ratio-legend strong {
		color: #16181D;
		font-weight: 700;
	}

	.grid-2 {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 16px;
		margin-bottom: 16px;
	}
	
	.panel-box {
		background: #FFFFFF;
		border: 1px solid #ECEEF2;
		box-shadow: 0 1px 4px rgba(24, 26, 32, 0.03);
		border-radius: 8px;
		padding: 12px 14px;
	}
	
	.panel-title {
		font-size: 11px;
		font-weight: 700;
		color: #16181D;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		padding-bottom: 6px;
		border-bottom: 1px solid #ECEEF2;
		margin-bottom: 6px;
	}

	.data-table {
		width: 100%;
		border-collapse: collapse;
	}
	
	.data-table tr {
		border-bottom: 1px solid #F3F4F6;
	}
	
	.data-table td {
		padding: 4.5px 0;
		font-size: 10.5px;
	}
	
	.data-table td:first-child {
		color: #4B5563;
	}
	
	.data-table td:last-child {
		color: #16181D;
		font-weight: 600;
		text-align: right;
		font-variant-numeric: tabular-nums;
		white-space: nowrap;
	}
	
	.data-table tr.row-total td {
		padding-top: 7px;
		border-top: 1.5px solid #16181D;
		border-bottom: none;
		font-size: 11.5px;
		font-weight: 800;
		color: #16181D;
	}

	.steps-box {
		background: #F8F9FB;
		border: 1px solid #ECEEF2;
		box-shadow: 0 1px 4px rgba(24, 26, 32, 0.03);
		border-radius: 8px;
		padding: 12px 14px;
		margin-bottom: 14px;
	}
	
	.steps-header {
		font-size: 10.5px;
		font-weight: 700;
		color: #16181D;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		margin-bottom: 8px;
	}
	
	.steps-grid {
		display: grid;
		grid-template-columns: repeat(6, 1fr);
		gap: 6px;
	}
	
	.step-item {
		background: #FFFFFF;
		border: 1px solid #ECEEF2;
		box-shadow: 0 1px 2px rgba(24, 26, 32, 0.02);
		border-radius: 6px;
		padding: 6px 8px;
	}
	
	.step-num {
		font-size: 9px;
		font-weight: 800;
		color: #FFA41B;
		margin-bottom: 2px;
	}
	
	.step-name {
		font-size: 9.5px;
		font-weight: 700;
		color: #16181D;
		line-height: 1.2;
	}
	
	.step-desc {
		font-size: 8.5px;
		color: #6B7280;
		margin-top: 1px;
	}

	.doc-footer {
		border-top: 1px solid #ECEEF2;
		padding-top: 8px;
		display: flex;
		justify-content: space-between;
		align-items: flex-end;
		font-size: 8.5px;
		color: #9CA3AF;
		line-height: 1.35;
	}
	
	.doc-disclaimer {
		max-width: 480px;
	}
	
	.doc-contacts {
		text-align: right;
		color: #4B5563;
		font-size: 9.5px;
	}
	
	.doc-contacts strong {
		color: #16181D;
		font-size: 10px;
	}
	`;
}