# Playwright Tests

End-to-end tests for Code Snippets using `@wordpress/env`.

## Prerequisites

- Docker (for wp-env)
- Node.js 18+

## Quick Start

```bash
# Install dependencies
npm install

# Build plugin and install PHP dependencies
npm run build && cd src && composer install && cd ..

# Start WordPress environment
npm run wp-env:start

# Run tests
npm run test:playwright
```

## Commands

```bash
npm run wp-env:start              # Start WordPress
npm run wp-env:stop               # Stop WordPress
npm run wp-env:clean              # Clean environment
npm run test:playwright           # Run tests
npm run test:playwright:ui        # Run with UI
npm run test:playwright:debug     # Debug mode
```

## CI/CD

Tests run automatically on:
- Pull requests with `run-tests` label
- Push to `core` branch
- Manual workflow dispatch

## Troubleshooting

**Docker not running:**
```bash
docker --version && docker ps
```

**WordPress won't start:**
```bash
lsof -i :8888  # Check port availability
npm run wp-env:stop && npm run wp-env:start
```

**Tests failing:**
```bash
npm run test:playwright:debug
curl http://localhost:8888/wp-admin/  # Check WordPress
```
