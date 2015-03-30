<?
	BigTree::globalizePOSTVars();
	
	if ($type == "Location") {
		list($lat,$lon) = explode(" ",$query);
		$query = json_encode(array("latitude" => $lat,"longitude" => $lon,"radius" => $radius));
	}
	
	BigTreeAutoModule::updateItem("btx_social_feed_queries",$bigtree["commands"][0],array(
		"service" => $service,
		"type" => $type,
		"query" => $query,
		"cached_info" => $_POST["cached_info"]
	),$category_parser);
	BTXSocialFeed::sync($bigtree["commands"][0]);

	$admin->growl("Social Feed","Updated Query");
	BigTree::redirect(MODULE_ROOT."view-queries/");
?>