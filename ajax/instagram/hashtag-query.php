<?
	$instagram = new BigTreeInstagramAPI;
	$tags = $instagram->searchTags($_POST["query"]);
	if (is_array($tags)) {
		$tags = array_slice($tags,0,10);
		foreach ($tags as $t) {
?>
<a href="#" data-value="#<?=$t->Name?>"><?=$t->Name?> (<?=$t->MediaCount?> photos/videos)</a>
<?
		}
	}
?>