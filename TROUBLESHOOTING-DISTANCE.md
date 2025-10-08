# Distance Calculation Fix - October 8, 2025

## Issue
Distance calculations were showing "0 km" for very close locations (under 50 meters), which is not useful to end users.

**Example:**
- Search: `https://csaci.local/wp-json/faa/v1/physicians/search?postal=K1Y4G9&kms=50`
- Problem: Results showed `"distance_km": 0` for nearby locations

## Root Cause
The Haversine distance calculation was working correctly, but the rounding logic was causing the issue:
- Very small distances (e.g., 0.01 km, 0.04 km) were being rounded with `round($distance_km, 1)`
- This caused values between 0.00 and 0.04 km to display as "0.0" km
- Displaying "0 km" is confusing to users - it suggests no distance at all

## Solution
Modified `includes/rest-api-search.php` (line ~461-466) to ensure a minimum display value:

**Before:**
```php
if ($distance_km !== null && $distance_km !== false) {
    $org['distance_km'] = round($distance_km, 1);
}
```

**After:**
```php
if ($distance_km !== null && $distance_km !== false) {
    // Round to 1 decimal, but ensure minimum of 0.1 km to avoid showing "0 km"
    $rounded_distance = round($distance_km, 1);
    $org['distance_km'] = ($rounded_distance < 0.1 && $distance_km > 0) ? 0.1 : $rounded_distance;
}
```

## Results
- Very close locations (< 100m): Now show **"0.1 km"** instead of "0 km"
- All other distances: Display correctly with 1 decimal place (e.g., 1.3 km, 8.0 km, 18.8 km)
- Users now see meaningful distance information for all search results

## Testing

### Before Fix:
```bash
curl -s "http://csaci.local/wp-json/faa/v1/physicians/search?postal=K1Y4G9&kms=50" | grep distance_km
# Results: "distance_km": 0  ← Problem!
```

### After Fix:
```bash
curl -s "http://csaci.local/wp-json/faa/v1/physicians/search?postal=K1Y4G9&kms=50" | grep distance_km
# Results: 
# "distance_km": 0.1  ← Fixed! Shows minimum meaningful distance
# "distance_km": 3.5
# "distance_km": 5.7
# "distance_km": 8.0
```

## Impact
- **User Experience:** Users now see meaningful distance information for all results
- **Data Accuracy:** The actual calculated distance is preserved; only the display is adjusted
- **Backwards Compatible:** No breaking changes; all existing distances > 0.1 km display as before

## Notes
- The minimum displayed distance is 0.1 km (100 meters)
- Organizations at the exact same coordinates would still show 0 km (this is correct behavior)
- Debug logging has been enhanced to show both calculated and displayed distances when WP_DEBUG is enabled

1. **Clear your browser cache** or open an incognito window

2. **Navigate to the Find an Allergist page** on your site

3. **Open the browser console** (F12 or Cmd+Option+I on Mac)

4. **Perform a search** with a postal code and distance (e.g., "M5V 3A8" with 30 km)

5. **Check the console output** for:
   - `API Response:` - Shows the raw API response
   - `First result organizations:` - Shows the organizations data
   - `Distance debug:` - Shows distance data for each organization
   - Any warning messages about missing or invalid distance values

6. **Check the WordPress debug log** for backend distance calculations:
   ```bash
   tail -f /Users/kenchase/Local\ Sites/csaci/app/public/wp-content/debug.log | grep "FAA:"
   ```

## Expected Data Flow

1. **User submits search** with postal code and distance (kms)
2. **Backend geocodes** the postal code to get lat/lng coordinates
3. **Backend calculates** distance using Haversine formula for each organization
4. **Backend returns** data with `distance_km` field in each organization:
   ```json
   {
     "results": [
       {
         "acf": {
           "organizations_details": [
             {
               "distance_km": 10.5,
               "institutation_name": "..."
             }
           ]
         }
       }
     ]
   }
   ```
5. **Frontend receives** and renders the distance

## Common Issues to Check

### Distance Not Calculated (Backend)
- ✅ Google Maps API key is configured
- ✅ Postal code is valid Canadian format (A1A 1A1)
- ✅ Geocoding succeeds (check debug log)
- ✅ Organizations have valid lat/lng coordinates

### Distance Not Displayed (Frontend)
- ✅ Distance field exists in API response
- ✅ Distance value is a number (not string or null)
- ✅ JavaScript successfully accesses `org.distance_km`
- ✅ CSS doesn't hide the distance field

## Debugging Commands

```bash
# Watch debug log for FAA messages
tail -f /Users/kenchase/Local\ Sites/csaci/app/public/wp-content/debug.log | grep "FAA:"

# Search for a specific postal code in the log
grep "M5V" /Users/kenchase/Local\ Sites/csaci/app/public/wp-content/debug.log | tail -20

# Check if distance_km is being set
grep "Setting distance_km" /Users/kenchase/Local\ Sites/csaci/app/public/wp-content/debug.log | tail -10
```

## Next Steps

Based on the console output and debug logs, we can identify:
1. Whether the distance is being calculated on the backend
2. Whether the distance is in the API response
3. Whether the frontend is accessing it correctly
4. Any validation or type issues with the distance value

Please run a search and share:
- The browser console output
- Any relevant entries from the debug log
- Whether the distance appears on the page or not
