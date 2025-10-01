/**
 * Find Allergist Results JavaScript
 *
 * Handles search form submission, pagination, and map functionality
 * for the Dalen Find Allergist plugin.
 *
 * @package Dalen_Find_Allergist
 * @since 1.0.0
 */

// Constants
const ENDPOINT = '/wp-json/dalen/v1/physicians/search';
const RESULTS_PER_PAGE = 20;

// Validation constants
const VALIDATION_DEBOUNCE_DELAY = 300; // milliseconds
const POSTAL_CODE_MAX_LENGTH = 6; // Without space
const POSTAL_CODE_REGEX = /^[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d$/;

// Global state management
const AppState = {
  allergistMap: null,
  mapMarkers: [],
  mapInfoWindow: null,
  orgMarkerMap: new Map(),
  searchController: null,
  currentSearchData: null,
  currentPage: 1,
  allSearchResults: [],
  elements: {},
  validationTimeout: null, // For debouncing validation
};

/**
 * Initialize the application
 */
document.addEventListener('DOMContentLoaded', function () {
  initializeApp();
});

/**
 * Initialize app and cache DOM elements
 */
function initializeApp() {
  // Cache DOM elements to avoid repeated queries
  AppState.elements = {
    form: document.getElementById('allergist-search-form'),
    searchBtn: document.getElementById('btn-search'),
    clearBtn: document.getElementById('btn-clear'),
    results: document.getElementById('results'),
    postalInput: document.getElementById('phy_postal'),
    rangeSelect: document.getElementById('phy_kms'),
    postalError: document.getElementById('postal-error'),
    rangeHelpText: document.getElementById('range-help-text'),
  };

  bindEventHandlers();
  initializeRangeFieldState();
}

/**
 * Bind event handlers to DOM elements
 */
function bindEventHandlers() {
  const { form, searchBtn, clearBtn, postalInput } = AppState.elements;

  // Handle form submission
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      handleSearchSubmit();
    });
  }

  // Handle search button click
  if (searchBtn) {
    searchBtn.addEventListener('click', function (e) {
      e.preventDefault();
      handleSearchSubmit();
    });
  }

  // Handle clear button click
  if (clearBtn) {
    clearBtn.addEventListener('click', function (e) {
      e.preventDefault();
      clearForm();
    });
  }

  // Handle postal code input changes
  if (postalInput) {
    postalInput.addEventListener('input', function () {
      formatPostalCodeInput();

      // Debounce validation to improve performance
      if (AppState.validationTimeout) {
        clearTimeout(AppState.validationTimeout);
      }

      AppState.validationTimeout = setTimeout(() => {
        validatePostalCode();
        toggleRangeField();
      }, VALIDATION_DEBOUNCE_DELAY);
    });

    // Also validate on blur for better UX (immediate)
    postalInput.addEventListener('blur', function () {
      // Clear any pending timeout and validate immediately
      if (AppState.validationTimeout) {
        clearTimeout(AppState.validationTimeout);
        AppState.validationTimeout = null;
      }
      validatePostalCode();
      toggleRangeField();
    });
  }

  // Add event listener for clicks using event delegation
  document.addEventListener('click', handleDocumentClick);
}

/**
 * Clear the form and reset state
 */
function clearForm() {
  const { form } = AppState.elements;

  form?.reset();

  // Reset pagination state
  AppState.currentSearchData = null;
  AppState.currentPage = 1;
  AppState.allSearchResults = [];

  // Clear results
  setResultsHTML('');

  // Reset validation states
  clearPostalValidation();

  // Reset range field state
  toggleRangeField();
}

/**
 * Initialize the range field state on page load
 */
function initializeRangeFieldState() {
  toggleRangeField();
}

/**
 * Format postal code input as user types (add space after 3rd character)
 */
function formatPostalCodeInput() {
  const { postalInput } = AppState.elements;

  if (!postalInput) {
    return;
  }

  try {
    let value = postalInput.value.replace(/\s/g, '').toUpperCase(); // Remove spaces and convert to uppercase

    // Limit to maximum allowed characters
    if (value.length > POSTAL_CODE_MAX_LENGTH) {
      value = value.substring(0, POSTAL_CODE_MAX_LENGTH);
    }

    // Add space after 3rd character if we have more than 3 characters
    if (value.length > 3) {
      value = value.substring(0, 3) + ' ' + value.substring(3);
    }

    // Update the input value
    postalInput.value = value;
  } catch (error) {
    console.warn('Error formatting postal code input:', error);
  }
}

/**
 * Validate postal code input and show/hide error messages
 */
function validatePostalCode() {
  const { postalInput, postalError } = AppState.elements;

  if (!postalInput || !postalError) {
    console.warn('Postal validation elements not found');
    return false;
  }

  try {
    const postalValue = postalInput.value.trim();

    // If empty, clear validation state
    if (!postalValue) {
      clearPostalValidation();
      return true; // Empty is allowed
    }

    // Check if valid
    const isValid = isValidPostalCode(postalValue);

    if (isValid) {
      // Valid postal code
      postalInput.classList.remove('error');
      postalInput.classList.add('valid');
      postalError.classList.remove('show');
      postalError.style.display = 'none';
      return true;
    } else {
      // Invalid postal code
      postalInput.classList.remove('valid');
      postalInput.classList.add('error');
      postalError.classList.add('show');
      postalError.style.display = 'block';
      return false;
    }
  } catch (error) {
    console.error('Error during postal code validation:', error);
    // On error, clear validation state to avoid broken UI
    clearPostalValidation();
    return false;
  }
}

/**
 * Clear postal code validation state
 */
function clearPostalValidation() {
  const { postalInput, postalError } = AppState.elements;

  if (postalInput) {
    postalInput.classList.remove('error', 'valid');
  }

  if (postalError) {
    postalError.classList.remove('show');
    postalError.style.display = 'none';
  }
}

/**
 * Show loading overlay with spinner
 */
function showLoadingOverlay() {
  // Remove existing overlay if present
  hideLoadingOverlay();

  const overlay = document.createElement('div');
  overlay.id = 'search-loading-overlay';
  overlay.innerHTML = `
    <div class="search-loading-content">
      <div class="search-spinner"></div>
      <p>Searching for allergists...</p>
    </div>
  `;

  // Add CSS styles
  overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
  `;

  const content = overlay.querySelector('.search-loading-content');
  content.style.cssText = `
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 300px;
  `;

  const spinner = overlay.querySelector('.search-spinner');
  spinner.style.cssText = `
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007cba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem auto;
  `;

  // Add CSS animation
  if (!document.getElementById('search-spinner-styles')) {
    const style = document.createElement('style');
    style.id = 'search-spinner-styles';
    style.textContent = `
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    `;
    document.head.appendChild(style);
  }

  document.body.appendChild(overlay);
}

/**
 * Hide loading overlay
 */
function hideLoadingOverlay() {
  const overlay = document.getElementById('search-loading-overlay');
  if (overlay) {
    overlay.remove();
  }
}

/**
 * Scroll to results container smoothly
 */
function scrollToResults() {
  const resultsContainer = document.getElementById('results');
  if (resultsContainer) {
    resultsContainer.scrollIntoView({
      behavior: 'smooth',
      block: 'start',
    });
  }
}

/**
 * Handle search form submission - only makes API call for new searches
 */
async function handleSearchSubmit(page = 1) {
  // Validate form before proceeding
  if (!validateForm()) {
    return; // Stop submission if validation fails
  }

  // Get all form data
  const formData = getAllFormData();

  // Check if this is a new search (different parameters)
  const isNewSearch = !AppState.currentSearchData || JSON.stringify(AppState.currentSearchData) !== JSON.stringify(formData);

  if (isNewSearch) {
    // Show loading overlay for new searches
    showLoadingOverlay();

    // Abort previous request if still pending
    if (AppState.searchController) {
      AppState.searchController.abort();
    }

    // Create new abort controller for this request
    AppState.searchController = new AbortController();

    AppState.currentSearchData = formData;
    AppState.currentPage = 1;

    // Build query params (send only what's filled)
    const params = new URLSearchParams();
    if (formData.phy_name) params.set('name', formData.phy_name);
    if (formData.phy_city) params.set('city', formData.phy_city);
    if (formData.phy_province) params.set('province', formData.phy_province);
    if (formData.phy_postal) params.set('postal', normalizePostal(formData.phy_postal));
    if (formData.phy_kms) params.set('kms', formData.phy_kms);
    if (formData.phy_prac_pop) params.set('prac_pop', formData.phy_prac_pop);

    // UI: basic loading state
    setResultsHTML('<p>Searching…</p>');

    try {
      const nonce = window.wpApiSettings?.nonce;
      const res = await fetch(`${ENDPOINT}?${params.toString()}`, {
        headers: nonce ? { 'X-WP-Nonce': nonce } : undefined,
        signal: AppState.searchController.signal,
      });
      if (!res.ok) {
        throw new Error(`REST request failed (${res.status})`);
      }

      const data = await res.json();

      // Store all results for client-side pagination
      AppState.allSearchResults = data.results || [];
      renderPaginatedResults(1, true); // isNewSearch = true

      // Hide overlay and scroll to results
      hideLoadingOverlay();
      setTimeout(() => scrollToResults(), 100);
    } catch (err) {
      // Hide overlay on error
      hideLoadingOverlay();

      // Don't show error for aborted requests
      if (err.name === 'AbortError') {
        return;
      }

      console.error('Search error:', err);
      setResultsHTML('<p role="alert">Sorry, something went wrong. Please try again.</p>');
      AppState.allSearchResults = [];

      // Still scroll to results to show error
      setTimeout(() => scrollToResults(), 100);
    } finally {
      AppState.searchController = null;
    }
  } else {
    // Same search - just navigate to different page (client-side)
    AppState.currentPage = page;
    renderPaginatedResults(page, false); // isNewSearch = false
  }
}

/**
 * Pre-analyze results to determine which organizations will have markers
 * @param {Array} results - Array of search results
 * @returns {Set} Set of organization IDs that will have markers
 */
function getOrganizationsWithMarkers(results) {
  const orgIdsWithMarkers = new Set();

  for (const item of results) {
    if (!item.acf?.organizations_details) continue;

    for (const org of item.acf.organizations_details) {
      const lat = parseFloat(org.institution_gmap?.lat);
      const lng = parseFloat(org.institution_gmap?.lng);
      const orgName = org.institutation_name || '';

      if (isNaN(lat) || isNaN(lng) || !orgName) continue;

      // This organization will have a marker
      const orgId = `org-${generateOrgId(org, item.title)}`;
      orgIdsWithMarkers.add(orgId);
    }
  }

  return orgIdsWithMarkers;
}

/**
 * Render paginated results using client-side pagination
 * @param {number} page - Page number to render
 * @param {boolean} isNewSearch - Whether this is a new search (requires map initialization)
 */
function renderPaginatedResults(page, isNewSearch = false) {
  if (!Array.isArray(AppState.allSearchResults) || AppState.allSearchResults.length === 0) {
    if (isNewSearch) {
      // For new searches, set up the complete structure even with no results
      const fullResultParts = ['<div id="allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px; display: none;"></div>', '<div id="search-results-content"><p>No matches found.</p></div>'];
      setResultsHTML(fullResultParts.join(''));
    } else {
      // For pagination, just update the content area
      setSearchResultsContentHTML('<p>No matches found.</p>');
    }
    return;
  }

  // Calculate pagination
  const totalResults = AppState.allSearchResults.length;
  const totalPages = Math.ceil(totalResults / RESULTS_PER_PAGE);
  const startIndex = (page - 1) * RESULTS_PER_PAGE;
  const endIndex = Math.min(startIndex + RESULTS_PER_PAGE, totalResults);
  const currentPageResults = AppState.allSearchResults.slice(startIndex, endIndex);

  // Pre-analyze which organizations will have markers (using all results, not just current page)
  const orgIdsWithMarkers = getOrganizationsWithMarkers(AppState.allSearchResults);

  // For new searches, ensure we have a map container
  if (isNewSearch) {
    // Create the complete layout with map container
    const fullResultParts = ['<div id="allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px;"></div>', '<div id="search-results-content"></div>'];
    setResultsHTML(fullResultParts.join(''));
  }

  // Build the paginated content (without map container)
  const resultParts = [`<div class="search-results-info">`, `<p>Found ${totalResults} result${totalResults === 1 ? '' : 's'}${totalResults > RESULTS_PER_PAGE ? ` - showing ${startIndex + 1} to ${endIndex}` : ''}</p>`, `</div>`];

  // Add pagination controls at the top if there are multiple pages
  if (totalPages > 1) {
    const prevPage = page > 1 ? page - 1 : null;
    const nextPage = page < totalPages ? page + 1 : null;
    resultParts.push(generatePaginationHTML(page, totalPages, prevPage, nextPage));
  }

  resultParts.push('<div class="far-items">');

  for (const item of currentPageResults) {
    const city = item.acf?.city || '';
    const prov = item.acf?.province || '';
    const credentials = item.acf?.credentials || '';

    // Prepare physician info for organizations
    const physicianInfo = {
      title: item.title,
      credentials,
      link: item.link,
    };

    // Generate organizations HTML using separate function
    const organizationsHTML = generateOrganizationsHTML(item.acf?.organizations_details, physicianInfo, orgIdsWithMarkers);

    if (organizationsHTML) {
      resultParts.push(`<div class="far-item">${organizationsHTML}</div>`);
    }
  }

  resultParts.push('</div>');

  // Add pagination controls at the bottom if there are multiple pages
  if (totalPages > 1) {
    const prevPage = page > 1 ? page - 1 : null;
    const nextPage = page < totalPages ? page + 1 : null;
    resultParts.push(generatePaginationHTML(page, totalPages, prevPage, nextPage));
  }

  setSearchResultsContentHTML(resultParts.join(''));

  // Initialize the map only for new searches, not for pagination
  if (isNewSearch) {
    setTimeout(() => initializeMap(AppState.allSearchResults), 100);
  }
}

/**
 * Initialize Google Map with organization markers
 * @param {Array} results - Array of search results containing organizations
 */
function initializeMap(results) {
  const mapContainer = document.getElementById('allergist-map');
  if (!mapContainer || !window.google) {
    return;
  }

  // Clean up existing map resources
  cleanupMap();

  // Initialize map components
  AppState.mapMarkers = [];
  AppState.orgMarkerMap.clear(); // Clear the organization-marker mapping
  AppState.mapInfoWindow = new google.maps.InfoWindow();
  const bounds = new google.maps.LatLngBounds();
  const locations = [];

  // Single loop to collect locations and calculate bounds
  for (const item of results) {
    if (!item.acf?.organizations_details) continue;

    for (const org of item.acf.organizations_details) {
      const lat = parseFloat(org.institution_gmap?.lat);
      const lng = parseFloat(org.institution_gmap?.lng);
      const orgName = org.institutation_name || '';

      if (isNaN(lat) || isNaN(lng) || !orgName) continue;

      // Create a unique org ID based on organization data to ensure consistency
      const orgId = `org-${generateOrgId(org, item.title)}`;
      const location = {
        lat,
        lng,
        title: orgName,
        address: org.institution_gmap?.name || '',
        cityState: [org.institution_gmap?.city, org.institution_gmap?.state].filter(Boolean).join(', '),
        physicianName: item.title,
        physicianCredentials: item.acf?.credentials || '',
        orgId: orgId,
      };

      locations.push(location);
      bounds.extend(new google.maps.LatLng(lat, lng));
    }
  }

  if (locations.length === 0) {
    mapContainer.innerHTML = '<p>No locations available for mapping.</p>';
    return;
  }

  // Initialize map
  AppState.allergistMap = new google.maps.Map(mapContainer, {
    zoom: 10,
    center: bounds.getCenter(),
    mapTypeId: google.maps.MapTypeId.ROADMAP,
  });

  // Add markers efficiently
  for (const location of locations) {
    const marker = new google.maps.Marker({
      position: { lat: location.lat, lng: location.lng },
      map: AppState.allergistMap,
      title: location.title,
      icon: {
        url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
        scaledSize: new google.maps.Size(32, 32),
      },
    });

    // Add click listener for info window
    marker.addListener('click', () => {
      const content = createInfoWindowContent(location);
      AppState.mapInfoWindow.setContent(content);
      AppState.mapInfoWindow.open(AppState.allergistMap, marker);
    });

    AppState.mapMarkers.push(marker);

    // Store the mapping between organization ID and marker
    AppState.orgMarkerMap.set(location.orgId, marker);
  }

  // Fit map to show all markers
  if (locations.length === 1) {
    AppState.allergistMap.setCenter(bounds.getCenter());
    AppState.allergistMap.setZoom(15);
  } else {
    AppState.allergistMap.fitBounds(bounds);
  }
}

/**
 * Create HTML content for map info window
 * @param {Object} location - Location data
 * @returns {string} HTML content
 */
function createInfoWindowContent(location) {
  const parts = [`<div class="map-info-window">`, `<h4>${escapeHTML(location.title)}</h4>`];

  if (location.address) {
    parts.push(`<p><strong>Address:</strong> ${escapeHTML(location.address)}</p>`);
  }

  if (location.cityState) {
    parts.push(`<p><strong>Location:</strong> ${escapeHTML(location.cityState)}</p>`);
  }

  if (location.physicianName) {
    parts.push(`<p><strong>Physician:</strong> ${escapeHTML(location.physicianName)}${location.physicianCredentials ? `, ${escapeHTML(location.physicianCredentials)}` : ''}</p>`);
  }

  parts.push(`</div>`);
  return parts.join('');
}

/**
 * Generate a consistent organization ID based on organization data
 * @param {Object} org - Organization data
 * @param {string} physicianName - Physician name for uniqueness
 * @returns {string} Unique organization ID
 */
function generateOrgId(org, physicianName) {
  // Create a simple hash from organization name, address, and physician name
  const key = `${org.institutation_name || ''}-${org.institution_gmap?.name || ''}-${physicianName || ''}`;
  let hash = 0;
  for (let i = 0; i < key.length; i++) {
    const char = key.charCodeAt(i);
    hash = (hash << 5) - hash + char;
    hash = hash & hash; // Convert to 32-bit integer
  }
  return Math.abs(hash).toString();
}

/**
 * Clean up existing map resources
 */
function cleanupMap() {
  if (AppState.mapMarkers) {
    for (const marker of AppState.mapMarkers) {
      marker.setMap(null);
    }
    AppState.mapMarkers = [];
  }

  if (AppState.mapInfoWindow) {
    AppState.mapInfoWindow.close();
  }

  // Clear the organization-marker mapping
  AppState.orgMarkerMap.clear();

  AppState.allergistMap = null;
}

/**
 * Generate organizations HTML
 * @param {Array} organizations - Organizations data
 * @param {Object} physicianInfo - Physician information
 * @param {Set} orgIdsWithMarkers - Set of organization IDs that will have markers
 * @returns {string} HTML string
 */
function generateOrganizationsHTML(organizations, physicianInfo, orgIdsWithMarkers) {
  if (!Array.isArray(organizations) || organizations.length === 0) {
    return '';
  }

  const parts = [];

  // Physician header
  parts.push(`
		<div class="far-physician-info">
			<h3 class="far-physician-name">
			  ${escapeHTML(physicianInfo.title)}
				${physicianInfo.credentials ? `, ${escapeHTML(physicianInfo.credentials)}` : ''}
			</h3>
		</div>
	`);

  // Organizations container
  parts.push(`<div class="far-orgs">`);

  for (const org of organizations) {
    // Generate the same org ID used in map initialization
    const orgId = `org-${generateOrgId(org, physicianInfo.title)}`;
    const orgName = org.institutation_name || 'Organization';
    const address = org.institution_gmap?.name || '';
    const city = org.institution_gmap?.city || '';
    const state = org.institution_gmap?.state || '';
    const postalCode = org.institution_gmap?.post_code || '';
    const phone = org.institution_phone || '';
    const phone_ext = org.intitution_ext || '';
    const fax = org.institution_fax || '';
    const distance = org.distance_km;

    // Adding New Fields
    const practiceSetting = org.institution_practice_setting || '';
    const practicesOIT = org.institution_oit_practices || '';
    const specialAreasOfInterest = org.institution_special_areas_of_interest || '';
    const treatmentServicesOffered = org.institution_treatment_services_offered || [];
    const treatmentServicesOfferedOther = org.institution_treatment_services_offered_other || '';
    const siteForClinicalTrials = org.institution_site_for_clinical_trials || '';
    const consultationServices = org.institution_consultation_services || [];
    const practicePopulation = org.institution_practice_population || '';

    // Check if this organization will have a marker on the map
    const hasMapMarker = orgIdsWithMarkers.has(orgId);

    parts.push(`<div class="far-org" id="${orgId}">`);

    // Org Summary section
    parts.push(`<div class="far-org__summary">`);
    parts.push(`<h4 class="far-org-title">${escapeHTML(orgName)}</h4>`);

    parts.push(`<p class="far-org-address">`);

    if (address) {
      parts.push(`<span class="far-org-address_street"> ${escapeHTML(address)}</span>`);
    }

    if (city || state) {
      const cityStateParts = [city, state].filter(Boolean);
      parts.push(`<span class="far-org-address_city-state">, ${escapeHTML(cityStateParts.join(', '))}</span>`);
    }

    if (postalCode) {
      parts.push(`<span class="far-org-address_postal">, ${escapeHTML(postalCode)}</span>`);
    }

    parts.push(`</p>`);

    if (phone) {
      parts.push(`<p class="far-org-phone"><strong aria-label="Phone">T:</strong> ${escapeHTML(phone)}</p>`);
    } else {
      parts.push(`<p class="far-org-phone far-org-phone--no-phone">Not available</p>`);
    }

    parts.push(`<button class="far-org-view-more">View More</button>`);

    parts.push(`</div>`); // Close org summary

    // Org Body section
    parts.push(`<div class="far-org__body far-org__body--hidden">`);

    // Org Body section  Row 1
    parts.push(`<div class="far-org__body-grid-cell">`);
    if (practiceSetting) {
      parts.push(`<div class="far-org__body-grid-item"><span class="far-org__body-grid-item-label">Practice Setting(s):</span> ${escapeHTML(practiceSetting)}</div>`);
    }
    if (practicesOIT) {
      parts.push(`<div class="far-org__body-grid-item"><span class="far-org__body-grid-item-label">Practices OIT:</span> ${escapeHTML(practicesOIT)}</div>`);
    }
    if (practicePopulation) {
      parts.push(`<div class="far-org__body-grid-item"><span class="far-org__body-grid-item-label">Practices Population:</span> ${escapeHTML(practicePopulation)}</div>`);
    }
    parts.push(`</div>`);
    parts.push(`<div class="far-org__body-grid-cell">`);
    if (specialAreasOfInterest) {
      parts.push(`<div class="far-org__body-grid-item"><span class="far-org__body-grid-item-label far-org__body-grid-item-label--lb">Special Areas of Interest:</span> ${escapeHTML(specialAreasOfInterest)}</div>`);
    }
    parts.push(`</div>`);

    // Org Body section Row 2
    parts.push(`<div class="far-org__body-grid-cell far-org__body-grid-cell--spacer">`);
    if (consultationServices && consultationServices.length > 0) {
      parts.push(`<div class="far-org__body-grid-item"><span class="far-org__body-grid-item-label far-org__body-grid-item-label--lb">Consultation Services:</span> ${escapeHTML(consultationServices.join(', '))}</div>`);
    }
    if (siteForClinicalTrials) {
      parts.push(`<div class="far-org__body-grid-item"><span class="far-org__body-grid-item-label far-org__body-grid-item-label--lb">Site for Clinical Trials:</span> ${escapeHTML(siteForClinicalTrials)}</div>`);
    }
    parts.push(`</div>`);
    parts.push(`<div class="far-org__body-grid-cell far-org__body-grid-cell--spacer">`);
    if (treatmentServicesOffered && treatmentServicesOffered.length > 0) {
      const treatmentServicesList = treatmentServicesOffered.map((service) => `<li class="far-org__body-grid-item_list-item">${escapeHTML(service)}</li>`).join('');
      parts.push(`<div class="far-org__body-grid-item"><span class="far-org__body-grid-item-label far-org__body-grid-item-label--lb">Treatment Services Offered:</span> <ul class="far-org__body-grid-item_list">${treatmentServicesList}</ul></div>`);
    }
    if (treatmentServicesOfferedOther) {
      parts.push(`<div class="far-org__body-grid-item"><span class="far-org__body-grid-item-label far-org__body-grid-item-label--lb">Special Areas of Interest:</span> ${escapeHTML(treatmentServicesOfferedOther)}</div>`);
    }
    parts.push(`</div>`);

    parts.push(`</div>`); // Close org body
    parts.push(`</div>`); // Close org
  }

  parts.push(`</div>`); // Close orgs
  return parts.join('');

  // Don't know where to include this yet
  // if (distance !== undefined) {
  //   parts.push(`<span class="far-org-list-item"><strong>Distance:</strong> ${distance} km</span>`);
  // }

  // Don't know where to include this yet
  // Add "Show on map" link if this organization has a marker
  // if (hasMapMarker) {
  //   parts.push(`<li class="far-org-list-item far-org-list-item--map-link"><a href="#" class="show-on-map-link" data-org-id="${orgId}">📍 Show on map</a></li>`);
  // }
}

/**
 * Get all form data as an object
 * @returns {Object} Form data object
 */
function getAllFormData() {
  // Cache field elements to avoid repeated DOM queries
  const fields = {
    name: document.getElementById('phy_name'),
    city: document.getElementById('phy_city'),
    postal: document.getElementById('phy_postal'),
    province: document.getElementById('phy_province'),
    kms: document.getElementById('phy_kms'),
    prac_pop: document.getElementById('phy_prac_pop'),
  };

  return {
    phy_name: fields.name?.value.trim() || '',
    phy_city: fields.city?.value.trim() || '',
    phy_postal: fields.postal?.value.trim() || '',
    phy_province: fields.province?.value.trim() || '',
    phy_kms: fields.kms?.value.trim() || '30',
    phy_prac_pop: fields.prac_pop?.value.trim() || '',
  };
}

/**
 * Normalize Canadian postal (uppercase, no spaces)
 */
function normalizePostal(v) {
  return String(v || '')
    .toUpperCase()
    .replace(/\s+/g, '');
}

/**
 * Validate Canadian postal code format
 * Accepts formats like: K1A 0A6, K1A0A6, k1a 0a6
 * Returns true if valid, false otherwise
 */
function isValidPostalCode(postalCode) {
  if (!postalCode || typeof postalCode !== 'string') {
    return false;
  }

  // Use constant regex for consistency
  return POSTAL_CODE_REGEX.test(postalCode.trim());
}

/**
 * Basic helper to set results container HTML
 */
function setResultsHTML(html) {
  const container = document.getElementById('results');
  if (container) container.innerHTML = html;
}

/**
 * Helper to set search results content HTML (for pagination updates)
 */
function setSearchResultsContentHTML(html) {
  const container = document.getElementById('search-results-content');
  if (container) {
    container.innerHTML = html;
  } else {
    // Fallback to main results container if sub-container doesn't exist
    setResultsHTML(html);
  }
}

/**
 * Generate pagination HTML
 * @param {number} currentPage - Current page number
 * @param {number} totalPages - Total number of pages
 * @param {number|null} prevPage - Previous page number
 * @param {number|null} nextPage - Next page number
 * @returns {string} HTML string for pagination
 */
function generatePaginationHTML(currentPage, totalPages, prevPage, nextPage) {
  const paginationParts = ['<div class="pagination-container">'];

  // Previous page button
  if (prevPage) {
    paginationParts.push(`<button type="button" class="pagination-btn" data-page="${prevPage}">← Previous</button>`);
  } else {
    paginationParts.push(`<button type="button" class="pagination-btn disabled" disabled>← Previous</button>`);
  }

  // Page numbers
  paginationParts.push('<span class="pagination-info">');

  // Show page numbers with ellipsis for large page counts
  const maxVisible = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
  let endPage = Math.min(totalPages, startPage + maxVisible - 1);

  // Adjust if we're near the end
  if (endPage - startPage < maxVisible - 1) {
    startPage = Math.max(1, endPage - maxVisible + 1);
  }

  // Show first page if not in range
  if (startPage > 1) {
    paginationParts.push(`<button type="button" class="pagination-btn page-number" data-page="1">1</button>`);
    if (startPage > 2) {
      paginationParts.push('<span class="pagination-ellipsis">...</span>');
    }
  }

  // Show page numbers in range
  for (let i = startPage; i <= endPage; i++) {
    if (i === currentPage) {
      paginationParts.push(`<button type="button" class="pagination-btn page-number current" disabled>${i}</button>`);
    } else {
      paginationParts.push(`<button type="button" class="pagination-btn page-number" data-page="${i}">${i}</button>`);
    }
  }

  // Show last page if not in range
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      paginationParts.push('<span class="pagination-ellipsis">...</span>');
    }
    paginationParts.push(`<button type="button" class="pagination-btn page-number" data-page="${totalPages}">${totalPages}</button>`);
  }

  paginationParts.push('</span>');

  // Next page button
  if (nextPage) {
    paginationParts.push(`<button type="button" class="pagination-btn" data-page="${nextPage}">Next →</button>`);
  } else {
    paginationParts.push(`<button type="button" class="pagination-btn disabled" disabled>Next →</button>`);
  }

  paginationParts.push('</div>');

  return paginationParts.join('');
}

/**
 * Handle "Show on map" link clicks
 * @param {string} orgId - Organization ID
 */
function showMarkerOnMap(orgId) {
  const marker = AppState.orgMarkerMap.get(orgId);
  if (!marker || !AppState.allergistMap) {
    return;
  }

  // Center the map on the marker
  AppState.allergistMap.setCenter(marker.getPosition());
  AppState.allergistMap.setZoom(15);

  // Trigger the marker click event to show the info window
  google.maps.event.trigger(marker, 'click');

  // Scroll to the map for better user experience
  const mapContainer = document.getElementById('allergist-map');
  if (mapContainer) {
    mapContainer.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
    });
  }
}

/**
 * Handle pagination button clicks and show on map links
 */
function handleDocumentClick(event) {
  // Handle pagination button clicks
  if (event.target.classList.contains('pagination-btn') && !event.target.disabled) {
    const page = parseInt(event.target.dataset.page);
    if (page && AppState.currentSearchData) {
      // Scroll to top of results
      const resultsContainer = document.getElementById('results');
      if (resultsContainer) {
        resultsContainer.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        });
      }

      // Perform search for the selected page (client-side navigation)
      handleSearchSubmit(page);
    }
  }

  // Handle "Show on map" link clicks
  if (event.target.classList.contains('show-on-map-link')) {
    event.preventDefault(); // Prevent default link behavior
    const orgId = event.target.dataset.orgId;
    if (orgId) {
      showMarkerOnMap(orgId);
    }
  }

  // Handle "View More" button clicks
  if (event.target.classList.contains('far-org-view-more')) {
    event.preventDefault();
    toggleOrgDetails(event.target);
  }
}

/**
 * Toggle organization details visibility
 * @param {HTMLElement} button - The "View More" button that was clicked
 */
function toggleOrgDetails(button) {
  // Find the parent organization container
  const orgContainer = button.closest('.far-org');
  if (!orgContainer) {
    return;
  }

  // Find the organization body element
  const orgBody = orgContainer.querySelector('.far-org__body');
  if (!orgBody) {
    return;
  }

  // Toggle the hidden class
  const isCurrentlyHidden = orgBody.classList.contains('far-org__body--hidden');

  if (isCurrentlyHidden) {
    // Show the content
    orgBody.classList.remove('far-org__body--hidden');
    button.textContent = 'View Less';
  } else {
    // Hide the content
    orgBody.classList.add('far-org__body--hidden');
    button.textContent = 'View More';
  }
}

/**
 * Optimized HTML escaper for titles/strings we render
 */
function escapeHTML(str) {
  if (!str) return '';

  // Use a more efficient approach with replace chain
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

/**
 * Cleanup function for preventing memory leaks
 */
function cleanup() {
  // Clear any pending timeouts
  if (AppState.validationTimeout) {
    clearTimeout(AppState.validationTimeout);
    AppState.validationTimeout = null;
  }

  // Abort any pending requests
  if (AppState.searchController) {
    AppState.searchController.abort();
    AppState.searchController = null;
  }

  // Hide loading overlay
  hideLoadingOverlay();
}

// Cleanup on page unload
window.addEventListener('beforeunload', cleanup);

/**
 * Toggle the range field based on postal code input
 */
function toggleRangeField() {
  const { postalInput, rangeSelect } = AppState.elements;

  if (!postalInput || !rangeSelect) {
    console.warn('toggleRangeField: Required elements not found');
    return;
  }

  const postalValue = postalInput.value.trim();
  const hasValidPostalCode = postalValue.length > 0 && isValidPostalCode(postalValue);

  if (hasValidPostalCode) {
    rangeSelect.disabled = false;
    rangeSelect.removeAttribute('disabled');

    // If there's a container element, update its styling too
    const container = rangeSelect.closest('.form-group, .field-container') || rangeSelect.parentElement;
    if (container) {
      container.classList.remove('disabled');
      container.style.opacity = '1';
      container.style.pointerEvents = 'auto';
    }

    // Hide help text when field is enabled
    const { rangeHelpText } = AppState.elements;
    if (rangeHelpText) {
      rangeHelpText.style.display = 'none';
    }
  } else {
    rangeSelect.disabled = true;
    rangeSelect.setAttribute('disabled', 'disabled');

    // If there's a container element, update its styling too
    const container = rangeSelect.closest('.form-group, .field-container') || rangeSelect.parentElement;
    if (container) {
      container.classList.add('disabled');
      container.style.opacity = '0.5';
      container.style.pointerEvents = 'none';
    }

    // Show help text when field is disabled
    const { rangeHelpText } = AppState.elements;
    if (rangeHelpText) {
      rangeHelpText.style.display = 'block';
    }
  }
}

/**
 * Validate the entire form before submission
 */
function validateForm() {
  const { postalInput } = AppState.elements;

  let isValid = true;

  // Validate postal code if provided
  if (postalInput && postalInput.value.trim()) {
    if (!validatePostalCode()) {
      isValid = false;
    }
  }

  return isValid;
}
