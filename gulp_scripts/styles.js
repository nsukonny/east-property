import gulp from 'gulp';
import plumber from 'gulp-plumber';
import * as dartSass from 'sass';
import gulpSassFactory from 'gulp-sass';
import postcss from 'gulp-postcss';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';
import rtlcss from 'rtlcss';
import rename from 'gulp-rename';
import {paths, isProd, createErrorHandler} from './config.js';

const {src, dest} = gulp;
const gulpSass = gulpSassFactory(dartSass);

/**
 * Собирает один из двух файлов стилей.
 *
 * Зеркальный нужен арабскому: WordPress сам подставляет его вместо обычного,
 * когда is_rtl(). rtlcss стоит до cssnano намеренно — он читает управляющие
 * комментарии вида /*rtl:ignore*\/, а минификатор их срезает.
 *
 * @param {boolean} rtl Собирать зеркальную версию.
 *
 * @return {NodeJS.ReadWriteStream}
 */
function compile(rtl) {
    const plugins = [autoprefixer()];

    if (rtl) {
        plugins.push(rtlcss());
    }

    if (isProd) {
        plugins.push(cssnano());
    }

    return src(paths.styles.src, {sourcemaps: !isProd})
        .pipe(plumber({errorHandler: createErrorHandler('SCSS')}))
        .pipe(
            gulpSass(
                {
                    includePaths: ['node_modules', 'src/scss']
                },
                undefined
            )
        )
        .pipe(
            rename({
                suffix: rtl ? '.min-rtl' : '.min'
            })
        )
        .pipe(postcss(plugins))
        .pipe(dest(paths.styles.dest, {sourcemaps: '.'}));
}

export function styles() {
    return compile(false);
}

export function stylesRtl() {
    return compile(true);
}
