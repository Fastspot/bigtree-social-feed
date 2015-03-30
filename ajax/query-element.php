<?
	$service = isset($_POST["service"]) ? $_POST["service"] : $service;
	$type = isset($_POST["type"]) ? $_POST["type"] : $type;
	$url = false;

	// Just a standard search field
	if ($type == "Search") {
?>
<input type="text" name="query" value="<?=htmlspecialchars($query)?>" placeholder="Search terms" id="btx_social_feed_query_element" autocomplete="off" />
<?
	// Hashtag field, Instagram needs a lookup
	} elseif ($type == "Hashtag") {
?>
<input type="text" name="query" value="<?=htmlspecialchars($query)?>" placeholder="#hashtag" id="btx_social_feed_query_element" autocomplete="off" />
<?
		if ($service == "Instagram") {
			$url = "instagram/hashtag-query";
			$fillers = array(
				array("target" => "btx_social_feed_query_element","attribute" => "value","key" => "value")
			);
		}
	// Person field, all of them need lookups
	} elseif ($type == "Person") {
		if ($service == "Flickr") {
			$url = "flickr/person-query";
?>
<input type="text" name="query" value="<?=htmlspecialchars($query)?>" placeholder="Flickr Email Address or Username" id="btx_social_feed_query_element" autocomplete="off" />
<?
		} else {
?>
<input type="text" name="query" value="<?=htmlspecialchars($query)?>" placeholder="Person's Name, Username, etc." id="btx_social_feed_query_element" autocomplete="off" />
<?
		}
?>
<span id="btx_social_feed_query_clear" class="icon_small icon_small_delete"></span>
<span id="btx_social_feed_person_name" class="btx_social_feed_add_info"<? if (!$cached_info) { ?> style="display: none;"><? } else { ?>><?=$cached_info["name"]?><? } ?></span>
<img id="btx_social_feed_person_image" <? if (!$cached_info) { ?>style="display: none;"<? } else { ?>src="<?=$cached_info["image"]?>"<? } ?> class="btx_social_feed_add_info" />
<input name="cached_info" type="hidden" id="btx_social_feed_cached_info" value="<?=htmlspecialchars(json_encode($cached_info))?>" />
<?
		if ($service == "Instagram") {
			$url = "instagram/person-query";
		} elseif ($service == "Twitter") {
			$url = "twitter/person-query";
		} elseif ($service == "YouTube") {
			$url = "youtube/person-query";
		} elseif ($service == "Google+") {
			$url = "googleplus/person-query";
		}
		$fillers = array(
			array("target" => "btx_social_feed_query_element","attribute" => "value","key" => "id"),
			array("target" => "btx_social_feed_person_image","attribute" => "src","key" => "image"),
			array("target" => "btx_social_feed_person_name","attribute" => "html","key" => "name"),
			array("target" => "btx_social_feed_cached_info","attribute" => "value","key" => "cache")
		);

	// Locations need Geo lookups and pin drop
	} elseif ($type == "Location") {
?>
<input type="hidden" name="query" id="btx_social_feed_query_element" value="<?=(is_array($query) ? $query["latitude"]." ".$query["longitude"] : "")?>" />
<input type="text" name="location" id="btx_social_feed_location_query" placeholder="Search for a place by address, city, state, etc." autocomplete="off" />
<div id="btx_social_feed_map"></div>
<label>Search Radius <small>(in <strong><? if ($service == "Instagram") { ?>meters<? } else { ?>miles<? } ?></strong> from the chosen location)</small></label>
<input type="text" name="radius" placeholder="i.e. 10" value="<?=(is_array($query) ? $query["radius"] : "")?>" />
<script>
	BTXSocialFeed.createMap(<?=((is_array($query) && $query["latitude"]) ? $query["latitude"].",".$query["longitude"] : "39.26415795094216, -76.6131591796875")?>);
	<? if (is_array($query) && $query["latitude"]) { ?>
	BTXSocialFeed.createMapMarker(<?=$query["latitude"]?>,<?=$query["longitude"]?>,false);
	<? } ?>
</script>
<?
	}
?>
<span id="btx_social_feed_query_spinner" style="display: none;"></span>
<div id="btx_social_feed_query_results" style="display: none;"></div>
<script>
	BTXSocialFeed.setupQuery("<?=$url?>",<?=json_encode($fillers)?>);
</script>