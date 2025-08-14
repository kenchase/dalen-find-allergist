=== Dalen Find Allergist ===
Contributors: dalendesign
Donate link: https://www.dalendesign.com/
Tags: directory, search, allergist, physician, medical, location, distance, maps, healthcare, canada
Requires at least: 5.0
Tested up to: 6.8.2
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced allergist and immunologist directory with location-based search, distance filtering, and interactive maps for Canadian healthcare professionals.

== Description ==

The Dalen Find Allergist plugin provides a comprehensive directory system for allergists and immunologists across Canada. Built specifically for the Canadian Society of Allergy and Clinical Immunology (CSACI), this plugin offers advanced search capabilities with location-based filtering.

= Key Features =

* **Advanced Search Engine** - Multi-criteria search by name, location, specialty, and distance
* **Distance-Based Filtering** - Find physicians within a specified radius using Canadian postal codes
* **Interactive Maps** - Google Maps integration for location visualization
* **REST API** - Robust API endpoints for flexible frontend implementations
* **Mobile Responsive** - Optimized for all device types
* **ACF Integration** - Advanced Custom Fields support for rich physician data

= Search Capabilities =

* **Name Search** - Search by first name, last name, or full name with fuzzy matching
* **Location Filters** - Filter by city, province, or postal code
* **Distance Search** - Find physicians within X kilometers of any Canadian postal code
* **Specialty Filters** - Filter by treatments offered (e.g., Oral Immunotherapy - OIT)
* **Combined Search** - Mix and match multiple search criteria for precise results

= Geographic Features =

* **Postal Code Geocoding** - Automatic conversion of Canadian postal codes to coordinates
* **Haversine Distance Calculation** - Accurate distance measurements in kilometers
* **Radius Search** - Configurable search radius from 1km to 500km
* **Multiple Locations** - Support for physicians with multiple practice locations

= For Developers =

* **REST API Endpoints** - `/wp-json/my/v1/physicians/search`
* **Shortcodes** - Easy integration with `[find_allergist_form]` and `[find_allergist_results]`
* **Custom Post Types** - Structured physician data with proper taxonomy
* **Extensible Architecture** - Hook-based system for customizations

= Requirements =

* Advanced Custom Fields (ACF) plugin
* Google Maps API key (for geocoding and mapping)
* WordPress 5.0 or higher
* PHP 7.4 or higher

This plugin is specifically designed for the Canadian healthcare system and uses Canadian postal code formatting and geographic conventions.

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin dashboard
2. Navigate to Plugins → Add New
3. Search for "Dalen Find Allergist"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Upload the entire `dalen-find-allergist` folder to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

= Post-Installation Setup =

1. **Install Advanced Custom Fields (ACF)** - Required for physician data management
2. **Configure Google Maps API Key** - Add your API key in the plugin settings
3. **Import Physician Data** - Add physician profiles manually or via import
4. **Add Shortcodes** - Use `[find_allergist_form]` and `[find_allergist_results]` on your pages

= Google Maps API Setup =

1. Visit the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the following APIs:
   * Geocoding API
   * Maps JavaScript API
   * Places API (optional)
4. Create an API key and add it to your plugin configuration

== Frequently Asked Questions ==

= What is distance-based search? =

Distance-based search allows users to find physicians within a specific radius (in kilometers) of any Canadian postal code. For example, you can search for all allergists within 50km of postal code M5V 3M6 (Toronto downtown).

= Which Canadian postal codes are supported? =

All valid Canadian postal codes are supported. The plugin automatically geocodes postal codes using Google's Geocoding API to determine precise coordinates for distance calculations.

= Can physicians have multiple practice locations? =

Yes! The plugin supports physicians with multiple practice locations. Each location can have its own address, phone number, and coordinates. Distance searches will find physicians if ANY of their locations are within the specified radius.

= What specialties can be filtered? =

Currently, the plugin supports filtering for Oral Immunotherapy (OIT) specialists. Additional specialty filters can be added by extending the ACF field structure.

= Is the plugin mobile-friendly? =

Yes, the plugin is fully responsive and optimized for mobile devices. The search interface adapts to different screen sizes for optimal user experience.

= Can I customize the search form? =

Yes, the plugin provides hooks and filters for customization. Developers can modify the search form appearance, add custom fields, or integrate with existing themes.

= Does this work with any theme? =

The plugin is designed to work with any properly coded WordPress theme. It uses shortcodes for display, making it compatible with most page builders and theme structures.

= Is there an API for developers? =

Yes! The plugin provides a REST API endpoint at `/wp-json/my/v1/physicians/search` with comprehensive search parameters. See the documentation for detailed API usage examples.

== Screenshots ==

1. **Search Form** - Advanced search interface with name, location, and distance filters
2. **Search Results** - Physician listings with contact information and distance display
3. **Interactive Map** - Google Maps integration showing physician locations
4. **Distance Filtering** - Results filtered by proximity to postal code
5. **Admin Interface** - Physician profile management in WordPress admin
6. **Mobile View** - Responsive design optimized for mobile devices

== Changelog ==

= 0.1.0 (2025-08-14) =
* Initial release
* **New:** Distance-based search with Canadian postal code geocoding
* **New:** REST API endpoint for physician search
* **New:** Advanced search form with multiple filter options
* **New:** Google Maps integration for location display
* **New:** Responsive design for mobile compatibility
* **New:** ACF integration for physician data management
* **New:** Custom post type for physician profiles
* **New:** Shortcode system for easy page integration
* **New:** Haversine formula for accurate distance calculations
* **New:** Support for multiple practice locations per physician
* **New:** OIT (Oral Immunotherapy) specialty filtering
* **New:** Optimized database queries for performance

== Upgrade Notice ==

= 0.1.0 =
Initial release of the Dalen Find Allergist plugin. Install to begin using the advanced physician directory with distance-based search capabilities.

== API Documentation ==

= Search Endpoint =

**GET** `/wp-json/my/v1/physicians/search`

**Parameters:**
* `fname` (string) - First name search
* `lname` (string) - Last name search
* `city` (string) - City filter
* `province` (string) - Province filter
* `postal` (string) - Postal code for distance filtering
* `kms` (integer) - Search radius in kilometers
* `oit` (boolean) - Filter for OIT specialists

**Example:**
`/wp-json/my/v1/physicians/search?postal=M5V3M6&kms=50`

= Shortcodes =

**Search Form:**
`[find_allergist_form]`

**Results Display:**
`[find_allergist_results]`

== Technical Specifications ==

= System Requirements =
* WordPress 5.0+
* PHP 7.4+
* MySQL 5.6+
* Advanced Custom Fields plugin
* Google Maps API key

= Performance =
* Optimized database queries
* Efficient distance calculations
* Minimal server resource usage
* Compatible with caching plugins

= Security =
* Sanitized input validation
* Secure API endpoints
* WordPress coding standards compliance
* Regular security updates

== Support ==

For technical support, feature requests, or bug reports:

* **Developer:** Dalen Design
* **Website:** [https://www.dalendesign.com/](https://www.dalendesign.com/)
* **Documentation:** See README.md for detailed technical documentation

This plugin is developed specifically for the Canadian Society of Allergy and Clinical Immunology (CSACI).
