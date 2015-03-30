<?
	BTXSocialFeed::deleteQuery($bigtree["commands"][0]);

	$admin->growl("Social Feed","Deleted Query");
	BigTree::redirect(MODULE_ROOT."view-queries/");
?>