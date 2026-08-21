#!/bin/bash

echo "🧪 Testing Playwright Setup for Code Snippets"
echo "=============================================="

if [ ! -f "package.json" ]; then
    echo "❌ Error: Please run this script from the project root directory"
    exit 1
fi

if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
fi

echo "🔨 Building plugin and installing PHP dependencies..."
npm run build
cd src && composer install && cd ..

echo "🚀 Starting WordPress environment..."
npx wp-env start

echo "⏳ Waiting for WordPress to start..."
sleep 15

if curl -s http://localhost:8888/wp-admin/ > /dev/null; then
    echo "✅ WordPress is running on http://localhost:8888"
else
    echo "❌ WordPress failed to start"
    npx wp-env stop
    exit 1
fi

echo "🔧 Setting up test data..."
npm run test:setup:playwright

echo "🧪 Running Playwright tests..."
npm run test:playwright

echo "🧹 Cleaning up..."
npx wp-env stop

echo "✅ Test complete!"
