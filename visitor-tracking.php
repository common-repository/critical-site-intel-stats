<?php
/*
Plugin Name: Critical Site Intel
Plugin URI: http://www.mikeleembruggen.com
Description: Displays critical site intel on visitor activity and site performance. Find out exactly: 1. How long your pages take to load. 2. How long visitors are staying on your pages. 3. Which country is sending you the most traffic.
Version: 1.0
Author: Mike Leembruggen
License: GNU General Public License, version 2

Copyright 2011   Techiehelpdesk.com
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
?>
<?php
## Dashboard add
function vtracker_add() {
	wp_add_dashboard_widget('vtracker', 'Critical Site Intel', 'vtracker_display');
	loadvtcss();
}

## Dashboard display
function vtracker_display() {
	$func = 1; include("func.php");
}

## Menu build
function vtracker_menu() {
	if (function_exists('add_options_page')) {
		$page = add_options_page(__('Critical Site Intel'), __('Critical Site Intel'), 1, __FILE__, 'vtracker_admin');
		add_action( 'admin_print_styles-'.$page, 'loadvtcss' );
	}
}

function loadvtcss() {
	wp_enqueue_style('vtracker_css');
}

## Tracker code
function vtracking() {
	global $wpdb;
	$tname = $wpdb->prefix."vtracker";
	$sitel = get_bloginfo('url');
	
	$date = date('Y-m-j');
	$time = date('H:i:s');
	$ip = $_SERVER['REMOTE_ADDR'];
	$filename = $_SERVER['SCRIPT_NAME'];
	$request = $_SERVER['REQUEST_URI'];
	$agent = $_SERVER['HTTP_USER_AGENT'];
	
	
	/*
	//Old visitors tracking
	
	if (file_exists("http://www.ipgp.net/api/xml/")) {
    	$xml = simplexml_load_file("http://www.ipgp.net/api/xml/". $ip);
 		$country = $xml->Country;
	} else {
		$country = "not found";
	}
	*/
	
	$json = file_get_contents("http://iptocountry.tellhowto.com/api/?key=009946535a6e8e4926ab4d7b07c5d57a&ip=".$ip);
	$json = json_decode($json,true);
	if(empty($json))
		$country = "Error- Server problem";
	else if($json["result"] == "success")
		$country = $json['country'];
	else if($json["result"] == "failed")
		 $country = "Error- ".$json["error"];
		 
	
/*	$xml = simplexml_load_file("http://www.ipgp.net/api/xml/". $ip);
	$country = $xml->Country;
*/	
	if (!isset($_REQUEST['visitid'])) {
		$visit_id = $_REQUEST['visitid'];
		$wpdb->insert($tname, array('date' => $date, 'time' => $time, 'ip' => $ip, 'country' => $country, 'filename' => $filename, 'request' => $request, 'agent' => $agent));
		$visit_id = $wpdb->insert_id;
		
		echo "<script type='text/javascript' src='".get_bloginfo('url').'/wp-content/plugins/'.plugin_basename( dirname( __FILE__ )) ."/tcall.js'></script>";
		echo '<script type="text/javascript">
var myTracking = { visitId: '.$visit_id.', preLoadStamp : (new Date()).getTime(), onLoadStamp : 0, oldOnLoad: function (){}, loaded: function (newTime){
if(this.onLoadStamp == 0) this.onLoadStamp = newTime;
if(this.oldOnLoad){this.oldOnLoad(); this.oldOnLoad = null; }
var loadTime = this.onLoadStamp - this.preLoadStamp;
var s = "'.$sitel.'?loaded=" + loadTime + "&visitid=" + this.visitId;
tcall(s);
setTimeout(myTracking.l, 5000);
}, l: function (){ myTracking.loaded((new Date()).getTime()); }, init: function (){ this.oldOnLoad = window.onload; window.onload = this.l;}};
myTracking.init();
</script>';
	} else {
		$loadtime = "";
		if(isset($_REQUEST['loaded'])) {
			$loadtime = $_REQUEST['loaded']*1;
		}
		$laststamp = date("Y-m-d H:i:s");
		$visit_id = $_REQUEST['visitid'];

		$wpdb->query("UPDATE $tname SET loadtime = '$loadtime', laststamp = '$laststamp' WHERE id = $visit_id");
	}
}

function vtracker_activate() {
	global $wpdb;
	
	$tname = $wpdb->prefix."vtracker";
	if ($wpdb->get_var("SHOW TABLES LIKE '$tname'") != $tname) {
		$sql = "CREATE TABLE " . $tname . " (id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, loadtime INT(100) NOT NULL, laststamp DATETIME NOT NULL, date DATE NOT NULL, time TIME NOT NULL, ip VARCHAR(20) NOT NULL, country VARCHAR(200) NOT NULL, filename TEXT NOT NULL, request TEXT NOT NULL, agent TEXT NOT NULL);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	$opname = "vtrack-settings";
	$defval = "0";
	add_option($opname, $defval, ' ', 'no');
	
	$crontimer = get_option($opname);
	if (!wp_next_scheduled('vtracker_cron') && $crontimer != 0) {
		if ($crontimer == 1) {
			$timevar = 2419200;
		} else if ($crontimer == 2) {
			$timevar = 4838400;
		} else if ($crontimer == 3) {
			$timervar = 7257600;
		} else if ($crontimer == 6) {
			$timervar = 14515200;
		}
		wp_schedule_event( time(), $timevar, 'vtracker_cron' );
	}
}

## Disable tracking and cron maintenance
function vtracker_deactivate() {
	remove_action('wp_footer', 'vtracking');
	wp_clear_scheduled_hook('vtracker_cron');
}

function vtracker_cron() {
	# Maintenance works on amount of months, not month to month
	global $wpdb;
	$cronarray = array();
	$tname = $wpdb->prefix."vtracker";
	$mcnt = get_option('vtrack-settings');
	$dress = $wpdb->get_results("SELECT DISTINCT date FROM $tname ORDER BY date");
	foreach ($dress as $dres) {
		$cronarray[] = substr($dres->date, 0, 7);
	}
	
	$cronarray = array_unique($cronarray);
	
	krsort($cronarray);
	
	$i = 0;
	while ($i != $mcnt-1) {
		next($cronarray);
		$i++;
	}
	
	# Return the earliest date to keep - earlier dates to be deleted
	$dress = $wpdb->get_results("SELECT id, date FROM $tname ORDER BY date");
	foreach ($dress as $dres) {
		if ((int)str_replace("-", "", substr($dres->date, 0, 7)) < (int)str_replace("-", "", current($cronarray))) {
			$mid = $dres->id;
			$wpdb->query("DELETE FROM $tname WHERE id = '$mid'");
		}
	}
}

## Admin display
function vtracker_admin() {
	$func = 2; include("func.php");
}

## Hooks
if (is_admin == true) {
	add_action('admin_menu', 'vtracker_menu');
	add_action('wp_dashboard_setup', 'vtracker_add');
	add_action('wp_head', 'vtracking');
	wp_register_style('vtracker_css', get_bloginfo('url').'/wp-content/plugins/'.plugin_basename( dirname( __FILE__ )) .'/styling.css');
	
	register_activation_hook(__FILE__, 'vtracker_activate');
	register_deactivation_hook(__FILE__, 'vtracker_deactivate');
}	
?>