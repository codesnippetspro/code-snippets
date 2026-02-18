#!/usr/bin/env node
/* eslint-disable no-console */
const { execFileSync } = require('child_process')

const run = (cmd, args) => {
  execFileSync(cmd, args, { stdio: 'inherit' })
}

const runWpEnvCli = (args) => run('npx', ['wp-env', 'run', 'cli', ...args])

const main = () => {
  // Ensure a clean slate for file-based execution tests:
  // - remove flat-file execution directory (stale indexes can break the WP site)
  // - ensure plugin is active
  // - force enable_flat_files=false so the Playwright setup test can flip it to true
  // - delete all DB snippets with an E2E prefix (keeps list clean across runs)

  runWpEnvCli(['sh', '-lc', 'rm -rf wp-content/code-snippets'])
  runWpEnvCli(['wp', 'plugin', 'activate', 'code-snippets'])

  runWpEnvCli([
    'wp',
    'eval',
    `
      $settings = get_option('code_snippets_settings', []);
      $settings['general']['enable_flat_files'] = false;
      update_option('code_snippets_settings', $settings);
    `
  ])

  runWpEnvCli([
    'wp',
    'eval',
    `
      global $wpdb;
      $wpdb->query(
        $wpdb->prepare(
          "DELETE FROM {$wpdb->prefix}snippets WHERE name LIKE %s",
          "E2E%"
        )
      );
    `
  ])
}

try {
  main()
} catch (error) {
  console.error(error)
  process.exitCode = 1
}

