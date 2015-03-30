<?
	$flickr = new BigTreeFlickrAPI;
	$googleplus = new BigTreeGooglePlusAPI;
	$instagram = new BigTreeInstagramAPI;
	$twitter = new BigTreeTwitterAPI;
	$youtube = new BigTreeYouTubeAPI;

	$connected_services = array();
	$flickr->Connected ? $connected_services[] = "Flickr" : false;
	$googleplus->Connected ? $connected_services[] = "Google+" : false;
	$instagram->Connected ? $connected_services[] = "Instagram" : false;
	$twitter->Connected ? $connected_services[] = "Twitter" : false;
	$youtube->Connected ? $connected_services[] = "YouTube" : false;

	$services = array("Flickr","Google+","Instagram","Twitter","YouTube");
?>
<div class="container">
	<form method="post" action="<?=MODULE_ROOT?>update-settings/">
		<section>
			<fieldset>
				<h3>Queryable Services</h3>
				<? foreach ($services as $service) { ?>
				<div class="contain" style="margin-bottom: 10px;">
					<input type="checkbox" name="services[]" value="<?=$service?>"<? if (!in_array($service,$connected_services)) { ?> disabled="disabled"<? } elseif (!in_array($service,$settings->Disabled)) { ?> checked="checked"<? } ?> />
					<label class="for_checkbox"><?=$service?><? if (!in_array($service,$connected_services)) { ?><small>(needs setup)</small><? } ?></label>
				</div>
				<? } ?>
			</fieldset>
		</section>
		<footer>
			<input type="submit" class="button blue" value="Update" />
		</footer>
	</form>
</div>