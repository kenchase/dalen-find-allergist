# Build Process

This plugin uses a modern build process with Vite to optimize JavaScript and CSS files for production.

## Quick Start

1. **Install dependencies** (first time only):
   ```bash
   npm install
   ```

2. **Build for production**:
   ```bash
   npm run build:zip
   ```
   
   Or use the convenience script:
   ```bash
   ./build.sh
   ```

This will create:
- `dist/dalen-find-allergist.zip` ready for WordPress installation

The build process creates minified assets temporarily during the build, then packages them into the zip file and cleans up the intermediate files, leaving only the production-ready zip.

## Available Commands

- `npm run dev` - Start development server (if needed)
- `npm run build` - Build minified assets only
- `npm run build:zip` - Build assets and create production zip
- `./build.sh` - One-command build with helpful output

## What Gets Optimized

- ✅ JavaScript files are minified and optimized
- ✅ CSS files are minified and optimized
- ✅ Console.log statements are removed from production builds
- ✅ Only production files are included in the zip
- ✅ Files are correctly named (e.g., `admin.min.css`, not `admin-css.min2.css`)

## Production Zip Contents

The production zip includes only the files needed for WordPress:
- Main plugin file (`dalen-find-allergist.php`)
- `/includes/` directory
- `/admin/` directory  
- `/assets/` directory (with minified CSS and JS files)
- `/languages/` directory (if present)
- `readme.txt` and other documentation

The minified assets inside the zip are:
- `assets/css/admin.min.css`
- `assets/css/find-allergist-results.min.css`
- `assets/js/admin.min.js`
- `assets/js/find-allergist-results.min.js`

## Development vs Production

- **Development**: Use the original files in `/assets/js/` and `/assets/css/`
- **Production**: The build process creates optimized files in `/dist/assets/`

The plugin should automatically detect and use the appropriate files based on whether `WP_DEBUG` is enabled.

## File Naming

The build process creates correctly named minified files:
- `assets/css/admin.css` → `dist/assets/css/admin.min.css`
- `assets/css/find-allergist-results.css` → `dist/assets/css/find-allergist-results.min.css`
- `assets/js/admin.js` → `dist/assets/js/admin.min.js`
- `assets/js/find-allergist-results.js` → `dist/assets/js/find-allergist-results.min.js`

These files will work perfectly with the Asset Loader utility.
