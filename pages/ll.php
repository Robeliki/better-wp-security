<?php
	global $wpdb, $BWPS;
	
	$opts = $BWPS->getOptions();
	
	if (isset($_POST['BWPS_ll_save'])) {
		
		if (!wp_verify_nonce($_POST['wp_nonce'], 'BWPS_ll_save')) {
			die('Security error!');
		}	
		
		$mahinput = (string)absint(intval($_POST['BWPS_ll_maxattemptshost']));
		$mauinput = (string)absint(intval($_POST['BWPS_ll_maxattemptsuser']));
		$ciinput = (string)absint(intval($_POST['BWPS_ll_checkinterval']));
		$bainput = (string)absint(intval($_POST['BWPS_ll_banperiod']));
		
		if (strcmp($_POST['BWPS_ll_maxattemptshost'], $mahinput) || strcmp($_POST['BWPS_ll_maxattemptsuser'], $mauinput) || strcmp($_POST['BWPS_ll_checkinterval'], $ciinput) || strcmp($_POST['BWPS_ll_banperiod'], $bainput)) {
			
			if (!$errorHandler) {
				$errorHandler = new WP_Error();
			}
			
			if ($_POST['BWPS_ll_maxattemptshost'] != $mahinput) {
				$errorHandler->add("3", __("<strong>Max Login Attempts Per Host</strong> MUST be a positive integer."));
			}
		
			if ($_POST['BWPS_ll_maxattemptsuser'] != $mauinput) {
				$errorHandler->add("3", __("<strong>Max Login Attempts Per User</strong> MUST be a positive integer."));
			}
		
			if ($_POST['BWPS_ll_checkinterval'] != $ciinput) {
				$errorHandler->add("3", __("<strong>Login Time Period (minutes)</strong> MUST be a positive integer."));
			}
		
			if ($_POST['BWPS_ll_banperiod'] != $bainput) {
				$errorHandler->add("3", __("<strong>Lockout Time Period (minutes)</strong> MUST be a positive integer."));
			}
			
		} else {
			$opts = $BWPS->saveOptions("ll_enable", $_POST['BWPS_ll_enable']);
			$opts = $BWPS->saveOptions("ll_maxattemptshost", $mahinput);
			$opts = $BWPS->saveOptions("ll_maxattemptsuser", $mauinput);
			$opts = $BWPS->saveOptions("ll_checkinterval", $ciinput);
			$opts = $BWPS->saveOptions("ll_banperiod", $bainput);
			$opts = $BWPS->saveOptions("ll_denyaccess", $_POST['BWPS_ll_denyaccess']);
			$opts = $BWPS->saveOptions("ll_emailnotify", $_POST['BWPS_ll_emailnotify']);
		}
		
		if (isset($errorHandler)) {
			echo '<div id="message" class="error"><p>' . $errorHandler->get_error_message() . '</p></div>';
		} else {
			$BWPS->saveVersions('LL', BWPS_VERSION_LL);
			echo '<div id="message" class="updated"><p>Settings Saved</p></div>';
		}
		
		$ledisplay = $_POST['BWPS_ll_enable'];
		$mahdisplay = $_POST['BWPS_ll_maxattemptshost'];
		$maudisplay = $_POST['BWPS_ll_maxattemptsuser'];
		$cidsplay = $_POST['BWPS_ll_checkinterval'];
		$bpdisplay = $_POST['BWPS_ll_banperiod'];
		$dadisplay = $_POST['BWPS_ll_denyaccess'];
		$endisplay = $_POST['BWPS_ll_emailnotify'];
		
	} else {
		
		$ledisplay = $opts['ll_enable'];
		$mahdisplay = $opts['ll_maxattemptshost'];
		$maudisplay = $opts['ll_maxattemptsuser'];
		$cidsplay = $opts['ll_checkinterval'];
		$bpdisplay = $opts['ll_banperiod'];
		$dadisplay = $opts['ll_denyaccess'];
		$endisplay = $opts['ll_emailnotify'];
		
	}
	
	if (isset($_POST['BWPS_releasesave'])) {
		if (!wp_verify_nonce($_POST['wp_nonce'], 'BWPS_releasesave')) { //verify nonce field
			die('Security error!');
		}
		
		
		while (list($key, $value) = each($_POST)) {
			if (strstr($key,"lo")) {
				$wpdb->query("DELETE FROM " . BWPS_TABLE_LOCKOUTS . " WHERE lockout_ID = " . $value . " AND mode = 2;");
			}
		}
	}
	
?>

<div class="wrap" >

	<h2>Better WP Security - Limit Logins Options</h2>
	
	<div id="poststuff" class="ui-sortable">
		
		<div class="postbox-container" style="width:70%">	
			<div class="postbox opened">
				<h3>Limit Logins Options</h3>	
				<div class="inside">
					<p>Set options below to limit the number of bad login attempts. Once this limit is reached, the host or computer attempting to login will be banned from the site for the specified "lockout length" period.</p>
					<form method="post">
						<?php wp_nonce_field('BWPS_ll_save','wp_nonce') ?>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_ll_enable">Enable Limit Bad Login Attempts</label>
									</th>
									<td>
										<label><input name="BWPS_ll_enable" id="BWPS_ll_enable" value="1" <?php if ($ledisplay == 1) echo 'checked="checked"'; ?> type="radio" /> On</label>
										<label><input name="BWPS_ll_enable" value="0" <?php if ($ledisplay == 0) echo 'checked="checked"'; ?> type="radio" /> Off</label>
									</td>
								</tr>

								<tr valign="top">
									<th scope="row">
										<label for="BWPS_ll_maxattemptshost">Max Login Attempts Per Host</label>
									</th>
									<td>
										<input name="BWPS_ll_maxattemptshost" id="BWPS_ll_maxattemptshost" value="<?php echo $mahdisplay; ?>" type="text">
										<p>
											The number of login attempts a user has before their host or computer is locked out of the system.
										</p>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_ll_maxattemptsuser">Max Login Attempts Per User</label>
									</th>
									<td>
										<input name="BWPS_ll_maxattemptsuser" id="BWPS_ll_maxattemptsuser" value="<?php echo $maudisplay; ?>" type="text">
										<p>
											The number of login attempts a user has before their username is locked out of the system.
										</p>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_ll_checkinterval">Login Time Period (minutes)</label>
									</th>
									<td>
										<input name="BWPS_ll_checkinterval" id="BWPS_ll_checkinterval" value="<?php echo $cidsplay; ?>" type="text"><br />
										<p>
											The number of minutes in which bad logins should be remembered.
										</p>
									</td>
								</tr>
		
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_ll_banperiod">Lockout Time Period (minutes)</label>
									</th>
									<td>
										<input name="BWPS_ll_banperiod" id="BWPS_ll_banperiod" value="<?php echo $bpdisplay; ?>" type="text"><br />
										<p>
											The length of time a host or computer will be banned from this site after hitting the limit of bad logins.
										</p>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_ll_denyaccess">Deny All Site Access To Locked Out Hosts.</label>
									</th>
									<td>
										<label><input name="BWPS_ll_denyaccess" id="BWPS_ll_denyaccess" value="1" <?php if ($dadisplay == 1) echo 'checked="checked"'; ?> type="radio" /> On</label>
										<label><input name="BWPS_ll_denyaccess" value="0" <?php if ($dadisplay == 0) echo 'checked="checked"'; ?> type="radio" /> Off</label><br />
										<p>
											If the host is locked out it will be completely banned from the site and unable to access either content or the backend for the duration of the logout.
										</p>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_ll_emailnotify">Enable Email Notifications.</label>
									</th>
									<td>
										<label><input name="BWPS_ll_emailnotify" id="BWPS_ll_emailnotify" value="1" <?php if ($endisplay == 1) echo 'checked="checked"'; ?> type="radio" /> On</label>
										<label><input name="BWPS_ll_emailnotify" value="0" <?php if ($endisplay == 0) echo 'checked="checked"'; ?> type="radio" /> Off</label><br />
										<p>
											Enabling this feature will trigger an email to be sent to the website administrator whenever a host or user is locked out of the system.
										</p>
									</td>
								</tr>
							</tbody>
						</table>	
						<p class="submit"><input type="submit" name="BWPS_ll_save" value="<?php _e('save', 'better-wp-security'); ?>"></p>
					</form>
				</div>
			</div>
		</div>
			
		<?php include_once(trailingslashit(WP_PLUGIN_DIR) . 'better-wp-security/pages/donate.php'); ?>
		
		
		<?php if ($opts['ll_enable'] == 1) { ?>
			<div class="clear"></div>
		
			<div class="postbox-container" style="width:70%">
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
												$lockedList = $BWPS->ll_listLocked();
												
												if (sizeof($lockedList) > 0) {
													foreach ($lockedList as $item) {
														echo "<span style=\"border-bottom: 1px solid #ccc; padding: 2px; margin: 2px 10px 2px 10px; display: block;\"><input type=\"checkbox\" name=\"" . "lo" . $item['lockout_ID'] . "\" id=\"" . "lo" . $item['lockout_ID'] . "\" value=\"" . $item['lockout_ID'] . "\" /> <label for=\"" . "lo" . $item['lockout_ID'] . "\">" . $item['loLabel'] . " <span style=\"color: #ccc; font-style:italic;\">Expires in: " . $BWPS->dispRem(($item['lockout_date'] + ($opts['ll_banperiod'] * 60))) . "</span></label>\n";
													}
													echo "<p class=\"submit\"><input type=\"submit\" name=\"BWPS_releasesave\" value=\"Release Selected Lockouts\"></p>\n";
												} else {
													echo "<p style=\"text-align: center;\">There are no hosts currently locked out.</p>\n";
												}
											?>
										</td>
										<td width="50%">
											<?php 
												$lockedList = $BWPS->ll_listLocked("users");
												
												if (sizeof($lockedList) > 0) {
													foreach ($lockedList as $item) {
														echo "<span style=\"border-bottom: 1px solid #ccc; padding: 2px; margin: 2px 10px 2px 10px; display: block;\"><input type=\"checkbox\" name=\"" . "lo" . $item['lockout_ID'] . "\" id=\"" . "lo" . $item['lockout_ID'] . "\" value=\"" . $item['lockout_ID'] . "\" /> <label for=\"" . "lo" . $item['lockout_ID'] . "\">" . $item['loLabel'] . " <span style=\"color: #ccc; font-style:italic;\">Expires in: " . $BWPS->dispRem(($item['lockout_date'] + ($opts['ll_banperiod'] * 60))) . "</span></label>\n";
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