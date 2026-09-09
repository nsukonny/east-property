import {setOptions} from '@googlemaps/js-api-loader';

export const MAP_CONFIG = {
	API_KEY: window.MAP_CONFIG?.apiKey ?? '',
	MAP_ID: window.MAP_CONFIG?.mapId ?? '',
	DATA_URL: '/data/properties.json',
	DEFAULT_CENTER: {lat: 25.2048, lng: 55.2708},
	DEFAULT_ZOOM: 11,
	// Радиус группировки в пикселях экрана: булавки, оказавшиеся ближе этого
	// расстояния друг к другу, сливаются в один кластер. В пикселях, а не в
	// метрах, поэтому на любом зуме карта выглядит одинаково плотной.
	CLUSTER_RADIUS: 70,
	// Зум, выше которого группировка выключается и каждый объект показывается
	// отдельно. В Дубае проекты стоят плотно, поэтому порог поднят: раньше
	// него кластеры полезны, дальше — только мешают выбирать конкретный дом.
	CLUSTER_MAX_ZOOM: 15,
};

setOptions({
	key: MAP_CONFIG.API_KEY,
	v: 'weekly',
	mapIds: [MAP_CONFIG.MAP_ID],
});
