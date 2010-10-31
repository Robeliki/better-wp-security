<?php
	$BWPS_banips = new BWPS_banips();
	
	$opts = $BWPS_banips->getOptions();
	
	if (isset($_POST['BWPS_banips_save'])) { // Save options
		
		if (!wp_verify_nonce($_POST['wp_nonce'], 'BWPS_banips_save')) { //verify nonce field
			die('Security error!');
		}
		
		$opts = $BWPS_banips->saveOptions("banips_Version", BWPS_BANIPS_VERSION);
		
		/*
		 * Save ban ips options
		 */
		$opts = $BWPS_banips->saveOptions("banips_enable",$_POST['BWPS_banips_enable']);
				
		if (strlen($_POST['BWPS_banips_iplist']) > 0) { //save banned IPs if present
		
			//sanitize the input
			$ipInput = esc_html__($_POST['BWPS_banips_iplist']);
		
			$ipArray = explode("\n", $ipInput);	
			
			if (!$BWPS_banips->createRules($ipArray)) { //make sure all ips are valid IPv4 addresses and NOT the users own address
				$errorHandler = new WP_Error();
				$errorHandler->add("1", __("You entered a bad IP address"));
			}  else { //save the IP addresses to the database
				$opts = $BWPS_banips->saveOptions("banips_iplist",$ipInput);
			}
		} else { //delete any IPs from the database
			$opts = $BWPS_banips->saveOptions("banips_enable","0");
			$opts = $BWPS_banips->saveOptions("banips_iplist","");
		}
		
		$htaccess = trailingslashit(ABSPATH).'.htaccess'; //get htaccess info
		
		if (!$BWPS_banips->can_write($htaccess)) { //verify the .htaccess file is writeable

			$opts = $BWPS_banips->saveOptions("banips_enable","0");
			
			$errorHandler = new WP_Error();
			
			$errorHandler->add("2", __("Unable to update htaccess rules"));
			
		} else {
		
			/*
			 * Save banned ips to .htaccess
			 */
			if ($_POST['BWPS_banips_enable'] == 1 && $opts['banips_iplist'] != "") { //if ban ips is enabled write them to .htaccess
				$BWPS_banips->remove_section($htaccess, 'Better WP Security Ban IPs');
				insert_with_markers($htaccess,'Better WP Security Ban IPs', explode( "\n", $BWPS_banips->getList()));

			} else { //make sure no ips are banned if ban ips is disabled
			
				$opts = $BWPS_banips->saveOptions("banips_enable","0");
				$BWPS_banips->remove_section($htaccess, 'Better WP Security Ban IPs');
				
			}		
			
		} 
		
		if (isset($errorHandler)) {
			echo '<div id="message" class="error"><p>' . $errorHandler->get_error_message() . '</p></div>';
		} else {
			echo '<div id="message" class="updated"><p>Settings Saved</p></div>';
		}
		
		$banips_iplist = $_POST['BWPS_banips_iplist'];
		
	} else {
	
		$banips_iplist = $opts['banips_iplist'];
		
	}
		
?>

<div class="wrap" >

	<h2>Better WP Security - Ban IPs Options</h2>
	
	<div id="poststuff" class="ui-sortable">
		
		<div class="postbox-container" style="width:70%">	
			<div class="postbox opened">
				<h3>Ban IPs Options</h3>	
				<div class="inside">
					<p>List below the IP addresses you would like to ban from your site. These will be banned in .htaccess.</p>
					<form method="post">
						<?php wp_nonce_field('BWPS_banips_save','wp_nonce') ?>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_banips_enable">Enable Ban IPs</label>
									</th>
									<td>
										<label><input name="BWPS_banips_enable" id="BWPS_banips_enable" value="1" <?php if ($opts['banips_enable'] == 1) echo 'checked="checked"'; ?> type="radio" /> On</label>
										<label><input name="BWPS_banips_enable" value="0" <?php if ($opts['banips_enable'] == 0) echo 'checked="checked"'; ?> type="radio" /> Off</label>
									</td>
								</tr>
								
								<tr valign="top">
									<th scope="row">
										<label for="BWPS_banips_iplist">IP List</label>
									</th>
									<td>
										<textarea rows="10" cols="50" name="BWPS_banips_iplist" id="BWPS_banips_iplist"><?php echo $banips_iplist; ?></textarea><br />
										<p><em>
											IP addesses must be in IPV4 standard format (i.e. ###.###.###.###).<br />
											<a href="http://ip-lookup.net/domain-lookup.php" target="_blank">Lookup IP Address.</a><br />
											Enter only 1 IP address per line.<br />
											You may NOT ban your own IP address
										</em></p>
									</td>
								</tr>
							</tbody>
						</table>	
						<p class="submit"><input type="submit" name="BWPS_banips_save" value="<?php _e('save', 'better-wp-security'); ?>"></p>
					</form>
				</div>
			</div>
		</div>
			
		<?php include_once(trailingslashit(ABSPATH) . 'wp-content/plugins/better-wp-security/pages/donate.php'); ?>
		
		<?php if ($opts['banips_enable'] == 1) { ?>
			<div class="clear"></div>
		
			<?php
				$bgColor = $BWPS_banips->confirmRules();
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
						<pre><?php echo $BWPS_banips->getList(); ?></pre>
					</div>
				</div>
			</div>
		<?php } ?>
		
	</div>
</div>