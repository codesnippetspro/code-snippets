module.exports = function(grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

	var codemirror = 'node_modules/codemirror/';

	grunt.initConfig({

		watch: {

			css: {
				files: ['css/**/*.scss'],
				tasks: ['postcss', 'csso']
			}
		},

		jshint: {
			gruntfile: ['Gruntfile.js']
		},

		postcss: {
			options: {
				map: true,
				processors: [
					require('precss')(),
					require('autoprefixer')()

				]
			},
			dist: {
				cwd: 'css',
				src: '*.scss',
				dest: 'css/build',
				expand: true,
				ext: '.css'
			}
		},

		csso: {
			dist: {
				expand: true,
				flatten: true,
				src: 'css/build/*.css',
				dest: 'css/min'
			},
			cmthemes: {
				expand: true,
				flatten: true,
				src: codemirror + 'theme/*.css',
				dest: 'css/min/cmthemes'
			},
			vendor: {
				files: {
					'css/min/codemirror.css': [
						codemirror + 'lib/codemirror.css'
					],
					'css/min/tagit.css': [
						'js/vendor/jquery.tagit.css',
						'js/vendor/tagit.ui-zendesk.css'
					]
				}
			}
		},

		uglify: {
			vendor: {
				files: {
					'js/min/codemirror.js': [
						codemirror + 'lib/codemirror.js',
						codemirror + 'mode/clike/clike.js',
						codemirror + 'mode/php/php.js',
						codemirror + 'addon/search/searchcursor.js',
						codemirror + 'addon/search/search.js',
						codemirror + 'addon/edit/matchbrackets.js'
					],
					'js/min/tag-it.js': ['js/vendor/tag-it.js']
				}
			}
		},

		imagemin: {
			screenshots: {
				files: [{
					expand: true,
					cwd: 'screenshots/',
					src: '**/*',
					dest: 'screenshots/'
				}]
			}
		},

		clean: {
			js: ['js/min'],
			css: ['css/min'],
			dist: ['dist']
		},

		copy: {
			dist: {
				files: [{
					expand: true,
					cwd: './',
					src: [
						'code-snippets.php',
						'uninstall.php',
						'readme.txt',
						'license.txt',
						'php/**/*',
						'languages/**/*',
						'css/min/**/*',
						'css/font/**/*',
						'js/min/**/*'
					],
					dest: 'dist',
					filter: 'isFile'
				}]
			}
		},

		phpunit: {
			classes: {
				dir: 'tests/'
			},
			options: {
				bin: 'vendor/bin/phpunit',
				bootstrap: 'tests/bootstrap.php',
				colors: true
			}
		},

		phpcs: {
			application: {
				src: ['*.php', 'php/**/*.php']
			},
			options: {
				bin: 'vendor/bin/phpcs',
				standard: 'codesniffer.ruleset.xml',
				showSniffCodes: true
			}
		},

		wp_deploy: {
			release: {
				options: {
					plugin_slug: 'code-snippets',
					svn_user: 'bungeshea',
					build_dir: 'dist'
				}
			}
		},

		potomo: {
			dist: {
				files: [{
					expand: true,
					cwd: 'languages',
					src: ['*.po'],
					dest: 'languages',
					ext: '.mo',
					nonull: true
				}]
			}
		},

		pot: {
			options:{
				text_domain: 'code-snippets',
				dest: 'languages/',
				keywords: [
					'__:1', '_e:1', '_x:1,2c',
					'esc_html__:1', 'esc_html_e:1', 'esc_html_x:1,2c',
					'esc_attr__:1', 'esc_attr_e:1', 'esc_attr_x:1,2c',
					'_ex:1,2c', '_n:1,2', '_nx:1,2,4c',
					'_n_noop:1,2', '_nx_noop:1,2,3c'
				]
			},
			files: {
				src: [ 'code-snippets.php', 'php/**/*.php' ],
				expand: true
			}
		}
	});

	grunt.registerTask( 'css', ['clean:css', 'postcss', 'csso'] );
	grunt.registerTask( 'js', ['clean:js', 'uglify'] );
	grunt.registerTask( 'l18n', ['pot', 'potomo'] );
	grunt.registerTask( 'test', ['jshint', 'phpcs', 'phpunit'] );

	grunt.registerTask( 'package', ['clean:dist', 'copy:dist'] );
	grunt.registerTask( 'default', ['css', 'js', 'l18n'] );
};
