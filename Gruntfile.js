module.exports = function(grunt) {
	'use strict';

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		jshint: {
			gruntfile: ['Gruntfile.js'],
			assets: ['assets/js/**/*.js']
		},

		uglify: {

			/* Compress the CodeMirror scripts into a single file */
			codemirror: {
				files: {
					'vendor/codemirror.min.js': [
						'vendor/codemirror/lib/codemirror.js',
						'vendor/codemirror/mode/clike.js',
						'vendor/codemirror/mode/php.js',
						'vendor/codemirror/addon/dialog.js',
						'vendor/codemirror/addon/searchcursor.js',
						'vendor/codemirror/addon/search.js',
						'vendor/codemirror/addon/matchbrackets.js'
					]
				}
			}
		},

		compass: {
			dist: {
				options: {
					config: 'assets/config.rb'
				}
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
		}

	});

	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-imagemin');

	grunt.registerTask( 'default', ['jshint', 'uglify', 'compass'] );
};
