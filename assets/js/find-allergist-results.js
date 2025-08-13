// Find Allergist Results JavaScript
document.addEventListener("DOMContentLoaded", function () {
	// Handle form submission
	const allergistForm = document.getElementById("allergistfrm");
	if (allergistForm) {
		allergistForm.addEventListener("submit", function (e) {
			e.preventDefault();
			handleSearchSubmit();
		});
	}

	// Handle search button click
	const btnSearch = document.getElementById("btn-search");
	if (btnSearch) {
		btnSearch.addEventListener("click", function (e) {
			e.preventDefault();
			handleSearchSubmit();
		});
	}

	// Handle clear button click
	const btnClear = document.getElementById("btn-clear");
	if (btnClear) {
		btnClear.addEventListener("click", function (e) {
			e.preventDefault();
			allergistForm.reset();
		});
	}
});

const ENDPOINT = "/wp-json/my/v1/physicians/search";

// Global variables for map functionality
let allergistMap = null;
let mapMarkers = [];
let mapInfoWindow = null;
let orgIndexCounter = 0;

/**
 * Handle search form submission
 */
async function handleSearchSubmit() {
	console.log("Search submitted");

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
	if (formData.phy_miles) params.set("miles", formData.phy_miles);

	// Optional pagination defaults
	params.set("per_page", "10");
	params.set("page", "1");

	// UI: basic loading state
	setResultsHTML("<p>Searching…</p>");

	try {
		const nonce = window.wpApiSettings?.nonce; // present if you localized it; not required for public reads
		const res = await fetch(`${ENDPOINT}?${params.toString()}`, {
			headers: nonce ? { "X-WP-Nonce": nonce } : undefined,
		});

		if (!res.ok) {
			throw new Error(`REST request failed (${res.status})`);
		}

		const data = await res.json();
		renderResults(data);
	} catch (err) {
		console.error(err);
		setResultsHTML(
			'<p role="alert">Sorry, something went wrong. Please try again.</p>'
		);
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

	// Reset global variables
	mapMarkers = [];
	mapInfoWindow = new google.maps.InfoWindow();

	// Collect all organization locations
	const locations = [];
	let orgIndex = 0;

	results.forEach((item) => {
		if (item.acf?.organizations_details) {
			item.acf.organizations_details.forEach((org) => {
				const lat = parseFloat(org.institution_gmap?.lat);
				const lng = parseFloat(org.institution_gmap?.lng);
				const orgName = org.institutation_name || "";
				const address = org.institution_gmap?.name || "";
				const city = org.institution_gmap?.city || "";
				const state = org.institution_gmap?.state || "";
				const cityState = [city, state].filter(Boolean).join(", ");

				if (!isNaN(lat) && !isNaN(lng) && orgName) {
					locations.push({
						lat: lat,
						lng: lng,
						title: orgName,
						address: address,
						cityState: cityState,
						physicianName: item.title,
						physicianCredentials: item.acf?.credentials || "",
						orgId: `org-${orgIndex}`, // Unique identifier for each organization
					});
					orgIndex++;
				}
			});
		}
	});

	if (locations.length === 0) {
		mapContainer.innerHTML = "<p>No locations available for mapping.</p>";
		return;
	}

	// Calculate map bounds
	const bounds = new google.maps.LatLngBounds();
	locations.forEach((location) => {
		bounds.extend(new google.maps.LatLng(location.lat, location.lng));
	});

	// Initialize map
	allergistMap = new google.maps.Map(mapContainer, {
		zoom: 10,
		center: bounds.getCenter(),
		mapTypeId: google.maps.MapTypeId.ROADMAP,
	});

	// Add markers
	locations.forEach((location, index) => {
		const marker = new google.maps.Marker({
			position: { lat: location.lat, lng: location.lng },
			map: allergistMap,
			title: location.title,
			animation: google.maps.Animation.DROP,
		});

		// Create info window content
		const infoContent = `
			<div class="map-info-window">
				<h4>${escapeHTML(location.title)}</h4>
				<p><strong>${escapeHTML(location.physicianName)}</strong>
				${
					location.physicianCredentials
						? `<br><em>${escapeHTML(
								location.physicianCredentials
						  )}</em>`
						: ""
				}
				</p>
				${location.address ? `<p>${escapeHTML(location.address)}</p>` : ""}
				${location.cityState ? `<p>${escapeHTML(location.cityState)}</p>` : ""}
			</div>
		`;

		marker.addListener("click", () => {
			mapInfoWindow.setContent(infoContent);
			mapInfoWindow.open(allergistMap, marker);
		});

		// Store marker with orgId for reference
		mapMarkers.push({
			marker: marker,
			infoContent: infoContent,
			orgId: location.orgId,
		});
	});

	// Fit map to show all markers
	if (locations.length > 1) {
		allergistMap.fitBounds(bounds);
	} else {
		allergistMap.setCenter(bounds.getCenter());
		allergistMap.setZoom(15);
	}
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

	const organizationsList = organizations
		.map((org) => {
			const orgName = org.institutation_name || "";
			const orgAddressName = org.institution_gmap?.name || "";
			const orgCity = org.institution_gmap?.city || "";
			const orgState = org.institution_gmap?.state || "";
			const orgPostal = org.institution_gmap?.post_code || "";
			const lat = parseFloat(org.institution_gmap?.lat);
			const lng = parseFloat(org.institution_gmap?.lng);
			const distance = org.distance_km || null; // Distance from backend calculation

			// Create unique org ID using global counter
			const orgId = `org-${orgIndexCounter}`;
			const hasValidCoords = !isNaN(lat) && !isNaN(lng);
			orgIndexCounter++; // Increment global counter

			// Format city and state display
			const cityState = [orgCity, orgState].filter(Boolean).join(", ");

			if (!orgName) return ""; // Skip if no organization name

			return `
				<div class="far-org">
					<div class="far-physician-info">
						<a href="${link}" rel="bookmark" class="far-physician-name">${escapeHTML(
				title
			)}</a>
						${
							credentials
								? `<div class="far-physician-credentials">${escapeHTML(
										credentials
								  )}</div>`
								: ""
						}
						${
							oit !== ""
								? `<div class="far-oit-status">Practices Oral Immunotherapy (OIT)?: ${oit}</div>`
								: ""
						}
						${
							distance !== null
								? `<div class="far-distance">Distance: ${distance} km</div>`
								: ""
						}
					</div>
					<div class="far-org-info">
						<strong class="far-org-name">${escapeHTML(orgName)}</strong>
						${
							orgAddressName
								? `<div class="far-org-address">${escapeHTML(
										orgAddressName
								  )}</div>`
								: ""
						}
						${
							cityState
								? `<div class="far-org-city-state">${escapeHTML(
										cityState
								  )}</div>`
								: ""
						}
						${orgPostal ? `<div class="far-org-postal">${escapeHTML(orgPostal)}</div>` : ""}
						${
							hasValidCoords
								? `<div class="far-view-map"><a href="#" onclick="showOnMap('${orgId}'); return false;" class="far-map-link">📍 View on Map</a></div>`
								: ""
						}
					</div>
				</div>`;
		})
		.filter(Boolean) // Remove empty entries
		.join("");

	return organizationsList
		? `<div class="far-orgs">${organizationsList}</div>`
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

	const list = results
		.map((item) => {
			const city = item.acf?.city || "";
			const prov = item.acf?.province || "";
			const location = [city, prov].filter(Boolean).join(", ");
			const credentials = item.acf?.credentials || "";
			const oit =
				item.acf?.oit === "OIT"
					? "Yes"
					: item.acf?.oit === ""
					? "No"
					: "";

			// Prepare physician info for organizations
			const physicianInfo = {
				title: item.title,
				credentials: credentials,
				oit: oit,
				link: item.link,
			};

			// Generate organizations HTML using separate function
			const organizationsHTML = generateOrganizationsHTML(
				item.acf?.organizations_details,
				physicianInfo
			);

			return `
        <li class="far-list-item">
          ${organizationsHTML}
        </li>`;
		})
		.join("");

	setResultsHTML(`
    <div id="allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px;"></div>
    <p>Found ${count} result${count === 1 ? "" : "s"}.</p>
    <ul class="far-list">${list}</ul>
  `);

	// Initialize the map after HTML is rendered
	setTimeout(() => initializeMap(results), 100);
}

/**
 * Get all form data as an object
 * @returns {Object} Form data object
 */
function getAllFormData() {
	return {
		phy_fname: getFieldValue("phy_fname"),
		phy_lname: getFieldValue("phy_lname"),
		phy_oit: document.getElementById("phy_oit")
			? document.getElementById("phy_oit").checked
			: false,
		phy_city: getFieldValue("phy_city"),
		phy_postal: getFieldValue("phy_postal"),
		phy_province: getFieldValue("phy_province"),
		phy_miles: getFieldValue("phy_miles") || "30",
	};
}

/**
 * Get field value safely
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
 * Very small HTML escaper for titles/strings we render
 */
function escapeHTML(str) {
	return String(str)
		.replaceAll("&", "&amp;")
		.replaceAll("<", "&lt;")
		.replaceAll(">", "&gt;")
		.replaceAll('"', "&quot;")
		.replaceAll("'", "&#039;");
}
