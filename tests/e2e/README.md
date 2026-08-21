# Playwright Tests

End-to-end tests for Code Snippets using `@wordpress/env`.

## Prerequisites

- Docker (for wp-env)
- Node.js 18+

## Quick Start

```bash
# Install dependencies
npm install && composer -d src install

# Build plugin
npm run build

# Start WordPress environment
npx wp-env start

# Run tests
npm run test:playwright
```

## Commands

```bash
npx wp-env start              # Start WordPress
npx wp-env stop               # Stop WordPress
npx wp-env clean all          # Clean environment
npm run test:playwright       # Run tests
npm run test:playwright:ui    # Run with UI
npm run test:playwright:debug # Debug mode
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
npx wp-env stop && npx wp-env start
```

**Tests failing:**
```bash
npm run test:playwright:debug
curl http://localhost:8888/wp-admin/  # Check WordPress
```
