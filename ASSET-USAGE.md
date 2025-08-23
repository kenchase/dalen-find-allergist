# Asset Loading Update Example

To use the new build system with your existing code, you'll need to update how assets are loaded. Here's how to modify your existing files:

## Before (in admin/class-admin.php):

```php
wp_enqueue_style(
    'dalen-find-allergist-admin',
    plugin_dir_url(__FILE__) . '../assets/css/admin.css',
    array(),
    '1.0.0'
);

wp_enqueue_script(
    'dalen-find-allergist-admin',
    plugin_dir_url(__FILE__) . '../assets/js/admin.js',
    array('jquery'),
    '1.0.0',
    true
);
```

## After:

```php
$asset_base_url = plugin_dir_url(__FILE__) . '../assets/';

wp_enqueue_style(
    'dalen-find-allergist-admin',
    dalen_get_asset_url('css/admin.css', $asset_base_url),
    array(),
    dalen_get_asset_version('css/admin.css')
);

wp_enqueue_script(
    'dalen-find-allergist-admin',
    dalen_get_asset_url('js/admin.js', $asset_base_url),
    array('jquery'),
    dalen_get_asset_version('js/admin.js'),
    true
);
```

## What this does:

1. **Automatic minification detection**: In production (when `WP_DEBUG` is false), it will automatically load `admin.min.css` and `admin.min.js`
2. **Fallback**: If minified files don't exist, it falls back to the original files
3. **Cache busting**: Uses file modification time for versioning instead of hardcoded version numbers

## Files to update:

- `admin/class-admin.php` (lines around 123-137)
- `includes/shortcodes/class-shortcode-base.php` (lines around 84)
- `includes/shortcodes/class-find-allergist-form.php` (lines around 50)

This way, your plugin will automatically use optimized assets in production while keeping the original files for development.
