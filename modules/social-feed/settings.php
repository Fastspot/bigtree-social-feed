<?php
	$flickr = new BigTreeFlickrAPI;
	$googleplus = new BigTreeGooglePlusAPI;
	$instagram = new BigTreeInstagramAPI;
	$twitter = new BigTreeTwitterAPI;
	$youtube = new BigTreeYouTubeAPI;
	$facebook = new BigTreeFacebookAPI;

	$connected_services = array();
	$flickr->Connected ? $connected_services[] = "Flickr" : false;
	$googleplus->Connected ? $connected_services[] = "Google+" : false;
	$instagram->Connected ? $connected_services[] = "Instagram" : false;
	$twitter->Connected ? $connected_services[] = "Twitter" : false;
	$youtube->Connected ? $connected_services[] = "YouTube" : false;
	$facebook->Connected ? $connected_services[] = "Facebook" : false;

	$services = array("Flickr", "Google+", "Instagram", "Twitter", "YouTube", "Facebook");
?>
<div class="container">
	<form method="post" action="<?=MODULE_ROOT?>update-settings/">
		<section>
			<fieldset>
				<h3>Queryable Services</h3>
				<?php foreach ($services as $service) { ?>
				<div class="contain" style="margin-bottom: 10px;">
					<input type="checkbox" name="services[]" value="<?=$service?>"<?php if (!in_array($service, $connected_services)) { ?> disabled="disabled"<?php } elseif (!in_array($service, $settings->Disabled)) { ?> checked="checked"<?php } ?> />
					<label class="for_checkbox"><?=$service?><?php if (!in_array($service, $connected_services)) { ?><small>(needs setup)</small><?php } ?></label>
				</div>
				<?php } ?>
			</fieldset>
		</section>
		<footer>
			<input type="submit" class="button blue" value="Update" />
		</footer>
	</form>
</div>