import gulp from 'gulp';
import {paths} from './config.js';
import {ensureDir} from './config.js';

const {src, dest, parallel} = gulp;

export function images() {
    ensureDir('src/img');

    return src(paths.images.src, {
        allowEmpty: true,
        encoding: false,
        buffer: true,
        objectMode: false
    })
        .pipe(dest(paths.images.dest));
}

export function fonts() {
    ensureDir('src/fonts');

    return src(paths.fonts.src, {
        allowEmpty: true,
        encoding: false,
        buffer: true,
        objectMode: false
    })
        .pipe(dest(paths.fonts.dest));
}

export const assets = parallel(images, fonts);
