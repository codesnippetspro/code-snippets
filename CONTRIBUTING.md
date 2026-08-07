# Contributing to Code Snippets

## Project structure

The base plugin folder is not the root of repository – instead, files to be loaded into WordPress as a plugin are
located in `src/`. You should copy or symlink this folder into your WordPress `wp-content/plugins` folder as
`code-snippets`.

Keep in mind that you will need to follow the steps below to populate files under `src/dist/` and`src/vendor/`, which
are required for the plugin to function correctly.

## Required software

In order to build a copy of Code Snippets from the files in this repository, you will need to prepare a few tools
locally, and be comfortable running commands from a terminal or command line interface.

The following tools will need to be installed.

- [Composer](https://getcomposer.org/download/) v2 or later.
- [Node.js](https://nodejs.org/en/download/) v20.16.0 or later with
  [npm](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm) v10 or later.

Once Node.js and npm are installed, run the following command from inside the plugin directory to install the required
node packages:

```shell
npm install
```

Additionally, run the following command to install the required Composer packages and generate autoload files:

```shell
npm run bundle
```

If you want to interface with Composer directly, you will need to run commands under the `src/` directory, as that is
where `composer.json`, `composer.lock`, and the `vendor/` directory are located.

## Building the plugin

Code Snippets is primarily written in PHP, which does not require any post-processing and should work out-of-the-box,
once the required Composer packages are installed. However, it also includes code written in abstracted languages]
such as ES6 TypeScript and PostCSS/Sass, which need to be processed and turned into browser-ready code before the
plugin can be loaded into WordPress.

In order to get things built from the source files, the following command can be used:

```shell
npm run build
```

This will run the basic Webpack configuration, which will:

1. Copies required vendor files from the `node_modules/` directory, such as CodeMirror and PrismJS theme files.
2. Transforms the SCSS source files into browser-ready minified CSS code, including right-to-left files where
   appropriate.
3. Transforms the TypeScript source files into browser-ready minified JavaScript code, after checking with a linter.

The generated files will be located together under the `dist/` directory, where they are loaded from by WordPress.

## Building while developing

If you are actively editing the included TypeScript or SCSS sources and wish to see your changes reflected more quickly
than running the entire build script, there is an alternative command available that will monitor the source files
for changes and run only the necessary tasks when changes are detected. This can be begun by running the following
command:

```shell
npm run watch
```

## Pre-commit hooks (automatic linting & autofix) 🔧

We use a native Git hook and lint-staged to run linters only on the files being committed. The hook will:

- Run the appropriate autofix for the changed files (PHP, JS/TS, CSS/SCSS).
- Automatically stage any files that were fixed so the fixes are included in the same commit.
- Block the commit only if non-fixable linter errors remain.

Files → actions (configured in this repository):

- `*.php` → `npm run lint:php:fix` (phpcbf)
- `*.{js,ts,jsx,tsx}` → `npm run lint:js:fix` (ESLint --fix)
- `*.{css,scss}` → `npm run lint:styles:fix` (Stylelint --fix)

Setup

1. Install node deps. The `prepare` script configures Git to use this repository's hooks:

```shell
npm install
```

2. If you already have the repo checked out, activate the Git hooks (run once):

```shell
npm run prepare
```

Usage notes

- To bypass hooks in an emergency: `git commit --no-verify` (not recommended).
- To run the same checks locally on staged files: `npx lint-staged`.
- If fixes were applied by the hook they will be included automatically in the commit; the commit is only blocked when
  a non-autofixable problem remains.

If you need to change which linters run for a filetype, see `package.json` -> `lint-staged`.


## Managing Composer dependencies

Code Snippets uses the [Imposter plugin](https://github.com/TypistTech/imposter) to namespace-prefix all vendor
dependencies under `Code_Snippets\Vendor\`. This prevents conflicts with other WordPress plugins that might use the
same libraries (e.g., Guzzle, Minify, Monolog).

### Adding a new dependency

When adding a new Composer dependency that might exist in other plugins:

1. Add the package to `src/composer.json` as usual:
   ```shell
   cd src
   composer require vendor/package
   ```

2. Add corresponding PSR-4 autoload entries for the prefixed namespace in `src/composer.json`:
   ```json
   "autoload": {
       "psr-4": {
           "Code_Snippets\\Vendor\\OriginalVendor\\PackageName\\": "vendor/vendor-name/package-name/src/"
       }
   }
   ```

3. Run `composer dump-autoload -o` to regenerate autoload files.

4. The Imposter plugin will automatically rewrite the namespaces during `post-install-cmd` and `post-update-cmd` hooks.

5. Our autoloader in `src/php/load.php` automatically removes original (non-prefixed) namespace mappings to prevent
   collisions, so no code changes are needed.

### How it works

- Imposter rewrites all vendor code from `Vendor\Package\Class` to `Code_Snippets\Vendor\Vendor\Package\Class`
- The `load.php` file dynamically detects and removes original namespace PSR-4 mappings at runtime
- Other plugins can load their own versions of the same libraries without conflicts
- Your code should always use the prefixed namespace: `use Code_Snippets\Vendor\Vendor\Package\Class;`

## Preparing for release

The plugin repository includes a number of files that are unnecessary when distributing the plugin files for
installation on WordPress sites, such as configuration files and code only used when developing the plugin.

In order to simplify things and reduce the file size of the distributed plugin package, a command is included for
generating these distribution files separately from the plugin source. Running the following command will commence this
process:

```shell
npm run bundle
```

This command will regenerate all processed files and copy those to be distributed to the `bundle/` directory, where they
can be copied directly into a Subversion repository or similar for distribution.

Additionally, a `code-snippets.x.x.x.zip` file will also be generated, where `x.x.x` is the plugin version number,
containing all files in the `bundle/` directory nested in a `code-snippets/` folder, as is expected by WordPress. This
zip file is suitable for direct uploading through the WordPress plugin installer.
