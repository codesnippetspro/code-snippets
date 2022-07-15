import { src, dest, series, parallel, watch as watchFiles, TaskFunction } from 'gulp'
import { webpack } from 'webpack'
import * as path from 'path'
import * as fs from 'fs'
import del from 'del'
import * as sourcemaps from 'gulp-sourcemaps'
import rename from 'gulp-rename'
import copy from 'gulp-copy'
import change from 'gulp-change'
import postcss from 'gulp-postcss'
import sass from 'gulp-sass'
import libsass from 'sass'
import cssnano from 'cssnano'
import autoprefixer from 'autoprefixer'
import zip from 'gulp-zip'
import rtlcss from 'gulp-rtlcss'
import cssimport from 'postcss-easy-import'
import hexrgba from 'postcss-hexrgba'
import eslint from 'gulp-eslint'
import makepot from 'gulp-wp-pot'
import gettext from 'gulp-gettext'
import codesniffer from 'gulp-phpcs'
import composer from 'gulp-composer'
import flatmap from 'gulp-flatmap'
import prefixSelector from 'postcss-prefix-selector'
import * as pkg from './package.json'
import { config as webpackConfig } from './webpack.config'

const SRC_FILES = {
	php: ['*.php', 'php/**/*.php'],
	js: ['js/**/*.ts', 'js/**/*.tsx', 'js/**/*.js', '!js/min/**/*'],
	css: {
		all: ['css/**/*.scss'],
		source: ['css/*.scss', '!css/_*.scss'],
		directional: ['edit.css', 'manage.css'],
	},
}

const DEST_DIRS = {
	js: 'js/min/',
	css: 'css/min/'
}

const TEXT_DOMAIN = 'code-snippets'

const transformCss = () =>
	src(SRC_FILES.css.source)
		.pipe(sourcemaps.init())
		.pipe(sass(libsass)().on('error', sass(libsass).logError))
		.pipe(postcss([
			cssimport({
				prefix: '_',
				extensions: ['.scss', '.css']
			}),
			hexrgba(),
			autoprefixer(),
			cssnano({
				preset: ['default', { discardComments: { removeAll: true } }]
			})
		]))
		.pipe(sourcemaps.write('.'))
		.pipe(dest(DEST_DIRS.css))

const createRtlCss: TaskFunction = () =>
	src(SRC_FILES.css.directional.map(file => DEST_DIRS.css + file))
		.pipe(rename({ suffix: '-rtl' }))
		.pipe(sourcemaps.init())
		.pipe(rtlcss())
		.pipe(sourcemaps.write('.'))
		.pipe(dest(DEST_DIRS.css))

export const css: TaskFunction = series(transformCss, createRtlCss)

export const jsLint: TaskFunction = () =>
	src(SRC_FILES.js)
		.pipe(eslint())
		.pipe(eslint.format())
		.pipe(eslint.failAfterError())

export const js: TaskFunction = series(jsLint, done => {
	webpack({
		...webpackConfig,
		mode: 'development',
		devtool: 'eval'
	}, done)
})

const createPotFile: TaskFunction = () =>
	src(SRC_FILES.php)
		.pipe(makepot({
			domain: TEXT_DOMAIN,
			package: 'Code Snippets',
			bugReport: 'https://github.com/sheabunge/code-snippets/issues',
			metadataFile: 'code-snippets.php',
		}))
		.pipe(dest(`languages/${TEXT_DOMAIN}.pot`))

const createPoFiles: TaskFunction = () =>
	src('languages/*.po')
		.pipe(gettext())
		.pipe(dest('languages'))

export const i18n: TaskFunction = parallel(createPotFile, createPoFiles)

export const phpcs: TaskFunction = () =>
	src(SRC_FILES.php)
		.pipe(codesniffer({ bin: 'vendor/bin/phpcs', showSniffCode: true }))
		.pipe(codesniffer.reporter('log'))

const copyPrismThemes: TaskFunction = () =>
	src(['node_modules/prismjs/themes/prism-*.css', '!node_modules/prismjs/themes/prism-*.min.css'])
		.pipe(flatmap((stream, file) =>
			stream.pipe(postcss([
				prefixSelector({
					prefix: `.is-style-${path.parse(file.path).name}`
				}),
				cssnano()
			]))
		))
		.pipe(dest(`${DEST_DIRS.css}prism-themes`))

const copyCodeMirrorThemes: TaskFunction = () =>
	src('node_modules/codemirror/theme/*.css')
		.pipe(postcss([cssnano()]))
		.pipe(dest(`${DEST_DIRS.css}editor-themes`))

export const vendor: TaskFunction = parallel(copyCodeMirrorThemes, copyPrismThemes)

export const clean: TaskFunction = () =>
	del([DEST_DIRS.css, DEST_DIRS.js])

export const test = parallel(jsLint, phpcs)

export const build = series(clean, parallel(vendor, css, js))

export default build

export const bundle: TaskFunction = (() => {
	const cleanupBefore: TaskFunction = () =>
		del(['dist', pkg.name, `${pkg.name}.*.zip`])

	const composerProduction: TaskFunction = () =>
		composer('install', { 'no-dev': true })

	const webpackProduction: TaskFunction = done => {
		webpack({ ...webpackConfig, mode: 'production' }, done)
	}

	const copyFiles: TaskFunction = () => src([
		'code-snippets.php',
		'readme.txt',
		'license.txt',
		'vendor/**/*',
		'php/**/*',
		'js/min/**/*',
		'css/font/**/*',
		'languages/**/*'
	]).pipe(copy(pkg.name))

	const copyStylesheets: TaskFunction = () => src('css/min/**/*.css')
		.pipe(change(content => content.replace(/\/\*# sourceMappingURL=[\w.-]+\.map \*\/\s+$/, '')))
		.pipe(dest(`${pkg.name}/css/min`))

	const archive: TaskFunction = () => src(`${pkg.name}/**/*`, { base: '.' })
		.pipe(zip(`${pkg.name}.${pkg.version}.zip`))
		.pipe(dest('.'))

	const cleanupAfter: TaskFunction = done => {
		// Reinstall dev dependencies
		composer()

		// Rename the distribution directory to its proper name
		fs.rename(pkg.name, 'dist', error => {
			if (error) throw error
			done()
		})
	}

	return series(
		build,
		cleanupBefore,
		parallel(composerProduction, webpackProduction),
		parallel(copyFiles, copyStylesheets),
		archive,
		cleanupAfter
	)
})()

export const watch: TaskFunction = series(build, done => {
	watchFiles(SRC_FILES.css.all, css)
	watchFiles(SRC_FILES.js, js)
	done()
})
