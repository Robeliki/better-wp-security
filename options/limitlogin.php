<?php
	global $wpdb, $opts, $BWPS, $limitLogin;
	
	if (isset($_POST['BWPS_save'])) { // Save options
		
		if (!wp_verify_nonce($_POST['wp_nonce'], 'BWPS_save')) { //verify nonce field
			die('Security error!');
		}	
		
		$BWPS->saveOptions("limitlogin_enable",$_POST['BWPS_limitlogin_enable']);
		$BWPS->saveOptions("limitlogin_maxattemptshost",$_POST['BWPS_limitlogin_maxattemptshost']);
		$BWPS->saveOptions("limitlogin_maxattemptsuser",$_POST['BWPS_limitlogin_maxattemptsuser']);
		$BWPS->saveOptions("limitlogin_checkinterval",$_POST['BWPS_limitlogin_checkinterval']);
		$BWPS->saveOptions("limitlogin_banperiod",$_POST['BWPS_limitlogin_banperiod']);
		$BWPS->saveOptions("limitlogin_denyaccess",$_POST['BWPS_limitlogin_denyaccess']);
		$BWPS->saveOptions("limitlogin_emailnotify",$_POST['BWPS_limitlogin_emailnotify']);
		
		$opts = $BWPS->getOptions();
		
		if (is_wp_error($errorHandler)) {
			echo '<div id="message" class="error"><p>' . $errorHandler->get_error_message() . '</p></div>';
		} else {
			echo '<div id="message" class="updated"><p>Settings Saved</p></div>';
		}
		
	}
	
	if (isset($_POST['BWPS_releasesave'])) {
		if (!wp_verify_nonce($_POST['wp_nonce'], 'BWPS_releasesave')) { //verify nonce field
			die('Security error!');
		}
		
		
		while (list($key, $value) = each($_POST)) {

			if (strstr($key,"lo")) {
				$wpdb->query("DELETE FROM " . $opts['limitlogin_table_lockouts'] . " WHERE lockout_ID = " . $value . ";");
			}
		}
	
	}
	
?>

<div class="wrap" >

	<h2>Better WP Security - Limit Logins Options</h2>
	
	<div id="poststuff" class="ui-sortable">
		
		<div class="postbox-container" style="width:80%">	
			<div class="postbox opened">
				<h3>Limit Logins Options</h3>	
				<div class="inside">
					<p>Set options below to limit the number of bad login attempts. Once this limit is reached, the host or computer attempting to login will be banned from the site for the specified "lockout length" period.</p>
					<form method="post">
						<?php wp_nonce_field('BWPS_save','wp_nonce') ?>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_limitlogin_enable">Enable Limit Bad Login Attempts</label>
									</th>
									<td>
										<label><input name="BWPS_limitlogin_enable" id="BWPS_limitlogin_enable" value="1" <?php if ($opts['limitlogin_enable'] == 1) echo 'checked="checked"'; ?> type="radio" /> On</label>
										<label><input name="BWPS_limitlogin_enable" value="0" <?php if ($opts['limitlogin_enable'] == 0) echo 'checked="checked"'; ?> type="radio" /> Off</label>
									</td>
								</tr>

								<tr valign="top">
									<th scope="row">
										<label for="BWPS_limitlogin_maxattemptshost">Max Login Attempts Per Host</label>
									</th>
									<td>
										<input name="BWPS_limitlogin_maxattemptshost" id="BWPS_limitlogin_maxattemptshost" value="<?php echo $opts['limitlogin_maxattemptshost']; ?>" type="text">
										<p>
											The number of login attempts a user has before their host or computer is locked out of the system.
										</p>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_limitlogin_maxattemptsuser">Max Login Attempts Per User</label>
									</th>
									<td>
										<input name="BWPS_limitlogin_maxattemptsuser" id="BWPS_limitlogin_maxattemptsuser" value="<?php echo $opts['limitlogin_maxattemptsuser']; ?>" type="text">
										<p>
											The number of login attempts a user has before their username is locked out of the system.
										</p>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_limitlogin_checkinterval">Login Time Period (minutes)</label>
									</th>
									<td>
										<input name="BWPS_limitlogin_checkinterval" id="BWPS_limitlogin_checkinterval" value="<?php echo $opts['limitlogin_checkinterval']; ?>" type="text"><br />
										<p>
											The number of minutes in which bad logins should be remembered.
										</p>
									</td>
								</tr>
		
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_limitlogin_banperiod">Login Time Period (minutes)</label>
									</th>
									<td>
										<input name="BWPS_limitlogin_banperiod" id="BWPS_limitlogin_banperiod" value="<?php echo $opts['limitlogin_banperiod']; ?>" type="text"><br />
										<p>
											The length of time a host or computer will be banned from this site after hitting the limit of bad logins.
										</p>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_limitlogin_denyaccess">Deny All Site Access To Locked Out Hosts.</label>
									</th>
									<td>
										<label><input name="BWPS_limitlogin_denyaccess" id="BWPS_limitlogin_denyaccess" value="1" <?php if ($opts['limitlogin_denyaccess'] == 1) echo 'checked="checked"'; ?> type="radio" /> On</label>
										<label><input name="BWPS_limitlogin_denyaccess" value="0" <?php if ($opts['limitlogin_denyaccess'] == 0) echo 'checked="checked"'; ?> type="radio" /> Off</label><br />
										<p>
											If the host is locked out it will be completely banned from the site and unable to access either content or the backend for the duration of the logout.
										</p>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_limitlogin_emailnotify">Enable Email Notifications.</label>
									</th>
									<td>
										<label><input name="BWPS_limitlogin_emailnotify" id="BWPS_limitlogin_emailnotify" value="1" <?php if ($opts['limitlogin_emailnotify'] == 1) echo 'checked="checked"'; ?> type="radio" /> On</label>
										<label><input name="BWPS_limitlogin_emailnotify" value="0" <?php if ($opts['limitlogin_emailnotify'] == 0) echo 'checked="checked"'; ?> type="radio" /> Off</label><br />
										<p>
											Enabling this feature will trigger an email to be sent to the website administrator whenever a host or user is locked out of the system.
										</p>
									</td>
								</tr>
							</tbody>
						</table>	
						<p class="submit"><input type="submit" name="BWPS_save" value="<?php _e('save', 'better-wp-security'); ?>"></p>
					</form>
				</div>
			</div>
		</div>
			
		<?php include_once(trailingslashit(ABSPATH) . 'wp-content/plugins/better-wp-security/options/donate.php'); ?>
		
		
		<?php if ($opts['limitlogin_enable'] == 1) { ?>
			<div class="clear"></div>
		
			<div class="postbox-container" style="width:80%">
				<div class="postbox opened">
					<h3>Active Lockouts</h3>	
					<div class="inside">
						<p>Select a host or computer and click remove to release the lockout and allow them to log into the system.</p>
						<table width="100%" border="1">
							<tbody>
								<thead>
									<tr valign="top">
										<th>Locked Out Hosts</th>
										<th>Locked Out Users</th>	
									</tr>
								</thead>
								<tr valign="top">
									<form method="post">
										<?php wp_nonce_field('BWPS_releasesave','wp_nonce') ?>
										<td width="50%">
											<?php 
												$lockedList = $limitLogin->listLocked();
												
												if (sizeof($lockedList) > 0) {
													foreach ($lockedList as $item) {
														echo "<span style=\"border-bottom: 1px solid #ccc; padding: 2px; margin: 2px 10px 2px 10px; display: block;\"><input type=\"checkbox\" name=\"" . "lo" . $item['lockout_ID'] . "\" id=\"" . "lo" . $item['lockout_ID'] . "\" value=\"" . $item['lockout_ID'] . "\" /> <label for=\"" . "lo" . $item['lockout_ID'] . "\">" . $item['loLabel'] . " <span style=\"color: #ccc; font-style:italic;\">Expires in: " . $limitLogin->dispRem(($item['lockout_date'] + ($opts['limitlogin_banperiod'] * 60))) . "</span></label>\n";
													}
													echo "<p class=\"submit\"><input type=\"submit\" name=\"BWPS_releasesave\" value=\"Release Selected Lockouts\"></p>\n";
												} else {
													echo "<p style=\"text-align: center;\">There are no hosts currently locked out.</p>\n";
												}
											?>
										</td>
										<td width="50%">
											<?php 
												$lockedList = $limitLogin->listLocked("users");
												
												if (sizeof($lockedList) > 0) {
													foreach ($lockedList as $item) {
														echo "<span style=\"border-bottom: 1px solid #ccc; padding: 2px; margin: 2px 10px 2px 10px; display: block;\"><input type=\"checkbox\" name=\"" . "lo" . $item['lockout_ID'] . "\" id=\"" . "lo" . $item['lockout_ID'] . "\" value=\"" . $item['lockout_ID'] . "\" /> <label for=\"" . "lo" . $item['lockout_ID'] . "\">" . $item['loLabel'] . " <span style=\"color: #ccc; font-style:italic;\">Expires in: " . $limitLogin->dispRem(($item['lockout_date'] + ($opts['limitlogin_banperiod'] * 60))) . "</span></label>\n";
													}
													echo "<p class=\"submit\"><input type=\"submit\" name=\"BWPS_releasesave\" value=\"Release Selected Lockouts\"></p>\n";
												} else {
													echo "<p style=\"text-align: center;\">There are no users currently locked out.</p>\n";
												}
											?>
										</td>
									</form>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php } ?>
		
	</div>
</div>