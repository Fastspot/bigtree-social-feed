<?php
	$youtube = new BigTreeYouTubeAPI;
	$channels = $youtube->searchChannels($_POST["query"]);
	
	if (is_array($channels->Results)) {
		foreach ($channels->Results as $channel) {
			$cached_info = htmlspecialchars(json_encode(array(
				"image" => $channel->Images->Default,
				"name" => $channel->Title
			)));
?>
<a class="with_image" href="#" data-id="<?=$channel->ID?>" data-image="<?=$channel->Images->Default?>" data-name="<?=$channel->Title?>" data-cache="<?=$cached_info?>"><?=$channel->Title?><img src="<?=$channel->Images->Default?>" alt="" /></a>
<?php
		}
	}
?>