<?php
	BigTree::globalizePOSTVars();

	if ($type == "Location") {
		list($lat,$lon) = explode(" ",$query);
		$query = array("latitude" => $lat,"longitude" => $lon,"radius" => $radius);
	}

	$id = BigTreeAutoModule::createItem("btx_social_feed_queries",array(
		"service" => $service,
		"type" => $type,
		"query" => $query,
		"cached_info" => $cached_info
	),$category_parser);
	BTXSocialFeed::sync($id);
	
	$admin->growl("Social Feed","Created Query");
	BigTree::redirect(MODULE_ROOT."view-queries/");
