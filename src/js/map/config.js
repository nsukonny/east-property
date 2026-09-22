import {setOptions} from '@googlemaps/js-api-loader';

export const MAP_CONFIG = {
	API_KEY: window.MAP_CONFIG?.apiKey ?? '',
	// How early a map starts before it scrolls into view.
	LAZY_ROOT_MARGIN: '300px',
	MAP_ID: window.MAP_CONFIG?.mapId ?? '',
	DATA_URL: '/data/properties.json',
	DEFAULT_CENTER: {lat: 25.2048, lng: 55.2708},
	DEFAULT_ZOOM: 11,
	CLUSTER_RADIUS: 70,
	CLUSTER_MAX_ZOOM: 15,
	CLUSTER_VIEWPORT_PADDING: 200,
};

setOptions({
	key: MAP_CONFIG.API_KEY,
	v: 'weekly',
	mapIds: [MAP_CONFIG.MAP_ID],
});
