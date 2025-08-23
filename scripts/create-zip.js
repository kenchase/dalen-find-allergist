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
    zlib: { level: 9 } // Maximum compression
  });
  
  // Listen for all archive data to be written
  output.on('close', function() {
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
  archive.on('error', function(err) {
    console.error('❌ Error creating zip:', err);
    throw err;
  });
  
  // Pipe archive data to the file
  archive.pipe(output);
  
  // Add files to the archive
  console.log('📁 Adding files to production zip...');
  
  // Main plugin file
  archive.file('dalen-find-allergist.php', { name: `${pluginName}/dalen-find-allergist.php` });
  
  // Include the entire includes directory
  archive.directory('includes/', `${pluginName}/includes/`);
  
  // Include the entire admin directory
  archive.directory('admin/', `${pluginName}/admin/`);
  
  // Add minified assets from dist directory
  if (fs.existsSync('dist/assets')) {
    archive.directory('dist/assets/', `${pluginName}/assets/`);
  } else {
    console.log('⚠️  No dist/assets found. Make sure to run "npm run build" first.');
    // Fallback to original assets if dist doesn't exist
    archive.directory('assets/', `${pluginName}/assets/`);
  }
  
  // Add readme and license files
  if (fs.existsSync('readme.txt')) {
    archive.file('readme.txt', { name: `${pluginName}/readme.txt` });
  }
  if (fs.existsSync('README.md')) {
    archive.file('README.md', { name: `${pluginName}/README.md` });
  }
  if (fs.existsSync('LICENSE')) {
    archive.file('LICENSE', { name: `${pluginName}/LICENSE` });
  }
  
  // Languages directory (if it exists)
  if (fs.existsSync('languages/')) {
    archive.directory('languages/', `${pluginName}/languages/`);
  }
  
  // Finalize the archive
  await archive.finalize();
}

// Files and directories to exclude from production
const excludeFromProduction = [
  'node_modules/',
  'src/',
  'scripts/',
  'dist/',
  '.git/',
  '.gitignore',
  '.gitattributes',
  'package.json',
  'package-lock.json',
  'vite.config.js',
  'postcss.config.js',
  '.env',
  '.env.example',
  'composer.json',
  'composer.lock',
  'phpunit.xml',
  'tests/',
  '*.zip',
  '.DS_Store',
  'Thumbs.db',
];

// Run the script
if (require.main === module) {
  createProductionZip().catch(console.error);
}

module.exports = { createProductionZip };
