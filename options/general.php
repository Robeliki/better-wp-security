<?php
	global $opts, $BWPS;
	
	if (isset($_POST['BWPS_save'])) { // Save options
		
		if (!wp_verify_nonce($_POST['wp_nonce'], 'BWPS_save')) { //verify nonce field
			die('Security error!');
		}	
		
		$BWPS->saveOptions("general_removeGenerator",$_POST['BWPS_removeGenerator']);
		$BWPS->saveOptions("general_removeLoginMessages",$_POST['BWPS_removeLoginMessages']);
		$BWPS->saveOptions("general_randomVersion",$_POST['BWPS_randomVersion']);
		
		$opts = $BWPS->getOptions();
		
		if (is_wp_error($errorHandler)) {
			echo '<div id="message" class="error"><p>' . $errorHandler->get_error_message() . '</p></div>';
		} else {
			echo '<div id="message" class="updated"><p>Settings Saved</p></div>';
		}
		
	}
	
?>

<div class="wrap" >

	<h2>Better WP Security - General Options</h2>
	
	<div id="poststuff" class="ui-sortable">
		
		<div class="postbox-container" style="width:80%">	
			<div class="postbox opened">
				<h3>General Options</h3>	
				<div class="inside">
					<p></p>
					<form method="post">
						<?php wp_nonce_field('BWPS_save','wp_nonce') ?>
						<table class="form-table">
							<tbody>
								<p>
									<input type="checkbox" name="BWPS_removeGenerator" id="BWPS_removeGenerator" value="1" <?php if ($opts['general_removeGenerator'] == 1) echo "checked"; ?> /> <label for="BWPS_removeGenerator"><strong>Remove Wordpress Generator Meta Tag</strong></label><br />
									Removes the <em>&lt;meta name="generator" content="WordPress [version]" /&gt;</em> meta tag from your sites header. This process hides version information from a potential attacker making it more difficult to determine vulnerabilities.
								</p>
								<p>
									<input type="checkbox" name="BWPS_removeLoginMessages" id="BWPS_removeLoginMessages" value="1" <?php if ($opts['general_removeLoginMessages'] == 1) echo "checked"; ?> /> <label for="BWPS_removeLoginMessages"><strong>Remove Wordpress Login Error Messages</strong></label><br />
									Prevents error messages from being displayed to a user upon a failed login attempt.
								</p>
								<p>
									<input type="checkbox" name="BWPS_randomVersion" id="BWPS_randomVersion" value="1" <?php if ($opts['general_randomVersion'] == 1) echo "checked"; ?> /> <label for="BWPS_randomVersion"><strong>Display random version number to all non-administrative users</strong></label><br />
									Displays a random version number to non-administrator users in all places where version number must be used.
								</p>
							</tbody>
						</table>	
						<p class="submit"><input type="submit" name="BWPS_save" value="<?php _e('save', 'better-wp-security'); ?>"></p>
					</form>
				</div>
			</div>
		</div>
			
		<?php include_once(trailingslashit(ABSPATH) . 'wp-content/plugins/better-wp-security/options/donate.php'); ?>
		
	</div>
</div>