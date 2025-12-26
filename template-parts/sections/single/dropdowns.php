<?php
/**
 * Display Unit prices
 *
 * @var \MessiaTheme\Entities\Unit $unit
 *
 * @package MessiaTheme
 */

$unit = $args['unit'] ?? null;
if ( ! $unit ) {
    return;
}


?>
<div class="dropdown info-dropdown">
    <button class="dropdown-button" type="button" aria-haspopup="true" aria-expanded="false">
        <span class="dropdown-arrow">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="vector arrow">
        </span>
        <span class="dropdown-row">
            <span class="button-row-item">
                <img class="button-row-ico" src="/img/bed.svg" width="16" height="16" alt="">
                <span class="button-row-text">1 Bed</span>
            </span>
            <span class="button-row-item">
                <img class="button-row-ico" src="/img/bath.svg" width="16" height="16" alt="">
                <span class="button-row-text">2-3 Baths</span>
            </span>
             <span class="button-row-item">
                <img class="button-row-ico" src="/img/meters.svg" width="16" height="16" alt="">
                <span class="button-row-text">957-1309 sqft</span>
            </span>
             <span class="button-row-item">
                <span class="button-row-price">from <em>AED 1,600,000</em></span>
            </span>
        </span>
    </button>
    <div class="dropdown-content">
        <div class="dropdown-inner">
            <div class="dropdown-content-row">
                <div class="dropdown-content-col">
                    <img src="/img/bed.svg" width="16" height="16" alt="">
                    <span class="button-row-text">1 Bed</span>
                </div>
                <div class="dropdown-content-col">
                    <img src="/img/bath.svg" width="16" height="16" alt="">
                    <span class="button-row-text">2 Baths</span>
                </div>
                <div class="dropdown-content-col">
                    <img src="/img/meters.svg" width="16" height="16" alt="">
                    <span class="button-row-text">957 sqft</span>
                </div>
                <div class="dropdown-content-col image-col">
                    <div data-modal-open="plan-modal">
                        <img src="/img/plan.jpg" width="851" height="575" alt="image">
                    </div>
                </div>
            </div>
            <div class="dropdown-content-row">
                <div class="dropdown-content-col">
                    <img src="/img/bed.svg" width="16" height="16" alt="">
                    <span class="button-row-text">1 Bed</span>
                </div>
                <div class="dropdown-content-col">
                    <img src="/img/bath.svg" width="16" height="16" alt="">
                    <span class="button-row-text">2 Baths</span>
                </div>
                <div class="dropdown-content-col">
                    <img src="/img/meters.svg" width="16" height="16" alt="">
                    <span class="button-row-text">957 sqft</span>
                </div>
                <div class="dropdown-content-col image-col">
                    <div data-modal-open="plan-modal">
                        <img src="/img/plan.jpg" width="851" height="575" alt="image">
                    </div>
                </div>
            </div>
            <div class="dropdown-content-row">
                <div class="dropdown-content-col">
                    <img src="/img/bed.svg" width="16" height="16" alt="">
                    <span class="button-row-text">1 Bed</span>
                </div>
                <div class="dropdown-content-col">
                    <img src="/img/bath.svg" width="16" height="16" alt="">
                    <span class="button-row-text">2 Baths</span>
                </div>
                <div class="dropdown-content-col">
                    <img src="/img/meters.svg" width="16" height="16" alt="">
                    <span class="button-row-text">957 sqft</span>
                </div>
                <div class="dropdown-content-col image-col">
                    <div data-modal-open="plan-modal">
                        <img src="/img/plan.jpg" width="851" height="575" alt="image">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>