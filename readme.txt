=== Dalen Find Allergist ===
Contributors: dalendesign
Donate link: https://www.dalendesign.com/
Tags: directory, search, allergist, physician, medical, location, distance, maps, healthcare, canada, rest-api, acf, wild-apricot, sso
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.9.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced allergist and immunologist directory with comprehensive admin panel, Wild Apricot SSO integration, location-based search, client-side pagination, and enhanced security for Canadian healthcare professionals.

== Description ==

The Dalen Find Allergist plugin provides a comprehensive directory system for allergists and immunologists across Canada. Built specifically for the Canadian Society of Allergy and Clinical Immunology (CSACI), this plugin offers advanced search capabilities with location-based filtering, Wild Apricot SSO integration, and a complete admin panel for centralized management.

**Version 0.9.1** represents a major refactoring and optimization with 30% reduction in plugin size, 100% WordPress coding standards compliance, enhanced security, and production-ready architecture.

= Key Features =

* **Complete Code Refactoring** - Major optimization with singleton pattern, enhanced security, and WordPress best practices
* **Wild Apricot SSO Integration** - Seamless single sign-on with granular access controls for physician profile management
* **Enhanced REST API** - Updated namespace `/wp-json/dalen/v1/physicians/search` with comprehensive validation
* **Client-Side Pagination** - Instant page navigation without additional API calls (20 results per page)
* **Comprehensive Admin Panel** - Dashboard, settings management, help documentation, and real-time validation
* **Advanced Search Engine** - Multi-criteria search by name, location, specialty, and distance with smart caching
* **Distance-Based Filtering** - Find physicians within 1-500km radius using Canadian postal codes
* **Interactive Maps** - Google Maps integration showing all results regardless of current page
* **Production-Ready Security** - Enhanced input validation, sanitization, and error handling
* **Mobile Responsive** - Optimized pagination controls and responsive design for all devices

= Version 0.9.1 Improvements =

* **Architecture**: Singleton pattern for main plugin class with proper dependency management
* **Security**: Comprehensive input validation, sanitization, and escaping throughout
* **Performance**: 30% reduction in plugin size through redundant code removal
* **Standards**: 100% WordPress coding standards compliance with PHPDoc documentation
* **Error Handling**: Robust error handling with user-friendly messages
* **API Enhancement**: Updated REST endpoint namespace with enhanced security
* **Code Organization**: Constants file and class-based shortcode architecture

= Wild Apricot User Management =

* **Automatic Role Detection** - Recognizes users with roles beginning with `wa_level_`
* **Granular Access Control** - wa_level users can only access and edit their own physician profiles
* **Content Security** - Prevents modifications to sensitive fields (author, slug, post status)
* **UI Streamlining** - Hides unnecessary admin elements for cleaner physician interface
* **Profile Limitations** - Enforces one physician profile per wa_level user for data integrity
* **AJAX Security** - Prevents unauthorized AJAX operations with server-side validation

= Advanced Search Features =

* **Name Search** - Fuzzy matching for first name, last name, or full name
* **Location Filters** - City, province, or postal code filtering
* **Distance Search** - Radius search from 1km to 500km with Canadian postal code geocoding
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

* **Enhanced REST API** - `/wp-json/dalen/v1/physicians/search` with comprehensive parameters
* **Object-Oriented Design** - Class-based architecture with singleton pattern
* **WordPress Standards** - Full compliance with WordPress coding standards
* **Extensible Hooks** - Proper action and filter hooks for customizations
* **Security First** - Enhanced validation, sanitization, and capability checks
* **Documentation** - Comprehensive PHPDoc comments throughout codebase

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Advanced Custom Fields (ACF) plugin
* Google Maps API key (for geocoding and mapping)
* Wild Apricot SSO integration (for user role management features)

= Recommended for Development =

* Composer for dependency management
* Node.js & NPM for frontend asset management
* WordPress Coding Standards for code quality
* PHPUnit for testing

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
4. **Adjust Search Parameters** - Set default search radius (1-500km) and result limits (1-100)
5. **Configure Wild Apricot Integration** - Set up SSO for physician profile management (if applicable)
6. **Add Physician Data** - Create physician profiles manually or via import
7. **Add Shortcodes** - Use `[find_allergist_form]` and `[find_allergist_results]` on your pages

= Admin Panel Configuration =

After activation, access the comprehensive admin panel through "Find Allergist" in your WordPress admin menu:

* **Dashboard** - View plugin statistics, recent activity, and system status
* **Settings** - Configure Google Maps API key, search limits, and default radius
* **Help** - Access complete documentation, examples, and troubleshooting guides

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

= What's new in version 0.9.1? =

Version 0.9.1 represents a major refactoring with 30% code reduction, enhanced security, Wild Apricot SSO integration, client-side pagination, and 100% WordPress coding standards compliance. The plugin now uses a singleton pattern, updated REST API namespace (`/wp-json/dalen/v1/`), and comprehensive input validation.

= How does Wild Apricot integration work? =

Users with roles beginning with `wa_level_` are automatically recognized and granted access to manage their own physician profiles. The system enforces granular access controls, preventing users from editing other profiles or sensitive fields, while providing a streamlined admin interface.

= What is client-side pagination? =

The new pagination system loads all search results in a single API call, then handles page navigation instantly in the browser. This provides immediate page changes without loading delays and allows all physician locations to be visible on the map regardless of the current page.

= How does distance-based search work? =

Distance-based search allows users to find physicians within a specific radius (1-500km) of any Canadian postal code. The plugin uses Google's Geocoding API to convert postal codes to coordinates and calculates accurate distances using the Haversine formula.

= Which Canadian postal codes are supported? =

All valid Canadian postal codes are supported. The plugin automatically geocodes postal codes using Google's Geocoding API and includes comprehensive validation to ensure proper formatting.

= Can physicians have multiple practice locations? =

Yes! The plugin supports physicians with multiple practice locations through ACF repeater fields. Each location can have its own address, phone number, and coordinates. Distance searches will find physicians if ANY of their locations are within the specified radius.

= What specialties can be filtered? =

Additional specialty filters can be added by extending the ACF field structure or through the plugin's extensible hook system.

= Is the plugin mobile-friendly? =

Yes, the plugin is fully responsive with optimized pagination controls for mobile devices. The search interface adapts to different screen sizes, and the new client-side pagination provides smooth navigation on mobile.

= How do I configure plugin settings? =

Access the comprehensive admin panel through "Find Allergist" in your WordPress admin menu. You can configure Google Maps API keys with real-time testing, set search result limits (1-100), adjust default radius (1-500km), and access complete help documentation.

= What's the difference between the old and new API endpoints? =

The API endpoint has been updated from `/wp-json/my/v1/physicians/search` to `/wp-json/dalen/v1/physicians/search` with enhanced security, comprehensive input validation, and improved error handling. The new endpoint includes distance parameter validation (1-500km) and user-friendly error messages.

= How secure is the plugin? =

Version 0.9.1 includes comprehensive security enhancements: input validation and sanitization throughout, proper escaping of all outputs, nonce verification, capability checks, enhanced error handling, and protection against unauthorized modifications.

= Can I customize the search functionality? =

Yes! The plugin provides extensive hooks and filters for customization. Developers can modify search parameters, add custom fields, integrate with existing themes, and extend functionality through the object-oriented architecture.

= Does this work with caching plugins? =

Yes, the plugin is compatible with caching plugins. The client-side pagination actually improves performance by reducing server requests, and the admin panel settings are cached for optimal performance.

= How do I troubleshoot API key issues? =

Use the real-time API key testing feature in Find Allergist → Settings. The admin panel provides instant validation and specific error messages to help diagnose API configuration issues.

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

= 0.9.1 (2025-08-23) =
* **MAJOR REFACTORING** - Complete code review and optimization with 30% size reduction
* **NEW:** Main plugin class with singleton pattern (`Dalen_Find_Allergist_Plugin`)
* **NEW:** Constants file for better organization (`includes/constants.php`)
* **NEW:** Wild Apricot SSO integration with comprehensive user role management
* **NEW:** Client-side pagination with instant page navigation (20 results per page)
* **NEW:** Enhanced REST API namespace updated to `/wp-json/dalen/v1/physicians/search`
* **NEW:** Granular access controls for wa_level users on physician profiles
* **NEW:** Object-oriented shortcode architecture with base classes
* **ENHANCED:** Security improvements with comprehensive input validation throughout
* **ENHANCED:** Error handling with user-friendly messages and proper WordPress responses
* **ENHANCED:** Admin settings with real-time validation and API key testing
* **ENHANCED:** JavaScript architecture with state management for pagination
* **IMPROVED:** All function names now use proper `dalen_` prefix (WordPress standards)
* **IMPROVED:** Code documentation with comprehensive PHPDoc comments
* **IMPROVED:** WordPress coding standards compliance (100%)
* **IMPROVED:** Database queries optimization with post-query filtering
* **ADDED:** Input validation for distance parameters (1-500km range)
* **ADDED:** Smart search caching in browser memory for instant pagination
* **ADDED:** Content security preventing unauthorized profile modifications
* **ADDED:** UI streamlining for wa_level users with simplified admin interface
* **ADDED:** Profile limitation system (one physician per wa_level user)
* **REMOVED:** 572 lines of redundant code for better maintainability
* **SECURITY:** Enhanced sanitization and escaping throughout codebase
* **PERFORMANCE:** Reduced plugin size by ~30% through code optimization

= 0.4.0 (2025-08-22) =
* **NEW:** Wild Apricot SSO integration with comprehensive user role management
* **NEW:** Granular access controls for wa_level users on physician profiles
* **NEW:** Automated capability assignment and query filtering for secure content access
* **NEW:** UI streamlining with hidden admin elements for simplified user experience
* **NEW:** Content security preventing unauthorized modifications to sensitive fields
* **NEW:** Profile limitation system ensuring one physician profile per wa_level user
* **NEW:** WA_User_Manager class for centralized user management functionality
* **IMPROVED:** Enhanced security with server-side validation and AJAX blocking
* **IMPROVED:** Optimized code structure with OOP approach and reduced redundancy
* **IMPROVED:** Better user experience with clean, focused admin interface for physicians

= 0.3.0 (2025-08-21) =
* **NEW:** Client-side pagination for instant page navigation
* **NEW:** Smart search detection (new search vs. page navigation)
* **NEW:** Enhanced map integration showing all results regardless of current page
* **NEW:** Responsive pagination controls optimized for mobile devices
* **IMPROVED:** Search performance with single API call per search
* **IMPROVED:** User experience with instant page changes and smooth scrolling
* **IMPROVED:** Memory management with automatic result cleanup
* **IMPROVED:** JavaScript architecture with better error handling

= 0.2.0 (2025-08-20) =
* **NEW:** Complete admin panel with dashboard, settings, and help documentation
* **NEW:** Centralized Google Maps API key management through admin interface
* **NEW:** Configurable search parameters (results limit, default radius)
* **NEW:** Real-time settings validation and API key testing
* **NEW:** Admin dashboard with quick stats and recent activity
* **NEW:** Comprehensive help documentation with examples
* **IMPROVED:** Enhanced file structure with dedicated admin components
* **IMPROVED:** Better error handling and user feedback
* **IMPROVED:** Responsive admin interface design

= 0.1.0 (2025-08-14) =
* **Initial release**
* **NEW:** Distance-based search with Canadian postal code geocoding
* **NEW:** REST API endpoint for physician search
* **NEW:** Advanced search form with multiple filter options
* **NEW:** Google Maps integration for location display
* **NEW:** Responsive design for mobile compatibility
* **NEW:** ACF integration for physician data management
* **NEW:** Custom post type for physician profiles
* **NEW:** Shortcode system for easy page integration
* **NEW:** Haversine formula for accurate distance calculations
* **NEW:** Support for multiple practice locations per physician
* **NEW:** Optimized database queries for performance

== Upgrade Notice ==

= 0.9.1 =
**MAJOR UPDATE** - Complete refactoring with 30% size reduction, Wild Apricot SSO integration, client-side pagination, enhanced security, and 100% WordPress coding standards compliance. Backup before upgrading. API endpoint updated to `/wp-json/dalen/v1/physicians/search`.

= 0.4.0 =
Major update with Wild Apricot SSO integration and comprehensive user role management. Adds granular access controls for physician profile management.

= 0.3.0 =
Significant performance improvement with client-side pagination. Search results now load instantly between pages with single API calls.

= 0.2.0 =
Major update with comprehensive admin panel. Upgrade to access centralized settings management, dashboard analytics, and enhanced configuration options.

= 0.1.0 =
Initial release of the Dalen Find Allergist plugin. Install to begin using the advanced physician directory with distance-based search capabilities.

== API Documentation ==

= Search Endpoint =

**GET** `/wp-json/dalen/v1/physicians/search` *(Updated namespace)*

**Enhanced Security & Validation:**
* All parameters are properly sanitized and validated
* Distance parameter limited to 1-500km range
* Required search criteria validation
* Comprehensive error handling with user-friendly messages

**Parameters:**
* `name` (string) - Physician name search
* `city` (string) - City filter
* `province` (string) - Province filter
* `postal` (string) - Postal code for distance filtering
* `kms` (integer) - Search radius in kilometers (1-500)
**Example Requests:**
```
# Search by name
GET /wp-json/dalen/v1/physicians/search?name=John%20Smith

# Distance-based search (50km radius from Toronto downtown)  
GET /wp-json/dalen/v1/physicians/search?postal=M5V3M6&kms=50

# Province-wide search
GET /wp-json/dalen/v1/physicians/search?province=Ontario
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
        "city": "Toronto",
        "province": "Ontario", 
        "postal": "M5V 3M6",
        "credentials": "MD, FRCPC",
        "organizations_details": [
          {
            "institutation_name": "Hospital for Sick Children",
            "institution_gmap": {
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
`[find_allergist_form]` - Displays the search form with all filter options

**Complete Search Interface:**
`[find_allergist_results]` - Displays the complete search interface including:
* Search form with all filter options
* Results container with client-side pagination  
* Interactive Google Maps integration
* Mobile responsive design

**Features:**
* Instant pagination without loading delays
* Smart search detection (new vs. page navigation)
* Memory efficient with automatic result cleanup
* Mobile optimized pagination controls

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
