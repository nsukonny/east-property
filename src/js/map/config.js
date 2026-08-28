import {setOptions} from '@googlemaps/js-api-loader';

export const MAP_CONFIG = {
	API_KEY: window.MAP_CONFIG?.apiKey ?? '',
	MAP_ID: window.MAP_CONFIG?.mapId ?? '',
	DATA_URL: '/data/properties.json',
	DEFAULT_CENTER: {lat: 25.2048, lng: 55.2708},
	DEFAULT_ZOOM: 11,
};

setOptions({
	key: MAP_CONFIG.API_KEY,
	v: 'weekly',
	mapIds: [MAP_CONFIG.MAP_ID],
});
