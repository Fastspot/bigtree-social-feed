<?php
	$id = intval($bigtree["commands"][0]);
	BigTreeAutoModule::updateItem("btx_social_feed_stream", $id, array("ignored" => "on", "approved" => ""));

	$admin->growl("Social Feed", "Ignored Item");
	BigTree::redirect(MODULE_ROOT);
