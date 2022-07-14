import { src, dest, series, parallel, watch as watchFiles, TaskFunction } from 'gulp';
import * as fs from 'fs';
import del from 'del';
import * as sourcemaps from 'gulp-sourcemaps';
import rename from 'gulp-rename';
import copy from 'gulp-copy';
import change from 'gulp-change';
import postcss from 'gulp-postcss';
import sass from 'gulp-sass';
import libsass from 'sass';
import cssnano from 'cssnano';
import autoprefixer from 'autoprefixer';
import zip from 'gulp-zip';
import rtlcss from 'gulp-rtlcss';
import cssimport from 'postcss-easy-import';
import hexrgba from 'postcss-hexrgba';
import eslint from 'gulp-eslint';
import makepot from 'gulp-wp-pot';
import gettext from 'gulp-gettext';
import codesniffer from 'gulp-phpcs';
import composer from 'gulp-composer';
import { webpack } from 'webpack';

import * as pkg from './package.json';
import { config as webpackConfig } from './webpack.config';

const src_files = {
	php: ['*.php', 'php/**/*.php'],
	js: ['js/**/*.ts', 'js/**/*.tsx', 'js/**/*.js', '!js/min/**/*'],
	css: ['css/*.scss', '!css/_*.scss'],
	all_css: ['css/**/*.scss'],
	dir_css: ['edit.css', 'manage.css'],
};

const dist_dirs = {
	js: 'js/min/',
	css: 'css/min/'
};

const text_domain = pkg.name;

const postcss_processors = [
	cssimport({ prefix: '_', extensions: ['.scss', '.css'] }),
	hexrgba(),
	autoprefixer(),
	cssnano({ preset: ['default', { discardComments: { removeAll: true } }] })
];

export const css: TaskFunction = done => series(
	() => src(src_files.css)
		.pipe(sourcemaps.init())
		.pipe(sass(libsass)().on('error', sass(libsass).logError))
		.pipe(postcss(postcss_processors))
		.pipe(sourcemaps.write('.'))
		.pipe(dest(dist_dirs.css)),
	() => src(src_files.dir_css.map(file => dist_dirs.css + file))
		.pipe(rename({ suffix: '-rtl' }))
		.pipe(sourcemaps.init())
		.pipe(rtlcss())
		.pipe(sourcemaps.write('.'))
		.pipe(dest(dist_dirs.css))
)(done)

export const jslint: TaskFunction = () =>
	src(src_files.js)
		.pipe(eslint())
		.pipe(eslint.format())
		.pipe(eslint.failAfterError())

export const js: TaskFunction = series(jslint, done => {
	webpack({
		...webpackConfig,
		mode: 'development',
		devtool: 'eval'
	}, done)
})

export const i18n: TaskFunction = parallel([
	() => src(src_files.php)
		.pipe(makepot({
			domain: text_domain,
			package: 'Code Snippets',
			bugReport: 'https://github.com/sheabunge/code-snippets/issues',
			metadataFile: 'code-snippets.php',
		}))
		.pipe(dest(`languages/${text_domain}.pot`)),
	() => src('languages/*.po')
		.pipe(gettext())
		.pipe(dest('languages'))
])

export const phpcs: TaskFunction = () =>
	src(src_files.php)
		.pipe(codesniffer({ bin: 'vendor/bin/phpcs', showSniffCode: true }))
		.pipe(codesniffer.reporter('log'))

export const vendor: TaskFunction = () =>
	src('node_modules/codemirror/theme/*.css')
		.pipe(postcss([cssnano()]))
		.pipe(dest(`${dist_dirs.css}editor-themes`))

export const clean: TaskFunction = () =>
	del([dist_dirs.css, dist_dirs.js])


export const test = parallel(jslint, phpcs)

export const build = series(clean, parallel(vendor, css, js, i18n))

export default build

export const bundle: TaskFunction = series(
	build,
	vendor,

	// Remove files from last run
	() => del(['dist', pkg.name, `${pkg.name}.*.zip`]),

	// Remove composer dev dependencies
	() => composer('install', { 'no-dev': true }),

	// Run Webpack in production mode
	done => {
		webpack({ ...webpackConfig, mode: 'production' }, done)
	},

	// Copy files into a new directory
	() => src([
		'code-snippets.php',
		'uninstall.php',
		'readme.txt',
		'license.txt',
		'vendor/**/*',
		'php/**/*',
		'js/min/**/*',
		'css/font/**/*',
		'languages/**/*'
	])
		.pipe(copy(pkg.name)),

	// Copy minified stylesheets, while removing source map references
	() => src('css/min/**/*.css')
		.pipe(change(content => content.replace(/\/\*# sourceMappingURL=[\w.-]+\.map \*\/\s+$/, '')))
		.pipe(dest(`${pkg.name}/css/min`)),

	// Create a zip archive
	() => src(`${pkg.name}/**/*`, { base: '.' })
		.pipe(zip(`${pkg.name}.${pkg.version}.zip`))
		.pipe(dest('.')),

	done => {
		// Reinstall dev dependencies
		composer();

		// Rename the distribution directory to its proper name
		fs.rename(pkg.name, 'dist', error => {
			if (error) throw error;
			done();
		});
	}
)

export const watch: TaskFunction = series(build, done => {
	watchFiles(src_files.all_css, css);
	watchFiles(src_files.js, js);
	done();
})
