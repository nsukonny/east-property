import gulp from 'gulp';
import {styles} from './styles.js';
import {scripts, validateJsSyntax} from './scripts.js';
import {assets} from './assets.js';
import {paths} from './config.js';

const {watch, series, parallel} = gulp;

const scriptsPipeline = series(validateJsSyntax, scripts);

function watcher() {
    watch(paths.styles.watch, styles);
    watch(paths.scripts.watch, scriptsPipeline);
}

const buildTasks = parallel(styles, scriptsPipeline, assets);

export {styles, scripts, assets, validateJsSyntax};
export const dev = series(buildTasks, watcher);
export const build = series(buildTasks);
export default dev;
