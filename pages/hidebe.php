<?php
	require_once(trailingslashit(WP_PLUGIN_DIR) . 'better-wp-security/functions/hidebe.php');
	
	$BWPS_hidebe = new BWPS_hidebe();
	
	$opts = $BWPS_hidebe->getOptions();
	
	if (isset($_POST['BWPS_hidebe_save'])) {
		
		if (!wp_verify_nonce($_POST['wp_nonce'], 'BWPS_hidebe_save')) {
			die('Security error!');
		}	
		
		$opts = $BWPS_hidebe->saveOptions("hidebe_Version", BWPS_HIDEBE_VERSION);
		
		$login_slug = sanitize_title(esc_html__($_POST['BWPS_hidebe_login_slug']));
		$admin_slug = sanitize_title(esc_html__($_POST['BWPS_hidebe_admin_slug']));
		$register_slug = sanitize_title(esc_html__($_POST['BWPS_hidebe_register_slug']));
		
		$opts = $BWPS_hidebe->saveOptions("hidebe_enable",$_POST['BWPS_hidebe_enable']);
		$opts = $BWPS_hidebe->saveOptions("hidebe_login_slug", $login_slug);
		$opts = $BWPS_hidebe->saveOptions("hidebe_admin_slug", $admin_slug);
		$opts = $BWPS_hidebe->saveOptions("hidebe_register_slug", $register_slug);
		
		if (get_option('users_can_register')) {
			$opts = $BWPS_hidebe->saveOptions("hidebe_canregister","1");
		} else {
			$opts = $BWPS_hidebe->saveOptions("hidebe_canregister","0");
		}
		
		$htaccess = trailingslashit(ABSPATH).'.htaccess';
		
		if (!$BWPS_hidebe->can_write($htaccess)) {
			
			$opts = $BWPS_hidebe->saveOptions("hidebe_enable","0");
			
			if (!$errorHandler) {
				$errorHandler = new WP_Error();
			}
			
			$errorHandler->add("2", __("Unable to update htaccess rules"));
			
		} else {
		
			if ($_POST['BWPS_hidebe_enable'] == 1) {
			
				$wprules = implode("\n", extract_from_markers($htaccess, 'WordPress' ));
				
				$BWPS_hidebe->remove_section($htaccess, 'WordPress');
				$BWPS_hidebe->remove_section($htaccess, 'Better WP Security Hide Backend');
				
				insert_with_markers($htaccess,'Better WP Security Hide Backend', explode( "\n", $BWPS_hidebe->getRules()));
				insert_with_markers($htaccess,'WordPress', explode( "\n", $wprules));			
				
			} else {
			
				$BWPS_hidebe->remove_section($htaccess, 'Better WP Security Hide Backend');
				
			}
			
		}
		
		if (isset($errorHandler)) {
			echo '<div id="message" class="error"><p>' . $errorHandler->get_error_message() . '</p></div>';
		} else {
			echo '<div id="message" class="updated"><p>Settings Saved</p></div>';
		}
		
	}
?>

<div class="wrap" >

	<h2>Better WP Security - Hide Backend Options</h2>
	
	<div id="poststuff" class="ui-sortable">
		
		<div class="postbox-container" style="width:70%">	
			<div class="postbox opened">
				<h3>Hide Backend Options</h3>	
				<div class="inside">
					<p>The options below allow you to "hide" the backed of Wordpress replacing known URLs of important areas with ones of your choosing. This is useful if slowing bots and keeping away other nosy users especially on closed or private systems.</p>
					<form method="post">
						<?php wp_nonce_field('BWPS_hidebe_save','wp_nonce') ?>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_hidebe_enable">Enable Hide Backend</label>
									</th>
									<td>
										<label><input name="BWPS_hidebe_enable" id="BWPS_hidebe_enable" value="1" <?php if ($opts['hidebe_enable'] == 1) echo 'checked="checked"'; ?> type="radio" /> On</label>
										<label><input name="BWPS_hidebe_enable" value="0" <?php if ($opts['hidebe_enable'] == 0) echo 'checked="checked"'; ?> type="radio" /> Off</label>
									</td>
								</tr>
     
								<tr valign="top">
									<th scope="row">
										<label for="login_slug">Login Slug</label>
									</th>
									<td>
										<input name="BWPS_hidebe_login_slug" id="login_slug" value="<?php echo $opts['hidebe_login_slug']; ?>" type="text"><br />
										<em><span style="color: #666666;"><strong>Login URL:</strong> <?php echo trailingslashit( get_option('siteurl') ); ?></span><span style="color: #4AA02C"><?php echo $opts['hidebe_login_slug']; ?>	</span></em>
									</td>
								</tr>
		                            	
								<?php if (get_option('users_can_register')) { ?>
									<tr valign="top">
										<th scope="row">
											<label for="register_slug">Register Slug</label>
										</th>
										<td>
											<input type="text" name="BWPS_hidebe_register_slug" id="register_slug" value="<?php echo $opts['hidebe_register_slug']; ?>" /><br />
											<em><span style="color: #666666;"><strong>Register URL:</strong> <?php echo trailingslashit( get_option('siteurl') ); ?></span><span style="color: #4AA02C"><?php echo $opts['hidebe_register_slug']; ?></span></em>
										</td>
									</tr>
								<?php } else { ?>
									<input type="hidden" name="BWPS_hidebe_register_slug" id="register_slug" value="<?php echo $opts['hidebe_register_slug']; ?>" />
								<?php } ?>
	
								<tr valign="top">
									<th scope="row">
										<label for="admin_slug">Admin Slug</label>
									</th>
									<td>
										<input name="BWPS_hidebe_admin_slug" id="admin_slug" value="<?php echo $opts['hidebe_admin_slug']; ?>" type="text"><br />
										<em><span style="color: #666666;"><strong>Admin URL:</strong> <?php echo trailingslashit( get_option('siteurl') ); ?></span><span style="color: #4AA02C"><?php echo $opts['hidebe_admin_slug']; ?></span></em>
									</td>
								</tr>
							</tbody>
						</table>	
						<p class="submit"><input type="submit" name="BWPS_hidebe_save" value="<?php _e('save', 'better-wp-security'); ?>"></p>
					</form>
				</div>
			</div>
		</div>
			
		<?php include_once(trailingslashit(WP_PLUGIN_DIR) . 'better-wp-security/pages/donate.php'); ?>
		
		<?php if ($opts['hidebe_enable'] == 1) { ?>
			<div class="clear"></div>
		
			<?php
				$bgColor = $BWPS_hidebe->confirmRules();
			?>
			<div class="postbox-container" style="width:70%">
				<div class="postbox opened" style="background-color: <?php echo $bgColor; ?>;">
					<h3>Hide Backend Rewrite Rules</h3>	
					<div class="inside">
						<?php
							if ($bgColor == "#ffebeb") {
								echo "<h4 style=\"text-align: center;\">Your htaccess rules have a problem. Please save this form to fix them</h4>";
							}
						?>
						<pre><?php echo $BWPS_hidebe->getRules(); ?></pre>
					</div>
				</div>
			</div>
		<?php } ?>
		
	</div>
</div>