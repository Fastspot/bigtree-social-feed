<?php
	$twitter = new BigTreeTwitterAPI;
	$users = $twitter->searchUsers($_POST["query"]);
	
	if (is_array($users->Results)) {
		foreach ($users->Results as $user) {
			$cached_info = htmlspecialchars(json_encode(array(
				"image" => $user->Image,
				"name" => $user->Name." — ".$user->Username
			)));
?>
<a class="with_image" href="#" data-id="<?=$user->Username?>" data-image="<?=$user->Image?>" data-name="<?=$user->Name?> &mdash; <?=$user->Username?>" data-cache="<?=$cached_info?>"><?=$user->Name?> &mdash; <?=$user->Username?><img src="<?=$user->Image?>" alt="" /></a>
<?php
		}
	}
?>