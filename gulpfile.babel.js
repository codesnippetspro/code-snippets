'use strict';

import fs from 'fs';
import gulp from 'gulp';
import sourcemaps from 'gulp-sourcemaps';
import flatten from 'gulp-flatten';
import rename from 'gulp-rename';
import concat from 'gulp-concat';

import clean from 'gulp-clean';
import copy from 'gulp-copy';
import archiver from 'gulp-archiver';

import postcss from 'gulp-postcss';
import autoprefixer from 'autoprefixer';
import precss from 'precss';
import rtlcss from 'gulp-rtlcss';
import cssnano from 'cssnano';

import imagemin from 'gulp-imagemin';

import browserify from 'browserify';
import babelify from 'babelify';
import source from 'vinyl-source-stream';
import buffer from 'vinyl-buffer';
import uglify from 'gulp-uglify';
import eslint from 'gulp-eslint';

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

const src_dirs = {
	codemirror: 'node_modules/codemirror/',
};

const dist_dirs = {
	js: 'js/min/',
	css: 'css/min/'
};

gulp.task('css', (done) => {

	let processors = [
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

gulp.task('vendor', gulp.parallel(
	// CodeMirror scripts
	() => {
		let scripts = ['lib/codemirror.js'];

		for (let mode of ['clike', 'php']) {
			scripts.push(`mode/${mode}/${mode}.js`);
		}

		let codemirror_addons = ['edit/matchbrackets', 'dialog/dialog', 'search/searchcursor', 'search/search'];
		for (let addon of codemirror_addons) {
			scripts.push(`addon/${addon}.js`);
		}

		return gulp.src(scripts.map((file) => src_dirs.codemirror + file))
			.pipe(concat('codemirror.js'))
			.pipe(uglify())
			.pipe(gulp.dest(dist_dirs.js));
	},

	// CodeMirror styles
	() => gulp.src(['lib/codemirror.css', 'addon/dialog/dialog.css'].map((file) => src_dirs.codemirror + file))
		.pipe(concat('codemirror.css'))
		.pipe(postcss([cssnano()]))
		.pipe(gulp.dest(dist_dirs.css)),

	// CodeMirror themes
	() => {
		return gulp.src(src_dirs.codemirror + 'theme/*.css')
			.pipe(postcss([cssnano()]))
			.pipe(gulp.dest(dist_dirs.css + 'cmthemes'));
	},

	// Tag-it script
	() => gulp.src('js/vendor/tag-it.js')
		.pipe(uglify())
		.pipe(gulp.dest(dist_dirs.js)),

	// Tag-it styles
	() => gulp.src(['jquery.tagit.css', 'tagit.ui-zendesk.css'].map((file) => 'js/vendor/' + file))
		.pipe(concat('tag-it.css'))
		.pipe(postcss([cssnano()]))
		.pipe(gulp.dest(dist_dirs.css)),

	// Prism script
	() => gulp.src('js/vendor/prism.js')
		.pipe(uglify())
		.pipe(gulp.dest(dist_dirs.js)),

	// Prism styles
	() => gulp.src('js/vendor/prism.css')
		.pipe(postcss([cssnano()]))
		.pipe(gulp.dest(dist_dirs.css))
));

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
			'indent': ['error', 'tab']
		}
	};

	return gulp.src(src_files.js)
		.pipe(eslint(options))
		.pipe(eslint.format())
		.pipe(eslint.failAfterError())
});

gulp.task('js', gulp.series('test-js', () => {

	const b = browserify({
		debug: true,
		entries: 'js/manage.js'
	});

	b.transform('babelify', {
		presets: ['@babel/preset-env'], sourceMaps: true
	});

	return b.bundle()
		.pipe(source('js/manage.js'))
		.pipe(buffer())
		.pipe(sourcemaps.init())
		.pipe(uglify())
		.pipe(sourcemaps.write('.'))
		.pipe(flatten())
		.pipe(gulp.dest('js/min'))
}));

gulp.task('images', () => {
	return gulp.src('screenshots/**/*')
		.pipe(imagemin())
		.pipe(gulp.dest('screenshots'));
});

gulp.task('makepot', () => {
	return gulp.src(src_files.php)
		.pipe(makepot({
			domain: pkg.name,
			package: pkg.name,
			bugReport: 'https://github.com/sheabunge/code-snippets/issues',
			metadataFile: 'code-snippets.php',
		}))
		.pipe(gulp.dest(`languages/${pkg.name}.pot`));
});

gulp.task('gettext', () => {
	return gulp.src('languages/*.po')
		.pipe(gettext())
		.pipe(gulp.dest('languages'))

});

gulp.task('i18n', gulp.parallel(['makepot', 'gettext']));


gulp.task('phpcs', () => {
	return gulp.src(src_files.php)
		.pipe(phpcs({
			bin: 'vendor/bin/phpcs',
			standard: 'codesniffer.ruleset.xml',
			showSniffCode: true
		}))
		.pipe(phpcs.reporter('log'));
});

gulp.task('phpunit', () => {
	return gulp.src('phpunit.xml')
		.pipe(phpunit('phpunit'));
});

gulp.task('clean', () => {
	return gulp.src([dist_dirs.css, dist_dirs.js], {read: false, allowEmpty: true})
		.pipe(clean());
});

gulp.task('package', gulp.series(
	() => gulp.src(['dist', pkg.name, `${pkg.name}.*.zip`], {read: false, allowEmpty: true})
		.pipe(clean()),

	() => gulp.src([
		'code-snippets.php', 'uninstall.php', 'php/**/*',
		'readme.txt', 'license.txt', 'languages/**/*',
		'css/min/**/*', 'css/font/**/*', 'js/min/**/*'
	])
		.pipe(copy(pkg.name)),

	() => gulp.src(pkg.name + '/**/*', {base: '.'})
		.pipe(archiver(`${pkg.name}.${pkg.version}.zip`))
		.pipe(gulp.dest('.')),

	(done) => {
		fs.rename(pkg.name, 'dist', err => {
			if (err) throw err;
			done();
		});
	}
));

gulp.task('test', gulp.parallel(['test-js', gulp.series(['phpcs', 'phpunit'])]));

gulp.task('default', gulp.series('clean', gulp.parallel('css', 'js', 'i18n', 'vendor')));

gulp.task('watch', gulp.series('default', (done) => {
	gulp.watch('css/*.scss', gulp.series('css'));
	gulp.watch('js/*.js', gulp.series('js'));
	done();
}));
