const archiver = require('archiver');
const fs = require('fs');
const path = require('path');

/**
 * Creates a production-ready zip file of the plugin
 */
async function createProductionZip() {
  const pluginName = 'dalen-find-allergist';
  const distDir = path.join(__dirname, '..', 'dist');
  const zipPath = path.join(distDir, `${pluginName}.zip`);
  const tempBuildDir = path.join(__dirname, '..', 'temp-build');

  // Ensure dist directory exists
  if (!fs.existsSync(distDir)) {
    fs.mkdirSync(distDir, { recursive: true });
  }

  // Remove existing zip if it exists
  if (fs.existsSync(zipPath)) {
    fs.unlinkSync(zipPath);
  }

  // Create a file to stream archive data to
  const output = fs.createWriteStream(zipPath);
  const archive = archiver('zip', {
    zlib: { level: 9 }, // Maximum compression
  });

  // Listen for all archive data to be written
  output.on('close', function () {
    // Clean up build artifacts after creating zip
    const buildDir = path.join(__dirname, '..', 'dist', 'assets');
    const tempDir = path.join(__dirname, '..', 'dist', 'temp');

    if (fs.existsSync(buildDir)) {
      fs.rmSync(buildDir, { recursive: true, force: true });
      console.log('🧹 Cleaned up build assets');
    }

    if (fs.existsSync(tempDir)) {
      fs.rmSync(tempDir, { recursive: true, force: true });
    }

    console.log(`✅ Production zip created: dist/${pluginName}.zip (${archive.pointer()} total bytes)`);
    console.log('📦 Ready for distribution!');
  });

  // Handle errors
  archive.on('error', function (err) {
    console.error('❌ Error creating zip:', err);
    throw err;
  });

  // Pipe archive data to the file
  archive.pipe(output);

  // Add files to the archive
  console.log('📁 Adding files to production zip...');

  // Main plugin file - check if it exists
  const mainPluginFile = path.join(__dirname, '..', 'faa.php');
  if (fs.existsSync(mainPluginFile)) {
    archive.file(mainPluginFile, { name: `${pluginName}/faa.php` });
    console.log('✓ Added main plugin file');
  } else {
    console.error('❌ Main plugin file not found: faa.php');
    return;
  }

  // Include directories with existence checks
  const includesDir = path.join(__dirname, '..', 'includes');
  if (fs.existsSync(includesDir)) {
    archive.directory(includesDir, `${pluginName}/includes/`);
    console.log('✓ Added includes directory');
  } else {
    console.log('⚠️  No includes directory found');
  }

  const adminDir = path.join(__dirname, '..', 'admin');
  if (fs.existsSync(adminDir)) {
    archive.directory(adminDir, `${pluginName}/admin/`);
    console.log('✓ Added admin directory');
  } else {
    console.log('⚠️  No admin directory found');
  }

  // Handle assets with better error checking and file mapping
  const distAssetsDir = path.join(__dirname, '..', 'dist', 'assets');
  const originalAssetsDir = path.join(__dirname, '..', 'assets');

  if (fs.existsSync(distAssetsDir)) {
    // Manually add assets with proper file name mapping
    const cssDir = path.join(distAssetsDir, 'css');
    const jsDir = path.join(distAssetsDir, 'js');

    // Handle CSS files
    if (fs.existsSync(cssDir)) {
      // Handle main find-allergist CSS
      const minifiedCss = path.join(cssDir, 'find-allergist-styles.min.css');
      const regularCss = path.join(cssDir, 'find-allergist.css');

      if (fs.existsSync(minifiedCss)) {
        // Add minified CSS as the expected filename
        archive.file(minifiedCss, { name: `${pluginName}/assets/css/find-allergist.css` });
        console.log('✓ Added minified CSS as find-allergist.css');
      } else if (fs.existsSync(regularCss)) {
        archive.file(regularCss, { name: `${pluginName}/assets/css/find-allergist.css` });
        console.log('✓ Added CSS file');
      }

      // Handle admin CSS
      const adminMinifiedCss = path.join(cssDir, 'admin.min.css');
      const adminRegularCss = path.join(cssDir, 'admin.css');

      if (fs.existsSync(adminMinifiedCss)) {
        archive.file(adminMinifiedCss, { name: `${pluginName}/assets/css/admin.css` });
        console.log('✓ Added minified admin CSS as admin.css');
      } else if (fs.existsSync(adminRegularCss)) {
        archive.file(adminRegularCss, { name: `${pluginName}/assets/css/admin.css` });
        console.log('✓ Added admin CSS file');
      }
    }

    // Handle JS files
    if (fs.existsSync(jsDir)) {
      // Handle main find-allergist JS
      const findAllergistMinJs = path.join(jsDir, 'find-allergist-scripts.min.js');
      const findAllergistRegularJs = path.join(jsDir, 'find-allergist.js');

      if (fs.existsSync(findAllergistMinJs)) {
        archive.file(findAllergistMinJs, { name: `${pluginName}/assets/js/find-allergist.js` });
        console.log('✓ Added minified find-allergist JS as find-allergist.js');
      } else if (fs.existsSync(findAllergistRegularJs)) {
        archive.file(findAllergistRegularJs, { name: `${pluginName}/assets/js/find-allergist.js` });
        console.log('✓ Added find-allergist JS file');
      }

      // Handle admin JS
      const adminMinJs = path.join(jsDir, 'admin.min.js');
      const adminRegularJs = path.join(jsDir, 'admin.js');

      if (fs.existsSync(adminMinJs)) {
        archive.file(adminMinJs, { name: `${pluginName}/assets/js/admin.js` });
        console.log('✓ Added minified admin JS as admin.js');
      } else if (fs.existsSync(adminRegularJs)) {
        archive.file(adminRegularJs, { name: `${pluginName}/assets/js/admin.js` });
        console.log('✓ Added admin JS file');
      }
    }

    // Add any other asset directories/files (images, etc.)
    const items = fs.readdirSync(distAssetsDir);
    items.forEach((item) => {
      if (item !== 'css' && item !== 'js') {
        const itemPath = path.join(distAssetsDir, item);
        const stat = fs.statSync(itemPath);
        if (stat.isDirectory()) {
          archive.directory(itemPath, `${pluginName}/assets/${item}/`);
          console.log(`✓ Added ${item} directory`);
        } else {
          archive.file(itemPath, { name: `${pluginName}/assets/${item}` });
          console.log(`✓ Added ${item} file`);
        }
      }
    });

    console.log('✓ Added built assets from dist/assets');
  } else if (fs.existsSync(originalAssetsDir)) {
    console.log('⚠️  No dist/assets found. Using original assets directory.');
    archive.directory(originalAssetsDir, `${pluginName}/assets/`);
    console.log('✓ Added original assets');
  } else {
    console.log('⚠️  No assets directory found (neither dist/assets nor assets/)');
  }

  // Add optional files with existence checks
  const optionalFiles = [
    { src: 'readme.txt', dest: `${pluginName}/readme.txt` },
    { src: 'README.md', dest: `${pluginName}/README.md` },
    { src: 'LICENSE', dest: `${pluginName}/LICENSE` },
  ];

  optionalFiles.forEach((file) => {
    const filePath = path.join(__dirname, '..', file.src);
    if (fs.existsSync(filePath)) {
      archive.file(filePath, { name: file.dest });
      console.log(`✓ Added ${file.src}`);
    }
  });

  // Languages directory (if it exists)
  const languagesDir = path.join(__dirname, '..', 'languages');
  if (fs.existsSync(languagesDir)) {
    archive.directory(languagesDir, `${pluginName}/languages/`);
    console.log('✓ Added languages directory');
  }

  // Finalize the archive
  await archive.finalize();
}

// Files and directories to exclude from production
const excludeFromProduction = ['node_modules/', 'src/', 'scripts/', 'dist/', '.git/', '.gitignore', '.gitattributes', 'package.json', 'package-lock.json', 'vite.config.js', 'postcss.config.js', '.env', '.env.example', 'composer.json', 'composer.lock', 'phpunit.xml', 'tests/', '*.zip', '.DS_Store', 'Thumbs.db'];

// Run the script
if (require.main === module) {
  createProductionZip().catch(console.error);
}

module.exports = { createProductionZip };
