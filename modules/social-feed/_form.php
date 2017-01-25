<?php
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
<?php
	} else {
?>
<style type="text/css">
	#btx_social_feed_query_clear { display: none; }
	#btx_social_feed_query_clear.active { display: block; }
</style>

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<div class="container" id="btx_social_feed_container">
	<form method="post" action="<?=MODULE_ROOT.$action?>/" id="btx_social_feed_form">
		<section>
			<p class="error_message" style="display: none;">Please select a query result before submitting.</p>
			<fieldset>
				<label>Service</label>
				<select name="service" id="btx_social_feed_service_select">
					<?php
						foreach ($services as $s) {
							if ($service === false) {
								$service = $s;
							}
					?>
					<option<?php if ($s == $service) { ?> selected="selected"<?php } ?>><?=$s?></option>
					<?php
						}
					?>
				</select>
			</fieldset>
			<fieldset>
				<label>Type</label>
				<div id="btx_social_feed_type">
					<?php include EXTENSION_ROOT."ajax/type-dropdown.php" ?>
				</div>
			</fieldset>
			<fieldset>
				<label>Query</label>
				<div id="btx_social_feed_query">
					<?php
						if ($service && $type) {
							include EXTENSION_ROOT."ajax/query-element.php";
						} else {
					?>
					<input type="text" disabled="disabled" value="Choose a Service and Type" />
					<?php
						}
					?>
				</div>
			</fieldset>
			<fieldset id="btx_social_feed_categories">
				<label>Categories</label>
				<?php
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
<?php
	}
?>