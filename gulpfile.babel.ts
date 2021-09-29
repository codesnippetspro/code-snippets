import * as fs from 'fs';
import gulp from 'gulp';
import sourcemaps from 'gulp-sourcemaps';
import rename from 'gulp-rename';

import clean from 'gulp-clean';
import copy from 'gulp-copy';
import change from 'gulp-change';
import archiver from 'gulp-archiver';

import postcss from 'gulp-postcss';
import cssnano from 'cssnano';
import rtlcss from 'gulp-rtlcss';
import cssimport from 'postcss-easy-import';
import hexrgba from 'postcss-hexrgba';
import autoprefixer from 'autoprefixer';

import imagemin from 'gulp-imagemin';

import webpack from 'webpack-stream';
import terser from 'gulp-terser';
import eslint from 'gulp-eslint';

import makepot from 'gulp-wp-pot';
import gettext from 'gulp-gettext';

import phpcs from 'gulp-phpcs';
import composer from 'gulp-composer';

import sass from 'gulp-sass';
import libsass from 'sass';

import * as pkg from './package.json';
import webpackConfig from './webpack.config';

const src_files = {
	php: ['*.php', 'php/**/*.php'],
	js: ['js/**/*.ts', 'js/**/*.tsx', 'js/**/*.js', '!js/min/**/*'],
	css: ['css/*.scss', '!css/_*.scss'],
	dir_css: ['edit.css', 'manage.css'],
};

const dist_dirs = {
	js: 'js/min/',
	css: 'css/min/'
};

const text_domain = pkg.name;

const postcss_processors = [
	cssimport({prefix: '_', extensions: ['.scss', '.css']}),
	hexrgba(),
	autoprefixer(),
	cssnano({preset: ['default', {discardComments: {removeAll: true}}]})
];

gulp.task('css', done =>
	gulp.series(
		() => gulp.src(src_files.css)
			.pipe(sourcemaps.init())
			.pipe(sass(libsass)().on('error', sass(libsass).logError))
			.pipe(postcss(postcss_processors))
			.pipe(sourcemaps.write('.'))
			.pipe(gulp.dest(dist_dirs.css)),
		() => gulp.src(src_files.dir_css.map(file => dist_dirs.css + file))
			.pipe(rename({suffix: '-rtl'}))
			.pipe(sourcemaps.init())
			.pipe(rtlcss())
			.pipe(sourcemaps.write('.'))
			.pipe(gulp.dest(dist_dirs.css))
	)(done));

gulp.task('jslint', () =>
	gulp.src(src_files.js)
		.pipe(eslint())
		.pipe(eslint.format())
		.pipe(eslint.failAfterError()));

gulp.task('js', gulp.series('jslint', () =>
	gulp.src(src_files.js)
		.pipe(webpack(webpackConfig))
		.pipe(sourcemaps.init())
		.pipe(terser())
		.pipe(sourcemaps.write('.'))
		.pipe(gulp.dest('js/min'))));

gulp.task('images', () =>
	gulp.src('screenshots/**/*')
		.pipe(imagemin())
		.pipe(gulp.dest('screenshots')));

gulp.task('makepot', () =>
	gulp.src(src_files.php)
		.pipe(makepot({
			domain: text_domain,
			package: 'Code Snippets',
			bugReport: 'https://github.com/sheabunge/code-snippets/issues',
			metadataFile: 'code-snippets.php',
		}))
		.pipe(gulp.dest(`languages/${text_domain}.pot`)));

gulp.task('gettext', () =>
	gulp.src('languages/*.po')
		.pipe(gettext())
		.pipe(gulp.dest('languages')));

gulp.task('i18n', gulp.parallel(['makepot', 'gettext']));

gulp.task('phpcs', () =>
	gulp.src(src_files.php)
		.pipe(phpcs({bin: 'vendor/bin/phpcs', showSniffCode: true}))
		.pipe(phpcs.reporter('log', {})));

gulp.task('vendor', () =>
	gulp.src('node_modules/codemirror/theme/*.css')
		.pipe(postcss([cssnano()]))
		.pipe(gulp.dest(`${dist_dirs.css}editor-themes`)));

gulp.task('clean', () =>
	gulp.src([dist_dirs.css, dist_dirs.js], {read: false, allowEmpty: true})
		.pipe(clean()));


gulp.task('test', gulp.parallel('jslint', 'phpcs'));

gulp.task('default', gulp.series('clean', gulp.parallel('vendor', 'css', 'js', 'i18n')));

gulp.task('package', gulp.series(
	'default',
	'vendor',

	// Remove files from last run
	() => gulp.src(['dist', pkg.name, `${pkg.name}.*.zip`], {read: false, allowEmpty: true})
		.pipe(clean()),

	// Remove composer dev dependencies
	() => composer({'no-dev': true}),

	// Copy files into a new directory
	() => gulp.src([
		'code-snippets.php', 'uninstall.php', 'php/**/*', 'vendor/**/*',
		'readme.txt', 'license.txt', 'css/font/**/*', 'languages/**/*'
	])
		.pipe(copy(pkg.name, {})),

	// Copy minified scripts and stylesheets, while removing source map references
	() => gulp.src('css/min/**/*.css')
		.pipe(change(content => content.replace(/\/\*# sourceMappingURL=[\w.-]+\.map \*\/\s+$/, '')))
		.pipe(gulp.dest(`${pkg.name}/css/min`)),

	() => gulp.src('js/min/**/*.js')
		.pipe(change(content => content.replace(/\/\/# sourceMappingURL=[\w.-]+\.map\s+$/, '')))
		.pipe(gulp.dest(`${pkg.name}/js/min`)),

	// Create a zip archive
	() => gulp.src(`${pkg.name}/**/*`, {base: '.'})
		.pipe(archiver(`${pkg.name}.${pkg.version}.zip`))
		.pipe(gulp.dest('.')),

	done => {
		// Reinstall dev dependencies
		composer();

		// Rename the distribution directory to its proper name
		fs.rename(pkg.name, 'dist', err => {
			if (err) throw err;
			done();
		});
	}
));

gulp.task('test', gulp.parallel('jslint', 'phpcs'));

gulp.task('default', gulp.series('clean', gulp.parallel('css', 'js', 'i18n')));

gulp.task('watch', gulp.series('default', done => {
	gulp.watch('css/*.scss', gulp.series('css'));
	gulp.watch(['js/**/*.js', '!js/min/**/*'], gulp.series('js'));
	done();
}));
