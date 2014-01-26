module.exports = function(grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		watch: {

			css: {
				files: ['css/**/*.scss'],
				tasks: ['css']
			},

			js: {
				files: ['js/**/*.js'],
				tasks: ['js']
			}

		},

		jshint: {
			gruntfile: ['Gruntfile.js'],
			dist: ['js/**/*.js']
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
						'readme.txt',
						'license.txt',
						'includes/**/*',
						'admin/**/*',
						'css/**/*',
						'js/**/*',
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

		wp_deploy: {
			release: {
				options: {
					plugin_slug: 'code-snippets',
					svn_user: 'bungeshea',
					build_dir: 'deploy/plugin'
				},
			}
		}

	});

	grunt.registerTask( 'css', ['sass', 'autoprefixer', 'csso'] );
	grunt.registerTask( 'js', ['jshint'] );

	grunt.registerTask( 'deploy', ['imagemin', 'clean:deploy', 'copy:plugin', 'copy:assets'] );
	grunt.registerTask( 'release', ['default', 'deploy', 'wp_deploy'] );

	grunt.registerTask( 'default', ['css', 'js'] );
};
