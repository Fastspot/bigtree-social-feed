<?
	// Include our CSS and JS
	$bigtree["css"][] = "social-feed.css";
	$bigtree["js"][] = "social-feed.js";

	// Initiate settings
	$settings = &$cms->autoSaveSetting("settings");
	$settings->Disabled = is_array($settings->Disabled) ? $settings->Disabled : array();

	// Setup a many to many
	$category_field = array(
		"id" => "btx_social_feed_categories",
		"key" => "categories",
		"options" => array(
			"mtm-connecting-table" => "btx_social_feed_query_categories",
			"mtm-other-table" => "btx_social_feed_categories",
			"mtm-my-id" => "query",
			"mtm-other-id" => "category",
			"mtm-other-descriptor" => "name",
			"mtm-sort" => "name"
		)
	);
	$category_parser = array(array(
		"table" => $category_field["options"]["mtm-connecting-table"],
		"my-id" => $category_field["options"]["mtm-my-id"],
		"other-id" => $category_field["options"]["mtm-other-id"],
		"data" => $_POST["categories"]
	));
?>