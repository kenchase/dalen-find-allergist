#!/bin/bash

# Build script for Dalen Find Allergist plugin
# This script builds the plugin and creates a production zip

echo "🚀 Building Dalen Find Allergist plugin..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

# Install dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
fi

# Build the assets
echo "🔨 Building assets..."
npm run build

# Create production zip
echo "📦 Creating production zip..."
npm run zip

echo "✅ Build complete!"
echo ""
echo "Production file created:"
echo "  📦 dist/dalen-find-allergist.zip - Ready for WordPress upload"
echo ""
echo "You can upload dist/dalen-find-allergist.zip to WordPress!"
