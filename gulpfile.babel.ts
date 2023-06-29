import { src, dest, series, parallel, watch as watchFiles, TaskFunction } from 'gulp'
import * as path from 'path'
import { exec } from 'child_process'
import { promises as fs } from 'fs'
import { webpack } from 'webpack'
import del from 'del'
import * as sourcemaps from 'gulp-sourcemaps'
import rename from 'gulp-rename'
import postcss from 'gulp-postcss'
import sass from 'gulp-sass'
import libsass from 'sass'
import cssnano from 'cssnano'
import autoprefixer from 'autoprefixer'
import zip from 'gulp-zip'
import rtlcss from 'gulp-rtlcss'
import cssimport from 'postcss-easy-import'
import hexrgba from 'postcss-hexrgba'
import eslint from 'gulp-eslint-new'
import codesniffer from 'gulp-phpcs'
import flatmap from 'gulp-flatmap'
import prefixSelector from 'postcss-prefix-selector'
import removeSourceMaps from 'gulp-remove-sourcemaps'
import * as pkg from './package.json'
import { config as webpackConfig } from './webpack.config'

const SRC_FILES = {
	php: ['*.php', 'php/**/*.php'],
	js: ['js/**/*.ts', 'js/**/*.tsx', 'js/**/*.js', 'js/**/*.jsx'],
	css: {
		all: ['css/**/*.scss'],
		source: ['css/*.scss', '!css/**/_*.scss'],
		directional: ['edit.css', 'manage.css'],
	},
}

const DEST_DIR = 'dist/'
const BUNDLE_DIR = 'bundle/'

const BUNDLE_FILES = [
	'assets/**/*',
	'css/**/*',
	'js/**/*',
	'dist/**/*',
	'!dist/**/*.map',
	'php/**/*',
	'vendor/**/*',
	'code-snippets.php',
	'readme.txt',
	'license.txt',
]

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
		.pipe(dest(DEST_DIR))

const createRtlCss: TaskFunction = () =>
	src(SRC_FILES.css.directional.map(file => path.join(DEST_DIR, file)))
		.pipe(rename({ suffix: '-rtl' }))
		.pipe(sourcemaps.init())
		.pipe(rtlcss())
		.pipe(sourcemaps.write('.'))
		.pipe(dest(DEST_DIR))

export const css: TaskFunction = series(transformCss, createRtlCss)

export const lintJs: TaskFunction = () =>
	src(SRC_FILES.js)
		.pipe(eslint())
		.pipe(eslint.format())
		.pipe(eslint.failAfterError())

const transformJs: TaskFunction = done => {
	webpack({
		...webpackConfig,
		mode: 'development',
		devtool: 'eval-cheap-source-map'
	}, done)
}

export const js: TaskFunction = series(lintJs, transformJs)

export const phpcs: TaskFunction = () =>
	src(SRC_FILES.php)
		.pipe(codesniffer({ bin: 'vendor/bin/phpcs', showSniffCode: true }))
		.pipe(codesniffer.reporter('log'))

const copyPrismThemes: TaskFunction = () =>
	src([
		'node_modules/prismjs/themes/prism-*.css',
		'node_modules/prism-themes/themes/prism-*.css',
		'!node_modules/prismjs/themes/prism-*.min.css',
		'!node_modules/prism-themes/themes/prism-*.min.css',
	])
		.pipe(flatmap((stream, file) =>
			stream.pipe(postcss([
				prefixSelector({
					prefix: `.is-style-${path.parse(file.path).name}`
				}),
				cssnano()
			]))
		))
		.pipe(dest(`${DEST_DIR}/prism-themes`))

const copyCodeMirrorThemes: TaskFunction = () =>
	src('node_modules/codemirror/theme/*.css')
		.pipe(postcss([cssnano()]))
		.pipe(dest(`${DEST_DIR}/editor-themes`))

export const vendor: TaskFunction = parallel(copyCodeMirrorThemes, copyPrismThemes)

export const clean: TaskFunction = () => del(DEST_DIR)

export const test = parallel(lintJs, phpcs)

export const build = series(clean, parallel(vendor, css, js))

export default build

export const bundle: TaskFunction = (() => {
	const cleanupBefore: TaskFunction = () =>
		del([BUNDLE_DIR, pkg.name, `${pkg.name}.*.zip`])

	const composerProduction: TaskFunction = done =>
		exec('composer install --no-dev', error => {
			if (error) throw error
			done()
		})

	const composer: TaskFunction = done =>
		exec('composer install', error => {
			if (error) throw error
			done()
		})

	const webpackProduction: TaskFunction = done => {
		webpack({ ...webpackConfig, mode: 'production' }, done)
	}

	const copyFiles: TaskFunction = () =>
		src(BUNDLE_FILES, { base: '.' })
			.pipe(removeSourceMaps())
			.pipe(dest(pkg.name))

	const archive: TaskFunction = () =>
		src(`${pkg.name}/**/*`, { base: '.' })
			.pipe(zip(`${pkg.name}.${pkg.version}.zip`))
			.pipe(dest('.'))

	const cleanupAfter: TaskFunction = done =>
		fs.rename(pkg.name, BUNDLE_DIR).then(() => done())

	return series(
		parallel(build, cleanupBefore),
		parallel(composerProduction, webpackProduction),
		copyFiles,
		archive,
		parallel(composer, cleanupAfter)
	)
})()

export const watch: TaskFunction = series(build, done => {
	watchFiles(SRC_FILES.css.all, series(css))
	watchFiles(SRC_FILES.js, series(js))
	done()
})
