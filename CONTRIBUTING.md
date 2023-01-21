# Contributing to Code Snippets

## Setting things up

In order to build a copy of Code Snippets from the files in this repository, you will need to prepare a few tools
locally, and be comfortable running commands from a terminal or command line interface.

The following tools will need to be installed.

- [Composer](https://getcomposer.org/download/) v2 or later.
- [Node.js](https://nodejs.org/en/download/) v14.15 or later with
[npm](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm) v7 or later.

Once Node.js and npm are installed, run the following command from inside the plugin directory to install the required
node packages:

    npm install

Additionally, run the following command to install the required Composer packages and generate autoload files:

    composer install

You will also need a system-level copy of [gulp](https://gulpjs.com/docs/en/getting-started/quick-start) in order to
run the build scripts:

    npm install --global gulp-cli

## Building the plugin

Code Snippets is primarily written in PHP, which does not require any post-processing and should work out-of-the-box,
once the required Composer packages are installed. However, it also includes code written in abstracted languages]
such as ES6 TypeScript and PostCSS/Sass, which need to be processed and turned into browser-ready code before the
plugin can be loaded into WordPress.

In order to get things built from the source files, the following command can be used:

    gulp

This will run the default `build` gulp task, which:

1. Copies required vendor files from the `node_modules/` directory, such as CodeMirror and PrismJS theme files.
2. Transforms the SCSS source files into browser-ready minified CSS code, including right-to-left files where appropriate.
3. Transforms the TypeScript source files into browser-ready minified JavaScript code, after checking with a linter.

The generated files will be located together under the `dist/` directory, where they are loaded from by WordPress.

This task can also be run with `gulp build`. More information on the specific tasks available, such as only performing
a single step out of those listed above, can be found by inspecting [`gulpfile.babel.ts`](gulpfile.babel.ts) or running
the following command:

    gulp --tasks

## Building while developing

If you are actively editing the included TypeScript or SCSS sources and wish to see your changes reflected more quickly
than running the entire build script, there is an alternative command available that will monitor the source files
for changes and run only the necessary tasks when changes are detected. This can be begun by running the following
command:

    gulp watch

## Preparing for release

The plugin repository includes a number of files that are unnecessary when distributing the plugin files for
installation on WordPress sites, such as configuration files and code only used when developing the plugin.

In order to simplify things and reduce the file size of the distributed plugin package, a command is included for
generating these distribution files separately from the plugin source. Running the following command will commence this
process:

    gulp bundle

This command will regenerate all processed files and copy those to be distributed to the `build/` directory, where they
can be copied directly into a Subversion repository or similar for distribution.

Additionally, a `code-snippets.x.x.x.zip` file will also be generated, where `x.x.x` is the plugin version number,
containing all files in the `build/` directory nested in a `code-snippets/` folder, as is expected by WordPress. This
zip file is suitable for direct uploading through the WordPress plugin installer.

## Switching between Free and Pro

While developing both Code Snippets and Code Snippets Pro, it is convenient to have both versions together in the same
repository to allow easy switching and merging between the `develop` and `pro` branches.

Due to the need for the free version of Code Snippets to be publicly available while the Pro version is only accessible
to the Code Snippets Team, these branches are divided into separate repositories, with the `develop` and `master`
branches stored at [codesnippetspro/code-snippets](https://github.com/codesnippetspro/code-snippets), and the `pro`
branch at [codesnippetspro/pro](https://github.com/codesnippetspro/pro).

Despite this fragmentation, it is recommended to not store the code in separate repositories, but to instead add these
as separate remotes in the same repository. To achieve this, first clone a version of Code Snippets to your local
machine:

    git clone git@github.com:codesnippetspro/code-snippets.git
    cd code-snippets

This will download a copy of the Code Snippets repository, and set the `origin` to be `codesnippetspro/code-snippets`.

Next, add the pro repository as a separate remote. The name here is not important, just as long as it is different
enough to reduce confusion:

    git remote add pro git@github.com:codesnippetspro/pro.git
    git fetch

Once this is achieved, you should be able to switch between versions with `git checkout develop` and `git checkout pro`.

When creating feature branches, ensure that they are based off the correct branch `develop` or `pro`, and that they
are pushed to the correct remote. It is especially important to avoid pushing any branches based on `pro` to the public
repository.
