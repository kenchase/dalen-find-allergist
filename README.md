# Find an Allergist

A WordPress plugin for CSACI (Canadian Society of Allergy and Clinical Immunology) providing a physician directory with location-based search, interactive maps, and Wild Apricot SSO integration.

## Features

- Location-based search with distance filtering (Canadian postal codes)
- Interactive Google Maps with practice locations
- Client-side pagination for instant results
- Wild Apricot SSO integration with role-based access
- REST API for physician search
- Mobile responsive design

## Admin Panel

Access via **Find Allergist** in WordPress admin:

- **Dashboard**: Overview, quick links, system status
- **Settings**: Google Maps API key configuration
- **Help**: Documentation and troubleshooting

## Wild Apricot Integration

Users with `wa_level_` roles can edit only their own physician profile with restricted access to sensitive settings and other profiles

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Advanced Custom Fields (ACF) plugin
- Google Maps API key (Geocoding API, Maps JavaScript API)

## Installation

1. Upload to `/wp-content/plugins/` and activate
2. Install and activate ACF plugin
3. Go to **Find Allergist → Settings** and add Google Maps API key
4. Create physician profiles
5. Add shortcodes to pages

## REST API

**Endpoint**: `GET /wp-json/faa/v1/physicians/search`

**Parameters**: `name`, `city`, `province`, `postal`, `kms` (1-500), `prac_pop`

**Example**:

```bash
GET /wp-json/faa/v1/physicians/search?postal=M5V3M6&kms=50
```

Returns all results in single response with client-side pagination

## Shortcodes

- `[faa-search-form]` - Search form with filters
- `[faa-search-results]` - Results container with pagination
- `[faa-profile-editor]` - ACF form for physicians to edit their profile

**Complete search page**:

```
[faa-search-form]
[faa-search-results]
```

## ACF Fields

**Physician**: `physician_credentials`

**Organizations (Repeater)**: `organizations_details` containing all org data including a Google Map that contains all location info

## File Structure

```
faa.php                    # Main plugin file
admin/                     # Admin panel and settings
includes/                  # Core classes, custom post type, API
  shortcodes/              # Shortcode implementations
assets/                    # CSS and JavaScript
scripts/                   # Build scripts
```

## Development

```bash
npm install              # Install dependencies
npm run dev              # Development build
npm run build            # Production build
npm run build:zip        # Create distribution package
```

## License

Developed for the Canadian Society of Allergy and Clinical Immunology (CSACI).

## Developer

**Dalen Design** - https://www.dalendesign.com/

---

_Note: Designed for the Canadian healthcare system with Canadian postal code formatting._
