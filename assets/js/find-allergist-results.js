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
let orgIndexCounter = 0;
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
			renderPaginatedResults(1);
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
		renderPaginatedResults(page);
	}
}

/**
 * Render paginated results using client-side pagination
 * @param {number} page - Page number to render
 */
function renderPaginatedResults(page) {
	console.log(
		`Rendering page ${page} of ${Math.ceil(
			allSearchResults.length / resultsPerPage
		)}`
	);

	if (!Array.isArray(allSearchResults) || allSearchResults.length === 0) {
		setResultsHTML("<p>No matches found.</p>");
		return;
	}

	// Calculate pagination
	const totalResults = allSearchResults.length;
	const totalPages = Math.ceil(totalResults / resultsPerPage);
	const startIndex = (page - 1) * resultsPerPage;
	const endIndex = Math.min(startIndex + resultsPerPage, totalResults);
	const currentPageResults = allSearchResults.slice(startIndex, endIndex);

	// Reset organization counter for consistent indexing
	orgIndexCounter = 0;

	// Build results HTML efficiently
	const resultParts = [
		'<div id="allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px;"></div>',
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

	resultParts.push('<ul class="far-list">');

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
			physicianInfo
		);

		if (organizationsHTML) {
			resultParts.push(
				`<li class="far-list-item">${organizationsHTML}</li>`
			);
		}
	}

	resultParts.push("</ul>");

	// Add pagination controls at the bottom if there are multiple pages
	if (totalPages > 1) {
		const prevPage = page > 1 ? page - 1 : null;
		const nextPage = page < totalPages ? page + 1 : null;
		resultParts.push(
			generatePaginationHTML(page, totalPages, prevPage, nextPage)
		);
	}

	setResultsHTML(resultParts.join(""));

	// Initialize the map after HTML is rendered - use all results for map markers
	setTimeout(() => initializeMap(allSearchResults), 100);
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
	mapInfoWindow = new google.maps.InfoWindow();
	const bounds = new google.maps.LatLngBounds();
	const locations = [];
	let orgIndex = 0;

	// Single loop to collect locations and calculate bounds
	for (const item of results) {
		if (!item.acf?.organizations_details) continue;

		for (const org of item.acf.organizations_details) {
			const lat = parseFloat(org.institution_gmap?.lat);
			const lng = parseFloat(org.institution_gmap?.lng);
			const orgName = org.institutation_name || "";

			if (isNaN(lat) || isNaN(lng) || !orgName) continue;

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
				orgId: `org-${orgIndex++}`,
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

	allergistMap = null;
}

/**
 * Generate organizations HTML
 * @param {Array} organizations - Organizations data
 * @param {Object} physicianInfo - Physician information
 * @returns {string} HTML string
 */
function generateOrganizationsHTML(organizations, physicianInfo) {
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
		const orgId = `org-${orgIndexCounter++}`;
		const orgName = org.institutation_name || "Organization";
		const address = org.institution_gmap?.name || "";
		const city = org.institution_gmap?.city || "";
		const state = org.institution_gmap?.state || "";
		const postalCode = org.institution_gmap?.post_code || "";
		const phone = org.institution_phone || "";
		const distance = org.distance_km;

		parts.push(`<div class="far-org" id="${orgId}">`);
		parts.push(`<h4>${escapeHTML(orgName)}</h4>`);

		if (address) {
			parts.push(
				`<p><strong>Address:</strong> ${escapeHTML(address)}</p>`
			);
		}

		if (city || state || postalCode) {
			const locationParts = [city, state, postalCode].filter(Boolean);
			parts.push(
				`<p><strong>Location:</strong> ${escapeHTML(
					locationParts.join(", ")
				)}</p>`
			);
		}

		if (phone) {
			parts.push(`<p><strong>Phone:</strong> ${escapeHTML(phone)}</p>`);
		}

		if (distance !== undefined) {
			parts.push(`<p><strong>Distance:</strong> ${distance} km</p>`);
		}

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
 * Handle pagination button clicks
 */
function handlePaginationClick(event) {
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
}

// Add event listener for pagination clicks using event delegation
document.addEventListener("click", handlePaginationClick);

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
