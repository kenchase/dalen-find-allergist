=== Dalen Find Allergist ===
Contributors: dalendesign
Tags: directory, search, allergist, physician, medical, location, distance, maps, healthcare, canada, rest-api, acf, wild-apricot, sso
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin for CSACI providing a physician directory with location-based search, interactive maps, client-side pagination, and Wild Apricot SSO integration for Canadian healthcare professionals.

== Description ==

The Find an Allergist plugin provides a comprehensive directory system for allergists and immunologists across Canada. Built specifically for the Canadian Society of Allergy and Clinical Immunology (CSACI), this plugin offers advanced search capabilities with location-based filtering, Wild Apricot SSO integration, and a complete admin panel for centralized management.

**Version 1.0.0** represents the stable production release with streamlined codebase, updated REST API namespace, and modern shortcode architecture.

= Key Features =

* **Location-Based Search** - Find physicians within 1-500km radius using Canadian postal codes with distance filtering
* **Wild Apricot SSO Integration** - Role-based access controls for physician profile management (`wa_level_` roles)
* **REST API** - Modern endpoint `/wp-json/faa/v1/physicians/search` with comprehensive validation
* **Client-Side Pagination** - Instant page navigation without additional API calls
* **Comprehensive Admin Panel** - Dashboard, settings management, help documentation, and system status
* **Advanced Search** - Multi-criteria search by name, location, specialty, and distance
* **Interactive Maps** - Google Maps integration showing all practice locations
* **Profile Editor** - ACF-based form shortcode for physicians to edit their own profiles
* **Production-Ready Security** - Input validation, sanitization, and error handling throughout
* **Mobile Responsive** - Optimized design for all devices

= Version 1.0.0 Highlights =

* **Production Release**: Stable, production-ready codebase
* **Modern API**: Updated REST endpoint namespace to `/wp-json/faa/v1/physicians/search`
* **Enhanced Shortcodes**: New shortcode architecture with `[faa-search-form]`, `[faa-search-results]`, and `[faa-profile-editor]`
* **Streamlined Code**: Clean, maintainable codebase following WordPress best practices
* **Complete Documentation**: Updated documentation reflecting current plugin state
* **ACF Integration**: Full integration with Advanced Custom Fields for physician data management

= Wild Apricot Integration =

* **Automatic Role Detection** - Recognizes users with roles beginning with `wa_level_`
* **Restricted Access** - wa_level users can only edit their own physician profile
* **Content Security** - Prevents modifications to sensitive fields and access to other profiles
* **Profile Editor** - Dedicated `[faa-profile-editor]` shortcode for physicians to manage their information
* **UI Streamlining** - Simplified admin interface for physician users

= Search Features =

* **Name Search** - Search by physician first name, last name, or full name
* **Location Filters** - City, province, or postal code filtering
* **Distance Search** - Radius search from 1km to 500km with Canadian postal code geocoding
* **Practice Population** - Filter by adult, pediatric, or all populations
* **Smart Caching** - Results cached in browser memory for instant pagination
* **Combined Search** - Mix multiple criteria for precise results

= Admin Panel Features =

* **Dashboard** - Quick stats, recent activity, and system status overview
* **Settings Management** - Google Maps API key configuration with real-time testing
* **Search Configuration** - Configurable result limits (1-100) and default radius (1-500km)
* **Help Documentation** - Comprehensive guides with examples and troubleshooting
* **API Key Validation** - Test API keys directly from admin interface
* **Performance Monitoring** - Plugin health checks and optimization recommendations

= For Developers =

* **REST API** - `/wp-json/faa/v1/physicians/search` with comprehensive parameters
* **Object-Oriented Design** - Class-based architecture with proper namespacing
* **WordPress Standards** - Full compliance with WordPress coding standards
* **Extensible Hooks** - Action and filter hooks for customizations
* **Security First** - Input validation, sanitization, and capability checks throughout
* **Build Tools** - Vite-based build system for modern asset management

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Advanced Custom Fields (ACF) plugin
* Google Maps API key (Geocoding API and Maps JavaScript API)
* Wild Apricot SSO integration (optional, for user role management features)

= Development Tools =

* Node.js and NPM for asset building
* Vite for modern JavaScript bundling
* PostCSS for CSS processing

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
2. **Access Admin Panel** - Navigate to "Find Allergist" in your WordPress admin menu
3. **Configure Google Maps API Key** - Add your API key through Find Allergist → Settings
4. **Configure Wild Apricot Integration** - Set up SSO for physician profile management (if applicable)
5. **Add Physician Data** - Create physician profiles manually or via import
6. **Add Shortcodes** - Use `[faa-search-form]` and `[faa-search-results]` on your search page, and `[faa-profile-editor]` for physician profile editing

= Admin Panel Configuration =

After activation, access the comprehensive admin panel through "Find Allergist" in your WordPress admin menu:

* **Dashboard** - View plugin overview, quick links, and system status
* **Settings** - Configure Google Maps API key
* **Help** - Access documentation and troubleshooting guides

= Google Maps API Setup =

1. Visit the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the following APIs:
   * Geocoding API (required)
   * Maps JavaScript API (required)
   * Places API (optional)
4. Create an API key and add it through Find Allergist → Settings
5. Test the API key using the real-time validation in the admin panel

= Wild Apricot SSO Configuration =

For organizations using Wild Apricot SSO:
1. Users with roles beginning with `wa_level_` are automatically recognized
2. These users gain access to manage their own physician profiles
3. Access controls are automatically applied for content security
4. No additional configuration required - the system detects and manages permissions automatically

1. Visit the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the following APIs:
   * Geocoding API
   * Maps JavaScript API
   * Places API (optional)
4. Create an API key and add it through the admin panel Settings page

== Frequently Asked Questions ==

= What's new in version 1.0.0? =

Version 1.0.0 is the stable production release featuring a streamlined codebase, updated REST API namespace (`/wp-json/faa/v1/physicians/search`), modern shortcode architecture (`[faa-search-form]`, `[faa-search-results]`, `[faa-profile-editor]`), and comprehensive documentation.

= How does Wild Apricot integration work? =

Users with roles beginning with `wa_level_` are automatically recognized and granted access to manage their own physician profile using the `[faa-profile-editor]` shortcode. The system enforces access controls, preventing users from editing other profiles or sensitive fields.

= How does client-side pagination work? =

The pagination system loads all search results in a single API call, then handles page navigation instantly in the browser. This provides immediate page changes without loading delays and allows all physician locations to be visible on the map regardless of the current page.

= How does distance-based search work? =

Distance-based search allows users to find physicians within a specific radius (1-500km) of any Canadian postal code. The plugin uses Google's Geocoding API to convert postal codes to coordinates and calculates accurate distances using the Haversine formula.

= Which Canadian postal codes are supported? =

All valid Canadian postal codes are supported. The plugin automatically geocodes postal codes using Google's Geocoding API and includes comprehensive validation to ensure proper formatting.

= Can physicians have multiple practice locations? =

Yes! The plugin supports physicians with multiple practice locations through ACF repeater fields (`organizations_details`). Each location can have its own address, phone number, and coordinates using ACF's Google Map field. Distance searches will find physicians if ANY of their locations are within the specified radius.

= What populations can be filtered? =

The plugin supports filtering by practice population (adult, pediatric, or all) using the `prac_pop` parameter in searches.

= Is the plugin mobile-friendly? =

Yes, the plugin is fully responsive with optimized pagination controls for mobile devices. The search interface adapts to different screen sizes, and the new client-side pagination provides smooth navigation on mobile.

= How do I configure plugin settings? =

Access the admin panel through "Find Allergist" in your WordPress admin menu. Navigate to Settings to configure your Google Maps API key and view system status.

= What is the API endpoint? =

The REST API endpoint is `/wp-json/faa/v1/physicians/search` with comprehensive input validation and security measures. It supports parameters for name, city, province, postal code, distance (1-500km), and practice population filtering.

= How secure is the plugin? =

The plugin includes comprehensive security measures: input validation and sanitization throughout, proper escaping of all outputs, nonce verification, capability checks, and protection against unauthorized access and modifications.

= Can I customize the search functionality? =

Yes! The plugin provides hooks and filters for customization. Developers can modify search parameters, add custom fields, and extend functionality through the class-based architecture.

= Does this work with caching plugins? =

Yes, the plugin is compatible with caching plugins. The client-side pagination improves performance by reducing server requests.

= How do I troubleshoot API key issues? =

Verify your Google Maps API key is properly configured in Find Allergist → Settings. Ensure you have enabled both the Geocoding API and Maps JavaScript API in your Google Cloud Console.

== Screenshots ==

1. **Search Form** - Advanced search interface with name, location, and distance filters
2. **Search Results** - Physician listings with contact information and distance display
3. **Interactive Map** - Google Maps integration showing physician locations
4. **Distance Filtering** - Results filtered by proximity to postal code
5. **Admin Dashboard** - Plugin statistics and management overview
6. **Admin Settings** - API configuration and search parameter management
7. **Admin Help** - Comprehensive documentation and troubleshooting
8. **Mobile View** - Responsive design optimized for mobile devices

== Changelog ==

= 1.0.0 (2025-10-08) =
* **PRODUCTION RELEASE** - Stable version 1.0.0 with production-ready codebase
* **API NAMESPACE** - Updated REST API endpoint to `/wp-json/faa/v1/physicians/search`
* **SHORTCODE UPDATES** - New shortcode names: `[faa-search-form]`, `[faa-search-results]`, `[faa-profile-editor]`
* **PROFILE EDITOR** - Added `[faa-profile-editor]` shortcode for physicians to edit their own profiles using ACF form
* **DOCUMENTATION** - Comprehensive documentation updates reflecting current plugin state
* **CODE CLEANUP** - Streamlined codebase with consistent naming conventions (`faa_` prefix)
* **ACF INTEGRATION** - Full integration with Advanced Custom Fields for physician data management
* **BUILD SYSTEM** - Vite-based build system for modern asset management
* **SECURITY** - Maintained comprehensive input validation and sanitization
* **WILD APRICOT** - Role-based access controls for `wa_level_` users with profile editing capabilities



== Upgrade Notice ==

= 1.0.0 =
**PRODUCTION RELEASE** - Stable version 1.0.0 with updated REST API endpoint (`/wp-json/faa/v1/physicians/search`) and new shortcode names (`[faa-search-form]`, `[faa-search-results]`, `[faa-profile-editor]`). Update your shortcodes after upgrading.

== API Documentation ==

= Search Endpoint =

**GET** `/wp-json/faa/v1/physicians/search`

**Security & Validation:**
* All parameters are properly sanitized and validated
* Distance parameter limited to 1-500km range
* Comprehensive error handling with user-friendly messages

**Parameters:**
* `name` (string) - Physician name search
* `city` (string) - City filter
* `province` (string) - Province filter
* `postal` (string) - Postal code for distance filtering
* `kms` (integer) - Search radius in kilometers (1-500)
* `prac_pop` (string) - Practice population filter (adult, pediatric, or all)

**Example Requests:**
```
# Search by name
GET /wp-json/faa/v1/physicians/search?name=John%20Smith

# Distance-based search (50km radius from Toronto downtown)  
GET /wp-json/faa/v1/physicians/search?postal=M5V3M6&kms=50

# Province-wide search
GET /wp-json/faa/v1/physicians/search?province=Ontario

# Pediatric physicians in a specific city
GET /wp-json/faa/v1/physicians/search?city=Vancouver&prac_pop=pediatric
```

**Response Format:**
```json
{
  "total_results": 25,
  "results": [
    {
      "id": 1234,
      "title": "Dr. John Smith",
      "link": "https://example.com/physicians/john-smith/",
      "acf": {
        "physician_credentials": "MD, FRCPC",
        "organizations_details": [
          {
            "institutation_name": "Hospital for Sick Children",
            "institution_gmap": {
              "address": "555 University Ave",
              "city": "Toronto",
              "state": "Ontario",
              "post_code": "M5G 1X8",
              "lat": "43.6570",
              "lng": "-79.3914"
            },
            "institution_phone": "416-813-1500",
            "distance_km": 1.2
          }
        ]
      }
    }
  ]
}
```

**Note:** The API returns all matching results in a single response. Pagination is handled client-side for improved performance and instant page navigation.

= Shortcodes =

**Search Form:**
`[faa-search-form]` - Displays the search form with all filter options (name, city, province, postal code, distance, practice population)

**Search Results:**
`[faa-search-results]` - Displays the results container with:
* Client-side pagination  
* Interactive Google Maps integration
* Mobile responsive design

**Profile Editor:**
`[faa-profile-editor]` - ACF form for physicians to edit their own profile (requires appropriate user role)

**Complete Search Page Setup:**
```
[faa-search-form]
[faa-search-results]
```

**Features:**
* Instant pagination without loading delays
* Smart search detection (new vs. page navigation)
* Memory efficient with automatic result cleanup
* Mobile optimized controls

== Technical Specifications ==

= System Requirements =
* WordPress 5.0+
* PHP 7.4+
* MySQL 5.6+
* Advanced Custom Fields plugin
* Google Maps API key (Geocoding API and Maps JavaScript API)

= ACF Field Structure =
* **Physician Field**: `physician_credentials` - Text field for credentials (MD, FRCPC, etc.)
* **Organizations Repeater**: `organizations_details` - Repeater field containing:
  * Organization name
  * Google Map field (address, coordinates, postal code)
  * Phone number
  * Additional organization details

= Performance =
* Optimized database queries with post-query filtering
* Client-side pagination for instant navigation
* Efficient distance calculations using Haversine formula
* Minimal server resource usage
* Compatible with caching plugins

= Security =
* Comprehensive input validation and sanitization
* Secure REST API endpoints with nonce verification
* WordPress coding standards compliance
* Role-based access controls for Wild Apricot users

== Support ==

For technical support, feature requests, or bug reports:

* **Developer:** Dalen Design
* **Website:** [https://www.dalendesign.com/](https://www.dalendesign.com/)
* **Documentation:** See README.md in the plugin directory for detailed technical documentation

This plugin is developed specifically for the Canadian Society of Allergy and Clinical Immunology (CSACI) and is designed for the Canadian healthcare system using Canadian postal code formatting and geographic conventions.
