import gulp from 'gulp';
import plumber from 'gulp-plumber';
import * as dartSass from 'sass';
import gulpSassFactory from 'gulp-sass';
import postcss from 'gulp-postcss';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';
import rename from 'gulp-rename';
import {paths} from './config.js';
import {isProd} from './config.js';
import {createErrorHandler} from './config.js';
import fg from "fast-glob";
import path from "path";
import fs from "fs";

const {src, dest, series} = gulp;
const gulpSass = gulpSassFactory(dartSass);
const rootDir = paths.projectName + '/dev/src';
const outputFile = path.join(rootDir, 'scss', 'generated', '_html-modules.scss');
const outputDir = path.dirname(outputFile);

export async function stylesIndex() {
    const files = await fg([
        paths.projectName + '/dev/src/html/components/**/*.scss',
        paths.projectName + '/dev/src/html/sections/**/*.scss',
        '../core/components/**/*.scss',
    ]);

    const forwards = files
        .filter((file) => !path.basename(file).startsWith('_'))
        .map((file) => {
            const relative = path.relative(outputDir, file).replace(/\\/g, '/');
            const withoutExt = relative.replace(/\.scss$/, '');
            return `@forward '${withoutExt}';`;
        })
        .sort();

    const content = forwards.join('\n') + (forwards.length ? '\n' : '');

    await fs.promises.mkdir(path.dirname(outputFile), {recursive: true});
    await fs.promises.writeFile(outputFile, content);
}

function stylesCompile() {
    const plugins = [autoprefixer()];
    if (isProd) {
        plugins.push(cssnano());
    }
    return src(paths.styles.src, {sourcemaps: !isProd})
        .pipe(plumber({errorHandler: createErrorHandler('SCSS')}))
        .pipe(
            gulpSass(
                {
                    includePaths: ['node_modules', 'dev/src/scss', 'dev/src/html']
                },
                undefined
            )
        )
        .pipe(
            rename({
                suffix: '.min'
            })
        )
        .pipe(postcss(plugins))
        .pipe(dest(paths.styles.dest, {sourcemaps: '.'}));
}

export const styles = series(stylesIndex, stylesCompile);
