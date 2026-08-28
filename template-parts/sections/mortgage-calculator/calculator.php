<section class="mortgage-calc-section" id="mortgage-calculator" data-mortgage-calculator>
	<div class="container">
		<div class="mortgage-calc-grid">

			<div class="mortgage-calc-controls">
				
				<div class="calc-segment-wrapper">
					<div class="calc-segment" role="tablist" aria-label="<?php esc_attr_e( 'Residency status', 'east-property' ); ?>">
						<button type="button" 
								class="calc-segment-btn is-active" 
								id="mcResidentBtn" 
								data-mc-toggle="resident"
								data-resident="true"
								role="tab" 
								aria-selected="true">
							<?php _e( 'UAE resident', 'east-property' ); ?>
						</button>
						<button type="button" 
								class="calc-segment-btn" 
								id="mcNonResidentBtn" 
								data-mc-toggle="non-resident"
								data-resident="false"
								role="tab" 
								aria-selected="false">
							<?php _e( 'Non-resident', 'east-property' ); ?>
						</button>
					</div>
				</div>

				<div class="calc-meta-bar">
					<div class="calc-meta-item">
						<span class="calc-meta-label"><?php _e( 'Minimum income', 'east-property' ); ?></span>
						<strong class="calc-meta-val" id="mcMetaIncome" data-mc-display="income">15,000 AED / mo</strong>
					</div>
					<div class="calc-meta-item">
						<span class="calc-meta-label"><?php _e( 'Age', 'east-property' ); ?></span>
						<strong class="calc-meta-val"><?php _e( 'from 21, loan repaid by 65', 'east-property' ); ?></strong>
					</div>
					<div class="calc-meta-item">
						<span class="calc-meta-label"><?php _e( 'Bank financing', 'east-property' ); ?></span>
						<strong class="calc-meta-val" id="mcMetaFinancing" data-mc-display="financing"><?php _e( 'up to 80%', 'east-property' ); ?></strong>
					</div>
				</div>

				<div class="calc-field-group">
					<label for="mcPriceInput" class="calc-field-label">
						<span><?php _e( 'Property price', 'east-property' ); ?></span>
						<?php 
						get_component_template( 'ui/tooltip', array(
							'text'     => __( 'Total purchase price of the property in AED', 'east-property' ),
							'position' => 'top',
						) ); 
						?>
					</label>
					<div class="calc-field-row">
						<div class="calc-input-box">
							<input type="text" 
								   id="mcPriceInput" 
								   class="calc-text-input" 
								   data-mc-input="price"
								   value="1 200 000" 
								   inputmode="numeric" 
								   autocomplete="off">
							<span class="calc-input-unit">AED</span>
						</div>
						<div class="calc-slider-group">
							<input type="range" 
								   id="mcPriceSlider" 
								   class="calc-range-slider" 
								   data-mc-slider="price"
								   min="300000" 
								   max="20000000" 
								   step="50000" 
								   value="1200000">
							<div class="calc-slider-labels">
								<span><?php _e( 'from 300,000', 'east-property' ); ?></span>
								<span><?php _e( 'to 20,000,000', 'east-property' ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div class="calc-field-group">
					<label for="mcDownInput" class="calc-field-label">
						<span class="b"><?php _e( 'Down payment', 'east-property' ); ?></span>
						<?php 
						get_component_template( 'ui/tooltip', array(
							'text'     => __( 'Upfront equity contribution (min 20% for residents, 40% for non-residents)', 'east-property' ),
							'position' => 'top',
						) ); 
						?>
					</label>
					<div class="calc-field-row">
						<div class="calc-input-box">
							<input type="text" 
								   id="mcDownInput" 
								   class="calc-text-input" 
								   data-mc-input="down"
								   value="67" 
								   inputmode="numeric" 
								   autocomplete="off">
							<span class="calc-input-unit">%</span>
						</div>
						<div class="calc-slider-group">
							<input type="range" 
								   id="mcDownSlider" 
								   class="calc-range-slider" 
								   data-mc-slider="down"
								   min="20" 
								   max="80" 
								   step="1" 
								   value="67">
							<div class="calc-slider-labels">
								<span id="mcDownMinLabel" data-mc-display="down-min-label"><?php _e( 'from 20', 'east-property' ); ?></span>
								<span><?php _e( 'to 80', 'east-property' ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div class="calc-field-group">
					<label for="mcTermInput" class="calc-field-label">
						<span class="b"><?php _e( 'Loan term', 'east-property' ); ?></span>
						<?php 
						get_component_template( 'ui/tooltip', array(
							'text'     => __( 'Mortgage duration in years (up to 25 years)', 'east-property' ),
							'position' => 'top',
						) ); 
						?>
					</label>
					<div class="calc-field-row">
						<div class="calc-input-box">
							<input type="text" 
								   id="mcTermInput" 
								   class="calc-text-input" 
								   data-mc-input="term"
								   value="10" 
								   inputmode="numeric" 
								   autocomplete="off">
							<span class="calc-input-unit"><?php _e( 'years', 'east-property' ); ?></span>
						</div>
						<div class="calc-slider-group">
							<input type="range" 
								   id="mcTermSlider" 
								   class="calc-range-slider" 
								   data-mc-slider="term"
								   min="1" 
								   max="25" 
								   step="1" 
								   value="10">
							<div class="calc-slider-labels">
								<span><?php _e( 'from 1', 'east-property' ); ?></span>
								<span><?php _e( 'to 25', 'east-property' ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div class="calc-field-group">
					<label for="mcRateInput" class="calc-field-label">
						<span class="b"><?php _e( 'Interest rate', 'east-property' ); ?></span>
						<?php 
						get_component_template( 'ui/tooltip', array(
							'text'     => __( 'Annual interest rate offered by UAE mortgage lenders', 'east-property' ),
							'position' => 'top',
						) ); 
						?>
					</label>
					<div class="calc-field-row">
						<div class="calc-input-box">
							<input type="text" 
								   id="mcRateInput" 
								   class="calc-text-input" 
								   data-mc-input="rate"
								   value="4,5" 
								   inputmode="decimal" 
								   autocomplete="off">
							<span class="calc-input-unit">%</span>
						</div>
						<div class="calc-slider-group">
							<input type="range" 
								   id="mcRateSlider" 
								   class="calc-range-slider" 
								   data-mc-slider="rate"
								   min="2.5" 
								   max="8.0" 
								   step="0.05" 
								   value="4.5">
							<div class="calc-slider-labels">
								<span><?php _e( 'from 2,5', 'east-property' ); ?></span>
								<span><?php _e( 'to 8', 'east-property' ); ?></span>
							</div>
						</div>
					</div>
				</div>

			</div>

			<div class="mortgage-calc-summary">
				<div class="calc-result-card">
					
					<div class="calc-result-header">
						<div class="calc-result-amount" id="mcMonthlyPayment" data-mc-display="monthly-payment">6 073 AED</div>
						<div class="calc-result-label"><?php _e( 'Monthly payment', 'east-property' ); ?></div>
					</div>
					<div class="calc-grid-wrapper">
						<div class="calc-stats-grid">
							<div class="calc-stat-item">
								<span class="calc-stat-name"><?php _e( 'Loan', 'east-property' ); ?></span>
								<strong class="calc-stat-value" id="mcStatLoan" data-mc-display="loan">960 000</strong>
							</div>
								<div class="calc-stat-item">
									<span class="calc-stat-name"><?php _e( 'Down payment', 'east-property' ); ?></span>
									<strong class="calc-stat-value" id="mcStatDown" data-mc-display="down">240 000</strong>
								</div>
								<div class="calc-stat-item">
									<span class="calc-stat-name"><?php _e( 'Interest paid', 'east-property' ); ?></span>
									<strong class="calc-stat-value" id="mcStatInterest" data-mc-display="interest">497 624</strong>
								</div>
							</div>

							<div class="calc-chart-box">
								<div class="calc-donut-wrapper">
									<canvas class="calc-donut-canvas" id="mcDonutCanvas" data-mc-canvas width="142" height="142" aria-hidden="true"></canvas>
								</div>

								<div class="calc-chart-legend">
									<div class="calc-legend-item">
										<span class="calc-legend-dot calc-legend-dot--orange"></span>
										<span class="calc-legend-text">
											<?php _e( 'Principal', 'east-property' ); ?> — <strong id="mcPrincipalPct" data-mc-display="principal-pct">66%</strong>
										</span>
									</div>
									<div class="calc-legend-item">
										<span class="calc-legend-dot calc-legend-dot--dark"></span>
										<span class="calc-legend-text">
											<?php _e( 'Interest over term', 'east-property' ); ?> — <strong id="mcInterestPct" data-mc-display="interest-pct">34%</strong>
										</span>
									</div>
								</div>
							</div>
					</div>

					<p class="calc-disclaimer-text">
						<?php _e( 'Preliminary estimate. The final rate depends on the bank, your profile, and the property.', 'east-property' ); ?>
					</p>

					<div class="calc-actions-group">
						<button type="button" 
								class="button orange sm full-width"
								data-modal-open="broker-modal">
							<?php _e( 'Get pre-approved', 'east-property' ); ?>
						</button>

						<button type="button" 
								class="button white sm full-width"
								id="mcDownloadBtn"
								data-mc-action="download">
							<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/big-arr-down.svg' ); ?>" alt="" width="24" height="24" aria-hidden="true">
							<?php _e( 'Download calculation', 'east-property' ); ?>
						</button>

						<button type="button" 
								class="calc-dda-link" 
								data-modal-open="broker-modal">
							<?php _e( 'DDA mortgage services', 'east-property' ); ?> →
						</button>
					</div>

				</div>
			</div>

		</div>
	</div>
</section>