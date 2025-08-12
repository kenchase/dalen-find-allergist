const urlsearchparams = new URLSearchParams(window.location.search);
const querystring_obj = Object.fromEntries(urlsearchparams.entries()) || {};

var map = null;
var markers = [];
var results_page_current = 0;
var search_results_per_page = 10;

function ajax(data, callback) {
	var data_send = {
		action: "find_an_allergist",
		data: data,
	};

	jQuery.post(ajax_url, data_send, callback);
}

function allergistSearch(that) {
	var values = jQuery(that).serialize();

	ajax(values, searchResultsDisplay);
}

function searchResultsDisplay(json) {
	var data = JSON.parse(json);

	markers = []; //Clear array

	var map_info = data.map_info;
	var items = data.items;

	var total_count = items.length; //TODO - write out the count somewhere

	results_page_current = 0;

	if (total_count > 0) {
		document.getElementById("no-results-container").style.display = "none";

		document.getElementById("search-results-container").style.display =
			"grid";

		var target = document.getElementById("search-result-count");
		target.innerHTML = total_count.toString();

		searchResultsPageDisplay(items, 0, search_results_per_page);

		searchUpdateMap(items, data.marker_path);
	} else {
		document.getElementById("no-results-container").style.display = "block";

		document.getElementById("search-results-container").style.display =
			"none";
	}
}

function searchResultsPageDisplay(items, page_num, per_page) {
	var target_page;

	var target = document.getElementById("search-results");
	target.innerHTML = "";

	var len = items.length;

	showResultsPagination(
		page_num,
		Math.ceil(len / per_page),
		"search-results-page"
	);

	var page_counter = 0;
	var page_previous = -1;

	for (var i = 0; i < len; i++) {
		page_counter = Math.floor(i / per_page);

		if (page_previous != page_counter) {
			target_page = document.createElement("div");
			target_page.id = "search-results-page-" + page_counter.toString();
			if (page_counter != page_num) {
				target_page.style.display = "none";
			}
			target.appendChild(target_page);

			page_previous = page_counter;
		}

		var key = i;
		var item = items[i];

		var box = document.createElement("div");
		box.className = "search-result-item";

		var address_reformatted = item["address"]["address"];

		if (item["address"]["city"]) {
			address_reformatted += "<br>" + item["address"]["city"];

			if (item["address"]["state"]) {
				address_reformatted += ", " + item["address"]["state"];
			}
		}

		if (item["address"]["zipcode"]) {
			address_reformatted += "<br>" + item["address"]["zipcode"];
		}

		var extension = "";
		var extension_link = "";
		if (item["institution"]["ext"].trim() != "") {
			extension = " ext. " + item["institution"]["ext"].trim();
			extension_link = ";" + item["institution"]["ext"].trim();
		}

		var html =
			'<div class="search-result-part result-name"><a href="#" onclick="triggerClick(' +
			key.toString() +
			'); return false;">' +
			item["physician"]["name"] +
			"</a></div>\n";
		html +=
			'<div class="search-result-part result-credentials"><a href="#" onclick="triggerClick(' +
			key.toString() +
			'); return false;">' +
			item["physician"]["credentials"] +
			"</a><br/>Practices Oral Immunotherapy (OIT)? " +
			item["physician"]["practices_oral_immunotherapy_oit"] +
			"</div>\n";

		if (item["institution"]["distance"] >= 0) {
			html +=
				'<div class="search-result-part result-distance"><a href="#" onclick="triggerClick(' +
				key.toString() +
				'); return false;"><div class="result-marker"></div>Distance ' +
				item["institution"]["distance"] +
				"KM</a></div>\n";
		}

		if (item["institution"]["phone"] != "") {
			html +=
				'<div class="search-result-part result-phone"><a href="tel:+1-' +
				item["institution"]["phone"] +
				extension_link +
				'">T. ' +
				item["institution"]["phone"].replace(/[\-\s\.]{1,}/g, ".") +
				extension +
				"</a></div>\n";
		}

		if (item["institution"]["fax"] != "") {
			html +=
				'<div class="search-result-part result-phone"><a href="tel:+1-' +
				item["institution"]["fax"] +
				'">Fax: ' +
				item["institution"]["fax"].replace(/[\-\s\.]{1,}/g, ".") +
				"</a></div>\n";
		}

		html +=
			'<div class="search-result-part result-institution"><a href="#" onclick="triggerClick(' +
			key.toString() +
			'); return false;">' +
			item["institution"]["name"] +
			"</a></div>\n";
		html +=
			'<div class="search-result-part result-address"><a href="#" onclick="triggerClick(' +
			key.toString() +
			'); return false;">' +
			address_reformatted +
			"</a></div>\n";

		box.innerHTML = html;

		target_page.appendChild(box);
	}

	scrollToMap();
}

function showResultsPagination(page_current, page_count, prefix) {
	var output = "";

	if (page_count > 1) {
		if (page_current > 0) {
			output +=
				'<a class="button-like" href="#" onclick="gotoResultsPage(' +
				(page_current - 1).toString() +
				"," +
				page_count.toString() +
				",'" +
				prefix +
				"'); return false;\">Previous Page</a> ";
		}

		if (page_current < page_count - 1) {
			if (output != "") {
				output += "   ";
			}

			output +=
				'<a class="button-like" href="#" onclick="gotoResultsPage(' +
				(page_current + 1).toString() +
				"," +
				page_count.toString() +
				",'" +
				prefix +
				"'); return false;\">Next Page</a> ";
		}
	}

	var targets = document.querySelectorAll(".pagination-box");

	targets.forEach(function (item, key) {
		item.innerHTML = output.trim();
	});
}

function gotoResultsPage(page_target, page_count, prefix) {
	for (var i = 0; i < page_count; i++) {
		let el = document.getElementById(prefix + "-" + i.toString());

		if (i == page_target) {
			el.style.display = "block";
		} else {
			el.style.display = "none";
		}
	}

	results_page_current = page_target;

	scrollToResultsTop();

	showResultsPagination(page_target, page_count, prefix);
}

function searchClear() {
	var frm = document.getElementById("allergistfrm");
	var items = frm.querySelectorAll("input,select");
	items.forEach(function (item, key) {
		if (item.tagName == "SELECT") {
			if (item.id != "phy_miles") {
				item.selectedIndex = -1;
			}
		} else {
			item.value = "";
		}
	});

	document.getElementById("no-results-container").style.display = "none";
	document.getElementById("search-results-container").style.display = "none";
}

function searchUpdateMap(locations, marker_path) {
	if (locations.length > 0) {
		var latitude = locations[0]["institution"]["latitude"];
		var longitude = locations[0]["institution"]["longitude"];

		map = new google.maps.Map(document.getElementById("googleMap"), {
			zoom: 10,
			center: new google.maps.LatLng(latitude, longitude),
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			mapTypeControl: false,
			streetViewControl: false,
			panControl: false,
			draggable: true,
			zoomControl: true,
			scaleControl: false,
			scrollwheel: false,
			disableDoubleClickZoom: true,
		});

		var marker, i;
		var infowindow = new google.maps.InfoWindow({
			maxWidth: 160,
		});

		// Add the markers and infowindows to the map
		for (var i = 0; i < locations.length; i++) {
			var marker = new google.maps.Marker({
				position: new google.maps.LatLng(
					locations[i]["institution"]["latitude"],
					locations[i]["institution"]["longitude"]
				),
				map: map,
				icon: marker_path + "/csacimarker.png",
			});

			google.maps.event.addListener(
				marker,
				"click",
				(function (marker, i) {
					return function () {
						if (marker) {
							//stop previously selected icon animation
							marker.setAnimation(null);
						}

						var phone = locations[i]["institution"][
							"phone"
						].replace(/[\-\s\.]{1,}/g, ".");
						if (phone != "") {
							if (
								locations[i]["institution"]["ext"].trim() != ""
							) {
								phone +=
									" ext. " +
									locations[i]["institution"]["ext"];
							}

							phone = "<br>" + phone;
						}

						var infocontent =
							"<strong>" +
							locations[i]["physician"]["name"] +
							"</strong><br>" +
							locations[i]["institution"]["name"] +
							phone;
						infowindow.setContent(infocontent);
						infowindow.open(map, marker);
						marker.setAnimation(google.maps.Animation.BOUNCE);
						setTimeout(function () {
							marker.setAnimation(null);
						}, 2920);
					};
				})(marker, i)
			);

			// Push the marker to the 'markers' array
			markers.push(marker);
		}

		if (locations.length > 1) {
			autoCenter();
		}
	}
}

function scrollToResultsTop() {
	let destination = jQuery("#search-results-container");
	let scrollPosition = destination.offset().top - 56;
	let animationDuration = 400;

	var scrollCurrent = $(window).scrollTop();

	if (scrollCurrent > scrollPosition) {
		jQuery("html, body").animate(
			{
				scrollTop: scrollPosition,
			},
			animationDuration
		);
	}
}

function scrollToMap() {
	let destination = jQuery("#googleMap");
	let scrollPosition = destination.offset().top - 56;
	let animationDuration = 400;

	jQuery("html, body").animate(
		{
			scrollTop: scrollPosition,
		},
		animationDuration
	);
}

function triggerClick(id) {
	google.maps.event.trigger(markers[id], "click");

	scrollToMap();
}

function autoCenter() {
	//  Create a new viewpoint bound
	var bounds = new google.maps.LatLngBounds();
	//  Go through each...
	for (var i = 0; i < markers.length; i++) {
		bounds.extend(markers[i].position);
	}
	//  Fit these bounds to the map
	map.fitBounds(bounds);
}

function autoFillAndSubmit() {
	if (querystring_obj.homesubmit) {
		var field = document.getElementById("phy_city");
		field.value = querystring_obj.phy_city;

		field = document.getElementById("phy_province");
		field.value = querystring_obj.phy_province;

		allergistSearch(document.getElementById("allergistfrm"));
	}

	window.history.replaceState(
		null,
		document.title,
		window.location.href.replace(window.location.search, "")
	);
}
