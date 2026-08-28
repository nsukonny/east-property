import gulp from 'gulp';
import browserSyncLib from 'browser-sync';
import {styles} from './styles.js';
import {scripts, validateJsSyntax} from './scripts.js';
import {assets} from './assets.js';
import {paths} from './config.js';

const {watch, series, parallel} = gulp;
const browserSync = browserSyncLib.create();

const scriptsPipeline = series(validateJsSyntax, scripts);

async function scriptsWatch(event, filePath) {
    if (event === 'unlink') {
        return;
    }
    try {
        await validateJsSyntax([filePath]);
    } catch {
        return;
    }
    await new Promise((resolve) => {
        scripts().on('end', resolve);
    });
    browserSync.reload();
}

function reload(done) {
    browserSync.reload();
    done();
}

function stylesWatch() {
    return styles().pipe(browserSync.stream());
}

function serve(cb) {
    browserSync.init({
        proxy: 'http://eastproperty.local',
        port: 3000,
        open: true,
        notify: true
    });

    watch(paths.styles.watch, stylesWatch);
    watch(paths.scripts.watch).on('all', scriptsWatch);
    watch(['**/*.php', '!node_modules/**'], reload);

    cb();
}

const buildTasks = parallel(styles, scriptsPipeline, assets);

export {styles, scripts, assets, validateJsSyntax};
export const dev = series(buildTasks, serve);
export const build = series(buildTasks);
export default dev;
