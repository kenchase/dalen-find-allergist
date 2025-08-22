// Find Allergist Results JavaScript
document.addEventListener("DOMContentLoaded", function () {
	// Cache DOM elements to avoid repeated queries
	const elements = {
		form: document.getElementById("allergistfrm"),
		searchBtn: document.getElementById("btn-search"),
		clearBtn: document.getElementById("btn-clear"),
		results: document.getElementById("results"),
	};

	// Handle form submission
	if (elements.form) {
		elements.form.addEventListener("submit", function (e) {
			e.preventDefault();
			handleSearchSubmit();
		});
	}

	// Handle search button click
	if (elements.searchBtn) {
		elements.searchBtn.addEventListener("click", function (e) {
			e.preventDefault();
			handleSearchSubmit();
		});
	}

	// Handle clear button click
	if (elements.clearBtn) {
		elements.clearBtn.addEventListener("click", function (e) {
			e.preventDefault();
			elements.form?.reset();
			// Reset pagination state
			currentSearchData = null;
			currentPage = 1;
			allSearchResults = [];
			// Clear results
			setResultsHTML("");
		});
	}
});

const ENDPOINT = "/wp-json/my/v1/physicians/search";

// Global variables for map functionality and pagination
let allergistMap = null;
let mapMarkers = [];
let mapInfoWindow = null;
let orgMarkerMap = new Map(); // Maps organization IDs to their markers
let searchController = null; // For aborting previous requests
let currentSearchData = null; // Store current search parameters
let currentPage = 1; // Track current page
let allSearchResults = []; // Store complete result set for client-side pagination
let resultsPerPage = 20; // Results per page

/**
 * Handle search form submission - only makes API call for new searches
 */
async function handleSearchSubmit(page = 1) {
	console.log("Search submitted");

	// Get all form data
	const formData = getAllFormData();

	// Check if this is a new search (different parameters)
	const isNewSearch =
		!currentSearchData ||
		JSON.stringify(currentSearchData) !== JSON.stringify(formData);

	if (isNewSearch) {
		// New search - make API call
		console.log("New search - making API call");

		// Abort previous request if still pending
		if (searchController) {
			searchController.abort();
		}

		// Create new abort controller for this request
		searchController = new AbortController();

		currentSearchData = formData;
		currentPage = 1;

		// Build query params (send only what's filled)
		const params = new URLSearchParams();
		if (formData.phy_fname) params.set("fname", formData.phy_fname);
		if (formData.phy_lname) params.set("lname", formData.phy_lname);
		if (typeof formData.phy_oit === "boolean")
			params.set("oit", String(!!formData.phy_oit));
		if (formData.phy_city) params.set("city", formData.phy_city);
		if (formData.phy_province)
			params.set("province", formData.phy_province);
		if (formData.phy_postal)
			params.set("postal", normalizePostal(formData.phy_postal));
		if (formData.phy_kms) params.set("kms", formData.phy_kms);

		// UI: basic loading state
		setResultsHTML("<p>Searching…</p>");

		try {
			const nonce = window.wpApiSettings?.nonce;
			const res = await fetch(`${ENDPOINT}?${params.toString()}`, {
				headers: nonce ? { "X-WP-Nonce": nonce } : undefined,
				signal: searchController.signal,
			});

			if (!res.ok) {
				throw new Error(`REST request failed (${res.status})`);
			}

			const data = await res.json();

			// Store all results for client-side pagination
			allSearchResults = data.results || [];

			// Render first page
			renderPaginatedResults(1, true); // isNewSearch = true
		} catch (err) {
			// Don't show error for aborted requests
			if (err.name === "AbortError") {
				console.log("Request was aborted");
				return;
			}

			console.error("Search error:", err);
			setResultsHTML(
				'<p role="alert">Sorry, something went wrong. Please try again.</p>'
			);
			allSearchResults = [];
		} finally {
			searchController = null;
		}
	} else {
		// Same search - just navigate to different page (client-side)
		console.log("Page navigation - client-side");
		currentPage = page;
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
			const orgName = org.institutation_name || "";

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
	console.log(
		`Rendering page ${page} of ${Math.ceil(
			allSearchResults.length / resultsPerPage
		)}`
	);

	if (!Array.isArray(allSearchResults) || allSearchResults.length === 0) {
		if (isNewSearch) {
			// For new searches, set up the complete structure even with no results
			const fullResultParts = [
				'<div id="allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px; display: none;"></div>',
				'<div id="search-results-content"><p>No matches found.</p></div>',
			];
			setResultsHTML(fullResultParts.join(""));
		} else {
			// For pagination, just update the content area
			setSearchResultsContentHTML("<p>No matches found.</p>");
		}
		return;
	}

	// Calculate pagination
	const totalResults = allSearchResults.length;
	const totalPages = Math.ceil(totalResults / resultsPerPage);
	const startIndex = (page - 1) * resultsPerPage;
	const endIndex = Math.min(startIndex + resultsPerPage, totalResults);
	const currentPageResults = allSearchResults.slice(startIndex, endIndex);

	// Pre-analyze which organizations will have markers (using all results, not just current page)
	const orgIdsWithMarkers = getOrganizationsWithMarkers(allSearchResults);

	// For new searches, ensure we have a map container
	if (isNewSearch) {
		// Create the complete layout with map container
		const fullResultParts = [
			'<div id="allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px;"></div>',
			'<div id="search-results-content"></div>',
		];
		setResultsHTML(fullResultParts.join(""));
	}

	// Build the paginated content (without map container)
	const resultParts = [
		`<div class="search-results-info">`,
		`<p>Found ${totalResults} result${totalResults === 1 ? "" : "s"}${
			totalResults > resultsPerPage
				? ` - showing ${startIndex + 1} to ${endIndex}`
				: ""
		}</p>`,
		`</div>`,
	];

	// Add pagination controls at the top if there are multiple pages
	if (totalPages > 1) {
		const prevPage = page > 1 ? page - 1 : null;
		const nextPage = page < totalPages ? page + 1 : null;
		resultParts.push(
			generatePaginationHTML(page, totalPages, prevPage, nextPage)
		);
	}

	resultParts.push('<div class="far-items">');

	for (const item of currentPageResults) {
		const city = item.acf?.city || "";
		const prov = item.acf?.province || "";
		const credentials = item.acf?.credentials || "";

		// Map OIT field value to Yes/No
		// OIT is either "" (No) or an array (Yes)
		const oitValue = item.acf?.oit;
		const oit = Array.isArray(oitValue) ? "Yes" : "No";

		// Prepare physician info for organizations
		const physicianInfo = {
			title: item.title,
			credentials,
			oit,
			link: item.link,
		};

		// Generate organizations HTML using separate function
		const organizationsHTML = generateOrganizationsHTML(
			item.acf?.organizations_details,
			physicianInfo,
			orgIdsWithMarkers
		);

		if (organizationsHTML) {
			resultParts.push(
				`<div class="far-item">${organizationsHTML}</div>`
			);
		}
	}

	resultParts.push("</div>");

	// Add pagination controls at the bottom if there are multiple pages
	if (totalPages > 1) {
		const prevPage = page > 1 ? page - 1 : null;
		const nextPage = page < totalPages ? page + 1 : null;
		resultParts.push(
			generatePaginationHTML(page, totalPages, prevPage, nextPage)
		);
	}

	setSearchResultsContentHTML(resultParts.join(""));

	// Initialize the map only for new searches, not for pagination
	if (isNewSearch) {
		setTimeout(() => initializeMap(allSearchResults), 100);
	}
}

/**
 * Initialize Google Map with organization markers
 * @param {Array} results - Array of search results containing organizations
 */
function initializeMap(results) {
	const mapContainer = document.getElementById("allergist-map");
	if (!mapContainer || !window.google) {
		console.log("Map container or Google Maps API not available");
		return;
	}

	// Clean up existing map resources
	cleanupMap();

	// Initialize map components
	mapMarkers = [];
	orgMarkerMap.clear(); // Clear the organization-marker mapping
	mapInfoWindow = new google.maps.InfoWindow();
	const bounds = new google.maps.LatLngBounds();
	const locations = [];

	// Single loop to collect locations and calculate bounds
	for (const item of results) {
		if (!item.acf?.organizations_details) continue;

		for (const org of item.acf.organizations_details) {
			const lat = parseFloat(org.institution_gmap?.lat);
			const lng = parseFloat(org.institution_gmap?.lng);
			const orgName = org.institutation_name || "";

			if (isNaN(lat) || isNaN(lng) || !orgName) continue;

			// Create a unique org ID based on organization data to ensure consistency
			const orgId = `org-${generateOrgId(org, item.title)}`;
			const location = {
				lat,
				lng,
				title: orgName,
				address: org.institution_gmap?.name || "",
				cityState: [
					org.institution_gmap?.city,
					org.institution_gmap?.state,
				]
					.filter(Boolean)
					.join(", "),
				physicianName: item.title,
				physicianCredentials: item.acf?.credentials || "",
				orgId: orgId,
			};

			locations.push(location);
			bounds.extend(new google.maps.LatLng(lat, lng));
		}
	}

	if (locations.length === 0) {
		mapContainer.innerHTML = "<p>No locations available for mapping.</p>";
		return;
	}

	// Initialize map
	allergistMap = new google.maps.Map(mapContainer, {
		zoom: 10,
		center: bounds.getCenter(),
		mapTypeId: google.maps.MapTypeId.ROADMAP,
	});

	// Add markers efficiently
	for (const location of locations) {
		const marker = new google.maps.Marker({
			position: { lat: location.lat, lng: location.lng },
			map: allergistMap,
			title: location.title,
			icon: {
				url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
				scaledSize: new google.maps.Size(32, 32),
			},
		});

		// Add click listener for info window
		marker.addListener("click", () => {
			const content = createInfoWindowContent(location);
			mapInfoWindow.setContent(content);
			mapInfoWindow.open(allergistMap, marker);
		});

		mapMarkers.push(marker);

		// Store the mapping between organization ID and marker
		orgMarkerMap.set(location.orgId, marker);
	}

	// Fit map to show all markers
	if (locations.length === 1) {
		allergistMap.setCenter(bounds.getCenter());
		allergistMap.setZoom(15);
	} else {
		allergistMap.fitBounds(bounds);
	}
}

/**
 * Create HTML content for map info window
 * @param {Object} location - Location data
 * @returns {string} HTML content
 */
function createInfoWindowContent(location) {
	const parts = [
		`<div class="map-info-window">`,
		`<h4>${escapeHTML(location.title)}</h4>`,
	];

	if (location.address) {
		parts.push(
			`<p><strong>Address:</strong> ${escapeHTML(location.address)}</p>`
		);
	}

	if (location.cityState) {
		parts.push(
			`<p><strong>Location:</strong> ${escapeHTML(
				location.cityState
			)}</p>`
		);
	}

	if (location.physicianName) {
		parts.push(
			`<p><strong>Physician:</strong> ${escapeHTML(
				location.physicianName
			)}${
				location.physicianCredentials
					? `, ${escapeHTML(location.physicianCredentials)}`
					: ""
			}</p>`
		);
	}

	parts.push(`</div>`);
	return parts.join("");
}

/**
 * Generate a consistent organization ID based on organization data
 * @param {Object} org - Organization data
 * @param {string} physicianName - Physician name for uniqueness
 * @returns {string} Unique organization ID
 */
function generateOrgId(org, physicianName) {
	// Create a simple hash from organization name, address, and physician name
	const key = `${org.institutation_name || ""}-${
		org.institution_gmap?.name || ""
	}-${physicianName || ""}`;
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
	if (mapMarkers) {
		for (const marker of mapMarkers) {
			marker.setMap(null);
		}
		mapMarkers = [];
	}

	if (mapInfoWindow) {
		mapInfoWindow.close();
	}

	// Clear the organization-marker mapping
	orgMarkerMap.clear();

	allergistMap = null;
}

/**
 * Generate organizations HTML
 * @param {Array} organizations - Organizations data
 * @param {Object} physicianInfo - Physician information
 * @param {Set} orgIdsWithMarkers - Set of organization IDs that will have markers
 * @returns {string} HTML string
 */
function generateOrganizationsHTML(
	organizations,
	physicianInfo,
	orgIdsWithMarkers
) {
	if (!Array.isArray(organizations) || organizations.length === 0) {
		return "";
	}

	const parts = [];

	// Physician header
	parts.push(`
		<div class="far-physician-info">
			<h3 class="far-physician-name">
				<a href="${escapeHTML(physicianInfo.link)}" target="_blank">
					${escapeHTML(physicianInfo.title)}
				</a>
				${physicianInfo.credentials ? `, ${escapeHTML(physicianInfo.credentials)}` : ""}
			</h3>
			<p><strong>Practices OIT:</strong> ${physicianInfo.oit}</p>
		</div>
	`);

	// Organizations container
	parts.push(`<div class="far-orgs">`);

	for (const org of organizations) {
		// Generate the same org ID used in map initialization
		const orgId = `org-${generateOrgId(org, physicianInfo.title)}`;
		const orgName = org.institutation_name || "Organization";
		const address = org.institution_gmap?.name || "";
		const city = org.institution_gmap?.city || "";
		const state = org.institution_gmap?.state || "";
		const postalCode = org.institution_gmap?.post_code || "";
		const phone = org.institution_phone || "";
		const distance = org.distance_km;

		// Check if this organization will have a marker on the map
		const hasMapMarker = orgIdsWithMarkers.has(orgId);

		parts.push(`<div class="far-org" id="${orgId}">`);
		parts.push(`<h4 class="far-org-title">${escapeHTML(orgName)}</h4>`);
		parts.push(`<ul class="far-org-list">`);

		if (address) {
			parts.push(
				`<li class="far-org-list-item"> ${escapeHTML(address)}</li>`
			);
		}

		if (city || state) {
			const cityStateParts = [city, state].filter(Boolean);
			parts.push(
				`<li class="far-org-list-item"> ${escapeHTML(
					cityStateParts.join(", ")
				)}</li>`
			);
		}

		if (postalCode) {
			parts.push(
				`<li class="far-org-list-item"> ${escapeHTML(postalCode)}</li>`
			);
		}

		if (phone) {
			parts.push(
				`<li class="far-org-list-item"><strong aria-label="Phone">T:</strong> ${escapeHTML(
					phone
				)}</li>`
			);
		}

		if (distance !== undefined) {
			parts.push(
				`<li class="far-org-list-item"><strong>Distance:</strong> ${distance} km</li>`
			);
		}

		// Add "Show on map" link if this organization has a marker
		if (hasMapMarker) {
			parts.push(
				`<li class="far-org-list-item far-org-list-item--map-link"><a href="#" class="show-on-map-link" data-org-id="${orgId}">📍 Show on map</a></li>`
			);
		}

		parts.push(`</ul>`);
		parts.push(`</div>`);
	}

	parts.push(`</div>`);
	return parts.join("");
}

/**
 * Get all form data as an object
 * @returns {Object} Form data object
 */
function getAllFormData() {
	// Cache field elements to avoid repeated DOM queries
	const fields = {
		fname: document.getElementById("phy_fname"),
		lname: document.getElementById("phy_lname"),
		oit: document.getElementById("phy_oit"),
		city: document.getElementById("phy_city"),
		postal: document.getElementById("phy_postal"),
		province: document.getElementById("phy_province"),
		kms: document.getElementById("phy_kms"),
	};

	return {
		phy_fname: fields.fname?.value.trim() || "",
		phy_lname: fields.lname?.value.trim() || "",
		phy_oit: fields.oit?.checked || false,
		phy_city: fields.city?.value.trim() || "",
		phy_postal: fields.postal?.value.trim() || "",
		phy_province: fields.province?.value.trim() || "",
		phy_kms: fields.kms?.value.trim() || "30",
	};
}

/**
 * Normalize Canadian postal (uppercase, no spaces)
 */
function normalizePostal(v) {
	return String(v || "")
		.toUpperCase()
		.replace(/\s+/g, "");
}

/**
 * Basic helper to set results container HTML
 */
function setResultsHTML(html) {
	const container = document.getElementById("results");
	if (container) container.innerHTML = html;
}

/**
 * Helper to set search results content HTML (for pagination updates)
 */
function setSearchResultsContentHTML(html) {
	const container = document.getElementById("search-results-content");
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
		paginationParts.push(
			`<button type="button" class="pagination-btn" data-page="${prevPage}">← Previous</button>`
		);
	} else {
		paginationParts.push(
			`<button type="button" class="pagination-btn disabled" disabled>← Previous</button>`
		);
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
		paginationParts.push(
			`<button type="button" class="pagination-btn page-number" data-page="1">1</button>`
		);
		if (startPage > 2) {
			paginationParts.push(
				'<span class="pagination-ellipsis">...</span>'
			);
		}
	}

	// Show page numbers in range
	for (let i = startPage; i <= endPage; i++) {
		if (i === currentPage) {
			paginationParts.push(
				`<button type="button" class="pagination-btn page-number current" disabled>${i}</button>`
			);
		} else {
			paginationParts.push(
				`<button type="button" class="pagination-btn page-number" data-page="${i}">${i}</button>`
			);
		}
	}

	// Show last page if not in range
	if (endPage < totalPages) {
		if (endPage < totalPages - 1) {
			paginationParts.push(
				'<span class="pagination-ellipsis">...</span>'
			);
		}
		paginationParts.push(
			`<button type="button" class="pagination-btn page-number" data-page="${totalPages}">${totalPages}</button>`
		);
	}

	paginationParts.push("</span>");

	// Next page button
	if (nextPage) {
		paginationParts.push(
			`<button type="button" class="pagination-btn" data-page="${nextPage}">Next →</button>`
		);
	} else {
		paginationParts.push(
			`<button type="button" class="pagination-btn disabled" disabled>Next →</button>`
		);
	}

	paginationParts.push("</div>");

	return paginationParts.join("");
}

/**
 * Handle "Show on map" link clicks
 * @param {string} orgId - Organization ID
 */
function showMarkerOnMap(orgId) {
	const marker = orgMarkerMap.get(orgId);
	if (!marker || !allergistMap) {
		console.log("Marker or map not found for org ID:", orgId);
		return;
	}

	// Center the map on the marker
	allergistMap.setCenter(marker.getPosition());
	allergistMap.setZoom(15);

	// Trigger the marker click event to show the info window
	google.maps.event.trigger(marker, "click");

	// Scroll to the map for better user experience
	const mapContainer = document.getElementById("allergist-map");
	if (mapContainer) {
		mapContainer.scrollIntoView({
			behavior: "smooth",
			block: "center",
		});
	}
}

/**
 * Handle pagination button clicks and show on map links
 */
function handleDocumentClick(event) {
	// Handle pagination button clicks
	if (
		event.target.classList.contains("pagination-btn") &&
		!event.target.disabled
	) {
		const page = parseInt(event.target.dataset.page);
		if (page && currentSearchData) {
			// Scroll to top of results
			const resultsContainer = document.getElementById("results");
			if (resultsContainer) {
				resultsContainer.scrollIntoView({
					behavior: "smooth",
					block: "start",
				});
			}

			// Perform search for the selected page (client-side navigation)
			handleSearchSubmit(page);
		}
	}

	// Handle "Show on map" link clicks
	if (event.target.classList.contains("show-on-map-link")) {
		event.preventDefault(); // Prevent default link behavior
		const orgId = event.target.dataset.orgId;
		if (orgId) {
			showMarkerOnMap(orgId);
		}
	}
}

// Add event listener for clicks using event delegation
document.addEventListener("click", handleDocumentClick);

/**
 * Optimized HTML escaper for titles/strings we render
 */
function escapeHTML(str) {
	if (!str) return "";

	// Use a more efficient approach with replace chain
	return String(str)
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}
