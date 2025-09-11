# Dalen Find Allergist

A WordPress plugin for the Canadian Society of Allergy and Clinical Immunology (CSACI) that provides a comprehensive allergist and immunologist directory with advanced search capabilities.

## Overview

This plugin helps patients across Canada find allergists and immunologists near them using location-based search, specialty filtering, and interactive maps. Built specifically for healthcare professionals, it includes secure user management, comprehensive admin tools, and seamless Wild Apricot SSO integration.

### Key Features

- **Advanced Search**: Find physicians by name, location, specialty, and distance
- **Interactive Maps**: Google Maps integration with location visualization
- **Distance Filtering**: Search within a specific radius using Canadian postal codes
- **Client-Side Pagination**: Instant page navigation without loading delays
- **Admin Dashboard**: Complete management interface with settings and analytics
- **Wild Apricot SSO**: Secure user authentication with granular access controls
- **Mobile Responsive**: Optimized interface for all devices
- **REST API**: Robust search endpoints with comprehensive validation

## Admin Panel

Access the admin panel through **Find Allergist** in your WordPress admin menu:

### Dashboard

- Overview of physician count and recent activity
- Quick links to add physicians and manage settings
- System status including API key validation

### Settings

- **Google Maps API Key**: Configure and test your API key
- **Search Results Limit**: Control maximum results (1-100, default: 20)
- **Default Search Radius**: Set default distance filter (1-500km, default: 50)

### Help

- Getting started guide
- Shortcode reference with examples
- FAQ and troubleshooting

## Wild Apricot Integration

The plugin includes seamless Wild Apricot SSO integration:

### Features

- **Automatic Role Detection**: Recognizes users with `wa_level_` roles
- **Access Control**: Users can only edit their own physician profiles
- **Content Security**: Prevents unauthorized modifications
- **Streamlined Interface**: Simplified admin for physician users

### What wa_level Users Can Do

- Edit their own physician profile
- Upload media for their profile
- Delete their own profile

### What They Cannot Do

- Access other users' profiles
- Change post author or sensitive fields
- Create multiple profiles
- Access admin areas outside their profile

## Installation

1. **Upload the plugin** to your WordPress plugins directory (`/wp-content/plugins/`)
2. **Activate the plugin** through the WordPress admin Plugins page
3. **Install Advanced Custom Fields (ACF)** if not already installed
4. **Configure plugin settings**:
   - Go to **Find Allergist → Settings** in your WordPress admin
   - Add your Google Maps API key
   - Set search results limit and default radius
5. **Create physician profiles** using the custom post type
6. **Add shortcodes** to your pages to display the search interface

### Required Dependencies

- **WordPress** 5.0 or higher
- **PHP** 7.4 or higher
- **Advanced Custom Fields (ACF)** plugin
- **Google Maps API key** (for geocoding and mapping)

### Optional

- **Wild Apricot SSO** (for user role management)

## Configuration

### Google Maps API Key

The plugin requires a Google Maps API key for location features:

1. Get an API key from the [Google Cloud Console](https://console.cloud.google.com/)
2. Enable these APIs:
   - Geocoding API
   - Maps JavaScript API
   - Places API (optional)
3. Add the key in **Find Allergist → Settings**
4. Test the key using the validation button

### Plugin Settings

Configure plugin behavior through **Find Allergist → Settings**:

- **Search Results Limit**: Maximum results per search (1-100, default: 20)
- **Default Search Radius**: Default distance filter (1-500km, default: 50)
- **Google Maps API Key**: API key for mapping and geocoding

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

**URL**: `GET /wp-json/dalen/v1/physicians/search`

**Parameters**:

- `name` (string): Physician name search (optional)
- `city` (string): City filter (optional)
- `province` (string): Province filter (optional)
- `postal` (string): Postal code for distance filtering (optional)
- `kms` (integer): Search radius in kilometers, 1-500 (optional)
- `prac_pop` (string): Practice population - "Adults" or "Pediatric" (optional)

**Example Requests**:

```bash
# Search by name
GET /wp-json/dalen/v1/physicians/search?name=John%20Smith

# Distance-based search (50km radius)
GET /wp-json/dalen/v1/physicians/search?postal=M5V3M6&kms=50

# City and practice population search
GET /wp-json/dalen/v1/physicians/search?city=Toronto&prac_pop=Pediatric

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
            "institution_phone": "416-813-1500"
          }
        ]
      }
    }
  ]
}
```

The API returns all matching results in a single response. Pagination is handled client-side for improved performance.

## Shortcodes

### Find Allergist Form

```
[find_allergists_form]
```

Displays the search form with all filter options including physician name, practice population, city, province, postal code, and distance radius.

### Find Allergist Results

```
[find_allergists_results]
```

Displays the results container where search results are displayed with client-side pagination.

### Find Allergist Single

```
[find_allergist_single]
```

Displays detailed information for a single physician including practice locations, credentials, specialties, and an interactive map.

### Complete Search Interface

To create a complete search page, combine the form and results shortcodes:

```
[find_allergists_form]
[find_allergists_results]
```

**Key Features**:

- **Instant Pagination**: Navigate between pages without loading delays
- **Smart Search**: Detects new searches vs. page navigation
- **Memory Efficient**: Automatic cleanup of cached results
- **Mobile Responsive**: Optimized pagination controls for mobile devices
- **Interactive Maps**: Google Maps integration showing all physician locations

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
├── README.md                         # Plugin documentation
├── dalen-find-allergist.php          # Main plugin file
├── package.json                      # Frontend build configuration
├── vite.config.js                    # Build tool configuration
├── admin/
│   ├── class-admin.php               # Admin panel controller
│   └── partials/
│       ├── admin-main.php            # Dashboard page
│       ├── admin-settings.php        # Settings page
│       └── admin-help.php            # Help documentation
├── includes/
│   ├── class-plugin.php              # Main plugin class
│   ├── class-asset-loader.php        # Asset management
│   ├── constants.php                 # Plugin constants
│   ├── custom-post.php               # Physician post type
│   ├── custom-role.php               # Wild Apricot user roles
│   ├── rest-api-search.php           # Search API endpoints
│   ├── shortcodes.php                # Shortcode loader
│   └── shortcodes/
│       ├── class-shortcode-base.php  # Base shortcode class
│       ├── class-find-allergist-form.php
│       ├── class-find-allergist-results.php
│       └── class-find-allergist-single.php
├── assets/
│   ├── css/
│   │   ├── admin.css                 # Admin styles
│   │   └── find-allergist-results.css # Frontend styles
│   └── js/
│       ├── admin.js                  # Admin functionality
│       └── find-allergist-results.js # Frontend search
└── scripts/
    └── create-zip.js                 # Build scripts
```

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

## ACF Field Structure

This plugin uses Advanced Custom Fields to store physician data. The main fields include:

### Physician Fields

- `physician_credentials` (Text): Medical credentials (e.g., "MD, FRCPC")
- `practices_oral_immunotherapy_oit` (Select): OIT practice indicator
- `practice_setting` (Select/Multi-select): Practice environment
- `practice_population` (Select): Patient demographics served
- `virtual_careconsultation_services` (Text): Virtual care offerings
- `site_for_clinical_trials` (Text): Clinical trial participation
- `special_areas_of_interest` (Text): Medical specialties
- `treatment_services_offered` (Multi-select): Specific treatments

### Organization Details (Repeater)

- `institutation_name` (Text): Organization/hospital name
- `institution_gmap` (Google Map): Location with coordinates
- `institution_phone` (Text): Contact phone
- `intitution_ext` (Text): Phone extension
- `institution_fax` (Text): Fax number

## Development

### Local Setup

1. Clone the repository to your WordPress plugins directory
2. Install ACF plugin and activate both plugins
3. Configure settings and add your Google Maps API key
4. Create test physician profiles

### Build Process

```bash
# Install dependencies
npm install

# Development build
npm run dev

# Production build
npm run build

# Create distribution package
npm run build:zip
```

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

## Support

For support, feature requests, or bug reports:

- **Developer**: Dalen Design
- **Website**: https://www.dalendesign.com/

## License

This plugin is developed for the Canadian Society of Allergy and Clinical Immunology (CSACI).

## Changelog

### Version 0.99 - Current Release

**Focus**: Wild Apricot SSO integration enhancement and code optimization

- ✨ Enhanced Wild Apricot SSO integration with improved access controls
- 🔧 Improved plugin architecture and code organization
- 🔒 Continued focus on secure coding practices and input validation
- 📝 Updated documentation to reflect current plugin state
- 🧹 Code cleanup and optimization for better performance
- ✅ Maintained all existing functionality for physician search and management

### Previous Versions

- **0.4.0**: Added Wild Apricot SSO integration and user role management
- **0.3.0**: Implemented client-side pagination and enhanced map integration
- **0.2.0**: Added comprehensive admin panel with settings management
- **0.1.0**: Initial release with basic physician directory functionality

---

**Note**: This plugin is specifically designed for the Canadian healthcare system and uses Canadian postal code formatting and geographic conventions.
