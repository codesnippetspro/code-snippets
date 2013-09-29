module.exports = function(grunt) {
	'use strict';

	require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

	grunt.initConfig({

		watch: {

			styles: {
				files: ['assets/scss/**/*.{scss,sass}'],
				tasks: ['styles'],
				options: {
					debounceDelay: 500
				}
			},

			scripts: {
				files: ['assets/js/**/*.js'],
				tasks: ['scripts'],
				options: {
					debounceDelay: 500
				}
			}

		},

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

		autoprefixer: {
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
			deploy: {
				files: [{
					expand: true,
					cwd: './',
					src: [
						'*',
						'includes/**/*',
						'admin/**/*',
						'assets/**/*',
						'vendor/**/*',
						'languages/**/*',

						'!.travis.yml',
						'!.gitignore',
						'!README.md',
						'!CHANGELOG.md',
						'!Gruntfile.js',
						'!package.json',
						'!phpunit.xml',
						'!**/Thumbs.db',
						'!composer.json',
						'!*.sublime-project',
						'!*.sublime-workspace'
					],
					dest: 'deploy/',
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

	grunt.registerTask( 'styles', ['compass', 'autoprefixer'] );
	grunt.registerTask( 'scripts', 'jshint' );

	grunt.registerTask( 'deploy', ['clean:deploy', 'copy:deploy'] );
	grunt.registerTask( 'phpdoc', 'shell:phpdoc' );

	grunt.registerTask( 'default', ['styles', 'scripts', 'uglify'] );
};
