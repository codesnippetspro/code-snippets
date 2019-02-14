'use strict';

import fs from 'fs';
import gulp from 'gulp';
import sourcemaps from 'gulp-sourcemaps';
import flatten from 'gulp-flatten';
import rename from 'gulp-rename';

import clean from 'gulp-clean';
import copy from 'gulp-copy';
import change from 'gulp-change';
import archiver from 'gulp-archiver';

import postcss from 'gulp-postcss';
import precss from 'precss';
import cssnano from 'cssnano';
import rtlcss from 'gulp-rtlcss';
import cssimport from 'postcss-import';
import autoprefixer from 'autoprefixer';

import imagemin from 'gulp-imagemin';

import babelify from 'babelify';
import browserify from 'browserify';
import uglify from 'gulp-uglify';
import eslint from 'gulp-eslint';
import source from 'vinyl-source-stream';
import buffer from 'vinyl-buffer';

import makepot from 'gulp-wp-pot';
import gettext from 'gulp-gettext'

import phpcs from 'gulp-phpcs';
import phpunit from 'gulp-phpunit';

const pkg = require('./package.json');

const src_files = {
	php: ['code-snippets.php', 'php/**/*.php'],
	js: ['js/*.js'],
	css: ['css/*.scss', '!css/_*.scss'],
};

const dist_dirs = {
	js: 'js/min/',
	css: 'css/min/'
};

gulp.task('css', (done) => {

	let processors = [
		cssimport(),
		precss(),
		autoprefixer(),
		cssnano({'preset': ['default', {'discardComments': {'removeAll': true}}]})
	];

	const dir_css = ['edit.css', 'manage.css'];

	return gulp.series(
		() => gulp.src(src_files.css)
			.pipe(rename({extname: '.css'}))
			.pipe(sourcemaps.init())
			.pipe(postcss(processors))
			.pipe(sourcemaps.write('.'))
			.pipe(gulp.dest(dist_dirs.css)),
		() => gulp.src(dir_css.map((f) => dist_dirs.css + f))
			.pipe(rename({suffix: '-rtl'}))
			.pipe(sourcemaps.init())
			.pipe(rtlcss())
			.pipe(sourcemaps.write('.'))
			.pipe(gulp.dest(dist_dirs.css))
	)(done);
});

gulp.task('test-js', () => {

	const options = {
		parserOptions: {
			ecmaVersion: 6,
			sourceType: 'module'
		},
		extends: 'eslint:recommended',
		rules: {
			'quotes': ['error', 'single'],
			'linebreak-style': ['error', 'unix'],
			'eqeqeq': ['warn', 'always'],
			'indent': ['error', 'tab', {'SwitchCase': 1}]
		}
	};

	return gulp.src(src_files.js)
		.pipe(eslint(options))
		.pipe(eslint.format())
		.pipe(eslint.failAfterError())
});

function bundlejs(file, babel_config) {
	const b = browserify({
		debug: true,
		entries: file
	});

	b.transform('babelify', {sourceMaps: true, ...babel_config, presets: ['@babel/preset-env']});

	return b.bundle()
		.pipe(source(file))
		.pipe(buffer())
		.pipe(sourcemaps.init())
		.pipe(uglify())
		.pipe(sourcemaps.write('.'))
		.pipe(flatten())
		.pipe(gulp.dest('js/min'));
}

gulp.task('js', gulp.series('test-js', gulp.parallel(
	() => bundlejs('js/editor.js'),
	() => bundlejs('js/manage.js'),
	() => bundlejs('js/edit.js'),
	() => bundlejs('js/edit-tags.js'),
	() => bundlejs('js/settings.js'),
	() => bundlejs('js/front-end.js', {
		plugins: [['prismjs', {languages: ['php', 'php-extras'], plugins: ['line-highlight', 'line-numbers']}]]
	})
)));

gulp.task('images', () =>
	gulp.src('screenshots/**/*')
		.pipe(imagemin())
		.pipe(gulp.dest('screenshots')));

gulp.task('makepot', () =>
	gulp.src(src_files.php)
		.pipe(makepot({
			domain: pkg.name,
			package: 'Code Snippets',
			bugReport: 'https://github.com/sheabunge/code-snippets/issues',
			metadataFile: 'code-snippets.php',
		}))
		.pipe(gulp.dest(`languages/${pkg.name}.pot`)));

gulp.task('gettext', () =>
	gulp.src('languages/*.po')
		.pipe(gettext())
		.pipe(gulp.dest('languages')));

gulp.task('i18n', gulp.parallel(['makepot', 'gettext']));


gulp.task('phpcs', () =>
	gulp.src(src_files.php)
		.pipe(phpcs({bin: 'vendor/bin/phpcs', showSniffCode: true}))
		.pipe(phpcs.reporter('log')));

gulp.task('phpunit', () =>
	gulp.src('phpunit.xml')
		.pipe(phpunit('phpunit')));

gulp.task('vendor', () =>
	gulp.src('node_modules/codemirror/theme/*.css')
		.pipe(postcss([cssnano()]))
		.pipe(gulp.dest(dist_dirs.css + 'editor-themes')));

gulp.task('clean', () =>
	gulp.src([dist_dirs.css, dist_dirs.js], {read: false, allowEmpty: true})
		.pipe(clean()));

gulp.task('package', gulp.series(
	() => gulp.src(['dist', pkg.name, `${pkg.name}.*.zip`], {read: false, allowEmpty: true})
		.pipe(clean()),

	() => gulp.src(['code-snippets.php', 'uninstall.php', 'php/**/*', 'readme.txt', 'license.txt', 'languages/**/*', 'css/font/**/*'])
		.pipe(copy(pkg.name)),

	() => gulp.src('css/min/**/*.css')
		.pipe(change((content) => content.replace(/\/\*# sourceMappingURL=[\w.-]+\.map \*\/\s+$/, '')))
		.pipe(gulp.dest(pkg.name + '/css/min')),

	() => gulp.src('js/min/**/*.js')
		.pipe(change((content) => content.replace(/\/\/# sourceMappingURL=[\w.-]+\.map\s+$/, '')))
		.pipe(gulp.dest(pkg.name + '/js/min')),

	() => gulp.src(pkg.name + '/**/*', {base: '.'})
		.pipe(archiver(`${pkg.name}.${pkg.version}.zip`))
		.pipe(gulp.dest('.')),

	(done) =>
		fs.rename(pkg.name, 'dist', err => {
			if (err) throw err;
			done();
		})
));

gulp.task('test', gulp.parallel('test-js', gulp.series('phpcs', 'phpunit')));

gulp.task('default', gulp.series('clean', gulp.parallel('vendor', 'css', 'js', 'i18n')));

gulp.task('watch', gulp.series('default', (done) => {
	gulp.watch('css/*.scss', gulp.series('css'));
	gulp.watch('js/*.js', gulp.series('js'));
	done();
}));
