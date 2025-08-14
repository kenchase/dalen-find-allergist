/**
 * Admin JavaScript for Dalen Find Allergist plugin
 */

jQuery(document).ready(function ($) {
	"use strict";

	// Initialize admin functionality
	DalenFindAllergistAdmin.init();
});

var DalenFindAllergistAdmin = {
	/**
	 * Initialize all admin functionality
	 */
	init: function () {
		this.initSettings();
		this.initHelp();
		this.initNotifications();
	},

	/**
	 * Initialize settings page functionality
	 */
	initSettings: function () {
		// Settings validation
		this.validateSettings();

		// API key testing
		this.initApiKeyTesting();
	},

	/**
	 * Validate settings before saving
	 */
	validateSettings: function () {
		$("form").on("submit", function (e) {
			var isValid = true;
			var errors = [];

			// Validate Google Maps API key format
			var apiKey = $("#google_maps_api_key").val();
			if (apiKey && !apiKey.match(/^AIza[0-9A-Za-z-_]{35}$/)) {
				isValid = false;
				errors.push(
					"Google Maps API key format appears to be invalid."
				);
			}

			// Validate search results limit
			var limit = parseInt($("#search_results_limit").val());
			if (limit < 1 || limit > 100) {
				isValid = false;
				errors.push("Search results limit must be between 1 and 100.");
			}

			// Validate search radius
			var radius = parseInt($("#default_search_radius").val());
			if (radius < 1 || radius > 500) {
				isValid = false;
				errors.push(
					"Default search radius must be between 1 and 500 km."
				);
			}

			if (!isValid) {
				e.preventDefault();
				alert(
					"Please fix the following errors:\n\n" + errors.join("\n")
				);
			}
		});
	},

	/**
	 * Initialize API key testing functionality
	 */
	initApiKeyTesting: function () {
		// Add test button after API key field
		if ($("#google_maps_api_key").length) {
			var testButton = $(
				'<button type="button" class="button button-secondary" id="test-api-key" style="margin-left: 10px;">Test API Key</button>'
			);
			$("#google_maps_api_key").after(testButton);

			$("#test-api-key").on("click", function () {
				var apiKey = $("#google_maps_api_key").val();
				if (!apiKey) {
					alert("Please enter an API key first.");
					return;
				}

				$(this).prop("disabled", true).text("Testing...");

				// Test the API key by making a simple geocoding request
				DalenFindAllergistAdmin.testGoogleMapsApiKey(apiKey, $(this));
			});
		}
	},

	/**
	 * Test Google Maps API key
	 */
	testGoogleMapsApiKey: function (apiKey, button) {
		var testUrl =
			"https://maps.googleapis.com/maps/api/geocode/json?address=Toronto,ON&key=" +
			apiKey;

		$.ajax({
			url: testUrl,
			method: "GET",
			timeout: 10000,
		})
			.done(function (response) {
				if (response.status === "OK") {
					button
						.removeClass("button-secondary")
						.addClass("button-primary")
						.text("✓ API Key Valid");
					setTimeout(function () {
						button
							.removeClass("button-primary")
							.addClass("button-secondary")
							.text("Test API Key")
							.prop("disabled", false);
					}, 3000);
				} else {
					button.text("✗ API Key Invalid").css("color", "#dc3232");
					setTimeout(function () {
						button
							.text("Test API Key")
							.css("color", "")
							.prop("disabled", false);
					}, 3000);
					console.error(
						"API Key test failed:",
						response.status,
						response.error_message
					);
				}
			})
			.fail(function (xhr, status, error) {
				button.text("✗ Test Failed").css("color", "#dc3232");
				setTimeout(function () {
					button
						.text("Test API Key")
						.css("color", "")
						.prop("disabled", false);
				}, 3000);
				console.error("API Key test failed:", status, error);
			});
	},

	/**
	 * Initialize help page functionality
	 */
	initHelp: function () {
		// FAQ accordion
		$(document).on("click", ".dalen-faq-question", function () {
			var answer = $(this).next(".dalen-faq-answer");
			var isOpen = answer.is(":visible");

			// Close all other FAQ items
			$(".dalen-faq-answer").slideUp();
			$(".dalen-faq-question").removeClass("active");

			// Toggle current item
			if (!isOpen) {
				answer.slideDown();
				$(this).addClass("active");
			}
		});

		// Smooth scrolling for anchor links
		$('a[href^="#"]').on("click", function (e) {
			e.preventDefault();
			var target = $(this.getAttribute("href"));
			if (target.length) {
				$("html, body").scrollTop(target.offset().top - 32);
			}
		});

		// Copy shortcode functionality
		$(document).on("click", ".dalen-shortcode-item code", function () {
			var text = $(this).text();
			if (navigator.clipboard) {
				navigator.clipboard.writeText(text).then(
					function () {
						// Show temporary success message
						var original = $(this).text();
						$(this).text("Copied!").css("background", "#46b450");
						setTimeout(
							function () {
								$(this).text(original).css("background", "");
							}.bind(this),
							1000
						);
					}.bind(this)
				);
			}
		});
	},

	/**
	 * Initialize notification system
	 */
	initNotifications: function () {
		// Auto-hide success messages
		$(".notice.is-dismissible").each(function () {
			var notice = $(this);
			setTimeout(function () {
				notice.fadeOut();
			}, 5000);
		});

		// Check for plugin updates
		this.checkForUpdates();
	},

	/**
	 * Check for plugin updates
	 */
	checkForUpdates: function () {
		// This would typically check against a remote server
		// For now, we'll just check if we're on an admin page
		if ($(".dalen-admin-header").length) {
			// Plugin is loaded and working
			console.log("Dalen Find Allergist Admin loaded successfully");
		}
	},

	/**
	 * Utility function to show admin notices
	 */
	showNotice: function (message, type) {
		type = type || "info";
		var noticeHtml =
			'<div class="notice notice-' +
			type +
			' is-dismissible"><p>' +
			message +
			"</p></div>";
		$(".wrap h1").after(noticeHtml);
	},

	/**
	 * Utility function for AJAX error handling
	 */
	handleAjaxError: function (xhr, status, error) {
		console.error("AJAX Error:", status, error);
		this.showNotice("An error occurred. Please try again.", "error");
	},
};

// Global utility functions
window.DalenFindAllergistAdmin = DalenFindAllergistAdmin;
