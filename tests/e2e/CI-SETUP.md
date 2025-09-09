# Playwright Tests with @wordpress/env

Simple Playwright testing setup for Code Snippets using `@wordpress/env`.

## 🎯 Approach

We use `@wordpress/env` to create a complete WordPress environment for testing, both locally and in CI.

## 🚀 Local Development

### Prerequisites
- **Docker**: wp-env requires Docker to be running
- **Node.js**: Version 18 or higher

### Setup
```bash
# Install dependencies
npm install

# Build plugin and install PHP dependencies
npm run build
cd src && composer install

# Start WordPress environment
npm run wp-env:start

# Run tests
npm run test:playwright
```

### Available Commands
```bash
npm run wp-env:start             # Start WordPress environment
npm run wp-env:stop              # Stop WordPress environment
npm run wp-env:clean             # Clean WordPress environment
npm run test:playwright          # Run all tests
npm run test:playwright:ui       # Run with UI
npm run test:playwright:debug    # Run in debug mode
```

## 🔄 CI/CD (GitHub Actions)

### Automatic Triggers
- **Pull Requests**: Tests run when PR has `run-tests` label
- **Push to main/develop**: Tests run automatically

### Manual Triggers
- **Workflow Dispatch**: Manual trigger from GitHub Actions tab

### How It Works
1. **Build Plugin**: Uses existing build workflow
2. **WordPress Environment**: Creates fresh WordPress with MySQL
3. **Plugin Installation**: Installs and activates Code Snippets
4. **Test Execution**: Runs Playwright tests
5. **Results**: Uploads test reports and artifacts

## 📁 File Structure

```
tests/
├── playwright/
│   ├── playwright.config.ts            # Playwright configuration
│   └── playwright-report/              # Test results
├── e2e/
│   ├── admin-page.spec.ts              # @admin tests
│   └── README.md                       # Local testing guide
├── .wp-env.json                        # WordPress environment config
└── test-playwright.sh                  # Convenience script
```

## 🏷️ Test Categories

- **@admin**: Admin interface tests

### Running Specific Categories
```bash
npm run test:playwright -- --grep="@admin"
```

## 🛠️ Configuration

### WordPress Environment
```json
{
  "core": null,
  "phpVersion": "8.1",
  "plugins": ["./src"],
  "themes": ["WordPress/twentytwentyfour"],
  "port": 8888,
  "testsPort": 8889,
  "config": {
    "WP_DEBUG": true,
    "WP_DEBUG_LOG": true,
    "WP_DEBUG_DISPLAY": false,
    "SCRIPT_DEBUG": true,
    "WP_ENVIRONMENT_TYPE": "local"
  }
}
```

### Playwright Configuration
- Base URL: `http://localhost:8888`
- HTML, JSON, and JUnit reporters
- Screenshots and videos on failure

## 📊 Test Reports

### Local
- HTML report opens automatically after tests
- Screenshots saved to `test-results/`

### CI
- **HTML Report**: Interactive test results
- **JSON Report**: Machine-readable results
- **JUnit Report**: CI integration
- **Screenshots**: Failure screenshots
- **Videos**: Test execution videos

### Accessing CI Reports
1. Go to GitHub Actions
2. Select a workflow run
3. Download the `playwright-test-results` artifact
4. Open `index.html` in your browser

## 🔧 Troubleshooting

### Local Issues

**WordPress Not Starting**
```bash
# Check if port 8888 is available
lsof -i :8888

# Stop any existing wp-env
npm run wp-env:stop

# Clean restart
npm run wp-env:clean
npm run wp-env:start
```

**Tests Failing**
```bash
# Run with debug output
npm run test:playwright:debug

# Check WordPress is running
curl http://localhost:8888/wp-admin/
```

### CI Issues

**WordPress Not Ready**
- Check MySQL service health
- Verify wp-lite-env configuration
- Check global setup logs

**Plugin Not Activated**
- Verify plugin path in wp-lite-env config
- Check setup script execution
- Review WordPress CLI output

## 📝 Best Practices

1. **Test Organization**: Use descriptive tags and test names
2. **Selectors**: Use data attributes when possible
3. **Wait Strategies**: Always wait for page load states
4. **Error Handling**: Include proper error messages
5. **Test Data**: Use setup scripts for consistent test data
6. **Parallel Execution**: Design tests to run independently

## 🎯 What Gets Tested

- ✅ Admin page loads correctly
- ✅ Functions tab displays and is visible
- ✅ WordPress authentication works
- ✅ Plugin is properly activated

## 🚀 Next Steps

1. **Add More Tests**: Expand test coverage for all plugin features
2. **Performance Tests**: Add performance monitoring
3. **Visual Regression**: Add screenshot comparison tests
4. **Cross-Browser**: Enable WebKit when issues are resolved