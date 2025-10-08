/**
 * Find an Allergist Results JavaScript
 *
 * Handles search form submission, pagination, and map functionality
 * for the Find an Allergist plugin.
 *
 * @package FAA
 * @since 1.0.0
 */

/**
 * NOTE: ACF Field Name Typo
 * The ACF field for organization name is 'institutation_name' (with typo).
 * This is the actual field name in the database and should not be "fixed"
 * to 'institution_name' without updating the ACF field definition first.
 */

/**
 * Configuration object - centralized settings for maintainability
 * @const {Object} FAA_CONFIG
 */
const FAA_CONFIG = {
  // API Settings
  api: {
    endpoint: '/wp-json/faa/v1/physicians/search',
    timeout: 5000,
  },

  // Pagination Settings
  pagination: {
    resultsPerPage: 20,
    maxVisiblePages: 2, // Number of page buttons to show on each side of current page
  },

  // Validation Settings
  validation: {
    debounceDelay: 300, // milliseconds
    postalCode: {
      maxLength: 6, // Without space
      regex: /^[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d$/,
      formatLength: 7, // With space: A1A 1A1
    },
  },

  // Google Maps Settings
  map: {
    defaultZoom: 10,
    singleLocationZoom: 15,
    markerIcon: {
      url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
      scaledSize: { width: 32, height: 32 },
    },
  },

  // UI Settings
  ui: {
    scrollOffset: 20, // Pixels offset when scrolling to elements
    animationDelay: 100, // Delay before map initialization
    transitionDelay: 500, // Delay for loading transitions
    parentSectionSelector: '.et_pb_section', // Divi theme specific - parent section to hide/show
  },

  // Error Messages
  messages: {
    searchError: 'Sorry, something went wrong. Please try again.',
    searching: 'Searching for allergists...',
    noResults: 'No results found.',
    networkError: 'Unable to connect. Please check your internet connection and try again.',
  },
};

// Backward compatibility - maintain original constant names
const ENDPOINT = FAA_CONFIG.api.endpoint;
const RESULTS_PER_PAGE = FAA_CONFIG.pagination.resultsPerPage;
const VALIDATION_DEBOUNCE_DELAY = FAA_CONFIG.validation.debounceDelay;
const POSTAL_CODE_MAX_LENGTH = FAA_CONFIG.validation.postalCode.maxLength;
const POSTAL_CODE_REGEX = FAA_CONFIG.validation.postalCode.regex;

/******************************************************************************
 * ERROR HANDLING
 ******************************************************************************/

/**
 * Error types for centralized error handling
 * @const {Object} FAA_ERROR_TYPES
 */
const FAA_ERROR_TYPES = {
  NETWORK: 'network',
  API_ERROR: 'api_error',
  VALIDATION: 'validation',
  NO_RESULTS: 'no_results',
  GEOCODING: 'geocoding',
  MAP_INIT: 'map_initialization',
  UNKNOWN: 'unknown',
};

/**
 * Centralized error handler
 * Logs errors for debugging and shows user-friendly messages
 *
 * @param {string} errorType - Type of error from FAA_ERROR_TYPES
 * @param {Object} details - Additional error details for logging
 * @param {string} customMessage - Optional custom message to display to user
 * @returns {void}
 */
function handleError(errorType, details = {}, customMessage = null) {
  // Get appropriate user message
  const userMessage = customMessage || FAA_CONFIG.messages.searchError;

  // Log error for debugging (only in console, not shown to users)
  if (window.console && console.error) {
    console.error(`[FAA Error - ${errorType}]:`, details);
  }

  // Show user-friendly message in results area
  setResultsHTML(`<p role="alert" class="faa-error-message">${escapeHTML(userMessage)}</p>`);

  // Optional: Send to error tracking service if available
  if (typeof window.trackError === 'function') {
    window.trackError('FAA', errorType, details);
  }
}

/******************************************************************************
 * UTILITY FUNCTIONS
 ******************************************************************************/

/**
 * Debounce function - delays execution until after specified wait time
 * Prevents excessive function calls during rapid user input
 *
 * @param {Function} func - Function to debounce
 * @param {number} wait - Milliseconds to wait before executing
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func.apply(this, args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/******************************************************************************
 * STATE MANAGEMENT
 ******************************************************************************/

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
};

/**
 * Initialize the application when DOM is ready
 * Sets up event listeners and caches DOM elements
 */
document.addEventListener('DOMContentLoaded', function () {
  initializeApp();
});

/**
 * Initialize app and cache DOM elements
 * Caches all DOM elements to AppState for efficient access throughout the application
 *
 * @returns {void}
 */
function initializeApp() {
  // Cache DOM elements to avoid repeated queries
  AppState.elements = {
    formSection: document.getElementById('faa-search'),
    form: document.getElementById('faa-search-form'),
    searchBtn: document.getElementById('btn-search'),
    clearBtn: document.getElementById('btn-clear'),
    results: document.getElementById('results'),
    postalInput: document.getElementById('phy_postal'),
    rangeSelect: document.getElementById('phy_kms'),
    postalError: document.getElementById('postal-error'),
    rangeHelpText: document.getElementById('range-help-text'),
  };

  // Find the first parent element with configured selector (Divi theme specific)
  // This is used to hide/show the entire search form section when displaying results
  if (AppState.elements.formSection) {
    AppState.elements.parentSection = AppState.elements.formSection.closest(FAA_CONFIG.ui.parentSectionSelector);
  }

  // Create ARIA live region for search status announcements
  createAriaLiveRegion();

  bindEventHandlers();
  initializeRangeFieldState();
}

/**
 * Create ARIA live region for accessibility announcements
 * Screen readers will announce updates to this region
 *
 * @returns {void}
 */
function createAriaLiveRegion() {
  if (!document.getElementById('faa-sr-status')) {
    const liveRegion = document.createElement('div');
    liveRegion.id = 'faa-sr-status';
    liveRegion.className = 'sr-only';
    liveRegion.setAttribute('role', 'status');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    document.body.appendChild(liveRegion);
  }
}

/**
 * Update ARIA live region with status message
 * Announces to screen readers without visual change
 *
 * @param {string} message - Message to announce
 * @returns {void}
 */
function announceToScreenReader(message) {
  const liveRegion = document.getElementById('faa-sr-status');
  if (liveRegion) {
    liveRegion.textContent = message;
  }
}

/**
 * Bind event handlers to DOM elements
 * Sets up all interactive behaviors: form submission, button clicks, input validation
 * Uses event delegation for dynamically generated content
 *
 * @returns {void}
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
    // Create debounced validation function
    const debouncedValidation = debounce(() => {
      validatePostalCode();
      toggleRangeField();
    }, VALIDATION_DEBOUNCE_DELAY);

    postalInput.addEventListener('input', function () {
      formatPostalCodeInput();
      debouncedValidation();
    });

    // Also validate on blur for better UX (immediate, not debounced)
    postalInput.addEventListener('blur', function () {
      validatePostalCode();
      toggleRangeField();
    });
  }

  // Add event listener for clicks using event delegation
  document.addEventListener('click', handleDocumentClick);
}

/**
 * Clear the form and reset all application state
 * Resets form fields, clears results, removes validation messages, and shows the search form
 *
 * @returns {void}
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

  // Remove "No results found" message from intro text container
  const introTextContainer = document.querySelector('.faa-search-intro__text');
  if (introTextContainer) {
    const existingMessage = introTextContainer.querySelector('.faa-search-intro__no-resuts');
    if (existingMessage) {
      existingMessage.remove();
    }
  }

  // Reset validation states
  clearPostalValidation();

  // Reset range field state
  toggleRangeField();

  // Show parent section when clearing form
  showParentSection();
}

/**
 * Initialize the range field state on page load
 * The range field should be disabled until a valid postal code is entered
 *
 * @returns {void}
 */
function initializeRangeFieldState() {
  toggleRangeField();
}

/**
 * Format postal code input as user types
 * Converts to uppercase and adds space after 3rd character (e.g., K1A 0A6)
 * Enforces maximum length of 6 characters (excluding space)
 *
 * @returns {void}
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
 * Checks against Canadian postal code format (A1A 1A1)
 * Updates UI with validation states and error messages
 *
 * @returns {boolean} True if valid or empty, false if invalid
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
 * Removes all validation classes and hides error messages
 *
 * @returns {void}
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
 * Creates and displays a full-screen loading overlay with animated spinner
 * Automatically removes any existing overlay before creating a new one
 * Styles are defined in find-allergist.css
 *
 * @returns {void}
 */
function showLoadingOverlay() {
  // Remove existing overlay if present
  hideLoadingOverlay();

  const overlay = document.createElement('div');
  overlay.id = 'search-loading-overlay';
  overlay.className = 'faa-loading-overlay';
  overlay.innerHTML = `
    <div class="faa-loading-content">
      <div class="faa-loading-spinner"></div>
      <p>${escapeHTML(FAA_CONFIG.messages.searching)}</p>
    </div>
  `;

  document.body.appendChild(overlay);
}

/**
 * Hide and remove loading overlay
 * Removes the loading overlay element from the DOM if it exists
 *
 * @returns {void}
 */
function hideLoadingOverlay() {
  const overlay = document.getElementById('search-loading-overlay');
  if (overlay) {
    overlay.remove();
  }
}

/**
 * Scroll to results container smoothly
 * Uses smooth scrolling behavior to bring the results into view
 * Manages focus for keyboard navigation and screen reader accessibility
 *
 * @returns {void}
 */
function scrollToResults() {
  const resultsContainer = document.getElementById('results');
  if (resultsContainer) {
    resultsContainer.scrollIntoView({
      behavior: 'smooth',
      block: 'start',
    });

    // Set focus to first heading in results for keyboard navigation
    setTimeout(() => {
      const firstHeading = resultsContainer.querySelector('h2, h3');
      if (firstHeading) {
        firstHeading.setAttribute('tabindex', '-1');
        firstHeading.focus();
      }
    }, FAA_CONFIG.ui.animationDelay + 100);
  }
}

/**
 * Handle search form submission - only makes API call for new searches
 * Implements client-side pagination - new searches trigger API calls,
 * pagination uses cached results for better performance
 *
 * @param {number} page - Page number to display (default: 1)
 * @returns {Promise<void>}
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
        const errorType = res.status >= 500 ? FAA_ERROR_TYPES.API_ERROR : FAA_ERROR_TYPES.NETWORK;
        throw new Error(`REST request failed with status ${res.status}`);
      }

      const data = await res.json();

      // Store all results for client-side pagination
      AppState.allSearchResults = data.results || [];
      renderPaginatedResults(1, true); // isNewSearch = true

      // Announce results to screen readers
      const resultCount = AppState.allSearchResults.length;
      const announcement = resultCount === 0 ? 'No results found. Please try adjusting your search criteria.' : `Found ${resultCount} result${resultCount === 1 ? '' : 's'}.`;
      announceToScreenReader(announcement);

      // Hide overlay and scroll to results
      hideLoadingOverlay();
      setTimeout(() => scrollToResults(), FAA_CONFIG.ui.animationDelay);
    } catch (err) {
      // Hide overlay on error
      hideLoadingOverlay();

      // Don't show error for aborted requests
      if (err.name === 'AbortError') {
        return;
      }

      // Use centralized error handler
      const errorType = err.message.includes('fetch') || err.message.includes('network') ? FAA_ERROR_TYPES.NETWORK : FAA_ERROR_TYPES.API_ERROR;

      handleError(errorType, {
        message: err.message,
        endpoint: ENDPOINT,
        params: Object.fromEntries(params),
      });

      AppState.allSearchResults = [];

      // Still scroll to results to show error
      setTimeout(() => scrollToResults(), FAA_CONFIG.ui.animationDelay);
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
 * Generate results header with pagination info and controls
 * @param {number} totalResults - Total number of results
 * @param {number} startIndex - Start index for current page
 * @param {number} endIndex - End index for current page
 * @param {number} page - Current page number
 * @param {number} totalPages - Total number of pages
 * @returns {string} HTML string for results header
 */
function generateResultsNav(totalResults, startIndex, endIndex, page, totalPages) {
  const resultParts = [];

  resultParts.push(`<div class="faa-res-head__item faa-res-head__start-over"><a href="#" id="faa-res-head__start-over-link" class="faa-res-head__start-over-link">Back to Search</a></div>`);
  resultParts.push(`<div class="faa-res-head__item faa-res-pagination-info">`, `<p>Found ${totalResults} result${totalResults === 1 ? '' : 's'}${totalResults > RESULTS_PER_PAGE ? ` - showing ${startIndex + 1} to ${endIndex}` : ''}</p></div>`);

  // Add pagination controls if there are multiple pages
  if (totalPages > 1) {
    const prevPage = page > 1 ? page - 1 : null;
    const nextPage = page < totalPages ? page + 1 : null;
    resultParts.push(`<div class="faa-res-head__item faa-res-pagination">`);
    resultParts.push(generatePaginationHTML(page, totalPages, prevPage, nextPage));
    resultParts.push(`</div>`);
  }

  return resultParts.join('');
}

/**
 * Render paginated results using client-side pagination
 * @param {number} page - Page number to render
 * @param {boolean} isNewSearch - Whether this is a new search (requires map initialization)
 */
function renderPaginatedResults(page, isNewSearch = false) {
  // Don't hide parent section when no results are found
  if (isNewSearch && Array.isArray(AppState.allSearchResults) && AppState.allSearchResults.length > 0) {
    hideParentSection();
  }

  if (!Array.isArray(AppState.allSearchResults) || AppState.allSearchResults.length === 0) {
    if (isNewSearch) {
      // Append "No results found." to the faa-search-intro__text container
      const introTextContainer = document.querySelector('.faa-search-intro__text');
      if (introTextContainer) {
        // Remove any existing "No results found" message
        const existingMessage = introTextContainer.querySelector('.faa-search-intro__no-resuts');
        if (existingMessage) {
          existingMessage.remove();
        }

        // Create and append the "No results found" message
        const noResultsMessage = document.createElement('p');
        noResultsMessage.className = 'faa-search-intro__no-resuts';
        noResultsMessage.textContent = 'No results found.';
        introTextContainer.appendChild(noResultsMessage);
      }

      // Clear any existing results display
      setResultsHTML('');
    } else {
      // For pagination, just update the content area
      setSearchResultsContentHTML('<p>No matches found.</p>');
    }
    return;
  }

  // Results found - hide parent section and proceed with normal display
  if (isNewSearch) {
    hideParentSection();
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
    const fullResultParts = ['<div id="faa-res-map" class="faa-res-map"></div>', '<div id="faa-res-content" class="faa-res-content"></div>'];
    setResultsHTML(fullResultParts.join(''));
  }

  // Build the paginated content (without map container)
  const resultParts = [];

  // Add header at the top
  resultParts.push(`<div class="faa-res-head">`);
  resultParts.push(generateResultsNav(totalResults, startIndex, endIndex, page, totalPages));
  resultParts.push(`</div>`);

  resultParts.push(`<div class="faa-res-items">`);

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
      resultParts.push(`<div class="faa-res-item">${organizationsHTML}</div>`);
    }
  }

  resultParts.push('</div>');

  // Add header at the bottom
  resultParts.push(`<div class="faa-res-footer">`);
  resultParts.push(generateResultsNav(totalResults, startIndex, endIndex, page, totalPages));
  resultParts.push(`</div>`);

  setSearchResultsContentHTML(resultParts.join(''));

  // Initialize the map only for new searches, not for pagination
  if (isNewSearch) {
    setTimeout(() => initializeMap(AppState.allSearchResults), FAA_CONFIG.ui.animationDelay);
  }
}

/**
 * Initialize Google Map with organization markers
 * @param {Array} results - Array of search results containing organizations
 */
function initializeMap(results) {
  const mapContainer = document.getElementById('faa-res-map');
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
    zoom: FAA_CONFIG.map.defaultZoom,
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
        url: FAA_CONFIG.map.markerIcon.url,
        scaledSize: new google.maps.Size(FAA_CONFIG.map.markerIcon.scaledSize.width, FAA_CONFIG.map.markerIcon.scaledSize.height),
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
    AppState.allergistMap.setZoom(FAA_CONFIG.map.singleLocationZoom);
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
  const parts = [`<div class="faa-map-info-window">`];

  if (location.physicianName) {
    parts.push(`<p class="faa-map-info-window__title">${escapeHTML(location.physicianName)}${location.physicianCredentials ? `, ${escapeHTML(location.physicianCredentials)}` : ''}</p>`);
  }

  if (location.title) {
    parts.push(`<p class="faa-map-info-window__text">${escapeHTML(location.title)}</p>`);
  }

  if (location.address && location.cityState) {
    parts.push(`<p class="faa-map-info-window__text">${escapeHTML(location.address)}, ${escapeHTML(location.cityState)}</p>`);
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
		<div class="faa-res-physician-info">
			<h2 class="faa-res-physician-name">
			  ${escapeHTML(physicianInfo.title)}${physicianInfo.credentials ? `, ${escapeHTML(physicianInfo.credentials)}` : ''}
			</h2>
		</div>
	`);

  // Organizations container
  parts.push(`<div class="faa-res-orgs">`);

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

    parts.push(`<div class="faa-res-org" id="${orgId}">`);

    // Org Summary section
    parts.push(`<div class="faa-res-org__summary">`);

    parts.push(`<h3 class="faa-res-org__grid-item faa-res-org__title">${escapeHTML(orgName)}</h3>`);

    parts.push(`<p class="faa-res-org__grid-item faa-res-org__text faa-res-org__address">`);

    if (address) {
      parts.push(`<span class="faa-res-org__grid-item faa-res-org__address-street"> ${escapeHTML(address)}</span>`);
    }

    if (city || state) {
      const cityStateParts = [city, state].filter(Boolean);
      parts.push(`<span class="faa-res-org__grid-item faa-res-org__address-city-state">, ${escapeHTML(cityStateParts.join(', '))}</span>`);
    }

    if (postalCode) {
      parts.push(`<span class="faa-res-org-address_postal">, ${escapeHTML(postalCode)}</span>`);
    }

    parts.push(`</p>`);

    if (phone) {
      parts.push(`<p class="faa-res-org__grid-item faa-res-org__text faa-res-org__phone"><strong aria-label="Phone">T:</strong> ${escapeHTML(phone)}</p>`);
    } else {
      parts.push(`<p class="faa-res-org__grid-item faa-res-org__text faa-res-org__phone faa-res-org__phone--no-phone">Not available</p>`);
    }

    // Distance display in summary section (prominently visible)
    if (distance !== undefined && distance !== null) {
      const distanceNum = parseFloat(distance);
      if (!isNaN(distanceNum)) {
        parts.push(`<p class="faa-res-org__grid-item faa-res-org__text faa-res-distance"><strong>Distance:</strong> ${distanceNum.toFixed(1)} km</p>`);
      }
    }

    parts.push(`<button class="faa-res-org__grid-item faa-res-org__btn faa-res-org-view-more">More Info</button>`);

    parts.push(`</div>`); // Close org summary

    // Org Body section
    parts.push(`<div class="faa-res-org__body faa-res-org__body--hidden">`);

    // Org Body section  Row 1
    parts.push(`<div class="faa-res-org__grid-item">`);
    if (practiceSetting) {
      parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label">Practice Setting(s):</span> ${escapeHTML(practiceSetting)}</div>`);
    }
    if (practicesOIT) {
      parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label">Practices OIT:</span> ${escapeHTML(practicesOIT)}</div>`);
    }
    if (practicePopulation) {
      parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label">Practices Population:</span> ${escapeHTML(practicePopulation)}</div>`);
    }
    parts.push(`</div>`);
    parts.push(`<div class="faa-res-org__grid-item">`);
    if (specialAreasOfInterest) {
      parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label faa-res-org__grid-item-label--lb">Special Areas of Interest:</span> ${escapeHTML(specialAreasOfInterest)}</div>`);
    }
    parts.push(`</div>`);

    // Org Body section Row 2
    parts.push(`<div class="faa-res-org__grid-item">`);
    if (consultationServices && consultationServices.length > 0) {
      const consultationServicesList = consultationServices.map((service) => `<li class="faa-res-org__grid-item-cell_list-item">${escapeHTML(service)}</li>`).join('');
      parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label faa-res-org__grid-item-label--lb">Consultation Services:</span> <ul class="faa-res-org__grid-item-cell-list">${consultationServicesList}</ul></div>`);
    }
    if (siteForClinicalTrials) {
      parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label">Site for Clinical Trials:</span> ${escapeHTML(siteForClinicalTrials)}</div>`);
    }
    // Distance display - ensure it's displayed even if it's 0
    if (distance !== undefined && distance !== null) {
      const distanceNum = parseFloat(distance);
      if (!isNaN(distanceNum)) {
        parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label">Distance:</span> ${distanceNum.toFixed(1)} km</div>`);
      } else {
        console.warn('Distance value is not a valid number:', distance);
      }
    } else {
      console.warn('Distance is undefined or null for organization:', orgName);
    }
    parts.push(`</div>`);
    parts.push(`<div class="faa-res-org__grid-item">`);
    if (treatmentServicesOffered && treatmentServicesOffered.length > 0) {
      const treatmentServicesList = treatmentServicesOffered.map((service) => `<li class="faa-res-org__grid-item-cell_list-item">${escapeHTML(service)}</li>`).join('');
      parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label faa-res-org__grid-item-label--lb">Treatment Services Offered:</span> <ul class="faa-res-org__grid-item-cell-list">${treatmentServicesList}</ul></div>`);
    }
    if (treatmentServicesOfferedOther) {
      parts.push(`<div class="faa-res-org__grid-item-cell"><span class="faa-res-org__grid-item-label faa-res-org__grid-item-label--lb">Other areas of special interest unique to your practice:</span> ${escapeHTML(treatmentServicesOfferedOther)}</div>`);
    }
    parts.push(`</div>`);

    parts.push(`</div>`); // Close org body
    parts.push(`</div>`); // Close org
  }

  parts.push(`</div>`); // Close orgs
  return parts.join('');
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
 * Normalize Canadian postal code for API submission
 * Converts to uppercase and removes all spaces (e.g., "K1A 0A6" → "K1A0A6")
 *
 * @param {string} v - Postal code to normalize
 * @returns {string} Normalized postal code (uppercase, no spaces)
 */
function normalizePostal(v) {
  return String(v || '')
    .toUpperCase()
    .replace(/\s+/g, '');
}

/**
 * Validate Canadian postal code format
 * Accepts formats like: K1A 0A6, K1A0A6, k1a 0a6
 * Pattern: Letter-Digit-Letter space/no-space Digit-Letter-Digit
 *
 * @param {string} postalCode - Postal code to validate
 * @returns {boolean} True if valid, false otherwise
 */
function isValidPostalCode(postalCode) {
  if (!postalCode || typeof postalCode !== 'string') {
    return false;
  }

  // Use constant regex for consistency
  return POSTAL_CODE_REGEX.test(postalCode.trim());
}

/**
 * Set the main results container HTML
 * Updates the entire results section (faa-res-section)
 *
 * @param {string} html - HTML content to set
 * @returns {void}
 */
function setResultsHTML(html) {
  const container = document.getElementById('faa-res-section');
  if (container) container.innerHTML = html;
}

/**
 * Set search results content HTML (for pagination updates)
 * Updates only the content area (faa-res-content), preserving the map
 * Falls back to main results container if content container doesn't exist
 *
 * @param {string} html - HTML content to set
 * @returns {void}
 */
function setSearchResultsContentHTML(html) {
  const container = document.getElementById('faa-res-content');
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
  const paginationParts = ['<div class="faa-res-pagination-container">'];

  // Previous page button
  if (prevPage) {
    paginationParts.push(`<button type="button" class="faa-res-pagination-btn" data-page="${prevPage}" aria-label="Previous Page"><span class="dashicons dashicons-arrow-left-alt2"></span></button>`);
  } else {
    paginationParts.push(`<button type="button" class="faa-res-pagination-btn disabled" aria-label="Previous Page" disabled><span class="dashicons dashicons-arrow-left-alt2"></span></button>`);
  }

  // Page numbers
  paginationParts.push('<span class="faa-res-pagination-numbers">');

  // Show page numbers with ellipsis for large page counts
  const maxVisible = FAA_CONFIG.pagination.maxVisiblePages;
  let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
  let endPage = Math.min(totalPages, startPage + maxVisible - 1);

  // Adjust if we're near the end
  if (endPage - startPage < maxVisible - 1) {
    startPage = Math.max(1, endPage - maxVisible + 1);
  }

  // Show first page if not in range
  if (startPage > 1) {
    paginationParts.push(`<button type="button" class="faa-res-pagination-btn page-number" data-page="1">1</button>`);
    if (startPage > 2) {
      paginationParts.push('<span class="pagination-ellipsis">...</span>');
    }
  }

  // Show page numbers in range
  for (let i = startPage; i <= endPage; i++) {
    if (i === currentPage) {
      paginationParts.push(`<button type="button" class="faa-res-pagination-btn page-number current" disabled>${i}</button>`);
    } else {
      paginationParts.push(`<button type="button" class="faa-res-pagination-btn page-number" data-page="${i}">${i}</button>`);
    }
  }

  // Show last page if not in range
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      paginationParts.push('<span class="faa-res-pagination-ellipsis">...</span>');
    }
    paginationParts.push(`<button type="button" class="faa-res-pagination-btn page-number" data-page="${totalPages}">${totalPages}</button>`);
  }

  paginationParts.push('</span>');

  // Next page button
  if (nextPage) {
    paginationParts.push(`<button type="button" class="faa-res-pagination-btn" data-page="${nextPage}" aria-label="Next Page"><span class="dashicons dashicons-arrow-right-alt2"></span></button>`);
  } else {
    paginationParts.push(`<button type="button" class="faa-res-pagination-btn disabled" disabled><span class="dashicons dashicons-arrow-right-alt2"></span></button>`);
  }

  paginationParts.push('</div>');

  return paginationParts.join('');
}

/**
 * Show a specific marker on the map and display its info window
 * Centers the map on the marker location and triggers a click event
 *
 * @param {string} orgId - Organization ID to show on map
 * @returns {void}
 */
function showMarkerOnMap(orgId) {
  const marker = AppState.orgMarkerMap.get(orgId);
  if (!marker || !AppState.allergistMap) {
    return;
  }

  // Center the map on the marker
  AppState.allergistMap.setCenter(marker.getPosition());
  AppState.allergistMap.setZoom(FAA_CONFIG.map.singleLocationZoom);

  // Trigger the marker click event to show the info window
  google.maps.event.trigger(marker, 'click');

  // Scroll to the map for better user experience
  const mapContainer = document.getElementById('faa-res-map');
  if (mapContainer) {
    mapContainer.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
    });
  }
}

/**
 * Handle pagination button clicks and other dynamic UI interactions
 * Uses event delegation to handle clicks on dynamically generated elements
 * Handles: pagination buttons, "show on map" links, "back to search", and "view more" buttons
 *
 * @param {Event} event - Click event or keyboard event
 * @returns {void}
 */
function handleDocumentClick(event) {
  // Handle pagination button clicks - improved targeting and disabled state checking
  const paginationBtn = event.target.closest('.faa-res-pagination-btn');
  if (paginationBtn && !paginationBtn.disabled && !paginationBtn.classList.contains('disabled')) {
    const page = parseInt(paginationBtn.dataset.page);
    if (page && AppState.currentSearchData) {
      // Prevent default behavior
      event.preventDefault();

      // Announce page change to screen readers
      announceToScreenReader(`Loading page ${page}`);

      // Scroll to top of results content with configured offset
      const resultsContentContainer = document.getElementById('faa-res-content');
      if (resultsContentContainer) {
        const elementTop = resultsContentContainer.getBoundingClientRect().top + window.pageYOffset;
        const offsetTop = elementTop - FAA_CONFIG.ui.scrollOffset;

        window.scrollTo({
          top: offsetTop,
          behavior: 'smooth',
        });
      }

      // Perform search for the selected page (client-side navigation)
      handleSearchSubmit(page);
    }
    return;
  }

  // Handle "Show on map" link clicks
  if (event.target.classList.contains('show-on-map-link')) {
    event.preventDefault(); // Prevent default link behavior
    const orgId = event.target.dataset.orgId;
    if (orgId) {
      showMarkerOnMap(orgId);
    }
  }

  // Handle "Back to Search" link clicks
  if (event.target.id === 'faa-res-head__start-over-link') {
    event.preventDefault();
    handleStartOver();
  }

  // Handle "View More" button clicks
  if (event.target.classList.contains('faa-res-org-view-more')) {
    event.preventDefault();
    toggleOrgDetails(event.target);
  }
}

/**
 * Handle start over process - reset form and show search with loading transition
 * Cleans up map resources, resets all state, clears results, and returns to search form
 *
 * @returns {void}
 */
function handleStartOver() {
  // Show loading overlay
  showLoadingOverlay();

  // Clean up map resources
  cleanupMap();

  // Reset all state
  AppState.currentSearchData = null;
  AppState.currentPage = 1;
  AppState.allSearchResults = [];

  // Clear results
  setResultsHTML('');

  // Remove "No results found" message from intro text container
  const introTextContainer = document.querySelector('.faa-search-intro__text');
  if (introTextContainer) {
    const existingMessage = introTextContainer.querySelector('.faa-search-intro__no-resuts');
    if (existingMessage) {
      existingMessage.remove();
    }
  }

  // Show parent section (search form)
  showParentSection();

  // Hide loading overlay after a brief delay to show transition
  setTimeout(() => {
    hideLoadingOverlay();

    // Scroll to search form
    const { formSection } = AppState.elements;
    if (formSection) {
      formSection.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
      });
    }
  }, FAA_CONFIG.ui.transitionDelay);
}

/**
 * Toggle organization details visibility (expand/collapse)
 * Shows or hides additional organization information when "More Info" is clicked
 * Updates button text between "More Info" and "Less Info"
 *
 * @param {HTMLElement} button - The "View More" button that was clicked
 * @returns {void}
 */
function toggleOrgDetails(button) {
  // Find the parent organization container
  const orgContainer = button.closest('.faa-res-org');
  if (!orgContainer) {
    return;
  }

  // Find the organization body element
  const orgBody = orgContainer.querySelector('.faa-res-org__body');
  if (!orgBody) {
    return;
  }

  // Toggle the hidden class
  const isCurrentlyHidden = orgBody.classList.contains('faa-res-org__body--hidden');

  if (isCurrentlyHidden) {
    // Show the content
    orgBody.classList.remove('faa-res-org__body--hidden');
    button.textContent = 'Less Info';
    button.classList.add('faa-res-org__btn--expanded');
  } else {
    // Hide the content
    orgBody.classList.add('faa-res-org__body--hidden');
    button.textContent = 'More Info';
    button.classList.remove('faa-res-org__btn--expanded');
  }
}

/**
 * Escape HTML special characters to prevent XSS attacks
 * Converts &, <, >, ", and ' to their HTML entity equivalents
 *
 * @param {string} str - String to escape
 * @returns {string} Escaped HTML-safe string
 */
function escapeHTML(str) {
  if (!str) return '';

  // Use a more efficient approach with replace chain
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

/**
 * Cleanup function for preventing memory leaks
 * Aborts pending requests and hides overlays
 * Called automatically when page is unloaded
 *
 * @returns {void}
 */
function cleanup() {
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
 * Enables the range selector only when a valid postal code is entered
 * Shows/hides help text accordingly
 *
 * @returns {void}
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
 * Currently only validates postal code if provided
 *
 * @returns {boolean} True if form is valid, false otherwise
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

/**
 * Hide the parent section (form section)
 * Hides the Divi section containing the search form when showing results
 *
 * @returns {void}
 */
function hideParentSection() {
  const { parentSection } = AppState.elements;
  if (parentSection) {
    parentSection.style.display = 'none';
  }
}

/**
 * Show the parent section (form section)
 * Reveals the Divi section containing the search form
 *
 * @returns {void}
 */
function showParentSection() {
  const { parentSection } = AppState.elements;
  if (parentSection) {
    parentSection.style.display = '';
  }
}
