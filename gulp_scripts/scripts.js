import gulp from 'gulp';
import plumber from 'gulp-plumber';
import gulpEsbuild from 'gulp-esbuild';
import path from 'path';
import {Transform} from 'stream';
import javascriptObfuscator from 'javascript-obfuscator';
import {paths} from './config.js';
import {isProd} from './config.js';
import {createErrorHandler} from './config.js';
import fg from 'fast-glob';
import fs from 'fs/promises';
import * as esbuild from 'esbuild';

function createObfuscateStream() {
    const options = {
        compact: true,
        controlFlowFlattening: true,
        controlFlowFlatteningThreshold: 0.75,
        deadCodeInjection: false,
        stringArray: true,
        stringArrayThreshold: 0.75,
        shuffleStringArray: true,
        rotateStringArray: true,
        target: 'browser'
    };

    return new Transform({
        objectMode: true,
        transform(file, _encoding, callback) {
            if (path.extname(file.path) !== '.js') {
                return callback(null, file);
            }
            if (file.isBuffer()) {
                try {
                    const code = file.contents.toString('utf8');
                    const result = javascriptObfuscator.obfuscate(code, options);
                    file.contents = Buffer.from(result.getObfuscatedCode(), 'utf8');
                } catch (error) {
                    console.error('JS obfuscation error:', error);
                }
            }

            callback(null, file);
        }
    });
}

export async function validateJsSyntax() {
    const files = await fg(paths.scripts.watch);

    let hasErrors = false;

    for (const file of files) {
        const code = await fs.readFile(file, 'utf8');
        try {
            await esbuild.transform(code, {
                loader: 'js',
                sourcefile: file,
                logLevel: 'silent'
            });
        } catch (err) {
            hasErrors = true;
            if (Array.isArray(err.errors)) {
                for (const e of err.errors) {
                    const l = e.location;
                    console.error(`[JS syntax] ${l?.file}:${l?.line}:${l?.column} - ${e.text}`);
                }
            } else {
                console.error(`[JS syntax] ${file} - ${err.message}`);
            }
        }
    }

    if (hasErrors) {
        throw new Error('JS syntax validation failed');
    }
}

export function scripts() {
    let stream = gulp.src(paths.scripts.src, {allowEmpty: true})
        .pipe(plumber({errorHandler: createErrorHandler('JS')}))
        .pipe(
            gulpEsbuild({
                outfile: 'main.min.js',
                bundle: true,
                sourcemap: !isProd,
                minify: isProd,
                target: ['es2017'],
                platform: 'browser',
                format: 'iife',
                alias: {
                    '@': path.resolve(paths.projectName + '/dev/src/js')
                }
            })
        );

    if (isProd) {
        stream = stream.pipe(createObfuscateStream());
    }

    return stream.pipe(gulp.dest(paths.scripts.dest));
}
