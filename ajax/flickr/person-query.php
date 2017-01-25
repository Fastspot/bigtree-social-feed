<?php
	$flickr = new BigTreeFlickrAPI;
	$person = $flickr->searchPeople($_POST["query"]);
	
	if ($person) {
		$cached_info = htmlspecialchars(json_encode(array(
			"image" => $person->Image,
			"name" => $person->Name." — ".$person->Username
		)));
?>
<a class="with_image" href="#" data-id="<?=$person->ID?>" data-image="<?=$person->Image?>" data-name="<?=$person->Name?> &mdash; <?=$person->Username?>" data-cache="<?=$cached_info?>"><?=$person->Name?> &mdash; <?=$person->Username?><img src="<?=$person->Image?>" alt="" /></a>
<?php
	}
?>