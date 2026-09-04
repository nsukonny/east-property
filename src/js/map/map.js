import {importLibrary} from '@googlemaps/js-api-loader';
import Swiper from 'swiper';
import {Navigation} from 'swiper/modules';
import {MAP_CONFIG} from './config';
import {renderBuildingCard} from './html';

const {__} = window.wp.i18n;

document.addEventListener('DOMContentLoaded', () => {
	const mapInstances = document.querySelectorAll('.js-map-instance');
	mapInstances.forEach(instance => {
		instance.propertyMap = new PropertyMap(instance);
	});
});

document.addEventListener('click', (event) => {
	const button = event.target.closest('[data-tab-button]');
	if (!button) return;

	let sectionAbout = document.querySelector('section.about'),
		footer = document.querySelector('footer');
	if ('map' === button.dataset.tab) {
		sectionAbout?.classList.add('is-hidden');
		footer?.classList.add('is-hidden');
	} else {
		sectionAbout?.classList.remove('is-hidden');
		footer?.classList.remove('is-hidden');
	}
});

export class PropertyMap {
	constructor(root) {
		this.root = root;
		this.container = root.querySelector('.js-map-container');
		if (!this.container) return;

		// читаем конфигурацию из дата артибуов самого компонента
		this.mode = root.dataset.mapMode || 'list';
		this.propertyId = root.dataset.propertyId;
		this.showSidebar = root.dataset.showSidebar !== 'false';
		// The property form renders one fieldset per language, so each coordinate
		// lives in as many inputs as there are locales. They are kept identical by
		// syncLangValues(), so read the first one and write them all.
		this.latitudeInputs = PropertyMap.findCoordinateInputs('latitude');
		this.longitudeInputs = PropertyMap.findCoordinateInputs('longitude');
		this.latitudeInput = this.latitudeInputs[0] || null;
		this.longitudeInput = this.longitudeInputs[0] || null;
		this.addressContainer = root.querySelector('.js-map-address');
		this.selectionMarker = null;

		this.lat = MAP_CONFIG.DEFAULT_CENTER.lat;
		this.lng = MAP_CONFIG.DEFAULT_CENTER.lng;

		// The markup carries the coordinates now, so a single map no longer depends
		// on filterPropertiesJson — which the listing map empties for a project
		// with no available units, leaving the map on the default centre.
		const markupLat = parseFloat(root.dataset.latitude);
		const markupLng = parseFloat(root.dataset.longitude);

		if (!Number.isNaN(markupLat) && !Number.isNaN(markupLng)) {
			this.lat = markupLat;
			this.lng = markupLng;
		}

		if (this.mode === 'select' && this.latitudeInput?.value && this.longitudeInput?.value) {
			const latitude = parseFloat(this.latitudeInput.value);
			const longitude = parseFloat(this.longitudeInput.value);

			if (!Number.isNaN(latitude) && !Number.isNaN(longitude)) {
				this.lat = latitude;
				this.lng = longitude;
			}
		}

		this.map = null;
		this.sidebar = root.querySelector('.js-map-sidebar');
		this.sidebarContent = root.querySelector('.map-sidebar-content');
		this.sidebarClose = root.querySelector('.js-map-sidebar-close');
		this.sidebarTarget = root.querySelector('.js-sidebar-card-target');
		this.properties = [];
		this.markers = [];
		this.isDragging = false;
		this.startY = 0;
		this.currentTranslation = 0;

		if (!this.showSidebar && this.sidebar) {
			this.sidebar.remove();
			this.sidebar = null;
		}

		void this.init();
		this.initEvents();
	}

	async init() {
		try {
			const {Map} = await importLibrary('maps');
			const {AdvancedMarkerElement} = await importLibrary('marker');
			const {PlaceAutocompleteElement} = await importLibrary('places');

			this.AdvancedMarkerElement = AdvancedMarkerElement;

			if (filterPropertiesJson) {
				await this.loadProperties(filterPropertiesJson);
			}

			// если сингл мод -- уточняем точку по жсон, когда она там есть
			if (this.mode === 'single' && this.propertyId) {
				const prop = this.properties.find(p => p.id.toString() === this.propertyId.toString());
				const lat = prop ? parseFloat(prop.latitude) : NaN;
				const lng = prop ? parseFloat(prop.longitude) : NaN;

				if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
					this.lat = lat;
					this.lng = lng;
				}
			}

			// для одиночного режима зум побольше
			const zoom = this.mode === 'single' ? 14 : MAP_CONFIG.DEFAULT_ZOOM;

			this.map = new Map(this.container, {
				center: {lat: this.lat, lng: this.lng},
				zoom: zoom,
				mapId: MAP_CONFIG.MAP_ID,
				disableDefaultUI: true,
				zoomControl: false,
				gestureHandling: 'greedy'
			});

			if (this.mode === 'single') {
				this.renderSingleMarker(AdvancedMarkerElement);
			} else if (this.mode === 'select') {
				this.initPointSelection(
					AdvancedMarkerElement,
					PlaceAutocompleteElement
				);
			} else {
				this.renderMarkers(AdvancedMarkerElement);
			}

		} catch (error) {
			console.error('Error initializing Google Map:', error);
		}
	}

	async loadProperties(props = null) {
		if (props) {
			this.properties = Array.isArray(props) ? props : [props];
			return;
		}

		try {
			const response = await fetch(MAP_CONFIG.DATA_URL);
			const data = await response.json();

			this.properties = Array.isArray(data) ? data : [data];
		} catch (error) {
			console.error('Error loading properties:', error);
		}
	}

	clearMarkers() {
		this.markers.forEach(marker => {
			marker.map = null;
		});
		this.markers = [];
	}

	async updateProperties(propertiesJson) {
		try {
			if (typeof propertiesJson === 'string') {
				propertiesJson = JSON.parse(propertiesJson);
			}
			await this.loadProperties(propertiesJson);

			if (!this.map) return;

			const {AdvancedMarkerElement} = await importLibrary('marker');

			this.clearMarkers();

			if (this.mode === 'single') {
				this.renderSingleMarker(AdvancedMarkerElement);
			} else {
				this.renderMarkers(AdvancedMarkerElement);
			}
		} catch (error) {
			console.error('Error updating properties:', error);
		}
	}

	renderSingleMarker(AdvancedMarkerElement) {
		const markerElement = document.createElement('div');
		markerElement.className = 'map-marker';
		markerElement.innerHTML = '<img src="' + this.root.dataset.singleGeoMarker
			+ '" width="22" height="28" alt="' + __('Location', 'east-property') + '">';

		new AdvancedMarkerElement({
			map: this.map, position: {lat: this.lat, lng: this.lng}, content: markerElement, title: 'Location'
		});
	}

	initPointSelection(
		AdvancedMarkerElement,
		PlaceAutocompleteElement
	) {
		const latitude = parseFloat(this.latitudeInput?.value);
		const longitude = parseFloat(this.longitudeInput?.value);

		if (
			!Number.isNaN(latitude) &&
			!Number.isNaN(longitude)
		) {
			const position = {
				lat: latitude,
				lng: longitude,
			};

			this.setSelectionMarker(
				position,
				AdvancedMarkerElement
			);

			this.map.setCenter(position);
		}

		this.map.addListener('click', event => {
			if (!event.latLng) {
				return;
			}

			this.setSelectionMarker(
				{
					lat: event.latLng.lat(),
					lng: event.latLng.lng(),
				},
				AdvancedMarkerElement
			);
		});

		[...this.latitudeInputs, ...this.longitudeInputs].forEach(input => {
			input.addEventListener('change', () => {
				this.updateSelectionMarkerFromInputs(
					AdvancedMarkerElement
				);
			});
		});

		this.initAddressSearch(
			PlaceAutocompleteElement,
			AdvancedMarkerElement
		);
	}

	initAddressSearch(
		PlaceAutocompleteElement,
		AdvancedMarkerElement
	) {
		if (!this.addressContainer) {
			return;
		}

		const autocomplete = new PlaceAutocompleteElement({
			requestedLanguage: document.documentElement.lang || 'en',
			requestedRegion: 'ae',
		});

		autocomplete.classList.add('map-address-autocomplete');

		this.addressContainer.replaceChildren(autocomplete);

		autocomplete.addEventListener(
			'gmp-placeselect',
			async event => {
				const place = event.placePrediction.toPlace();

				await place.fetchFields({
					fields: [
						'displayName',
						'formattedAddress',
						'location',
						'viewport',
					],
				});

				if (!place.location) {
					return;
				}

				const position = {
					lat: place.location.lat(),
					lng: place.location.lng(),
				};

				this.setSelectionMarker(
					position,
					AdvancedMarkerElement
				);

				if (place.viewport) {
					this.map.fitBounds(place.viewport);
				} else {
					this.map.setCenter(position);
					this.map.setZoom(17);
				}

				if (this.addressInput) {
					this.addressInput.value =
						place.formattedAddress ||
						place.displayName ||
						'';
				}
			}
		);
	}

	setSelectionMarker(position, AdvancedMarkerElement) {
		const lat = Number(position.lat);
		const lng = Number(position.lng);

		if (
			!Number.isFinite(lat) ||
			!Number.isFinite(lng) ||
			lat < -90 ||
			lat > 90 ||
			lng < -180 ||
			lng > 180
		) {
			return;
		}

		const normalizedPosition = {lat, lng};

		if (!this.selectionMarker) {
			this.selectionMarker = new AdvancedMarkerElement({
				map: this.map,
				position: normalizedPosition,
				title: __('Selected location', 'east-property'),
				gmpDraggable: true,
			});

			this.selectionMarker.addListener(
				'dragend',
				() => {
					const markerPosition =
						this.selectionMarker.position;

					if (!markerPosition) {
						return;
					}

					this.updateCoordinateInputs({
						lat: Number(markerPosition.lat),
						lng: Number(markerPosition.lng),
					});
				}
			);
		} else {
			this.selectionMarker.position =
				normalizedPosition;

			this.selectionMarker.map = this.map;
		}

		this.updateCoordinateInputs(normalizedPosition);
	}

	/**
	 * Collect a coordinate input across every language fieldset.
	 *
	 * data-lang-sync is locale independent, unlike the name attribute which is
	 * prefixed with the language. Falls back to the plain name for a form without
	 * per-language fieldsets.
	 *
	 * @param {string} key Coordinate key, 'latitude' or 'longitude'.
	 *
	 * @return {HTMLInputElement[]} Inputs holding that coordinate.
	 */
	static findCoordinateInputs(key) {
		const synced = document.querySelectorAll(`input[data-lang-sync="${key}"]`);
		if (synced.length) {
			return Array.from(synced);
		}

		return Array.from(document.querySelectorAll(`input[name="${key}"]`));
	}

	updateCoordinateInputs(position) {
		PropertyMap.writeCoordinate(this.latitudeInputs, position.lat);
		PropertyMap.writeCoordinate(this.longitudeInputs, position.lng);
	}

	/**
	 * @param {HTMLInputElement[]} inputs Inputs to fill.
	 * @param {number} value Coordinate to write.
	 */
	static writeCoordinate(inputs, value) {
		const formatted = Number(value).toFixed(8);

		inputs.forEach(input => {
			input.value = formatted;
			input.dispatchEvent(
				new Event('input', {
					bubbles: true,
				})
			);
		});
	}

	updateSelectionMarkerFromInputs(
		AdvancedMarkerElement
	) {
		if (
			!this.latitudeInput ||
			!this.longitudeInput
		) {
			return;
		}

		const lat = parseFloat(
			this.latitudeInput.value
		);

		const lng = parseFloat(
			this.longitudeInput.value
		);

		if (
			Number.isNaN(lat) ||
			Number.isNaN(lng) ||
			lat < -90 ||
			lat > 90 ||
			lng < -180 ||
			lng > 180
		) {
			return;
		}

		const position = {lat, lng};

		this.setSelectionMarker(
			position,
			AdvancedMarkerElement
		);

		this.map.panTo(position);
	}

	renderMarkers(AdvancedMarkerElement) {
		this.properties.forEach(prop => {
			const lat = parseFloat(prop.latitude);
			const lng = parseFloat(prop.longitude);

			if (isNaN(lat) || isNaN(lng)) return;

			const markerElement = document.createElement('div');
			markerElement.className = 'map-marker';
			markerElement.innerHTML = `<span>${prop.units_available}</span>`;

			const marker = new AdvancedMarkerElement({
				map: this.map, position: {lat, lng}, content: markerElement, title: prop.name
			});

			marker.addListener('gmp-click', () => {
				this.openSidebar(prop);
			});

			this.markers.push(marker);
		});
	}

	openSidebar(prop) {
		if (!this.sidebar || !this.sidebarContent || !this.sidebarTarget) return;

		this.sidebar.classList.remove('is-hidden');
		this.sidebar.classList.add('is-loading');

		let formData = new FormData(),
			projectLink = this.sidebar.querySelector('.a-link .button');

		formData.append('action', 'get_map_property');
		formData.append('_ajax_nonce', ajax_object._ajax_nonce);
		formData.append('property_id', prop.id);

		fetch(ajax_object.ajax_url, {
			method: 'POST', body: formData, headers: {
				'Accept': 'application/json'
			}
		})
			.then(response => response.json())
			.then(response => {
				if (response.success) {
					this.sidebarTarget.innerHTML = response.data.map_property_html;
					projectLink.href = prop.url;
				}
				setTimeout(() => {
					this.sidebar.classList.remove('is-loading');
					this.initCardSlider();
				}, 600);
			})
			.catch(error => {
				console.log(error);
				this.sidebar.classList.remove('is-loading');
			});
	}

	initCardSlider() {
		const slider = this.root.querySelector('.building-card-slider');
		if (!slider) return;

		new Swiper(slider, {
			modules: [Navigation], slidesPerView: 1, loop: true, navigation: {
				nextEl: slider.querySelector('.swiper-next'), prevEl: slider.querySelector('.swiper-prev'),
			},
		});
	}

	initEvents() {
		if (this.sidebarClose) {
			this.sidebarClose.addEventListener('click', () => {
				this.sidebar.classList.add('is-hidden');
			});
		}

		if (this.sidebar) {
			const handle = this.sidebar.querySelector('.map-handle-wrapper');
			if (handle) {
				handle.addEventListener('touchstart', (e) => this.handleTouchStart(e), {passive: true});
				window.addEventListener('touchmove', (e) => this.handleTouchMove(e), {passive: false});
				window.addEventListener('touchend', () => this.handleTouchEnd(), {passive: true});
			}
		}
	}

	handleTouchStart(e) {
		if (window.innerWidth >= 768) return;
		this.isDragging = true;
		this.startY = e.touches[0].clientY;
		this.sidebar.classList.add('is-dragging');
	}

	handleTouchMove(e) {
		if (!this.isDragging || window.innerWidth >= 768) return;

		const deltaY = e.touches[0].clientY - this.startY;
		if (deltaY < 0) return;

		e.preventDefault();
		this.currentTranslation = deltaY;
		this.sidebar.style.transform = `translateY(${deltaY}px)`;
	}

	handleTouchEnd() {
		if (!this.isDragging) return;

		this.isDragging = false;
		this.sidebar.classList.remove('is-dragging');
		this.sidebar.style.transform = '';

		if (this.currentTranslation > 150) {
			this.sidebar.classList.add('is-hidden');
		}

		this.currentTranslation = 0;
	}
}
