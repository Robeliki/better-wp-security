<?php
/**
 * Create BWPS object.
 *
 * @package BWPS
 */

class BWPS {

	private $computer_id;

	/**
	 * Execute startup tasks
	 */
	function __construct() {
		global $wpdb;
		
		$this->computer_id = $wpdb->escape($_SERVER['REMOTE_ADDR']); //get the visitor's ip address
		
		$opts = $this->getOptions();
		
		if ($opts['d404_enable'] == 1) { //if detect 404 mode is enabled
		
			$computer_id = $wpdb->escape($_SERVER['REMOTE_ADDR']);
			
			if ($this->d404_checkLock($computer_id)) { //if locked out
				die(__('Please come back later'));
			}
			
			add_action('wp_head', array(&$this,'d404_check')); //register action
		}
		
		//remove wp-generator meta tag
		if ($opts['tweaks_removeGenerator'] == 1) { 
			remove_action('wp_head', 'wp_generator');
		}

		//remove login error messages if turned on
		if ($opts['tweaks_removeLoginMessages'] == 1) {
			add_filter('login_errors', create_function('$a', "return null;"));
		}

		//display random number for wordpress version if turned on
		if ($opts['tweaks_randomVersion'] == 1) {
			$this->tweaks_randomVersion();
		}
		
		//remove theme update notifications if turned on
		if ($opts['tweaks_themeUpdates'] == 1) {
			add_action('init', array(&$this, 'tweaks_themeupdates'), 1);
		}
		
		//remove plugin update notifications if turned on
		if ($opts['tweaks_pluginUpdates'] == 1) {
			add_action('init', array(&$this, 'tweaks_pluginupdates'), 1);
		}
		
		//remove core update notifications if turned on
		if ($opts['tweaks_coreUpdates'] == 1) {
			add_action('init', array(&$this, 'tweaks_coreupdates'), 1);
		}
		
		//remove wlmanifest link if turned on
		if ($opts['tweaks_removewlm'] == 1) {
			remove_action('wp_head', 'tweaks_wlwmanifest_link');
		}
		
		//remove rsd link from header if turned on
		if ($opts['tweaks_removersd'] == 1) {
			remove_action('wp_head', 'tweaks_rsd_link');
		}
		
		//require strong passwords if turned on
		if ($opts['tweaks_strongpass'] == 1) {
			add_action( 'user_profile_update_errors',  array(&$this, 'tweaks_strongpass'), 0, 3 ); 
		}
		
		//ban extra-long urls if turned on
		if ($opts['tweaks_longurls'] == 1 && !is_admin()) {
			if (strlen($_SERVER['REQUEST_URI']) > 255 ||
				strpos($_SERVER['REQUEST_URI'], "eval(") ||
				strpos($_SERVER['REQUEST_URI'], "CONCAT") ||
				strpos($_SERVER['REQUEST_URI'], "UNION+SELECT") ||
				strpos($_SERVER['REQUEST_URI'], "base64")) {
				@header("HTTP/1.1 414 Request-URI Too Long");
				@header("Status: 414 Request-URI Too Long");
				@header("Connection: Close");
				@exit;
			}
		}
		
		//see if they're locked out and banned from the site
		if ($opts['ll_denyaccess'] == 1 && $this->ll_checkLock()) {
			die('Security error!');
		}
			
		//check versions if user is admin
		if (is_admin()) {
			$this->checkVersions();
		}
		
		unset($opts);
	}

	/**
 	 * Returns the array of BWPS options
 	 * @return object 
 	 */
	function getOptions() {
		global $wpdb;
		
		if (!get_option("BWPS_options")) { //if options are not in the database retreive default options
			$opts = BWPS_defaults();
			update_option("BWPS_options", serialize($opts));
		} else { //get options from database and add db prefix to tablenames
			$opts = unserialize(get_option("BWPS_options"));
		}
		
		return $opts;
	}
		
	/**
 	 * Saves a new option to the database and returns an updated array of options
 	 * @return object 
 	 * @param String
 	 * @param String
 	 */
	function saveOptions($opt, $val) {
		global $wpdb;
		
		$opts = $this->getOptions(); 
			
		$opts[$opt] = $val;
				
		delete_option("BWPS_options");
		update_option("BWPS_options", serialize($opts));
			
		return $this->getOptions();;
	}
	
	/**
 	 * Returns the array of BWPS version numbers
 	 * @return object 
 	 */
	function getVersions() {
		global $wpdb;
		
		if (!get_option("BWPS_versions")) { //if options are not in the database retreive default options
			$vers = BWPS_versions();
			update_option("BWPS_versions", serialize($opts));
		} else { //get options from database and add db prefix to tablenames
			$vers = unserialize(get_option("BWPS_versions"));
		}
		
		return $vers;
	}
		
	/**
 	 * Saves a new option to the database and returns an updated array of version numbers
 	 * @return object 
 	 * @param String
 	 * @param String
 	 */
	function saveVersions($ver, $val) {
		global $wpdb;
		
		$vers = $this->getVersions(); 
			
		$vers[$ver] = $val;
				
		delete_option("BWPS_versions");
		update_option("BWPS_versions", serialize($vers));
			
		return $this->getVersions();;
	}

	/**
 	 * Check $path and return whether it is writable
 	 * @return Boolean
 	 * @param String file 
 	 */
	function can_write($path) {		 
		if ($path{strlen($path)-1} == '/') { //if we have a dir with a trailing slash
			return BWPS_can_write($path.uniqid(mt_rand()).'.tmp');
		} elseif (is_dir($path)) { //now make sure we have a directory
			return BWPS_can_write($path.'/'.uniqid(mt_rand()).'.tmp');
		}

		$rm = file_exists($path);
		$f = @fopen($path, 'a');
	
		if ($f===false) { //if we can't open the file
			return false;
		}
	
		fclose($f);
	
		if (!$rm) { //make sure to delete any temp files
			unlink($path);
		}
	
		return true; //return true
	}

	/**
 	 * Remove a given section of code from .htaccess
 	 * @return Boolean 
 	 * @param String
 	 * @param String
 	 */
	function remove_section($filename, $marker) {
		if (!file_exists($filename) || $this->can_write($filename)) { //make sure the file is valid and writable
	
			$markerdata = explode("\n", implode( '', file( $filename))); //parse each line of file into array

			$f = fopen($filename, 'w'); //open the file
			
			if ($markerdata) { //as long as there are lines in the file
				$state = true;
				
				foreach ($markerdata as $n => $markerline) { //for each line in the file
				
					if (strpos($markerline, '# BEGIN ' . $marker) !== false) { //if we're at the beginning of the section
						$state = false;
					}
					
					if ($state == true) { //as long as we're not in the section keep writing
						if ($n + 1 < count($markerdata)) //make sure to add newline to appropriate lines
							fwrite($f, "{$markerline}\n");
						else
							fwrite($f, "{$markerline}");
					}
					
					if (strpos($markerline, '# END ' . $marker) !== false) { //see if we're at the end of the section
						$state = true;
					}
				}
			}
			return true;
		} else {
			return false; //return false if we can't write the file
		}
	}

	/**
	 * Check supsection versions and prompt user if update is needed.
	 */	
	function checkVersions() {
	
		$vers = $this->getVersions();
		
		/**
	 	 * Display warning message
 		 */
 		 if (!function_exists('upWarning')) {
			function upWarning() {
				$preMess = '<div id="message" class="error"><p>' . __('Due to changes in the latest Better WP Security release you must update your') . ' <strong>';
				$postMess = '</strong></p></div>';
	
				if ($vers['AWAY'] != BWPS_VERSION_AWAY && $vers['AWAY'] > 0 && !isset($_POST['BWPS_away_save'])) { //see if away section needs updating
					echo $preMess . '<a href="/wp-admin/admin.php?page=BWPS-away">' . __('Better WP Security - Away Mode Settings.') . '</a>' . $postMess;
				}
				if ($vers['BANIPS'] != BWPS_VERSION_BANIPS && $vers['BANIPS'] > 0 && !isset($_POST['BWPS_banips_save'])) { //see if banips section needs updating
					echo $preMess . '<a href="/wp-admin/admin.php?page=BWPS-banips">' . __('Better WP Security - Ban IPs Settings.') . '</a>' . $postMess;
				}
				if ($vers['TWEAKS'] != BWPS_VERSION_TWEAKS && $vers['TWEAKS'] > 0 && !isset($_POST['BWPS_tweaks_save'])) { //see if tweaks section needs updating
					echo $preMess . '<a href="/wp-admin/admin.php?page=BWPS-tweaks">' . __('Better WP Security - System Tweaks.') . '</a>' . $postMess;
				}
				if ($vers['HIDEBE'] != BWPS_VERSION_HIDEBE && $vers['HIDEBE'] > 0 && !isset($_POST['BWPS_hidebe_save'])) { //see if hidebe section needs updating
					echo $preMess . '<a href="/wp-admin/admin.php?page=BWPS-hidebe">' . __('Better WP Security - Hide Backend Settings.') . '</a>' . $postMess;
				}
				if ($vers['LL'] != BWPS_VERSION_LL && $vers['LL'] > 0 && !isset($_POST['BWPS_ll_save'])) { //see if ll section needs updating
					echo $preMess . '<a href="/wp-admin/admin.php?page=BWPS-ll">' . __('Better WP Security - Limit Login Settings.') . '</a>' . $postMess;
				}
				if ($vers['HTACCESS'] != BWPS_VERSION_HTACCESS && $vers['HTACCESS'] > 0 && !isset($_POST['BWPS_htaccess_save'])) { //see if htaccess section needs updating
					echo $preMess . '<a href="/wp-admin/admin.php?page=BWPS-htaccess">' . __('Better WP Security - .htaccess Options.') . '</a>' . $postMess;
				}
				if ($vers['D404'] != BWPS_VERSION_D404 && $vers['D404'] > 0 && !isset($_POST['BWPS_d404_save'])) { //see if d404 section needs updating
					echo $preMess . '<a href="/wp-admin/admin.php?page=BWPS-d404">' . __('Better WP Security - Detect d404 Options.') . '</a>' . $postMess;
				}
			}
		}
		
		add_action('admin_notices', 'upWarning'); //register wordpress action
		unset($vers);
	}
		
	/**
	 * Returns local time
	 * @return String
	 */
	function getLocalTime() {
		return strtotime(get_date_from_gmt(date('Y-m-d H:i:s',time())));
	}
	
	/**
	 *  Returns domain without subdomain
	 * @return String
	 * @param String
	 */
	function uDomain($address) {
		preg_match("/^(http:\/\/)?([^\/]+)/i", $address, $matches);
		$host = $matches[2];
		preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
		$newAddress =  "http://(.*)" . $matches[0] ;
		
		return $newAddress;
	}
	
	/**
	 * Display time remaining for given future time
	 * @return String
	 * @param integer
	 */
	function dispRem($expTime) {
		$currTime = time(); 
    		$timeDif = $expTime - $currTime;
		$dispTime = floor($timeDif / 60) . " minutes and " . ($timeDif % 60) . " seconds";
		return $dispTime;
	}
	
	/**
	 * Check if given mode is turned on
	 * @return Boolean
	 * @param String
	 */
	function isOn($mode) {
		$opts = $this->getOptions();
		if ($mode == 'away') {
			$flag =  $opts['away_enable'];
		} elseif ($mode == 'll') {
			$flag =  $opts['ll_enable'];
		}
		unset($opts);
		return $flag;
	}
	
	/**
	 * Check to see if time restrictions allow login
	 * @return Boolean
	 */
	function away_check() {
		$opts = $this->getOptions();
			
		if ($opts['away_enable'] == 1) { //if away mode is enabled continue
			
			$lTime = $this->getLocalTime(); //get local time
			
			if ($opts['away_mode'] == 1) { //see if its daily
				if (date('a',$lTime) == "pm" && date('g',$lTime) != "12") {
					$linc = 12;
				}elseif (date('a',$lTime) == "am" && date('g',$lTime) == "12") {
					$linc = -12;
				} else {
					$linc = 0;
				}
				
				//check for appropriate times
				$local = ((date('g',$lTime) + $linc) * 60) + date('i',$lTime);
			
				if (date('a',$opts['away_start']) == "pm" && date('g',$opts['away_start']) != "12") {
					$sinc = 12;
				}elseif (date('a',$opts['away_start']) == "am" && date('g',$opts['away_start']) == "12") {
					$sinc = -12;
				} else {
					$sinc = 0;
				}
				
				$start = ((date('g',$opts['away_start']) + $sinc) * 60) + date('i',$opts['away_start']);
				
				if (date('a',$opts['away_end']) == "pm" && date('g',$opts['away_end']) != "12") {
					$einc = 12;
				} elseif (date('a',$opts['away_end']) == "am" && date('g',$opts['away_end']) == "12") {
					$einc = -12;
				} else {
					$einc = 0;
				}
				
				$end = ((date('g',$opts['away_end']) + $einc) * 60) + date('i',$opts['away_end']);
				
				//return true if they are not allowed to log in
				if ($start >= $end) { 
					if ($local >= $start || $local < $end) {
						unset($opts);
						return true;
					}
				} else {
					if ($local >= $start && $local < $end) {
						unset($opts);
						return true;
					}
				}
			} else {	
				if ($lTime >= $opts['away_start'] && $lTime <= $opts['away_end']) {
					unset($opts);
					return true;
				}
			}
		}
		unset($opts);
		return false; //they are allowed to log in
	}
	
	/**
	 * Execute if is a 404 page
	 */
	function d404_check() {
		global $wpdb;
		
		if (is_404()) { //if we're on a 404 page
			$computer_id = $wpdb->escape($_SERVER['REMOTE_ADDR']);
			$this->d404_log($computer_id);
			if ($this->d404_countAttempts($computer_id) >= 20 && !$this->d404_checkLock($computer_id) && !is_user_logged_in()) { //if we've seen too many 404s from an anonymous user lock them out
				$this->d404_lockout($computer_id);
			}
		}
	}
	
	/**
	 * Log the 404
	 * @return Boolean
	 * @param String
	 */
	function d404_log($computer_id) {
		global $wpdb;
		
		$opts = $this->getOptions();
		
		$qstring = $wpdb->escape($_SERVER['REQUEST_URI']);
					
		$hackQuery = "INSERT INTO " . BWPS_TABLE_D404 . " (computer_id, qstring, attempt_date)
			VALUES ('" . $computer_id . "', '" . $qstring . "', " . time() . ");";
			
		unset($opts);		
		return $wpdb->query($hackQuery);
	}
	
	/**
	 * Count how many 404s from host in previous 5 minutes
	 * @return integer
	 * @param String
	 */
	function d404_countAttempts($computer_id) {
		global $wpdb;
		
		$opts = $this->getOptions();
			
		$reTime = 300;
				
		$count = $wpdb->get_var("SELECT COUNT(attempt_ID) FROM " . BWPS_TABLE_D404 . "
			WHERE attempt_date +
			" . $reTime . " >'" . time() . "' AND
			computer_id = '" . $computer_id . "';"
		);
		
		unset($opts);
		
		return $count;
			
	}
	
	/**
	 * Lock out the host
	 * @param String
	 */
	function d404_lockOut($computer_id) {
		global $wpdb;
		
		$opts = $this->getOptions();
				
		$lHost = "INSERT INTO " . BWPS_TABLE_LOCKOUTS . " (computer_id, lockout_date, mode)
			VALUES ('" . $computer_id . "', " . time() . ", 1)";
					
		$wpdb->query($lHost);			
		
		unset($opts);
			
	}
	
	/**
	 * Determine if host is locked out
	 * @return Boolean
	 * @param String
	 */
	function d404_checkLock($computer_id) {
		global $wpdb;
		
		$opts = $this->getOptions();
		
		$hostCheck = $wpdb->get_var("SELECT computer_id FROM " . BWPS_TABLE_LOCKOUTS  . 
			" WHERE lockout_date < " . (time() + 1800) . " AND computer_id = '" . $computer_id . "' AND mode = 1;");
		
		unset($opts);
		
		if ($hostCheck) { //if host is locked out
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * List locked out users
	 * @return array
	 */
	function d404_listLocked() {
		global $wpdb;
		
		$opts = $this->getOptions();
			

		$lockList = $wpdb->get_results("SELECT lockout_ID, lockout_date, computer_id FROM " . BWPS_TABLE_LOCKOUTS  . " WHERE lockout_date < " . (time() + 1800) . " AND mode = 1;", ARRAY_A);
					
		unset($opts);
		return $lockList;
	}
	
	/**
	 * Count how many bad logins from host or username
	 * @return integer
	 * @param String
	 */
	function ll_countAttempts($username = "") {
		global $wpdb;
		
		$opts = $this->getOptions();
			
		$username = sanitize_user($username);
		$user = get_userdatabylogin($username);
			
		if ($user) { //if we are dealing with a user account return the approrpriate count
			$checkField = "user_id";
			$val = $user->ID;
		} else { 
			$checkField = "computer_id";
			$val = $this->computer_id;
		}
			
		$fails = $wpdb->get_var("SELECT COUNT(attempt_ID) FROM " . BWPS_TABLE_LL . "
			WHERE attempt_date +
			" . ($opts['ll_checkinterval'] * 60) . " >'" . time() . "' AND
			" . $checkField . " = '" . $val . "'"
		);
		
		unset($opts);	
		return $fails;
	}

	/**
	 * Log the bad login attempt
	 * @return Boolean
	 * @param String
	 */
	function ll_logAttempt($username = "") {
		global $wpdb;
		
		$opts = $this->getOptions();
		
		$username = sanitize_user($username);
		$user = get_userdatabylogin($username);
	
		if ($user) { //set the userid field if applicable
			$userId = $user->ID;
		} else {
			$userId = "";
		}
		
		unset($user);
					
		$failQuery = "INSERT INTO " . BWPS_TABLE_LL . " (user_id, computer_id, attempt_date)
			VALUES ('" . $userId . "', '" . $this->computer_id . "', " . time() . ");";
		
		unset($opts);		
		return $wpdb->query($failQuery);
	}
	
	/**
	 * Lock out the host or username
	 * @param String
	 */
	function ll_lockOut($username = "") {
		global $wpdb;
		
		$opts = $this->getOptions();

		$username = sanitize_user($username);
		$user = get_userdatabylogin($username);
			
		$loTime = time();
		$reTime = $loTime + ($opts['ll_banperiod'] * 60);
				
		if ($user) { //lock out the user
			$lUser = "INSERT INTO " . BWPS_TABLE_LOCKOUTS . " (user_id, lockout_date, mode)
				VALUES ('" . $user->ID . "', " . $loTime . ", 2)";
			unset($user);	
			$wpdb->query($lUser);
				
			$mesEmail = __("A Wordpress user, " . $username . ", has been locked out of the Wordpress site at "	. get_bloginfo('url') . " until " . date("l, F jS, Y \a\\t g:i:s a e",$reTime) . " due to too many failed login attempts. You may login to the site to manually release the lock if necessary.");
				
		} else { //just lock out the host
			$lHost = "INSERT INTO " . BWPS_TABLE_LOCKOUTS . " (computer_id, lockout_date, mode)
				VALUES ('" . $this->computer_id . "', " . $loTime . ", 2)";
					
			$wpdb->query($lHost);
				
			$mesEmail = __("A computer, " . $this->computer_id . ", has been locked out of the Wordpress site at "	. get_bloginfo('url') . " until " . date("l, F jS, Y \a\\t g:i:s a e",$reTime) . " due to too many failed login attempts. You may login to the site to manually release the lock if necessary.");
				
		}
		
		if ($opts['ll_emailnotify'] == 1) { //email the site admin if necessary
			$toEmail = get_site_option("admin_email");
			$subEmail = get_bloginfo('name') . ": Site Lockout Notification";
			$mailHead = 'From: ' . get_bloginfo('name')  . ' <' . $toEmail . '>' . "\r\n\\";
			
			$sendMail = wp_mail($toEmail, $subEmail, $mesEmail, $headers);
		}
		
		unset($opts);
			
	}
	
	/**
	 * Determine if host or username is locked out
	 * @return Boolean
	 * @param String
	 */
	function ll_checkLock($username = "") {
		global $wpdb;
		
		$opts = $this->getOptions();
		
		if (strlen($username) > 0) { //if a username was entered check to see if it's locked out
			$username = sanitize_user($username);
			$user = get_userdatabylogin($username);

			if ($user) {
				$userCheck = $wpdb->get_var("SELECT user_id FROM " . BWPS_TABLE_LOCKOUTS  . " WHERE lockout_date < " . (time() + ($opts['ll_banperiod'] * 60)). " AND user_id = '$user->ID' AND mode = 2");
				unset($user);
			}
		} else { //no username to be locked out
			$userCheck = false;
		}
		
		//see if the host is locked out
		$hostCheck = $wpdb->get_var("SELECT computer_id FROM " . BWPS_TABLE_LOCKOUTS  . " WHERE lockout_date < " . (time() + ($opts['ll_banperiod'] * 60)). " AND computer_id = '$this->computer_id' AND mode = 2");
		
		
		//return false if both the user and the host are not locked out	
		if (!$userCheck && !$hostCheck) {
			unset($opts);
			return false;
		} else {
			unset($opts);
			return true;
		}
	}
	
	/**
	 * List locked out users and computers
	 * @return array
	 */
	function ll_listLocked($ltype = "") {
		global $wpdb;
		
		$opts = $this->getOptions();
		
		//display the appropriate list	
		if ($ltype == "users") {
			$checkField = "user_id";
		} else {
			$checkField = "computer_id";
		}

		$lockList = $wpdb->get_results("SELECT lockout_ID, lockout_date, " . $checkField . " AS loLabel FROM " . BWPS_TABLE_LOCKOUTS  . " WHERE lockout_date < " . (time() + ($opts['ll_banperiod'] * 60)). " AND " . $checkField . " != '' AND mode = 2", ARRAY_A);
		
		//get the users's name if needed
		if ($ltype == "users" && sizeof($lockList) > 0) {
			$user = get_userdata($lockList[0]['loLabel']);
				
			$lockList[0]['loLabel'] = $user->user_login . " (" . $user->first_name . " " . $user->last_name . ")";
			unset($user);
		}
		
		unset($opts);
		return $lockList;
	}
	
	/**
	 * Set wordpress version to a random integer between 100 and 500
	 */
	function tweaks_randomVersion() {
		global $wp_version;

		$newVersion = rand(100,500);

		//always show real version to site administrators
		if (!is_admin()) {
			$wp_version = $newVersion;
		}
	}
	
	/**
	 * Disble plugin update notifications
	 */
	function tweaks_pluginupdates() {
		//don't remove for super admins
		if (!is_super_admin()) {
			remove_action( 'load-update-core.php', 'wp_update_plugins' );
			add_filter( 'pre_site_transient_update_plugins', create_function( '$a', "return null;" ) );
			wp_clear_scheduled_hook( 'wp_update_plugins' );
		}
	}

	/**
	 * Diable theme update notifications
	 */
	function tweaks_themeupdates() {
		if (!is_super_admin()) {
			remove_action( 'load-update-core.php', 'wp_update_themes' );
			add_filter( 'pre_site_transient_update_themes', create_function( '$a', "return null;" ) );
			wp_clear_scheduled_hook( 'wp_update_themes' );
		}
	}
	
	/**
	 * Disable core update notifications
	 */
	function tweaks_coreupdates() {
		if (!is_super_admin()) {
			remove_action('admin_notices', 'update_nag', 3);
			add_filter( 'pre_site_transient_update_core', create_function( '$a', "return null;" ) );
			wp_clear_scheduled_hook( 'wp_version_check' );
		}
	}
	
	/**
	 * add an error message if password is not strong enough
	 * @return object
	 * @param object
	 */
	function tweaks_strongpass( $errors ) {  
		$opts = $this->getOptions();
		
		//determine the minimum role for enforcement
		$minRole = $opts['tweaks_strongpassrole'];
	
		//all the standard roles and level equivalents
		$availableRoles = array(
			"administrator" => "8",
			"editor" => "5",
			"author" => "2",
			"contributor" => "1",
			"subscriber" => "0"
		);
		
		//roles and subroles
		$rollists = array(
			"administrator" => array("subscriber", "author", "contributor","editor"),
			"editor" =>  array("subscriber", "author", "contributor"),
			"author" =>  array("subscriber", "contributor"),
			"contributor" =>  array("subscriber"),
			"subscriber" => array()
		);
		
		$enforce = true;  
		$args = func_get_args();  
		$userID = $args[2]->ID;  
		if ( $userID ) {  //if updating an existing user
			$userInfo = get_userdata( $userID );  
			if ( $userInfo->user_level < $availableRoles[$minRole] ) {  
				$enforce = false;  
			}  
		} else {  //a new user
			if ( in_array( $_POST["role"],  $rollists[$minRole]) ) {  
				$enforce = false;  
			}  
		}  
		
		//add to error array if the password does not meet requirements
		if ( $enforce && !$errors->get_error_data("pass") && $_POST["pass1"] && $this->tweaks_pwordstrength( $_POST["pass1"], $_POST["user_login"] ) != 4 ) {  
			$errors->add( 'pass', __( '<strong>ERROR</strong>: You MUST Choose a password that rates at least <em>Strong</em> on the meter. Your setting have NOT been saved.' ) );  
		}  
		
		//cleanup
		unset($opts);
		unset ($rollists);
		unset($availableRoles);
		return $errors;  
	}  
 
 	/**
	 * Determin password strength based off wordpress display
	 * @return integer
	 * @param string
	 * @param string
	 */
	function tweaks_pwordstrength( $i, $f ) {  
		$h = 1; $e = 2; $b = 3; $a = 4; $d = 0; $g = null; $c = null;  
		if ( strlen( $i ) < 4 )  
			return $h;  
		if ( strtolower( $i ) == strtolower( $f ) )  
			return $e;  
		if ( preg_match( "/[0-9]/", $i ) )  
			$d += 10;  
		if ( preg_match( "/[a-z]/", $i ) )  
			$d += 26;  
		if ( preg_match( "/[A-Z]/", $i ) )  
			$d += 26;  
		if ( preg_match( "/[^a-zA-Z0-9]/", $i ) )  
			$d += 31;  
		$g = log( pow( $d, strlen( $i ) ) );  
		$c = $g / log( 2 );  
		if ( $c < 40 )  
			return $e;  
		if ( $c < 56 )  
			return $b;  
		return $a;  
	}
	
	/**
	 * Check to see if ssl is requirement for all admin and logins
	 * @return Boolean
	 */
	function tweaks_checkSSL() {
		if (FORCE_SSL_ADMIN == true && FORCE_SSL_LOGIN == true) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Generate htaccess rules for hidebe
	 * @return String
	 */
	function hidebe_getRules() {
	
		$opts = $this->getOptions();
		
		$siteurl = explode('/',trailingslashit(get_option('siteurl')));
		
		unset($siteurl[0]); unset($siteurl[1]); unset($siteurl[2]);
		
		$dir = implode('/',$siteurl);
		
		//get the slugs
		$login_slug = $opts['hidebe_login_slug'];
		$logout_slug = $opts['hidebe_logout_slug'];
		$admin_slug = $opts['hidebe_admin_slug'];
		$register_slug = $opts['hidebe_register_slug'];
				
		//generate the key
		$supsec_key = $this->hidebe_secKey();
		
		//get the domain without subdomain
		$reDomain = $this->uDomain(get_option('siteurl'));
		
		//see if user registration is allowed
		if (get_option('users_can_register') == 1) {
			$regEn = "RewriteCond %{QUERY_STRING} !^action=register\n";
		} else {
			$regEn = "";
		}
		
		//create string of rules
		$theRules = "<IfModule mod_rewrite.c>\n" . 
			"RewriteEngine On\n" . 
			"RewriteBase /\n" . 
			"RewriteRule ^" . $login_slug . " ".$dir."wp-login.php?" . $supsec_key . " [R,L]\n" .
			"RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$\n" .
			"RewriteRule ^" . $admin_slug . " ".$dir."wp-login.php?" . $supsec_key . "&redirect_to=/wp-admin/ [R,L]\n" .
			"RewriteRule ^" . $admin_slug . " ".$dir."wp-admin/?" . $supsec_key . " [R,L]\n" .
			"RewriteRule ^" . $register_slug . " " . $dir . "wp-login.php?" . $supsec_key . "&action=register [R,L]\n" .
			"RewriteCond %{HTTP_REFERER} !^" . $reDomain . "/wp-admin \n" .
			"RewriteCond %{HTTP_REFERER} !^" . $reDomain . "/wp-login\.php \n" .
			"RewriteCond %{HTTP_REFERER} !^" . $reDomain . "/" . $login_slug . " \n" .
			"RewriteCond %{HTTP_REFERER} !^" . $reDomain . "/" . $admin_slug . " \n" .
			"RewriteCond %{HTTP_REFERER} !^" . $reDomain . "/" . $register_slug . " \n" .
			"RewriteCond %{QUERY_STRING} !^" . $supsec_key . " \n" .
			"RewriteCond %{QUERY_STRING} !^action=logout\n" . 
			$regEn . 
			"RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$\n" .
			"RewriteRule ^wp-login\.php not_found [L]\n" .
			"</IfModule>\n";
		
		unset($opts);
		
		return $theRules;
	}
	
	/**
	 * Generates a random string to be used as a key
	 * @return String
	 */
	function hidebe_secKey() {	
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		srand((double)microtime()*1000000);
		$pass = '' ;		
		for ($i = 0; $i <= 20; $i++) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
		}
		return $pass;	
	}
		
	/**
	 * Checks to see if the saved htaccess rules are correct
	 * @return String
	 */
	function hidebe_confirmRules() {
		
		$htaccess = trailingslashit(ABSPATH).'.htaccess';
			
		$curRules = $this->hidebe_getRules();
		$savedRules = implode("\n", extract_from_markers($htaccess, 'Better WP Security Hide Backend' ));
			
		if (strlen($curRules) != strlen($savedRules)) {
			return "#ffebeb";
		} else {
			return "#fff";
		}
			
	}
	
	/**
	 * Echos currect htaccess contents
	 */
	function htaccess_showContents() {
	
		$htaccess = trailingslashit(ABSPATH).'.htaccess';
		
		$fh = fopen($htaccess, 'r');
		
		$contents = fread($fh, filesize($htaccess));
		
		fclose($fh);
		
		echo "<pre>" . $contents . "</pre>";
	
	}
	
	/**
	 * Generate htaccess rules for protect htaccess
	 * @return String
	 */
	function htaccess_genRules() {
	
		$opts = $this->getOptions();
		
		$rules = "";
		
		if ($_POST['BWPS_dirbrowse'] == 1 || $opts['htaccess_protectht'] == 1) { 
			$rules .= "Options All -Indexes\n\n";	
		} 
	
		if ($_POST['BWPS_protectht'] == 1 || $opts['htaccess_protectht'] == 1) { 
			$rules .= "<files .htaccess>\n" .
				"Order allow,deny\n" . 
				"Deny from all\n" .
				"</files>\n\n";
		}
		
		if ($_POST['BWPS_protectreadme'] == 1 || $opts['htaccess_protectreadme'] == 1) { 
			$rules .= "<files readme.html>\n" .
				"Order allow,deny\n" . 
				"Deny from all\n" .
				"</files>\n\n";
		}
		
		if ($_POST['BWPS_protectinstall'] == 1 || $opts['htaccess_protectinstall'] == 1) { 
			$rules .= "<files install.php>\n" .
				"Order allow,deny\n" . 
				"Deny from all\n" .
				"</files>\n\n";
		}
				
		if ($_POST['BWPS_protectwpc'] == 1 || $opts['htaccess_protectwpc'] == 1) { 
			$rules .= "<files wp-config.php>\n" .
				"Order allow,deny\n" . 
				"Deny from all\n" .
				"</files>\n\n";
		}
		
		if ($_POST['BWPS_request'] == 1 || $_POST['BWPS_qstring'] == 1 || $_POST['BWPS_hotlink'] == 1 || $opts['htaccess_request'] == 1 || $opts['htaccess_qstring'] == 1 || $opts['htaccess_hotlink'] == 1) { 
			$rules .= "<IfModule mod_rewrite.c>\n" . 
				"RewriteEngine On\n" . 
				"RewriteBase /\n\n";
		}
			
		if ($_POST['BWPS_request'] == 1 || $opts['htaccess_request'] == 1) { 
			$rules .= "RewriteCond %{REQUEST_METHOD} ^(HEAD|TRACE|DELETE|TRACK) [NC]\n" . 
				"RewriteRule ^(.*)$ - [F,L]\n";
		}
			
		if ($_POST['BWPS_qstring'] == 1 || $opts['htaccess_qstring'] == 1) { 
			$rules .= "RewriteCond %{QUERY_STRING} \.\.\/ [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} boot\.ini [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} tag\= [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} ftp\:  [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} http\:  [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} https\:  [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} mosConfig_[a-zA-Z_]{1,21}(=|%3D) [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} base64_encode.*\(.*\) [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} ^.*(\[|\]|\(|\)|<|>|�|\"|;|\?|\*|=$).* [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} ^.*(&#x22;|&#x27;|&#x3C;|&#x3E;|&#x5C;|&#x7B;|&#x7C;).* [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} ^.*(%24&x).* [NC,OR]\n" .  
				"RewriteCond %{QUERY_STRING} ^.*(%0|%A|%B|%C|%D|%E|%F|127\.0).* [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} ^.*(globals|encode|localhost|loopback).* [NC,OR]\n" . 
				"RewriteCond %{QUERY_STRING} ^.*(request|select|insert|union|declare).* [NC]\n" . 
				"RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$\n" .
				"RewriteRule ^(.*)$ - [F,L]\n\n";
		}
		
		if ($_POST['BWPS_hotlink'] == 1 || $opts['htaccess_hotlink'] == 1) { 
				
			$reDomain = $this->uDomain(get_option('siteurl'));
				
			$rules .= "RewriteCond %{HTTP_REFERER} !^$\n" .
				"RewriteCond %{HTTP_REFERER} !^" . $reDomain . "/.*$ [NC]\n" .
				"RewriteRule .(jpg|jpeg|png|gif|pdf|doc)$ - [F]\n\n";
				
		}
		
		if ($_POST['BWPS_request'] == 1 || $_POST['BWPS_qstring'] == 1 || $_POST['BWPS_hotlink'] == 1 || $opts['htaccess_request'] == 1 || $opts['htaccess_qstring'] == 1 || $opts['htaccess_hotlink'] == 1) { 
			$rules .= "</IfModule>\n";
		}
		
		unset($opts);
		return $rules;
	}

	/**
	 * Generates htaccess rules from a given array of ip addresses
	 * @return Boolean
	 * @param array
	 */
	function banips_createRules($ipArray) {
		global $theRules; 
		
		$goodAddress = true;
		
		//get current ip address
		$myIp = getenv("REMOTE_ADDR");
		
		//run through each ip
		for ($i = 0; $i < sizeof($ipArray) && $goodAddress == true; $i++) {
			$ipArray[$i] = trim($ipArray[$i]);
			if (strlen($ipArray[$i]) > 0 && (!$this->banips_checkIps($ipArray[$i]) || $ipArray[$i] == $myIp)) {
				$goodAddress = false; //we have a bad ip
			}
		}
	
		if ($goodAddress == true) { //if we don't have any bad ips create the string
			
			$ipList = implode(" ",$ipArray);
	
			$theRules = "order allow,deny\n" . 
				"deny from " . $ipList . "\n" . 
				"allow from all\n";
			return true;
				
		} else {
			
			return false;
				
		}
	}
	
	/**
	 * Return the htaccess rules in string format
	 * @return String
	 */
	function banips_getList() {
		global $theRules; 
		
		if (strlen($theRules) < 1) { //if $theRules isn't set check htaccess
			return implode("\n", extract_from_markers($htaccess, 'Better WP Security Ban IPs' ));
		} else {
			return $theRules;
		}
	}

	/**
	 * Validate IP address
	 * @return Boolean
	 * @param string
	 */
	function banips_checkIps($address) {
		if (preg_match( "/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $address)) {
			return true;
		} else {
			return false;
		}
	}
		
	/**
	 * Confirmed banip rules in htaccess are correct
	 * @return String
	 */
	function banips_confirmRules() {
		global $theRules; 
		
		$htaccess = trailingslashit(ABSPATH).'.htaccess';
			
		$savedRules = implode("\n", extract_from_markers($htaccess, 'Better WP Security Ban IPs' ));
			
		if (strlen($theRules) != strlen($savedRules)) { //if the rules aren't correct
			return "#ffebeb";
		} else { //rules are correct
			return "#fff";
		}
			
	}
	
	function status_getStatus() {
		$this->status_checkWPVersion();
		$this->status_checkAdminUser();
		$this->status_checkTablePre();
		$this->status_checkhtaccess();
		$this->status_checkLimitlogin();
		$this->status_checkAway();
		$this->status_checkhidebe();
		$this->status_checkStrongPass();
		$this->status_checkHead();
		$this->status_checkUpdates();
		$this->status_checklongurls();
		$this->status_checkranver();
		$this->status_check404();
		$this->status_checkSSL();
		$this->status_checkContentDir();
	}
	
	function status_checkContentDir() {
		echo "<p>\n";
		if (!strstr(WP_CONTENT_DIR,'wp-content') || !strstr(WP_CONTENT_URL,'wp-content')) {
			echo "<span style=\"color: green;\">You have renamed the wp-content directory of your site.</span>\n";
		} else {
			echo "<span style=\"color: red;\">You should rename the wp-content directory of your site. <a href=\"admin.php?page=BWPS-content\">Click here to do so</a>.</span>\n";
		}
		echo "</p>\n";
	}
	
	function status_checkSSL() {
		echo "<p>\n";
		if (FORCE_SSL_ADMIN == true && FORCE_SSL_LOGIN == true) {
			echo "<span style=\"color: green;\">You are requiring a secure connection for logins and the admin area.</span>\n";
		} else {
			echo "<span style=\"color: orange;\">You are not requiring a secure connection for longs or the admin area. <a href=\"admin.php?page=BWPS-tweaks\">Click here to fix this</a>.</span>\n";
		}
		echo "</p>\n";
	}
	
	function status_check404() {
		$opts = $this->getOptions();
			
		echo "<p>\n";
		if ($opts['d404_enable'] == 1) {
			echo "<span style=\"color: green;\">Your site is secured from attacks by XSS.</span>\n";
		} else {
			echo "<span style=\"color: red;\">Your site is still vulnerable to some XSS attacks. <a href=\"admin.php?page=BWPS-d404\">Click here to fix this</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkranver() {
		$opts = $this->getOptions();
			
		echo "<p>\n";
		if ($opts['tweaks_randomVersion'] == 1) {
			echo "<span style=\"color: green;\">Version information is obscured to all non admin users.</span>\n";
		} else {
			echo "<span style=\"color: red;\">Users may still be able to get version information from various plugins and themes. <a href=\"admin.php?page=BWPS-tweaks\">Click here to fix this</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checklongurls() {
		$opts = $this->getOptions();
			
		echo "<p>\n";
		if ($opts['tweaks_longurls'] == 1) {
			echo "<span style=\"color: green;\">Your installation does not accept long URLs.</span>\n";
		} else {
			echo "<span style=\"color: red;\">Your installation accepts long (over 255 character) URLS. This can lead to vulnerabilities. <a href=\"admin.php?page=BWPS-tweaks\">Click here to fix this</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkLogin() {
		$opts = $this->getOptions();
			
		echo "<p>\n";
		if ($opts['tweaks_removeLoginMessages'] == 1) {
			echo "<span style=\"color: green;\">No error messages are displayed on failed login.</span>\n";
		} else {
			echo "<span style=\"color: red;\">Error messages are displayed to users on failed login. <a href=\"admin.php?page=BWPS-tweaks\">Click here to remove them</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkUpdates() {
		$opts = $this->getOptions();
		
		$hcount = intval($opts["tweaks_themeUpdates"]) + intval($opts["tweaks_pluginUpdates"]) + intval($opts["tweaks_coreUpdates"]);
	
		echo "<p>\n";
		if ($hcount == 3) {
			echo "<span style=\"color: green;\">Non-administrators cannot see available updates.</span>\n";
		} elseif ($hcount > 0) {
			echo "<span style=\"color: orange;\">Non-administrators can see some updates. <a href=\"admin.php?page=BWPS-tweaks\">Click here to fully fix it</a>.</span>\n";
		} else {
			echo "<span style=\"color: red;\">Non-administrators can see all updates. <a href=\"admin.php?page=BWPS-tweaks\">Click here to fix it</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkHead() {
		$opts = $this->getOptions();
		
		$hcount = intval($opts["tweaks_removeGenerator"]) + intval($opts["tweaks_removersd"]) + intval($opts["tweaks_removewlm"]);
	
		echo "<p>\n";
		if ($hcount == 3) {
			echo "<span style=\"color: green;\">Your Wordpress header is revealing as little information as possible.</span>\n";
		} elseif ($hcount > 0) {
			echo "<span style=\"color: orange;\">Your Wordpress header is still revealing some information to users. <a href=\"admin.php?page=BWPS-tweaks\">Click here to fully fix it</a>.</span>\n";
		} else {
			echo "<span style=\"color: red;\">Your Wordpress header is showing too much information to users. <a href=\"admin.php?page=BWPS-tweaks\">Click here to fix it</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkStrongPass() {
		$opts = $this->getOptions();
		
		$isOn = $opts['tweaks_strongpass'];
		$role = $opts['tweaks_strongpassrole']; 
	
		echo "<p>\n";
		if ($isOn == 1 && $role == 'subscriber') {
			echo "<span style=\"color: green;\">You are enforcing strong passwords for all users</span>\n";
		} elseif ($isOn == 1) {
			echo "<span style=\"color: orange;\">You are enforcing strong passwords, but not for all users. <a href=\"admin.php?page=BWPS-tweaks\">Click here to fix</a>.</span>\n";
		} else {
			echo "<span style=\"color: red;\">You are not enforcing strong passwords. <a href=\"admin.php?page=BWPS-tweaks\">Click here to enforce strong passwords.</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkhidebe() {
		$opts = $this->getOptions();
			
		echo "<p>\n";
		if ($opts['hidebe_enable'] == 1) {
			echo "<span style=\"color: green;\">Your Wordpress admin area is hidden.</span>\n";
		} else {
			echo "<span style=\"color: red;\">Your Wordpress admin area  file is NOT hidden. <a href=\"admin.php?page=BWPS-hidebe\">Click here to secure it</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkWPVersion() {
		global $wp_version;
		
		$currVersion = "3.0.1";
		
		echo "<p>\n";
		
		if (!is_numeric(intval($wp_version))) {
			echo "<span style=\"color: orange;\">Your WordPress version: <strong><em>" . $wp_version . "</em></strong> Your using a non-stable version of Wordpress. Switch to a stable version to avoid potential security issues.</span>\n";
		} else {
			if ($wp_version >= $currVersion) {
				echo "<span style=\"color: green;\">Your WordPress version: <strong><em>" . $wp_version . "</em></strong> Your Wordpress version is stable and current.</span>\n";
			} else {
				echo "<span style=\"color: red;\">Your WordPress version: <strong><em>" . $wp_version . "</em></strong> You need version " . $currVersion . ".  You should <a href=\"http://wordpress.org/download/\">upgrade</a> immediately.</span>\n";
			}
		}
		echo "</p>\n";
	}
	
	function status_checkTablePre(){
		global $wpdb;
		
		echo "<p>\n";

		if ($table_prefix == 'wp_') {
			echo "<span style=\"color: red;\">" . __('Your table prefix should not be <em>wp_</em>.') . "  <a href=\"admin.php?page=BWPS-database\">" . __('Click here to change it') . "</a>.</span>\n";
		}else{
			echo "<span style=\"color: green;\">" . __('Your table prefix is') . " <em>" . $wpdb->prefix . "</em>.</span>\n";
		}

		echo "</p>\n";
	}
	
	function status_checkAdminUser() {
		global $wpdb;

		$adminUser = $wpdb->get_var("SELECT user_login FROM " . $wpdb->users . " WHERE user_login='admin'");
		
		echo "<p>\n";
		
		if ($adminUser =="admin") {
			echo "<span style=\"color: red;\">" . __('The <em>admin</em> user still exists.') . "  <a href=\"admin.php?page=BWPS-adminuser\">" . __('Click here to rename it') . "</a>.</span>\n";
		} else {
			echo "<span style=\"color: green;\">" . __('The <em>admin</em> user has been removed.') . "</span>\n";
		}
		
		echo "</p>\n";
	}
	
	function status_checkhtaccess() {
	
		$opts = $this->getOptions();
		
		$htcount = intval($opts["htaccess_protectht"]) + intval($opts["htaccess_protectwpc"]) + intval($opts["htaccess_dirbrowse"]) + intval($opts["htaccess_hotlink"]) + intval($opts["htaccess_request"]) + intval($opts["htaccess_qstring"]) + intval($opts["htaccess_protectreadme"]) + intval($opts["htaccess_protectinstall"]);
	
		echo "<p>\n";
		if ($htcount == 8) {
			echo "<span style=\"color: green;\">" . __('Your .htaccess file is fully secured.') . "</span>\n";
		} elseif ($htcount > 0) {
			echo "<span style=\"color: orange;\">" . __('Your .htaccess file is partially secured.') . " <a href=\"admin.php?page=BWPS-htaccess\">" . __('Click here to fully secure it') . "</a>.</span>\n";
		} else {
			echo "<span style=\"color: red;\">" . __('Your .htaccess file is NOT secured.') . " <a href=\"admin.php?page=BWPS-htaccess\">" . __('Click here to secure it') . "</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkLimitlogin() {
	
		$opts = $this->getOptions();
	
		echo "<p>\n";
		if ($opts['limitlogin_enable'] == 1) {
			echo "<span style=\"color: green;\">" . __('Your site is not vulnerable to brute force attacks.') . "</span>\n";
		} else {
			echo "<span style=\"color: red;\">" . __('Your site is vulnerable to brute force attacks.') . " <a href=\"admin.php?page=BWPS-limitlogin\">" . __('Click here to secure it') . "</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
	
	function status_checkAway() {
	
		$opts = $this->getOptions();
	
		echo "<p>\n";
		
		if ($opts['away_enable'] == 1) {
			echo "<span style=\"color: green;\">" . __('Your Wordpress admin area is not available when you will not be needing it.') . "</span>\n";
		} else {
			echo "<span style=\"color: orange;\">" . __('Your Wordpress admin area is available 24/7. Do you really update 24 hours a day?') . " <a href=\"admin.php?page=BWPS-away\">" . __('Click here to limit admin availability') . "</a>.</span>\n";
		}
		echo "</p>\n";
		
		unset($opts);
	}
}