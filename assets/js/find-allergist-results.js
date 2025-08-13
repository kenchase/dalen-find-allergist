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
			const orgAddress = org.institutation_map?.address || "";

			if (!orgName) return ""; // Skip if no organization name

			return `
				<div class="organization">
					<div class="physician-info">
						<a href="${link}" rel="bookmark" class="physician-name">${escapeHTML(title)}</a>
						${
							credentials
								? `<div class="physician-credentials">${escapeHTML(
										credentials
								  )}</div>`
								: ""
						}
						${
							oit !== ""
								? `<div class="oit-status">Practices Oral Immunotherapy (OIT)?: ${oit}</div>`
								: ""
						}
					</div>
					<div class="organization-info">
						<strong class="org-name">${escapeHTML(orgName)}</strong>
						${orgAddress ? `<div class="org-address">${escapeHTML(orgAddress)}</div>` : ""}
					</div>
				</div>`;
		})
		.filter(Boolean) // Remove empty entries
		.join("");

	return organizationsList
		? `<div class="organizations">${organizationsList}</div>`
		: "";
}

/**
 * Render results into #results
 * Expects the response shape from the earlier PHP example:
 * { page, per_page, count, results: [{id,title,link,acf:{...}}] }
 */
function renderResults(payload) {
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
        <li class="search-result-item">
          ${organizationsHTML}
        </li>`;
		})
		.join("");

	setResultsHTML(`
    <p>Found ${count} result${count === 1 ? "" : "s"}.</p>
    <ul>${list}</ul>
  `);
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
