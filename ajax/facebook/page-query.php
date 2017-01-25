<?php
	$facebook = new BigTreeFacebookAPI;
	$id = end(explode("/",rtrim($_POST["query"],"/")));
	$user = $facebook->callUncached($id."?fields=id,name,picture");
	
	if ($user) {
		$cached_info = htmlspecialchars(json_encode(array(
			"image" => $user->picture->data->url,
			"name" => $user->name,
			"id" => $user->id
		)));
?>
<a class="with_image" href="#" data-id="<?=$user->id?>" data-image="<?=$user->picture->data->url?>" data-name="<?=$user->name?>" data-cache="<?=$cached_info?>"><?=$user->name?><img src="<?=$user->picture->data->url?>" alt="" /></a>
<?php
	}
?>