module.exports = function(grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		watch: {

			css: {
				files: ['assets/scss/**/*.scss'],
				tasks: ['css']
			},

			js: {
				files: ['assets/js/**/*.js'],
				tasks: ['js']
			}

		},

		jshint: {
			gruntfile: ['Gruntfile.js'],
			assets: ['assets/js/**/*.js']
		},

		sass: {
			dist:  {
				cwd: 'assets/scss',
				src: '*.scss',
				dest: 'assets/css',
				expand: true,
				ext: '.css'
			}
		},

		autoprefixer: {
			dist: {
				expand: true,
				flatten: true,
				src: 'assets/css/*.css',
				dest: 'assets/css'
			}
		},

		csso: {
			dist: {
				expand: true,
				flatten: true,
				src: 'assets/css/*.css',
				dest: 'assets/css'
			}
		},

		imagemin: {
			dist: {
				options: {
					optimizationLevel: 7,
					progressive: true
				},
				files: [{
					expand: true,
					cwd: 'assets/images/',
					src: '**/*',
					dest: 'assets/images/'
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
						'readme.txt',
						'license.txt',
						'includes/**/*',
						'admin/**/*',
						'assets/**/*',
						'languages/**/*',

						// CodeMirror
						'vendor/codemirror/lib/codemirror.css',
						'vendor/codemirror/lib/codemirror.js',
						'vendor/codemirror/mode/clike/clike.js',
						'vendor/codemirror/mode/php/php.js',
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

		shell: {

			/**
			 * Requires PhpDocumentor to be installed and in PATH
			 */
			phpdoc: {
				command: 'phpdoc -t docs/api -f code-snippets.php -d admin,includes --title "Code Snippets"'
			}
		}

	});

	grunt.registerTask( 'css', ['sass', 'autoprefixer', 'csso'] );
	grunt.registerTask( 'js', ['jshint'] );

	grunt.registerTask( 'deploy', ['clean:deploy', 'copy:plugin', 'copy:assets'] );
	grunt.registerTask( 'phpdoc', 'shell:phpdoc' );

	grunt.registerTask( 'default', ['css', 'js'] );
};
