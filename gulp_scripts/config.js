import minimist from 'minimist';
import fs from 'fs';

const argv = minimist(process.argv.slice(2));

export const isProd = Boolean(argv.prod);

export const paths = {
    root: 'assets',
    styles: {
        src: 'assets/src/styles.scss',
        watch: [
            'src/scss/**/*.scss',
            'core/**/*.scss',
        ],
        dest: 'assets/css'
    },
    scripts: {
        src: 'assets/src/scripts.js',
        watch: [
            'src/js/**/*.js',
            'assets/src/**/*.js',
            'core/**/*.js'
        ],
        dest: 'assets/js'
    },
    images: {
        src: 'src/img/**/*.{png,jpg,jpeg,svg,gif,webp,ico}',
        dest: 'assets/img'
    },
    fonts: {
        src: 'src/fonts/**/*',
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

        if (isProd) {
            process.exit(1);
        } else {
            this.emit('end');
        }
    };
}

export function ensureDir(dirPath) {
    fs.mkdirSync(dirPath, {recursive: true});
}
