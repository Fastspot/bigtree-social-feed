<?
	// Get a list of all the social services that are both connected and not explicitly disabled by the developer for Social Feed
	$services = array();
	$facebook = new BigTreeFacebookAPI;
	$flickr = new BigTreeFlickrAPI;
	$googleplus = new BigTreeGooglePlusAPI;
	$instagram = new BigTreeInstagramAPI;
	$twitter = new BigTreeTwitterAPI;
	$youtube = new BigTreeYouTubeAPI;

	($facebook->Connected   && !in_array("Facebook",$settings->Disabled))  ? $services[] = "Facebook"  : false;
	($flickr->Connected     && !in_array("Flickr",$settings->Disabled))    ? $services[] = "Flickr"    : false;
	($googleplus->Connected && !in_array("Google+",$settings->Disabled))   ? $services[] = "Google+"   : false;
	($instagram->Connected  && !in_array("Instagram",$settings->Disabled)) ? $services[] = "Instagram" : false;
	($twitter->Connected    && !in_array("Twitter",$settings->Disabled))   ? $services[] = "Twitter"   : false;
	($youtube->Connected    && !in_array("YouTube",$settings->Disabled))   ? $services[] = "YouTube"   : false;

	// If we don't have any services enabled we shouldn't show the form at all.
	if (!count($services)) {
?>
<div class="container">
	<section>
		<p>You do not have any connected social services. Please enable one or more service APIs in the <a href="<?=ADMIN_ROOT?>developer/services/">Developer tab</a>.</p>
	</section>
</div>
<?
	} else {
?>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<div class="container" id="btx_social_feed_container">
	<form method="post" action="<?=MODULE_ROOT.$action?>/">
		<section>
			<fieldset>
				<label>Service</label>
				<select name="service" id="btx_social_feed_service_select">
					<?
						foreach ($services as $s) {
							if ($service === false) {
								$service = $s;
							}
					?>
					<option<? if ($s == $service) { ?> selected="selected"<? } ?>><?=$s?></option>
					<?
						}
					?>
				</select>
			</fieldset>
			<fieldset>
				<label>Type</label>
				<div id="btx_social_feed_type">
					<? include EXTENSION_ROOT."ajax/type-dropdown.php" ?>
				</div>
			</fieldset>
			<fieldset>
				<label>Query</label>
				<div id="btx_social_feed_query">
					<?
						if ($service && $type) {
							include EXTENSION_ROOT."ajax/query-element.php";
						} else {
					?>
					<input type="text" disabled="disabled" value="Choose a Service and Type" />
					<?
						}
					?>
				</div>
			</fieldset>
			<fieldset id="btx_social_feed_categories">
				<label>Categories</label>
				<?
					$field = $category_field;
					include SERVER_ROOT."core/admin/form-field-types/draw/many-to-many.php";
				?>
			</fieldset>
		</section>
		<footer>
			<input type="submit" class="button blue" value="<?=$button_value?>" />
		</footer>
	</form>
</div>
<?
	}
?>