const {__} = window.wp.i18n;

export const renderBuildingCard = (data) => {
    const galleryItems = data.gallery?.map(img => `
        <div class="swiper-slide">
            <img src="${img}" alt="${data.name}">
        </div>
    `).join('') || '';

    const unitsHtml = data.units?.map(unit => renderUnit({
        ...unit,
		// Фолбэк для юнита. Так как у него я не увидел деливери дейт. Если нет - берем деливери дейт апарта. Что логично в целом
        delivery_date: unit.delivery_date || data.delivery_date
    })).join('') || '';

    return `
        <div class="building-card">
            <div class="building-card-header">
                <div class="swiper single-swiper building-card-slider">
                    <div class="swiper-wrapper">
                        ${galleryItems}
                    </div>
                    <button class="swiper-prev sm"><img src="/img/swiper-arr.svg" width="16" height="16" alt="Prev"></button>
                    <button class="swiper-next sm"><img src="/img/swiper-arr.svg" width="16" height="16" alt="Next"></button>
                </div>

                <div class="building-card-info">
                    <div class="building-card-desc">
                        <h3>${data.name}</h3>
                        <p>${data.location}</p>
                    </div>
                    <div class="building-card-meta">
                        <span class="building-card-badge">${data.units_available} apartments for sale</span>
                    </div>
                    <div class="building-card-price">${data.price_from}</div>
                </div>
            </div>
            <div class="building-card-bottom">
                <div class="building-card-units">
                    ${unitsHtml}
                </div>
            </div>
        </div>
    `;
};

export const renderUnit = (unit) => {
    return `
        <a href="${unit.url}" class="unit">
            <span class="unit-date">${unit.delivery_date || 'TBA'}</span>
            <div class="unit-inner">
                <div class="unit-left">
                    <img src="${unit.image}" alt="${__('Unit', 'east-property')}">
                </div>
                <div class="unit-right">
                    <div class="unit-title">
                        <span>${unit.price}</span>
                        <p>${unit.developer || __('Developer', 'east-property')}</p>
                    </div>
                    <div class="unit-items">
                         <span>
                            <img src="/img/bed.svg" width="16" height="16" alt="${__('Beds', 'east-property')}">
                            ${unit.beds} ${__('Beds', 'east-property')}
                        </span>
                        <span>
                            <img src="/img/meters.svg" width="16" height="16" alt="${__('Area', 'east-property')}">
                            ${unit.area} ${__('sqft', 'east-property')}
                        </span>
                    </div>
                </div>
            </div>
        </a>
    `;
};
