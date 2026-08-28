import './common/common';
import './common/tabs';
// Components && Sections js
import './search-tabs.js';
import './header-menu.js';
import './contact-panel.js';
import './map/map';
import './filters.js';
import './swiper.js';
import './dropdowns.js';
import './submit-unit';
import './uploader';
import './news.js';
import {initMortgageCalculator} from './mortgage-calculator/index.js';

document.addEventListener('DOMContentLoaded', () => {
	initMortgageCalculator();
});



