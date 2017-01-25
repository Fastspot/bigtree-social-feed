var BTXSocialFeed = (function() {
	var $Container;
	var $Query;
	var $Service;
	var $Type;

	var Map;
	var MapMarker;
	var QueryEntered = false;
	var QueryFillers;
	var QueryTimer;
	var QueryURL;

	$(document).ready(function() {
		// Cache DOM
		$Container = $("#btx_social_feed_container");
		$Query = $("#btx_social_feed_query");
		$Service = $("#btx_social_feed_service_select");
		$Type = $("#btx_social_feed_type");

		$Service.change(function() {
			$Type.load("admin_root/*/com.fastspot.social-feed/ajax/type-dropdown/", { service: $(this).val() }, function() {
				// Reset the query field since we chose a new type
				$Query.html('<input type="text" disabled="disabled" value="Choose a Service and Type" />');
				BigTreeCustomControls();
			});
		});
		
		// Hook clear
		$Container.on("click","#btx_social_feed_query_clear",function() {
			$("#btx_social_feed_query_element").val("");
			$("#btx_social_feed_query_results").hide();
			$(".btx_social_feed_add_info").hide();
			$("#btx_social_feed_query_clear").removeClass("active");
			QueryEntered = false;

		// Hook location searches
		}).on("keyup","#btx_social_feed_location_query",function() {
			clearTimeout(QueryTimer);
			QueryTimer = setTimeout(locationLookup,500);

		// Prevent enter on location field for Google Maps lookups
		}).on("keydown","#btx_social_feed_location_query",function(ev) {
			if (ev.keyCode == 13) {
				ev.preventDefault();
				ev.stopPropagation();
			}

		// Hook other searches
		}).on("keyup","#btx_social_feed_query_element",function() {
			clearTimeout(QueryTimer);
			QueryTimer = setTimeout(socialLookup,500);

		// Hook query results options
		}).on("click","#btx_social_feed_query_results a",function(ev) {
			ev.preventDefault();

			// Fill out any fields that should be set by the click
			for (var i = 0; i < QueryFillers.length; i++) {
				var filler = QueryFillers[i];
				if (filler.attribute == "html") {
					$("#" + filler.target).html($(this).attr("data-" + filler.key)).show();
				} else if (filler.attribute == "value") {
					$("#" + filler.target).val($(this).attr("data-" + filler.key)).show();
				} else {
					$("#" + filler.target).attr(filler.attribute,$(this).attr("data-" + filler.key)).show();
				}
			}

			// Hide the results dropdown
			$("#btx_social_feed_query_results").hide();
			$("#btx_social_feed_query_clear").addClass("active");
			QueryEntered = true;

		// Hook type change
		}).on("change","#btx_social_feed_type_select", function() {
			$("#btx_social_feed_query").load("admin_root/*/com.fastspot.social-feed/ajax/query-element/",{ service: $("#btx_social_feed_service_select").val(), type: $(this).val() }, BigTreeCustomControls);
		});

		$("#btx_social_feed_form").submit(function(ev) {
			if (!QueryEntered) {
				ev.stopPropagation();
				ev.preventDefault();

				$(this).find(".error_message").show();
			}
		});
	});

	function createMap(latitude,longitude) {
		Map = new google.maps.Map(document.getElementById("btx_social_feed_map"), {
			zoom: 8,
			center: new google.maps.LatLng(latitude,longitude),
			mapTypeControl: true,
			mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
			navigationControl: true
		});
		google.maps.event.addListener(Map,'click',function(event) {
			createMapMarker(event.latLng.lat(),event.latLng.lng(),false);
		});
	}

	function createMapMarker(lat,lon,pan) {
		var latLng = new google.maps.LatLng(lat,lon);
		$("#btx_social_feed_query_element").val(lat + " " + lon);
		if (MapMarker) {
			MapMarker.setMap(null);
			MapMarker = null;
		 }
		 MapMarker = new google.maps.Marker({
		 	position: latLng,
		 	map: Map
		 });
		 if (pan) {
 			 Map.panTo(latLng);
		 }
	}
	
	function locationLookup() {
		var geocoder = new google.maps.Geocoder();
		geocoder.geocode({ 'address': $("#btx_social_feed_location_query").val() }, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				Map.setCenter(results[0].geometry.location);
				createMapMarker(results[0].geometry.location.lat(),results[0].geometry.location.lng());
			}
		});
	}

	function setupQuery(url, fillers, query_entered) {
		QueryURL = url;
		QueryEntered = query_entered;
		QueryFillers = fillers;
	}

	function socialLookup() {
		var query = $("#btx_social_feed_query_element").val();
		if (query && QueryURL) {
			// Show progress spinner, hide the clear button
			$("#btx_social_feed_query_spinner").show();

			// Request info from the social API
			$.ajax("admin_root/*/com.fastspot.social-feed/ajax/" + QueryURL + "/", { method: "POST", data: { query: query }, complete: function(r) {

				// Hide the spinner, show clear button
				$("#btx_social_feed_query_spinner").hide();

				if (r.responseText) {
					$("#btx_social_feed_query_results").html(r.responseText).show();
					
				} else {
					$("#btx_social_feed_query_results").hide();
				}
			}});
		} else {
			$("#btx_social_feed_query_results").hide();
		}
	}

	return { createMap: createMap, createMapMarker: createMapMarker, setupQuery: setupQuery };
})();