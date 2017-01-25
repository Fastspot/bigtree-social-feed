<?php
	$settings->Disabled = array("Flickr","Google+","Instagram","Twitter","YouTube");
	foreach ($_POST["services"] as $service) {
		unset($settings->Disabled[array_search($service,$settings->Disabled)]);
	}
	
	$admin->growl("Social Feed","Updated Settings");
	BigTree::redirect(MODULE_ROOT);
