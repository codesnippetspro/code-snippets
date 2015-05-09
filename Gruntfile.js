module.exports = function(grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		watch: {

			css: {
				files: ['css/**/*.scss'],
				tasks: ['css']
			}

		},

		jshint: {
			gruntfile: ['Gruntfile.js'],
		},

		sass: {
			dist: {
				cwd: 'css',
				src: '*.scss',
				dest: 'css/build',
				expand: true,
				ext: '.css'
			}
		},

		autoprefixer: {
			dist: {
				expand: true,
				flatten: true,
				src: 'css/build/*.css',
				dest: 'css/build'
			}
		},

		csso: {
			dist: {
				expand: true,
				flatten: true,
				src: 'css/build/*.css',
				dest: 'css/min'
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
			deploy: ['deploy']
		},

		copy: {
			plugin: {
				files: [{
					expand: true,
					cwd: './',
					src: [
						'code-snippets.php',
						'uninstall.php',
						'readme.txt',
						'license.txt',
						'includes/**/*',
						'admin/**/*',
						'languages/**/*',
						'css/**/*',
						'js/**/*',

						// CodeMirror
						'vendor/codemirror/lib/codemirror.css',
						'vendor/codemirror/lib/codemirror.js',
						'vendor/codemirror/mode/clike/clike.js',
						'vendor/codemirror/mode/php/php.js',
						'vendor/codemirror/theme/*.css',
						'vendor/codemirror/addon/search/searchcursor.js',
						'vendor/codemirror/addon/search/search.js',
						'vendor/codemirror/addon/edit/matchbrackets.js'
					],
					dest: 'deploy/plugin',
					filter: 'isFile'
				}]
			},
			assets: {
				files: [{
					expand: true,
					cwd: './screenshots',
					src: [
						'screenshot-*.{png,jpe?g}',
						'banner-772x250.{png,jpe?g,pdn,psd}'
					],
					dest: 'deploy/assets',
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

		wp_deploy: {
			release: {
				options: {
					plugin_slug: 'code-snippets',
					svn_user: 'bungeshea',
					build_dir: 'deploy/plugin'
				},
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
				keywords: ['__','_e','esc_html__','esc_html_e','esc_attr__', 'esc_attr_e', 'esc_attr_x', 'esc_html_x', 'ngettext', '_n', '_ex', '_nx'],
			},
			files: {
				src: [ 'code-snippets.php', 'includes/**/*.php' ],
				expand: true,
			}
		}

	});

	grunt.registerTask( 'css', ['sass', 'autoprefixer', 'csso'] );
	grunt.registerTask( 'l18n', ['pot', 'potomo'] );
	grunt.registerTask( 'test', ['jshint', 'phpunit'] );

	grunt.registerTask( 'deploy', ['imagemin', 'clean:deploy', 'copy:plugin', 'copy:assets'] );
	grunt.registerTask( 'release', ['default', 'deploy', 'wp_deploy'] );

	grunt.registerTask( 'default', ['css', 'l18n'] );
};
