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
			gruntfile: ['Gruntfile.js']
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
			},
			cmthemes: {
				expand: true,
				flatten: true,
				src: 'vendor/codemirror/theme/*.css',
				dest: 'css/min/cmthemes'
			},
			vendor: {
				files: {
					'css/min/codemirror.css': [
						'vendor/codemirror/lib/codemirror.css'
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
						'vendor/codemirror/lib/codemirror.js',
						'vendor/codemirror/mode/clike/clike.js',
						'vendor/codemirror/mode/php/php.js',
						'vendor/codemirror/addon/search/searchcursor.js',
						'vendor/codemirror/addon/search/search.js',
						'vendor/codemirror/addon/edit/matchbrackets.js'
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
			deploy: ['deploy']
		},

		copy: {
			deploy: {
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
					dest: 'deploy',
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
					build_dir: 'deploy'
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

	grunt.registerTask( 'css', ['clean:css', 'sass', 'autoprefixer', 'csso'] );
	grunt.registerTask( 'js', ['clean:js', 'uglify'] );
	grunt.registerTask( 'l18n', ['pot', 'potomo'] );
	grunt.registerTask( 'test', ['jshint', 'phpcs', 'phpunit'] );

	grunt.registerTask( 'deploy', ['clean:deploy', 'copy:deploy'] );
	grunt.registerTask( 'default', ['css', 'js', 'l18n'] );
};
