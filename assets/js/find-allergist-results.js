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
		});
	}
});

const ENDPOINT = "/wp-json/my/v1/physicians/search";

// Global variables for map functionality
let allergistMap = null;
let mapMarkers = [];
let mapInfoWindow = null;
let orgIndexCounter = 0;
let searchController = null; // For aborting previous requests

/**
 * Handle search form submission
 */
async function handleSearchSubmit() {
	console.log("Search submitted");

	// Abort previous request if still pending
	if (searchController) {
		searchController.abort();
	}

	// Create new abort controller for this request
	searchController = new AbortController();

	// Get all form data
	const formData = getAllFormData();

	// Build query params (send only what’s filled)
	const params = new URLSearchParams();
	if (formData.phy_fname) params.set("fname", formData.phy_fname);
	if (formData.phy_lname) params.set("lname", formData.phy_lname);
	if (typeof formData.phy_oit === "boolean")
		params.set("oit", String(!!formData.phy_oit));
	if (formData.phy_city) params.set("city", formData.phy_city);
	if (formData.phy_province) params.set("province", formData.phy_province);
	if (formData.phy_postal)
		params.set("postal", normalizePostal(formData.phy_postal));
	if (formData.phy_kms) params.set("kms", formData.phy_kms);

	// Optional pagination defaults
	params.set("per_page", "10");
	params.set("page", "1");

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
		renderResults(data);
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
	} finally {
		searchController = null;
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
	createMapMarkers(locations);

	// Fit map to show all markers
	if (locations.length > 1) {
		allergistMap.fitBounds(bounds);
	} else {
		allergistMap.setCenter(bounds.getCenter());
		allergistMap.setZoom(15);
	}
}

/**
 * Clean up existing map resources to prevent memory leaks
 */
function cleanupMap() {
	if (mapMarkers.length > 0) {
		mapMarkers.forEach(({ marker }) => {
			if (marker.setMap) marker.setMap(null);
		});
		mapMarkers = [];
	}
	if (mapInfoWindow) {
		mapInfoWindow.close();
	}
}

/**
 * Create map markers efficiently
 * @param {Array} locations - Array of location objects
 */
function createMapMarkers(locations) {
	const infoContents = new Map(); // Cache info window content

	locations.forEach((location) => {
		const marker = new google.maps.Marker({
			position: { lat: location.lat, lng: location.lng },
			map: allergistMap,
			title: location.title,
			animation: google.maps.Animation.DROP,
		});

		// Generate and cache info content
		const infoContent = generateInfoWindowContent(location);
		infoContents.set(location.orgId, infoContent);

		marker.addListener("click", () => {
			mapInfoWindow.setContent(infoContent);
			mapInfoWindow.open(allergistMap, marker);
		});

		mapMarkers.push({
			marker,
			infoContent,
			orgId: location.orgId,
		});
	});
}

/**
 * Generate info window content
 * @param {Object} location - Location object
 * @returns {string} HTML content for info window
 */
function generateInfoWindowContent(location) {
	const parts = [
		`<div class="map-info-window">`,
		`<h4>${escapeHTML(location.title)}</h4>`,
		`<p><strong>${escapeHTML(location.physicianName)}</strong>`,
	];

	if (location.physicianCredentials) {
		parts.push(`<br><em>${escapeHTML(location.physicianCredentials)}</em>`);
	}

	parts.push(`</p>`);

	if (location.address) {
		parts.push(`<p>${escapeHTML(location.address)}</p>`);
	}

	if (location.cityState) {
		parts.push(`<p>${escapeHTML(location.cityState)}</p>`);
	}

	parts.push(`</div>`);

	return parts.join("");
}

/**
 * Show marker info window and scroll to map
 * @param {string} orgId - Organization ID to show on map
 */
function showOnMap(orgId) {
	const markerData = mapMarkers.find((m) => m.orgId === orgId);
	if (markerData && mapInfoWindow && allergistMap) {
		// Set info window content and open it
		mapInfoWindow.setContent(markerData.infoContent);
		mapInfoWindow.open(allergistMap, markerData.marker);

		// Center map on the marker
		allergistMap.setCenter(markerData.marker.getPosition());

		// Scroll to map
		const mapContainer = document.getElementById("allergist-map");
		if (mapContainer) {
			mapContainer.scrollIntoView({
				behavior: "smooth",
				block: "center",
			});
		}
	}
}

/**
 * Generate organizations HTML from repeater field data
 * @param {Array} organizations - Array of organization objects from ACF repeater
 * @param {Object} physicianInfo - Object containing physician's basic info
 * @returns {string} HTML string for organizations
 */
function generateOrganizationsHTML(organizations, physicianInfo = {}) {
	if (!Array.isArray(organizations) || organizations.length === 0) {
		return "";
	}

	const { title = "", credentials = "", oit = "", link = "" } = physicianInfo;
	const organizationParts = [];

	for (const org of organizations) {
		const orgName = org.institutation_name || "";
		if (!orgName) continue; // Skip if no organization name

		const orgAddressName = org.institution_gmap?.name || "";
		const orgCity = org.institution_gmap?.city || "";
		const orgState = org.institution_gmap?.state || "";
		const orgPostal = org.institution_gmap?.post_code || "";
		const lat = parseFloat(org.institution_gmap?.lat);
		const lng = parseFloat(org.institution_gmap?.lng);
		const distance = org.distance_km || null;

		// Create unique org ID using global counter
		const orgId = `org-${orgIndexCounter++}`;
		const hasValidCoords = !isNaN(lat) && !isNaN(lng);
		const cityState = [orgCity, orgState].filter(Boolean).join(", ");

		// Build HTML parts array for better performance
		const htmlParts = [
			'<div class="far-org">',
			'<div class="far-physician-info">',
			`<a href="${link}" rel="bookmark" class="far-physician-name">${escapeHTML(
				title
			)}</a>`,
		];

		if (credentials) {
			htmlParts.push(
				`<div class="far-physician-credentials">${escapeHTML(
					credentials
				)}</div>`
			);
		}

		if (oit) {
			htmlParts.push(
				`<div class="far-oit-status">Practices Oral Immunotherapy (OIT)?: ${oit}</div>`
			);
		}

		if (distance !== null) {
			htmlParts.push(
				`<div class="far-distance">Distance: ${distance} km</div>`
			);
		}

		htmlParts.push(
			"</div>",
			'<div class="far-org-info">',
			`<strong class="far-org-name">${escapeHTML(orgName)}</strong>`
		);

		if (orgAddressName) {
			htmlParts.push(
				`<div class="far-org-address">${escapeHTML(
					orgAddressName
				)}</div>`
			);
		}

		if (cityState) {
			htmlParts.push(
				`<div class="far-org-city-state">${escapeHTML(cityState)}</div>`
			);
		}

		if (orgPostal) {
			htmlParts.push(
				`<div class="far-org-postal">${escapeHTML(orgPostal)}</div>`
			);
		}

		if (hasValidCoords) {
			htmlParts.push(
				`<div class="far-view-map"><a href="#" onclick="showOnMap('${orgId}'); return false;" class="far-map-link">📍 View on Map</a></div>`
			);
		}

		htmlParts.push("</div>", "</div>");
		organizationParts.push(htmlParts.join(""));
	}

	return organizationParts.length > 0
		? `<div class="far-orgs">${organizationParts.join("")}</div>`
		: "";
}

/**
 * Render results into #results
 * Expects the response shape from the earlier PHP example:
 * { page, per_page, count, results: [{id,title,link,acf:{...}}] }
 */
function renderResults(payload) {
	// Reset organization counter for consistent indexing
	orgIndexCounter = 0;

	const container = document.getElementById("results");
	if (!container) {
		console.log("Results:", payload);
		return;
	}

	const { results = [], count = 0 } = payload || {};
	if (!Array.isArray(results) || results.length === 0) {
		setResultsHTML("<p>No matches found.</p>");
		return;
	}

	// Build results HTML efficiently
	const resultParts = [
		'<div id="allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px;"></div>',
		`<p>Found ${count} result${count === 1 ? "" : "s"}.</p>`,
		'<ul class="far-list">',
	];

	for (const item of results) {
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
	setResultsHTML(resultParts.join(""));

	// Initialize the map after HTML is rendered
	setTimeout(() => initializeMap(results), 100);
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
 * Get field value safely (legacy function for backward compatibility)
 * @param {string} fieldId - The ID of the field
 * @returns {string} Field value or empty string
 */
function getFieldValue(fieldId) {
	const field = document.getElementById(fieldId);
	return field ? field.value.trim() : "";
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
