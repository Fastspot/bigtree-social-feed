<?php
	$id = intval($bigtree["commands"][0]));
	BigTreeAutoModule::updateItem("btx_social_feed_stream", $id, array("ignored" => ""));

	$admin->growl("Social Feed", "Unignored Item");
	BigTree::redirect(MODULE_ROOT."ignored/");
	