<?
	$service = isset($_POST["service"]) ? $_POST["service"] : $service;
	if ($service == "Instagram") {
		$types = array(
			"Person",
			"Hashtag",
			"Location"
		);
	} elseif ($service == "Twitter") {
		$types = array(
			"Person",
			"Search",
			"Hashtag"
		);
	} elseif ($service == "Flickr") {
		$types = array(
			"Person",
			"Search",
			"Location"
		);
	} elseif ($service == "Facebook") {
		$types = array(
			"Page"
		);
	} else {
		$types = array(
			"Person",
			"Search"
		);
	}
?>
<select name="type" id="btx_social_feed_type_select">
	<option></option>
	<? foreach ($types as $t) { ?>
	<option<? if ($t == $type) { ?> selected="selected"<? } ?>><?=$t?></option>
	<? } ?>
</select>