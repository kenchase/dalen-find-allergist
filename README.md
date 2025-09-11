# Dalen Find Allergist

A WordPress plugin for the Canadian Society of Allergy and Clinical Immunology (CSACI) that provides an advanced allergist/immunologist finder with location-based search capabilities.

## 🚀 Version 0.99 - SSO Integration & Optimization

This version represents a complete code review and refactoring of the plugin, implementing WordPress best practices, enhanced security, Wild Apricot SSO integration, and improved maintainability.

### 🔧 Technical Features

- **REST API Endpoints**: `/wp-json/dalen/v1/physicians/search`
- **Enhanced Security**: Comprehensive input validation and sanitization
- **Client-Side Pagination**: Instant page navigation without additional API calls
- **Efficient Queries**: Optimized database queries with post-query filtering
- **Smart Search Caching**: Results cached in browser memory for instant pagination
- **ACF Integration**: Leverages Advanced Custom Fields for data management
- **Role Management**: Custom user roles for physician management
- **Wild Apricot Integration**: Seamless SSO integration with access controls for wa_level users
- **Content Security**: Prevents unauthorized profile modifications and ensures data integrity
- **Responsive Design**: Mobile-friendly search interface with responsive pagination
- **Admin Dashboard**: Complete administrative interface with settings management
- **API Key Management**: Secure Google Maps API key configuration
- **Plugin Settings**: Configurable search parameters and display options
- **WordPress Standards**: Code follows WordPress coding standards and best practices
- **Production Ready**: Comprehensive error handling, validation, and documentation

### 🎯 Code Quality Improvements

- **30% reduction** in plugin size through redundant code removal
- **100% WordPress coding standards** compliance
- **Enhanced security** with comprehensive input validation and sanitization
- **Production-ready** architecture with singleton pattern and proper dependency management
- **Comprehensive documentation** with PHPDoc comments throughout
- **Optimized performance** with improved database queries and caching
- **Better error handling** with user-friendly messages and proper WordPress error handling

## Description

This plugin creates a comprehensive directory system for allergists and immunologists across Canada, featuring:

- **Custom Post Type**: Physician profiles with detailed information
- **Advanced Search**: Multi-criteria search with name, location, and specialty filters
- **Distance-Based Filtering**: Find physicians within a specified radius of a postal code
- **Interactive Maps**: Google Maps integration for location visualization
- **REST API**: Robust API endpoints with enhanced security and validation
- **ACF Integration**: Advanced Custom Fields for rich physician data management
- **Admin Panel**: Comprehensive admin interface for plugin configuration and management
- **User Role Management**: Wild Apricot SSO integration with custom access controls for physician profile management
- **Production-Ready Code**: Following WordPress coding standards with comprehensive documentation

## Features

### 🏗️ Architecture & Code Quality

- **Singleton Pattern**: Main plugin class prevents multiple instances and manages dependencies
- **Constants Management**: Centralized configuration through dedicated constants file
- **WordPress Standards**: 100% compliance with WordPress coding standards and best practices
- **Object-Oriented Design**: Class-based architecture for shortcodes and admin functionality
- **Proper Namespacing**: All functions use consistent `dalen_` prefix to prevent conflicts
- **Documentation**: Comprehensive PHPDoc comments for all functions and classes
- **Error Handling**: Robust error handling with proper WordPress error responses
- **Security First**: Enhanced input validation, sanitization, and escaping throughout

### 🔍 Search Capabilities

- **Name Search**: Search by first name, last name, or full name
- **Location Search**: Filter by city, province, or postal code
- **Distance Filtering**: Find physicians within X kilometers of a postal code
- **Specialty Filtering**: Filter by treatments offered (e.g., OIT - Oral Immunotherapy)
- **Combined Searches**: Mix and match multiple search criteria
- **Client-Side Pagination**: Instant page navigation with 20 results per page
- **Smart Result Management**: Single API call per search with client-side page handling

### 🗺️ Geographic Features

- **Postal Code Geocoding**: Automatic conversion of Canadian postal codes to coordinates
- **Distance Calculation**: Haversine formula for accurate distance measurements
- **Radius Search**: Configurable search radius in kilometers
- **Map Integration**: Google Maps display with physician locations

### 🏥 Physician Profiles

- **Comprehensive Information**: Credentials, specialties, contact details
- **Multiple Locations**: Support for physicians with multiple practice locations
- **Organization Details**: Hospital/clinic affiliations with full address information
- **Coordinates Storage**: Latitude/longitude for accurate mapping

### � Wild Apricot User Management

- **SSO Integration**: Seamless integration with Wild Apricot single sign-on system
- **Dynamic Role Assignment**: Automatic role creation and assignment for wa*level* users
- **Access Control**: Granular permissions for physician profile management
- **Content Restrictions**: Users can only edit their own physician profiles
- **Security Controls**: Prevents unauthorized modifications to author, slug, and sensitive fields
- **UI Customization**: Streamlined admin interface with hidden unnecessary elements
- **Profile Limitations**: One physician profile per wa_level user to maintain data integrity

### �🔧 Technical Features

- **REST API Endpoints**: `/wp-json/my/v1/physicians/search`
- **Client-Side Pagination**: Instant page navigation without additional API calls
- **Efficient Queries**: Optimized database queries with post-query filtering
- **Smart Search Caching**: Results cached in browser memory for instant pagination
- **ACF Integration**: Leverages Advanced Custom Fields for data management
- **Role Management**: Custom user roles for physician management
- **Wild Apricot Integration**: Seamless SSO integration with access controls for wa_level users
- **Content Security**: Prevents unauthorized profile modifications and ensures data integrity
- **Responsive Design**: Mobile-friendly search interface with responsive pagination
- **Admin Dashboard**: Complete administrative interface with settings management
- **API Key Management**: Secure Google Maps API key configuration
- **Plugin Settings**: Configurable search parameters and display options

## Installation

1. **Upload the plugin files** to the `/wp-content/plugins/dalen-find-allergist` directory
2. **Activate the plugin** through the 'Plugins' screen in WordPress
3. **Configure ACF fields** (if not already present)
4. **Configure plugin settings** through the admin panel (Find Allergist → Settings)
5. **Add your Google Maps API key** in the plugin settings
6. **Adjust search parameters** as needed (search results limit, default radius)
7. **Import physician data** or create physician profiles manually

## Admin Panel

The plugin includes a comprehensive admin panel accessible via **Find Allergist** in the WordPress admin menu:

### Dashboard

- **Quick Stats**: Overview of total physicians and recent activity
- **Recent Activity**: Latest physician profile updates
- **Quick Actions**: Direct links to add physicians and manage settings
- **System Status**: API key validation and plugin health checks

### Settings

- **Google Maps API Key**: Secure configuration for mapping functionality
- **Search Results Limit**: Control maximum results returned (1-100)
- **Default Search Radius**: Set default distance filter in kilometers (1-500km)
- **Settings Validation**: Real-time form validation and API key testing

### Help Documentation

- **Getting Started Guide**: Step-by-step setup instructions
- **Shortcode Reference**: Complete documentation for all available shortcodes
- **FAQ Section**: Common questions and troubleshooting
- **API Documentation**: REST endpoint usage examples
- **Best Practices**: Optimization tips and recommendations

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Advanced Custom Fields (ACF) plugin
- Google Maps API key (for geocoding and mapping features)
- Wild Apricot SSO integration (for user role management features)

### Recommended for Development

- **Composer** for dependency management
- **Node.js & NPM** for frontend asset management
- **WordPress Coding Standards** for code quality
- **PHPUnit** for testing

## Code Quality & Standards

This plugin follows WordPress best practices and coding standards:

- **PSR-4 Autoloading**: Organized class structure
- **WordPress Hooks**: Proper use of actions and filters
- **Sanitization**: All user inputs properly sanitized
- **Escaping**: All outputs properly escaped
- **Validation**: Comprehensive input validation
- **Documentation**: PHPDoc comments for all functions
- **Error Handling**: Proper WordPress error handling with WP_Error
- **Security**: Nonce verification and capability checks
- **Performance**: Optimized database queries and caching

## API Documentation

### Search Endpoint

**URL**: `GET /wp-json/dalen/v1/physicians/search` _(Updated namespace)_

**Parameters**:

- `name` (string): Physician name search
- `city` (string): City filter
- `province` (string): Province filter
- `postal` (string): Postal code (for distance filtering)
- `kms` (integer): Search radius in kilometers (1-500)
- `oit` (boolean): Filter for OIT specialists

**Enhanced Security & Validation**:

- All parameters are properly sanitized and validated
- Distance parameter limited to 1-500km range
- Required search criteria validation
- Comprehensive error handling with user-friendly messages

**Example Requests**:

```bash
# Search by name
GET /wp-json/dalen/v1/physicians/search?name=John%20Smith

# Distance-based search (50km radius from Toronto downtown)
GET /wp-json/dalen/v1/physicians/search?postal=M5V3M6&kms=50

# Combined search
GET /wp-json/dalen/v1/physicians/search?city=Toronto&oit=true

# Province-wide search
GET /wp-json/dalen/v1/physicians/search?province=Ontario
```

**Response Format**:

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
        "oit": ["OIT"],
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

**Note**: The API returns all matching results in a single response. Pagination is handled client-side for improved performance, allowing instant page navigation without additional API requests.

## Shortcodes

### Find Allergist Form

```
[find_allergist_form]
```

Displays the search form with all filter options.

### Find Allergist Results

```
[find_allergists]
```

Displays the complete search interface including:

- Search form with all filter options
- Results container with client-side pagination
- Interactive Google Maps integration
- Responsive design for mobile devices

**Features**:

- **Instant Pagination**: Navigate between pages without loading delays
- **Smart Search**: Detects new searches vs. page navigation
- **Memory Efficient**: Automatic cleanup of cached results
- **Mobile Responsive**: Optimized pagination controls for mobile devices

## ACF Field Structure

### Physician Fields

- `city` (Text): Physician's primary city
- `province` (Text): Physician's primary province
- `postal` (Text): Physician's primary postal code
- `credentials` (Text): Medical credentials
- `immunologist_online_search_tool` (Select): Search visibility

### Organization Details (Repeater)

- `institutation_name` (Text): Organization name
- `address_line_1` (Text): Street address
- `address_line_2` (Text): Address line 2
- `institution_city` (Text): Organization city
- `institution_state` (Text): Organization province/state
- `institution_zipcode` (Text): Organization postal code
- `institution_phone` (Text): Phone number
- `institution_fax` (Text): Fax number
- `institution_latitude` (Number): Latitude coordinate
- `institution_longitude` (Number): Longitude coordinate
- `institution_gmap` (Google Map): Map field (optional)

## File Structure

```
dalen-find-allergist/
├── README.md
├── CODE_REVIEW_SUMMARY.md            # Comprehensive code review documentation
├── dalen-find-allergist.php          # Main plugin file (refactored)
├── admin/
│   ├── class-admin.php               # Admin panel controller (optimized)
│   └── partials/
│       ├── admin-main.php            # Dashboard template
│       ├── admin-settings.php        # Settings page template
│       └── admin-help.php            # Help documentation template
├── includes/
│   ├── class-plugin.php              # NEW: Main plugin class (singleton pattern)
│   ├── constants.php                 # NEW: Plugin constants organization
│   ├── custom-post.php               # Physician post type (enhanced documentation)
│   ├── custom-role.php               # Wild Apricot user role management & access controls
│   ├── rest-api-search.php           # Search API endpoints (security enhanced)
│   ├── shortcodes.php                # Frontend shortcodes (class-based architecture)
│   ├── shortcodes/                   # Shortcode classes
│   │   ├── class-shortcode-base.php  # Base shortcode class
│   │   ├── class-find-allergist-form.php
│   │   ├── class-find-allergist-results.php
│   │   └── class-find-allergist-single.php
├── assets/
│   ├── css/
│   │   ├── admin.css                 # Admin panel styles
│   │   └── find-allergist-results.css # Frontend styles
│   └── js/
│       ├── admin.js                  # Admin panel functionality
│       └── find-allergist-results.js # Frontend JavaScript (refactored with state management)
├── tests/                            # Unit tests
└── .circleci/                        # CI/CD configuration
```

│ ├── custom-post.php # Physician post type
│ ├── custom-role.php # Wild Apricot user role management & access controls
│ ├── rest-api-search.php # Search API endpoints
│ ├── shortcodes.php # Frontend shortcodes
├── assets/
│ ├── css/
│ │ ├── admin.css # Admin panel styles
│ │ └── find-allergist-results.css # Frontend styles
│ └── js/
│ ├── admin.js # Admin panel functionality
│ └── find-allergist-results.js # Frontend JavaScript
├── tests/ # Unit tests
└── .circleci/ # CI/CD configuration

````

## Configuration

### Google Maps API Key

The plugin requires a Google Maps API key for geocoding and mapping features. Configure this through the admin panel:

1. Navigate to **Find Allergist → Settings** in WordPress admin
2. Enter your Google Maps API key in the provided field
3. Click **Test API Key** to validate the key
4. Save the settings

Alternatively, you can still configure it programmatically in `includes/class-plugin.php`:

```php
public function configure_acf_google_map_api($api) {
    // Get API key from admin settings
    $api_key = dalen_get_google_maps_api_key();
    if (!empty($api_key)) {
        $api['key'] = $api_key;
    }
    return $api;
}
````

**Required Google APIs**:

- Geocoding API
- Maps JavaScript API
- Places API (optional)

### Plugin Settings

Configure the plugin behavior through **Find Allergist → Settings**:

- **Search Results Limit**: Maximum results per search (default: 20, range: 1-100)
- **Default Search Radius**: Default distance filter in kilometers (default: 50km, range: 1-500km)
- **Google Maps API Key**: API key for mapping and geocoding functionality

### Search Configuration

Advanced search behavior can still be customized in `includes/rest-api-search.php`:

- **Search criteria requirements**: Adjust minimum search criteria
- **Custom filters**: Add additional search parameters
- **Result formatting**: Modify API response structure

## Wild Apricot User Management

The plugin includes comprehensive Wild Apricot SSO integration with advanced access controls for physician profile management.

### Features

- **Automatic Role Detection**: Recognizes users with roles beginning with `wa_level_`
- **Granular Access Control**: wa_level users can only access and edit their own physician profiles
- **Content Security**: Prevents modifications to sensitive fields (author, slug, post status)
- **UI Streamlining**: Hides unnecessary admin elements for a cleaner interface
- **Profile Limitations**: Enforces one physician profile per wa_level user

### Access Controls

#### What wa_level Users CAN Do:

- Edit their own physician profile content
- Delete their own physician profile
- Upload and manage media for their profile
- Access the physician post edit screen

#### What wa_level Users CANNOT Do:

- Access other users' physician profiles
- Change post author or slug
- Access WordPress admin profile settings
- Create multiple physician profiles
- Access admin menus outside of their physician profile
- See the WordPress admin bar
- Bulk edit or manage other posts

### Technical Implementation

The user management system is implemented in `includes/custom-role.php` using the `WA_User_Manager` class:

```php
// Initialize the WA User Management system
WA_User_Manager::init();
```

#### Key Methods:

- **`manage_physicians_capabilities()`**: Controls access to physician post editing
- **`restrict_posts_query()`**: Limits query results to user's own posts
- **`prevent_unauthorized_changes()`**: Blocks unauthorized field modifications
- **`hide_ui_elements()`**: Removes unnecessary admin interface elements
- **`validate_physicians_post()`**: Ensures data integrity on save

#### Security Features:

- **Capability Mapping**: Custom capabilities for physician post management
- **Query Filtering**: Automatic filtering of posts to show only user's content
- **Data Validation**: Server-side validation of all post modifications
- **UI Restrictions**: CSS and JavaScript to hide inaccessible elements
- **AJAX Blocking**: Prevents unauthorized AJAX operations

### Configuration

No additional configuration is required. The system automatically:

1. Detects Wild Apricot SSO users with `wa_level_` roles
2. Assigns appropriate capabilities for physician post management
3. Applies access restrictions and UI modifications
4. Enforces content security rules

### Troubleshooting

#### Common Issues:

**Users can't access physician posts:**

- Verify the user role begins with `wa_level_`
- Check that the physicians custom post type exists
- Ensure the user has been assigned to a physician profile

**UI elements still visible:**

- Clear browser cache
- Check for theme conflicts with admin styles
- Verify JavaScript is loading properly

**Permission errors:**

- Confirm user ownership of the physician profile
- Check WordPress user capabilities
- Review error logs for specific capability issues

## Development

### Local Development Setup

1. Clone the repository
2. Install WordPress locally
3. Install ACF plugin
4. Activate the Dalen Find Allergist plugin
5. Configure settings through the admin panel (Find Allergist → Settings)
6. Add Google Maps API key via admin interface
7. Import sample physician data

### Testing

```bash
# Run PHP unit tests
composer test

# Run JavaScript tests
npm test

# PHP Code Sniffer
composer lint
```

### API Testing

```bash
# Test basic search (updated endpoint)
curl "http://localhost/wp-json/dalen/v1/physicians/search?name=test"

# Test distance filtering
curl "http://localhost/wp-json/dalen/v1/physicians/search?postal=M5V3M6&kms=50"

# Test combined search
curl "http://localhost/wp-json/dalen/v1/physicians/search?city=Toronto&oit=true"

# Test validation (should return error)
curl "http://localhost/wp-json/dalen/v1/physicians/search?kms=1000"
```

## Key Functions

### Admin Panel Functions

- **Settings Management**: Centralized configuration through WordPress admin
- **API Key Integration**: `dalen_get_google_maps_api_key()` - Retrieves configured API key
- **Settings Validation**: Real-time validation of admin settings
- **Dashboard Analytics**: Overview of plugin usage and physician data

### Distance Filtering

- `dalen_geocode_postal($postal_code)`: Converts Canadian postal codes to coordinates (renamed for consistency)
- `dalen_haversine_distance($lat1, $lng1, $lat2, $lng2)`: Calculates distance between two points (renamed for consistency)
- `dalen_sanitize_postal($value)`: Sanitizes and validates Canadian postal codes (new function)
- Distance filtering logic in `dalen_physician_search()` function (renamed and enhanced)
- Distance filtering logic in `my_physician_search()` function

### Search Logic

- **Name Search**: Title-based fuzzy matching
- **Meta Field Search**: ACF field queries with LIKE comparisons
- **Post-Query Filtering**: Postal code and distance filtering after initial query
- **Hybrid Approach**: Combines physician-level and organization-level location data
- **Configurable Limits**: Admin-controlled result limits and search radius

## Performance Considerations

### Client-Side Pagination Benefits

- **Instant Page Navigation**: No loading time between pages after initial search
- **Reduced Server Load**: Single API call per search instead of one per page
- **Better User Experience**: Seamless browsing with smooth scrolling
- **Enhanced Map Integration**: All locations visible on map regardless of current page

### Search Optimization

- **Efficient Queries**: Uses WP_Query with optimized meta queries
- **Post-Query Filtering**: Distance calculations only on relevant results
- **Smart Result Caching**: Browser memory caching for instant pagination
- **Geocoding Caching**: Consider implementing geocoding result caching
- **Database Indexing**: Ensure proper indexing on frequently queried meta fields

### Admin Performance

- **Admin Settings Caching**: Plugin settings cached for improved performance
- **API Key Validation**: Efficient validation with caching to reduce API calls

### Memory Management

- **Automatic Cleanup**: Results cleared when starting new searches
- **Efficient Storage**: Optimized data structure for browser memory
- **Mobile Optimization**: Responsive pagination controls for all devices

## Support

For support, feature requests, or bug reports:

- **Developer**: Dalen Design
- **Website**: https://www.dalendesign.com/
- **Email**: [Contact through website]

## License

This plugin is developed for the Canadian Society of Allergy and Clinical Immunology (CSACI). All rights reserved.

## Changelog

### Version 0.99 - SSO Integration & Cleanup

- **REMOVED**: Optional login redirect functionality (`login-redirect.php`) - no longer needed
- **ENHANCED**: Wild Apricot SSO integration with improved access controls
- **IMPROVED**: Plugin architecture and code organization
- **UPDATED**: All version numbers across plugin files for consistency
- **MAINTAINED**: All existing functionality for physician search and management
- **SECURITY**: Continued focus on secure coding practices and input validation
- **DOCUMENTATION**: Updated README to reflect current plugin state

### Version 0.9.0 - Major Code Review & Refactoring

- **REMOVED**: 572 lines of redundant code (shortcodes-backup.php)
- **REFACTORED**: Function naming with proper WordPress prefixes
- **NEW**: Main plugin class with singleton pattern (`Dalen_Find_Allergist_Plugin`)
- **NEW**: Constants file for better organization (`includes/constants.php`)
- **ENHANCED**: REST API endpoint namespace updated to `/wp-json/dalen/v1/`
- **ENHANCED**: Security improvements with comprehensive input validation
- **ENHANCED**: Error handling with user-friendly messages
- **IMPROVED**: Admin settings with DRY principle implementation
- **IMPROVED**: JavaScript architecture with state management pattern
- **IMPROVED**: Code documentation with comprehensive PHPDoc comments
- **IMPROVED**: WordPress coding standards compliance (100%)
- **ADDED**: Plugin activation/deactivation hooks
- **ADDED**: Proper text domain usage for internationalization
- **ADDED**: Input validation for distance parameters (1-500km)
- **ADDED**: Code review summary documentation
- **FIXED**: All function names now use proper `dalen_` prefix
- **PERFORMANCE**: Reduced plugin size by ~30% through code optimization
- **SECURITY**: Enhanced sanitization and escaping throughout

### Version 0.4.0

- **NEW**: Wild Apricot SSO integration with comprehensive user role management
- **NEW**: Granular access controls for wa_level users on physician profiles
- **NEW**: Automated capability assignment and query filtering for secure content access
- **NEW**: UI streamlining with hidden admin elements for simplified user experience
- **NEW**: Content security preventing unauthorized modifications to sensitive fields
- **NEW**: Profile limitation system ensuring one physician profile per wa_level user
- **NEW**: WA_User_Manager class for centralized user management functionality
- **IMPROVED**: Enhanced security with server-side validation and AJAX blocking
- **IMPROVED**: Optimized code structure with OOP approach and reduced redundancy
- **IMPROVED**: Better user experience with clean, focused admin interface for physicians

### Version 0.3.0

- **NEW**: Client-side pagination for instant page navigation
- **NEW**: Smart search detection (new search vs. page navigation)
- **NEW**: Enhanced map integration showing all results regardless of current page
- **NEW**: Responsive pagination controls optimized for mobile devices
- **IMPROVED**: Search performance with single API call per search
- **IMPROVED**: User experience with instant page changes and smooth scrolling
- **IMPROVED**: Memory management with automatic result cleanup
- **IMPROVED**: JavaScript architecture with better error handling

### Version 0.2.0

- **NEW**: Complete admin panel with dashboard, settings, and help documentation
- **NEW**: Centralized Google Maps API key management through admin interface
- **NEW**: Configurable search parameters (results limit, default radius)
- **NEW**: Real-time settings validation and API key testing
- **NEW**: Admin dashboard with quick stats and recent activity
- **NEW**: Comprehensive help documentation with examples
- **IMPROVED**: Enhanced file structure with dedicated admin components
- **IMPROVED**: Better error handling and user feedback
- **IMPROVED**: Responsive admin interface design

### Version 0.1.0

- Initial release
- Basic physician directory functionality
- Distance-based search implementation
- REST API endpoints
- Google Maps integration
- ACF field structure
- Responsive search interface

---

**Note**: This plugin is specifically designed for the Canadian healthcare system and uses Canadian postal code formatting and geographic conventions.
