<?php
	$bigtree["edit_id"] = $bigtree["commands"][0];
	$item = BigTreeAutoModule::getItem("btx_social_feed_queries",$bigtree["edit_id"]);
	BigTree::globalizeArray($item["item"]);
	
	$action = "update-query/".$bigtree["edit_id"]."/";
	$button_value = "Update";
	include "_form.php";
