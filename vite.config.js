import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  // Configure build
  build: {
    // Output directory
    outDir: 'dist',
    
    // Don't empty the output directory (we'll handle this in our zip script)
    emptyOutDir: false,
    
    // Generate source maps for debugging (set to false for production)
    sourcemap: false,
    
    // Minify using terser
    minify: 'terser',
    
    // Configure terser options
    terserOptions: {
      compress: {
        drop_console: true, // Remove console.log statements
        drop_debugger: true, // Remove debugger statements
      },
    },
    
    // Configure rollup options
    rollupOptions: {
      input: {
        // JavaScript files
        'find-allergist-results': resolve(__dirname, 'assets/js/find-allergist-results.js'),
        'admin': resolve(__dirname, 'assets/js/admin.js'),
        // CSS files through JS imports
        'find-allergist-results-styles': resolve(__dirname, 'src/css-entries/find-allergist-results.js'),
        'admin-styles': resolve(__dirname, 'src/css-entries/admin.js'),
      },
      output: {
        // Configure output file naming
        entryFileNames: (chunkInfo) => {
          const name = chunkInfo.name;
          if (name.endsWith('-styles')) {
            // This is a CSS entry, we don't need the JS file
            return 'temp/[name].js';
          }
          return 'assets/js/[name].min.js';
        },
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            // Map the CSS files to the correct names
            if (assetInfo.name.includes('find-allergist-results')) {
              return 'assets/css/find-allergist-results.min.css';
            }
            if (assetInfo.name.includes('admin')) {
              return 'assets/css/admin.min.css';
            }
            return 'assets/css/[name].min.css';
          }
          return 'assets/[name][extname]';
        },
      },
    },
  },
  
  // Configure CSS processing
  css: {
    postcss: {
      plugins: [
        require('cssnano')({
          preset: 'default',
        }),
      ],
    },
  },
  
  // Configure dev server (optional, for development)
  server: {
    port: 3000,
    open: false,
  },
});
