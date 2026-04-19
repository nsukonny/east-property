import minimist from 'minimist';
import notify from 'gulp-notify';
import fs from 'fs';

const argv = minimist(process.argv.slice(2));

export const isProd = Boolean(argv.prod);

export const paths = {
    root: 'assets',
    projectName: 'east-property',
    styles: {
        src: 'assets/src/styles.scss',
        watch: [
            'east-property/dev/src/scss/**/*.scss',
            '!east-property/dev/src/scss/generated/**/*.scss',
            'east-property/dev/src/html/**/*.scss',
            'core/template-parts/**/*.scss',
            'core/template-parts/*.scss'
        ],
        dest: 'assets/css'
    },
    scripts: {
        src: 'assets/src/scripts.js',
        watch: [
            'east-property/dev/src/js/**/*.js',
            'east-property/dev/src/html/**/*.js',
            'assets/src/**/*.js',
            'core/components/*.js',
            'core/components/**/*.js'
        ],
        dest: 'assets/js'
    },
    static: {
        src: 'dev/public/**/*',
        dest: 'assets'
    },
    images: {
        src: 'dev/src/img/**/*.{png,jpg,jpeg,svg,gif,webp,ico}',
        dest: 'assets/img'
    },
    fonts: {
        src: 'dev/src/fonts/**/*',
        dest: 'assets/fonts'
    }
};

export function createErrorHandler(taskName) {
    return function handleError(err) {
        const rows = [];

        if (Array.isArray(err?.errors) && err.errors.length) {
            for (const e of err.errors) {
                const file = e.location?.file ?? '';
                const line = e.location?.line ?? '';
                const col = e.location?.column ?? '';
                const loc = file ? `${file}:${line}:${col}` : '';
                rows.push([loc, e.text].filter(Boolean).join(' - '));
            }
        } else {
            if (err?.file) {
                const location = [err.line, err.column].filter(Boolean).join(':');
                rows.push(location ? `${err.file}:${location}` : err.file);
            }
            rows.push(err?.message ? String(err.message) : String(err));
        }

        const message = rows.join('\n');
        console.error(`[${taskName}] ${message}`);

        this.emit('end');
    };
}

export function ensureDir(dirPath) {
    fs.mkdirSync(dirPath, {recursive: true});
}
