<?php
	$gplus = new BigTreeGooglePlusAPI;
	$people = $gplus->searchPeople($_POST["query"]);

	if (is_array($people->Results)) {
		foreach ($people->Results as $person) {
			$cached_info = htmlspecialchars(json_encode(array(
				"image" => $person->Image,
				"name" => $person->DisplayName
			)));
?>
<a class="with_image" href="#" data-id="<?=$person->ID?>" data-image="<?=$person->Image?>" data-name="<?=$person->DisplayName?>" data-cache="<?=$cached_info?>"><?=$person->DisplayName?><img src="<?=$person->Image?>" alt="" /></a>
<?php
		}
	}
?>