# Dalen Find Allergist

A WordPress plugin for the Canadian Society of Allergy and Clinical Immunology (CSACI) that provides an advanced allergist/immunologist finder with location-based search capabilities.

## Description

This plugin creates a comprehensive directory system for allergists and immunologists across Canada, featuring:

-   **Custom Post Type**: Physician profiles with detailed information
-   **Advanced Search**: Multi-criteria search with name, location, and specialty filters
-   **Distance-Based Filtering**: Find physicians within a specified radius of a postal code
-   **Interactive Maps**: Google Maps integration for location visualization
-   **REST API**: Robust API endpoints for flexible frontend implementations
-   **ACF Integration**: Advanced Custom Fields for rich physician data management
-   **Admin Panel**: Comprehensive admin interface for plugin configuration and management

## Features

### 🔍 Search Capabilities

-   **Name Search**: Search by first name, last name, or full name
-   **Location Search**: Filter by city, province, or postal code
-   **Distance Filtering**: Find physicians within X kilometers of a postal code
-   **Specialty Filtering**: Filter by treatments offered (e.g., OIT - Oral Immunotherapy)
-   **Combined Searches**: Mix and match multiple search criteria
-   **Client-Side Pagination**: Instant page navigation with 20 results per page
-   **Smart Result Management**: Single API call per search with client-side page handling

### 🗺️ Geographic Features

-   **Postal Code Geocoding**: Automatic conversion of Canadian postal codes to coordinates
-   **Distance Calculation**: Haversine formula for accurate distance measurements
-   **Radius Search**: Configurable search radius in kilometers
-   **Map Integration**: Google Maps display with physician locations

### 🏥 Physician Profiles

-   **Comprehensive Information**: Credentials, specialties, contact details
-   **Multiple Locations**: Support for physicians with multiple practice locations
-   **Organization Details**: Hospital/clinic affiliations with full address information
-   **Coordinates Storage**: Latitude/longitude for accurate mapping

### 🔧 Technical Features

-   **REST API Endpoints**: `/wp-json/my/v1/physicians/search`
-   **Client-Side Pagination**: Instant page navigation without additional API calls
-   **Efficient Queries**: Optimized database queries with post-query filtering
-   **Smart Search Caching**: Results cached in browser memory for instant pagination
-   **ACF Integration**: Leverages Advanced Custom Fields for data management
-   **Role Management**: Custom user roles for physician management
-   **Responsive Design**: Mobile-friendly search interface with responsive pagination
-   **Admin Dashboard**: Complete administrative interface with settings management
-   **API Key Management**: Secure Google Maps API key configuration
-   **Plugin Settings**: Configurable search parameters and display options

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

-   **Quick Stats**: Overview of total physicians and recent activity
-   **Recent Activity**: Latest physician profile updates
-   **Quick Actions**: Direct links to add physicians and manage settings
-   **System Status**: API key validation and plugin health checks

### Settings

-   **Google Maps API Key**: Secure configuration for mapping functionality
-   **Search Results Limit**: Control maximum results returned (1-100)
-   **Default Search Radius**: Set default distance filter in kilometers (1-500km)
-   **Settings Validation**: Real-time form validation and API key testing

### Help Documentation

-   **Getting Started Guide**: Step-by-step setup instructions
-   **Shortcode Reference**: Complete documentation for all available shortcodes
-   **FAQ Section**: Common questions and troubleshooting
-   **API Documentation**: REST endpoint usage examples
-   **Best Practices**: Optimization tips and recommendations

## Requirements

-   WordPress 5.0 or higher
-   PHP 7.4 or higher
-   Advanced Custom Fields (ACF) plugin
-   Google Maps API key (for geocoding and mapping features)

## API Documentation

### Search Endpoint

**URL**: `GET /wp-json/my/v1/physicians/search`

**Parameters**:

-   `fname` (string): First name search
-   `lname` (string): Last name search
-   `city` (string): City filter
-   `province` (string): Province filter
-   `postal` (string): Postal code (for distance filtering)
-   `kms` (integer): Search radius in kilometers
-   `oit` (boolean): Filter for OIT specialists

**Example Requests**:

```bash
# Search by name
GET /wp-json/my/v1/physicians/search?fname=John&lname=Smith

# Distance-based search (50km radius from Toronto downtown)
GET /wp-json/my/v1/physicians/search?postal=M5V3M6&kms=50

# Combined search
GET /wp-json/my/v1/physicians/search?city=Toronto&oit=true

# Province-wide search
GET /wp-json/my/v1/physicians/search?province=Ontario
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

-   Search form with all filter options
-   Results container with client-side pagination
-   Interactive Google Maps integration
-   Responsive design for mobile devices

**Features**:

-   **Instant Pagination**: Navigate between pages without loading delays
-   **Smart Search**: Detects new searches vs. page navigation
-   **Memory Efficient**: Automatic cleanup of cached results
-   **Mobile Responsive**: Optimized pagination controls for mobile devices

## ACF Field Structure

### Physician Fields

-   `city` (Text): Physician's primary city
-   `province` (Text): Physician's primary province
-   `postal` (Text): Physician's primary postal code
-   `credentials` (Text): Medical credentials
-   `practices_oral_immunotherapy_oit` (Checkbox): OIT specialization
-   `immunologist_online_search_tool` (Select): Search visibility

### Organization Details (Repeater)

-   `institutation_name` (Text): Organization name
-   `address_line_1` (Text): Street address
-   `address_line_2` (Text): Address line 2
-   `institution_city` (Text): Organization city
-   `institution_state` (Text): Organization province/state
-   `institution_zipcode` (Text): Organization postal code
-   `institution_phone` (Text): Phone number
-   `institution_fax` (Text): Fax number
-   `institution_latitude` (Number): Latitude coordinate
-   `institution_longitude` (Number): Longitude coordinate
-   `institution_gmap` (Google Map): Map field (optional)

## File Structure

```
dalen-find-allergist/
├── README.md
├── dalen-find-allergist.php          # Main plugin file
├── admin/
│   ├── class-admin.php               # Admin panel controller
│   └── partials/
│       ├── admin-main.php            # Dashboard template
│       ├── admin-settings.php        # Settings page template
│       └── admin-help.php            # Help documentation template
├── includes/
│   ├── custom-post.php               # Physician post type
│   ├── custom-role.php               # User role management
│   ├── rest-api-search.php           # Search API endpoints
│   ├── shortcodes.php                # Frontend shortcodes
│   └── login-redirect.php            # User management
├── assets/
│   ├── css/
│   │   ├── admin.css                 # Admin panel styles
│   │   └── find-allergist-results.css # Frontend styles
│   └── js/
│       ├── admin.js                  # Admin panel functionality
│       └── find-allergist-results.js # Frontend JavaScript
├── tests/                            # Unit tests
└── .circleci/                        # CI/CD configuration
```

## Configuration

### Google Maps API Key

The plugin requires a Google Maps API key for geocoding and mapping features. Configure this through the admin panel:

1. Navigate to **Find Allergist → Settings** in WordPress admin
2. Enter your Google Maps API key in the provided field
3. Click **Test API Key** to validate the key
4. Save the settings

Alternatively, you can still configure it programmatically in `dalen-find-allergist.php`:

```php
function my_acf_google_map_api($api) {
    // Get API key from admin settings
    $api_key = dalen_get_google_maps_api_key();
    if (!empty($api_key)) {
        $api['key'] = $api_key;
    }
    return $api;
}
```

**Required Google APIs**:

-   Geocoding API
-   Maps JavaScript API
-   Places API (optional)

### Plugin Settings

Configure the plugin behavior through **Find Allergist → Settings**:

-   **Search Results Limit**: Maximum results per search (default: 20, range: 1-100)
-   **Default Search Radius**: Default distance filter in kilometers (default: 50km, range: 1-500km)
-   **Google Maps API Key**: API key for mapping and geocoding functionality

### Search Configuration

Advanced search behavior can still be customized in `includes/rest-api-search.php`:

-   **Search criteria requirements**: Adjust minimum search criteria
-   **Custom filters**: Add additional search parameters
-   **Result formatting**: Modify API response structure

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
# Test basic search
curl "http://localhost/wp-json/my/v1/physicians/search?fname=test"

# Test distance filtering
curl "http://localhost/wp-json/my/v1/physicians/search?postal=M5V3M6&kms=50"

# Test combined search
curl "http://localhost/wp-json/my/v1/physicians/search?city=Toronto&oit=true"
```

## Key Functions

### Admin Panel Functions

-   **Settings Management**: Centralized configuration through WordPress admin
-   **API Key Integration**: `dalen_get_google_maps_api_key()` - Retrieves configured API key
-   **Settings Validation**: Real-time validation of admin settings
-   **Dashboard Analytics**: Overview of plugin usage and physician data

### Distance Filtering

-   `my_geocode_postal($postal_code)`: Converts Canadian postal codes to coordinates
-   `my_haversine_distance($lat1, $lng1, $lat2, $lng2)`: Calculates distance between two points
-   Distance filtering logic in `my_physician_search()` function

### Search Logic

-   **Name Search**: Title-based fuzzy matching
-   **Meta Field Search**: ACF field queries with LIKE comparisons
-   **Post-Query Filtering**: Postal code and distance filtering after initial query
-   **Hybrid Approach**: Combines physician-level and organization-level location data
-   **Configurable Limits**: Admin-controlled result limits and search radius

## Performance Considerations

### Client-Side Pagination Benefits

-   **Instant Page Navigation**: No loading time between pages after initial search
-   **Reduced Server Load**: Single API call per search instead of one per page
-   **Better User Experience**: Seamless browsing with smooth scrolling
-   **Enhanced Map Integration**: All locations visible on map regardless of current page

### Search Optimization

-   **Efficient Queries**: Uses WP_Query with optimized meta queries
-   **Post-Query Filtering**: Distance calculations only on relevant results
-   **Smart Result Caching**: Browser memory caching for instant pagination
-   **Geocoding Caching**: Consider implementing geocoding result caching
-   **Database Indexing**: Ensure proper indexing on frequently queried meta fields

### Admin Performance

-   **Admin Settings Caching**: Plugin settings cached for improved performance
-   **API Key Validation**: Efficient validation with caching to reduce API calls

### Memory Management

-   **Automatic Cleanup**: Results cleared when starting new searches
-   **Efficient Storage**: Optimized data structure for browser memory
-   **Mobile Optimization**: Responsive pagination controls for all devices

## Support

For support, feature requests, or bug reports:

-   **Developer**: Dalen Design
-   **Website**: https://www.dalendesign.com/
-   **Email**: [Contact through website]

## License

This plugin is developed for the Canadian Society of Allergy and Clinical Immunology (CSACI). All rights reserved.

## Changelog

### Version 1.1.0

-   **NEW**: Client-side pagination for instant page navigation
-   **NEW**: Smart search detection (new search vs. page navigation)
-   **NEW**: Enhanced map integration showing all results regardless of current page
-   **NEW**: Responsive pagination controls optimized for mobile devices
-   **IMPROVED**: Search performance with single API call per search
-   **IMPROVED**: User experience with instant page changes and smooth scrolling
-   **IMPROVED**: Memory management with automatic result cleanup
-   **IMPROVED**: JavaScript architecture with better error handling

### Version 1.0.0

-   **NEW**: Complete admin panel with dashboard, settings, and help documentation
-   **NEW**: Centralized Google Maps API key management through admin interface
-   **NEW**: Configurable search parameters (results limit, default radius)
-   **NEW**: Real-time settings validation and API key testing
-   **NEW**: Admin dashboard with quick stats and recent activity
-   **NEW**: Comprehensive help documentation with examples
-   **IMPROVED**: Enhanced file structure with dedicated admin components
-   **IMPROVED**: Better error handling and user feedback
-   **IMPROVED**: Responsive admin interface design

### Version 0.1.0

-   Initial release
-   Basic physician directory functionality
-   Distance-based search implementation
-   REST API endpoints
-   Google Maps integration
-   ACF field structure
-   Responsive search interface

---

**Note**: This plugin is specifically designed for the Canadian healthcare system and uses Canadian postal code formatting and geographic conventions.
