import Swiper from 'swiper';
import {Navigation, Thumbs, Pagination} from 'swiper/modules';


document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    initSwiperThumbs();
    initSingleSwiper();
    initGallerySlider();
});

const initSwiperThumbs = () => {
    const thumbsSwiper = new Swiper('.thumbs-swiper-container', {
        modules: [Thumbs],
        slidesPerView: 2.3,
        spaceBetween: 10,
        watchSlidesProgress: true,
        breakpoints: {
            420: {
                slidesPerView: 2.7
            },
            650: {
                slidesPerView: 3.5
            },
            768: {
                slidesPerView: 4.5
            },
            992: {
                slidesPerView: 5.5
            },
            1200: {
                slidesPerView: 8
            }
        }
    });

    new Swiper('.main-swiper', {
        modules: [Navigation, Thumbs],
        slidesPerView: 1,
        spaceBetween: 16,
        navigation: {
            nextEl: '.swiper-next',
            prevEl: '.swiper-prev',
        },
        thumbs: {
            swiper: thumbsSwiper,
        },
    });
};

export const initSingleSwiper = () => {
    new Swiper('.single-swiper', {
        modules: [Navigation],
        slidesPerView: 1,
        spaceBetween: 16,
        loop: true,
        navigation: {
            nextEl: '.swiper-next',
            prevEl: '.swiper-prev',
        }
    });
};

export const initGallerySlider = () => {
    return new Swiper('.gallery-swiper', {
        modules: [Navigation, Pagination],
        slidesPerView: 1,
        spaceBetween: 20,
        observer: true,
        observeParents: true,
        navigation: {
            nextEl: '.gallery-arrow-next',
            prevEl: '.gallery-arrow-prev',
        },
        pagination: {
            el: '.gallery-modal-counter',
            type: 'fraction',
        },
    });
};
