# Playwright Tests for Code Snippets

Simple Playwright tests for the Code Snippets WordPress plugin using `@wordpress/env`.

## 📋 Prerequisites

- **Docker**: wp-env requires Docker to be running
- **Node.js**: Version 18 or higher
- **npm**: For package management

## 🚀 Quick Start

### 1. Install Dependencies
```bash
npm install
```

### 2. Build Plugin and Install PHP Dependencies
```bash
npm run build
cd src && composer install
```

### 3. Start WordPress Environment
```bash
# Make sure Docker is running first
npm run wp-env:start
```

### 4. Run Tests
```bash
# Run all tests
npm run test:playwright

# Run with UI
npm run test:playwright:ui

# Run in debug mode
npm run test:playwright:debug

# Run specific test categories
npm run test:playwright -- --grep="@admin"
```

### 5. Quick Test Script
```bash
# Run everything with one command
./test-playwright.sh
```

## 📁 Test Structure

- `admin-page.spec.ts` - Tests for the main admin page (@admin)

## 🔧 Configuration

- **WordPress Environment**: `.wp-env.json`
- **Playwright Config**: `tests/playwright/playwright.config.ts`

## 🎯 What Gets Tested

- ✅ Admin page loads correctly
- ✅ Functions tab displays and is visible
- ✅ WordPress authentication works
- ✅ Plugin is properly activated

## 🛠️ Troubleshooting

### Docker Issues
```bash
# Check if Docker is running
docker --version
docker ps

# Start Docker Desktop (if installed)
# Or start Docker service on Linux
sudo systemctl start docker
```

### WordPress Not Starting
```bash
# Check if port 8888 is available
lsof -i :8888

# Stop any existing wp-env
npm run wp-env:stop
```

### Tests Failing
```bash
# Run with debug output
npm run test:playwright:debug

# Check WordPress is running
curl http://localhost:8888/wp-admin/
```

### Clean Restart
```bash
# Stop WordPress
npm run wp-env:stop

# Clean environment
npm run wp-env:clean

# Start fresh
npm run wp-env:start
```

### Alternative: Test Against Existing WordPress Site
If you don't want to use Docker, you can test against an existing WordPress site:

```bash
# Set environment variables
export WP_URL=http://your-wordpress-site.local
export WP_USERNAME=admin
export WP_PASSWORD=password

# Run tests against existing site
npm run test:playwright
```

## 📋 Available Commands

```bash
npm run wp-env:start     # Start WordPress environment
npm run wp-env:stop      # Stop WordPress environment
npm run wp-env:clean     # Clean WordPress environment
npm run test:playwright  # Run all tests
npm run test:playwright:ui    # Run with UI
npm run test:playwright:debug # Run in debug mode
```