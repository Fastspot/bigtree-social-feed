<?php
	BTXSocialFeed::sync();

	$admin->growl("Social Feed","Synced Stream");
	BigTree::redirect(MODULE_ROOT);
