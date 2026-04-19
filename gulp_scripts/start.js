import {spawn} from 'child_process';

function startGulp() {
    const extraArgs = process.argv.slice(2);
    const gulpArgs = [
        'node_modules/gulp/bin/gulp.js',
        '--gulpfile',
        'gulp_scripts/gulpfile.js',
        '--cwd',
        '.',
        'dev',
        ...extraArgs
    ];

    const child = spawn(process.execPath, gulpArgs, {
        stdio: 'inherit'
    });

    child.on('exit', (code) => {
        process.exit(code ?? 0);
    });
}

startGulp();