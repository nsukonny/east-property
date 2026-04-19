import fs from 'fs';
import path from 'path';
import fg from 'fast-glob';

const rootDir = 'dev/src';
const outputFile = path.join(rootDir, 'scss', 'generated', '_html-modules.scss');
const outputDir = path.dirname(outputFile);

export async function stylesIndex() {
	const files = await fg([
		'dev/src/html/components/**/*.scss',
		'dev/src/html/sections/**/*.scss'
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
