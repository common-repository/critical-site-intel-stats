<?php
/*
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

## Populate main entries table
function entries() {
	if (isset($_REQUEST['epg'])) { 
		$epg = $_REQUEST['epg'];
	} else {
		$epg = "1";
		$urivars = "";
	}
	
	$efilter = "";
	if (isset($_REQUEST['efilter'])) { 
		$efilter = $_REQUEST['efilter'];
		$urivars = "&efilter=".$efilter;
	}
	
	$countryo = "";
	if (isset($_REQUEST['countryonly'])) { 
		$countryo = $_REQUEST['countryonly'];
		$urivars = "&countryonly=".$countryo;
	}
	
	$pageo = "";
	if (isset($_REQUEST['pageonly'])) { 
		$pageo = $_REQUEST['pageonly'];
		$urivars = "&pageonly=".$pageo;
	}
	
	$rstr = "";
	$rstr .= '<th width="50">ID</th><th width="80">Load Time</th>
	<th width="90">Time On Page</th><th width="90">Date</th>
	<th width="80">Time</th><th width="90">IP</th><th width="100">Country</th>
	<th width="110">Filename</th><th width="110">Request</th><th width="200">Agent</th>';
	
	# Define database query based on filter request
	if ($efilter == "" && $countryo == "" && $pageo == "") {
		$qarg = "ORDER BY id";
	} else if ($efilter != "") {
		$qarg = "WHERE ip = '$efilter' ORDER BY id";
	} else if ($countryo != "") {
		if ($countryo == "unknown") {
			$qarg = "WHERE LENGTH(country) = 0 ORDER BY id";
		} else {
			$qarg = "WHERE country = '$countryo' ORDER BY id";
		}
	} else if ($pageo != "") {
		$qarg = "WHERE request = '$pageo' ORDER BY id";
	}
	
	# Build results
	global $wpdb;
	$tname = $wpdb->prefix."vtracker";
	$tress = $wpdb->get_results("SELECT * FROM $tname $qarg DESC");
	$i = 0;
	foreach ($tress as $tres) {
		if ($i >= $epg*10-10) {
			if ($i == 10*$epg) {
				break;
			}
			$elist .= "<tr><td>".$tres->id."</td><td>".$tres->loadtime."ms</td><td>".tconvert(timeonpage($tres->date." ".$tres->time, $tres->laststamp))."</td><td>".$tres->date."</a></td><td>".$tres->time."</td><td><a href='?page=critical-site-intel-stats/visitor-tracking.php&efilter=".$tres->ip."'>".$tres->ip."</a></td><td><a href='?page=critical-site-intel-stats/visitor-tracking.php&countryonly=".$tres->country."'>".$tres->country."</a></td><td>".$tres->filename."</td><td><a href='?page=critical-site-intel-stats/visitor-tracking.php&pageonly=".$tres->request."'>".$tres->request."</a></td><td>".$tres->agent."</td></tr>";
		}
		$i++;
	}
	
	if (count($tress) > 10) {
		$rstr .= '<div class="pag">Pages: '.paggen('epg', $tress, 10, $urivars).'</div>';
	}
	
	$rstr .= $elist;
	
	return $rstr;
}

## Pagination Generator
function paggen($type, $pgarray, $total, $urivars) {
	if (isset($_REQUEST[$type])) { 
		$vtpg = $_REQUEST[$type];
	} else {
		$vtpg = "1";
	}
	$strVTPages = "";
	$totalpages = ceil(count($pgarray)/$total);
	while ($totalpages != 0) {
		if ($vtpg == null && $totalpages == 1) {
			$strVTPages = $totalpages." | ".$strVTPages;
		} else if ($vtpg == $totalpages) {
			$strVTPages = $totalpages." | ".$strVTPages;
		} else {
			$strVTPages = "<a href='?page=critical-site-intel-stats/visitor-tracking.php&".$type."=".$totalpages.$urivars."'>".$totalpages."</a> | ".$strVTPages;
		}
		$totalpages = $totalpages-1;
	}
	return $strVTPages;
}

## Formula for calculating time on page
function timeonpage($start, $end) {
	if ($end == "0000-00-00 00:00:00") {
		$tonp = 0;
	} else {
		$start = strtotime($start);
		$end = strtotime($end);
		$tonp = $end-$start;
	}
	
	return $tonp;
}

function tconvert($secs) {
	$h = floor($secs / 3600);
	$m = floor($secs / 60);
	$s = (int)($secs - $h*3600 - $m*60);
		
	$formtime = $h."h ".$m."m ".$s."s";
	
	return $formtime;
}

## Country table populate
function countrypop() {
	if (isset($_REQUEST['vtcountry'])) { 
		$vtpg = $_REQUEST['vtcountry'];
	} else {
		$vtpg = "1";
	}
	$rstr = "";
	$rstr .= '<div class="stbc"><table class="wp-list-table widefat plugins"><th>Country</th><th width="60">Avg time on page</th><th width="60">Avg load time</th><th width="30">Imprs.</th>';
	
	global $wpdb;
	$pcntry = "0";
	$countryarray = array();
	$tname = $wpdb->prefix."vtracker";
	$cress = $wpdb->get_results("SELECT * FROM $tname ORDER BY country");
	foreach ($cress as $cres) {
		$cval = $cres->country;
		if ($pcntry != $cval) {
			$countrycount = $wpdb->get_var("SELECT COUNT(*) FROM $tname WHERE country = '$cval'");
			if ($cval == null) {
				$cval = "unknown";
			}
			$countryarray[$cval] = $countrycount;
			$ii++;
		}
		$pcntry = $cval;
	}
	arsort($countryarray);
	$i = 0;
	foreach ($countryarray as $cnt => $cvalue) {
		if ($i >= $vtpg*5-5) {
			if ($i == 5*$vtpg) {
				break;
			}
			$loadt = 0;
			$tonp = 0;
			$ii = 0;
			$cntr = $cnt;
			if ($cntr == "unknown") {
				$cntr = "";
			}
			$vds = $wpdb->get_results("SELECT loadtime, laststamp, date, time FROM $tname WHERE country = '$cntr'");
			foreach ($vds as $vd) {
				$loadt += $vd->loadtime;
				$tonp += timeonpage($vd->date." ".$vd->time, $vd->laststamp);
				$ii++;
			}
			$loadt = floor($loadt/$ii);
			$tonp = floor($tonp/$ii);
			$tonp = tconvert($tonp);
			$elist .= "<tr><td><a href='options-general.php?page=critical-site-intel-stats/visitor-tracking.php&countryonly=".$cnt."'>".$cnt."</a></td><td>".$tonp."</td><td>".$loadt."ms</td><td>".$cvalue."</td></tr>";
		}
		$i++;
	}
	
	$rstr .= $elist;
	
	$urivars = "";
	
	if (count($countryarray) > 5) {
		$rstr .= '<tr><td colspan="4" style="text-align: right">Pages: '.paggen("vtcountry", $countryarray, 5, $urivars).'</td></tr>';
	}
	$rstr .= '</table></div>';
	
	return $rstr;
}

## Page table populate
function pagepop() {
if (isset($_REQUEST['vtpage'])) { 
		$vtpg = $_REQUEST['vtpage'];
	} else {
		$vtpg = "1";
	}
	$rstr = "";
	$rstr .= '<div class="stbc"><table class="wp-list-table widefat plugins"><th>Page</th><th width="60">Avg time on page</th><th width="60">Avg load time</th><th width="30">Imprs.</th>';
	
	global $wpdb;
	$ppage = "0";
	$pagearray = array();
	$tname = $wpdb->prefix."vtracker";
	$cress = $wpdb->get_results("SELECT * FROM $tname ORDER BY request");
	foreach ($cress as $cres) {
		$cval = $cres->request;
		if ($ppage != $cval) {
			$pagecount = $wpdb->get_var("SELECT COUNT(*) FROM $tname WHERE request = '$cval'");
			$pagearray[$cval] = $pagecount;
			$ii++;
		}
		$ppage = $cval;
	}
	arsort($pagearray);
	$i = 0;
	foreach ($pagearray as $cnt => $cvalue) {
		if ($i >= $vtpg*5-5) {
			if ($i == 5*$vtpg) {
				break;
			}
			$loadt = 0;
			$tonp = 0;
			$ii = 0;
			$cntr = $cnt;
			$vds = $wpdb->get_results("SELECT loadtime, laststamp, date, time FROM $tname WHERE request = '$cntr'");
			foreach ($vds as $vd) {
				$loadt += $vd->loadtime;
				$tonp += timeonpage($vd->date." ".$vd->time, $vd->laststamp);
				$ii++;
			}
			$loadt = floor($loadt/$ii);
			$tonp = floor($tonp/$ii);
			$tonp = tconvert($tonp);
			$elist .= "<tr><td><a href='options-general.php?page=critical-site-intel-stats/visitor-tracking.php&pageonly=".$cnt."'>".$cnt."</a></td><td>".$tonp."</td><td>".$loadt."ms</td><td>".$cvalue."</td></tr>";
		}
		$i++;
	}
	
	$rstr .= $elist;
	
	$urivars = "";
	
	if (count($pagearray) > 5) {
		$rstr .= '<tr><td colspan="4" style="text-align: right">Pages: '.paggen("vtpage", $pagearray, 5, $urivars).'</td></tr>';
	}
	$rstr .= '</table></div>';
	
	return $rstr;
}

## Summary statistics populate
function sspop() {
	global $wpdb;
	$tname = $wpdb->prefix."vtracker";
	
	$rstr = "";
	$rstr .= '<div class="stbs"><table class="wp-list-table widefat plugins">
	<tr><td class="titlest" width="100"><div>total visits:</div></td><td class="valst"><div>';

	$rstr .= $wpdb->get_var("SELECT COUNT(*) FROM $tname");
	
	$rstr .= '</div></td><td width="18"><div class="morest"><a href="options-general.php?page=critical-site-intel-stats/visitor-tracking.php&ss=1">+</a></div></td></tr>
	<tr><td class="titlest"><div>unique visits:</div></td><td class="valst"><div>';
	
	$rstr .= $wpdb->get_var("SELECT COUNT(DISTINCT ip) FROM $tname");
	
	$rstr .= '</div></td><td><div class="morest"><a href="options-general.php?page=critical-site-intel-stats/visitor-tracking.php&ss=2">+</a></div></td></tr>
	<tr><td class="titlest"><div>average time on page:</div></td><td class="valst"><div>';
	
	$sress = $wpdb->get_results("SELECT laststamp, time, date FROM $tname");
	$i = 0;
	$tonp = 0;
	foreach($sress as $sres) {
		$tonp += timeonpage($sres->date." ".$sres->time, $sres->laststamp);
		$i++;
	}
	
	if ($i != 0) {
		$tonp = floor($tonp/$i);
		$rstr .= tconvert($tonp);
	}
	
	$rstr .= '</div></td><td><div class="morest"><a href="options-general.php?page=critical-site-intel-stats/visitor-tracking.php&ss=3">+</a></div></td></tr>
	<tr><td class="titlest">average page load time:</td><td class="valst">';
	
	$sress = $wpdb->get_results("SELECT loadtime FROM $tname");
	$i = 0;
	$totalcount = 0;
	foreach($sress as $sres) {
		$totalcount += $sres->loadtime;
		$i++;
	}
	
	if ($i != 0) {
		$rstr .= floor($totalcount/$i);
	}
	
	$rstr .= 'ms</td><td><div class="morest"><a href="options-general.php?page=critical-site-intel-stats/visitor-tracking.php&ss=4">+</a></div></td></tr></table></div>';
	
	return $rstr;
}

## Settings populate
function vtsettings() {
	$opname = 'vtrack-settings';
	$vtsetstr = '<div><input type="radio" name="cjm" value="1" />1 Month</div><div><input type="radio" name="cjm" value="2" />2 Months</div><div><input type="radio" name="cjm" value="3" />3 Months</div><div><input type="radio" name="cjm" value="6" />6 Months</div><div><input type="radio" name="cjm" value="0" />Never</div>';
	if (get_option($opname) == null) {
	} else {
		$setval = get_option($opname);
		$valpos = strpos($vtsetstr, 'value="'.$setval.'"');
		$vtsetstr = substr($vtsetstr, 0, $valpos+9).' checked="checked"'.substr($vtsetstr, $valpos+9-strlen($vtsetstr));
	}
	return $vtsetstr;
}

## Extended Statistics Calculate
function extss($ss) {
	if ($ss == 1) {
		$qarg = "*";
	} else if ($ss == 2) {
		$qarg = "DISTINCT ip";
	} else if ($ss == 3) {
		$qarg = "date, time, laststamp";
	} else if ($ss == 4) {
		$qarg = "loadtime";
	}	
	
	global $wpdb;
	$preq = "0";
	$extarray = array();
	$tname = $wpdb->prefix."vtracker";
	$reqs = $wpdb->get_results("SELECT * FROM $tname ORDER BY request");
	foreach ($reqs as $req) {
		$rval = $req->request;
		if ($ss == 1 || $ss == 2) {
			if ($preq != $rval) {
				$reqcount = $wpdb->get_var("SELECT COUNT($qarg) FROM $tname WHERE request = '$rval'");
				$extarray[$rval] = $reqcount;
			}
			$preq = $rval;
		} else if ($ss == 3 || $ss == 4) {
			$loadt = 0;
			$ii = 0;
			$vds = $wpdb->get_results("SELECT $qarg FROM $tname WHERE request = '$rval' GROUP BY country, ip");
			foreach ($vds as $vd) {
				if ($ss == 3) {
					$loadt += timeonpage($vd->date." ".$vd->time, $vd->laststamp);
				} else {
					$loadt += $vd->loadtime;
				}
				$ii++;
			}
			if ($ss == 3) {
				$loadt = tconvert(floor($loadt/$ii));
			} else {
				$loadt = floor($loadt/$ii)."ms";
			}
			$extarray[$rval] = $loadt;
		}
	}
	
	if ($ss == 4) {
		asort($extarray);
	} else {
		arsort($extarray);
	}
	foreach ($extarray as $rcnt => $rvalue) {
		$elist .= "<tr><td>".$rvalue."</td><td>".$rcnt."</td></tr>";
		## Add countries
		$pcnt = "0";
		$cntarray = array();
		$cntrys = $wpdb->get_results("SELECT * FROM $tname WHERE request = '$rcnt' ORDER BY country");
		foreach ($cntrys as $cntry) {
			$cval = $cntry->country;
			if ($ss == 1 || $ss == 2) {
				if ($pcnt != $cval) {
					$cntcount = $wpdb->get_var("SELECT COUNT(*) FROM $tname WHERE request = '$rcnt' AND country = '$cval'");
					$cntarray[$cval] = $cntcount;
				}
			} else if ($ss == 3 || $ss == 4) {
				$loadt = 0;
				$ii = 0;
				# Unique IP entries matching request and country
				$vds = $wpdb->get_results("SELECT $qarg FROM $tname WHERE request = '$rcnt' AND country = '$cval' GROUP BY ip");
				foreach ($vds as $vd) {
					if ($ss == 3) {
						$loadt += timeonpage($vd->date." ".$vd->time, $vd->laststamp);
					} else {
						$loadt += $vd->loadtime;
					}
					$ii++;
				}
				if ($ss == 3) {
					$loadt = tconvert(floor($loadt/$ii));
				} else {
					$loadt = floor($loadt/$ii)."ms";
				}
				$cntarray[$cval] = $loadt;
			}
			$pcnt = $cval;
		}
		arsort($cntarray);
		$clist = "<tr><td colspan='2' style='padding:  0 0 10px 0; margin: 0; background-color: #F1F1F1;'><div class='csubwrap'>";
		foreach ($cntarray as $ccnt => $cvalue) {
			if ($ccnt == "") {
				$ccnt = "unknown";
			}
			if ($ss == 2) {
				$clist .= "<div class='csubgrp'>".$ccnt."</div>";
			} else {
				$clist .= "<div class='csubgrp'>".$ccnt." (".$cvalue.")</div>";
			}
		}
		$clist .= "</div></td></tr>";
		
		$elist .= $clist;
	}
	
	return $elist;
}

if (is_admin == true) {
	if ($_POST['submit_vtsettings'] == "Save Settings") {
		$opname = 'vtrack-settings';
		$setval = $_POST['cjm'];
		
		add_option($opname, $setval, ' ', 'no');
		update_option($opname, $setval);
		
		echo "<p><b>Status:</b> Settings Saved</p><hr>";
	}
	
	if ($_POST['submit_vtdelall'] == "Delete Data") {
		## Truncate table
		global $wpdb;
		$tname = $wpdb->prefix."vtracker";
		$wpdb->query("TRUNCATE TABLE $tname");
		echo "<p><b>Status:</b> All Data Deleted</p><hr>";
	}
	
	## Dashboard Display
	if ($func == 1) {
		echo '<div class="wrap"><div class="vtdash">'.countrypop().sspop().'</div></div>';
	}
	
	## Admin Page Display
	if ($func == 2) {
		if (isset($_REQUEST['ss'])) { 
			$ss = $_REQUEST['ss'];
			
			if ($ss == 1) {
				$ptitle = "Total visits";
				$thead = "<th width='70'>Visits</th><th>Request</th>";
			} else if ($ss == 2) {
				$ptitle = "Unique visits";
				$thead = "<th width='70'>Unique Visits</th><th>Request</th>";
			} else if ($ss == 3) {
				$ptitle = "Average time on page";
				$thead = "<th width='70'>Average Time</th><th>Request</th>";
			} else if ($ss == 4) {
				$ptitle = "Average page load time";
				$thead = "<th width='70'>Average Time</th><th>Request</th>";
			}
			
			echo '<div class="wrap"><h2>Critical Site Intel by Mike Leembruggen - '.$ptitle.'</h2>
			<div class="vtbacklink"><a href="options-general.php?page=critical-site-intel-stats/visitor-tracking.php">[Back to Main]</a></div>
			<div class="exttb"><table class="wp-list-table widefat plugins">'.$thead.extss($ss).'</table></div></div>';
		} else {
			echo '<div class="wrap"><h2>Critical Site Intel by Mike Leembruggen</h2>
			<div class="vtcjm"><h4>Maintenance</h4>Delete old entries every: <form method="post" action="">'.vtsettings().'
			<div style="padding: 10px 0px;"><input type="submit" class="button-primary" name="submit_vtsettings" id="submit_vtsettings" value="Save Settings"></form></div>
			
			<div style="padding-top: 20px;"><h4>Clear All Data</h4></div>
			<div style="padding: 10px 0px;"><form method="post" action=""><input type="submit" class="button-primary" name="submit_vtdelall" id="submit_vtdelall" value="Delete Data"></form></div>
			Warning: Cannot Undo!</div>
			<div class="tvid">
			<iframe width="640" height="390" src="http://www.youtube.com/embed/VDPCTaHL0n4?rel=0" frameborder="0" allowfullscreen></iframe>
			</div>
			<div class="tifrm"><div><img src="http://www.techiehelpdesk.com/images/techiehelpdesklogo.jpg"></div>
			<table cellspacing="0" cellpadding="0" border="0"><tr><td valign="top" style="padding-top: 14px;"><img src="http://www.techiehelpdesk.com/images/new_ticket_icon.jpg"></td><td valign="top">
			<h3>Open A New Ticket</h3><p>If you are looking for help or advice developing, maintaining, or fixing your website, then please feel free to click the button below to open a new ticket and tell us exactly what you are looking for. <br /> <br /> With Techiehelpdesk.com, you can rest easy knowing the experts are on the job!</p>
			</td></tr></table>
			<div style="padding-left: 45px"><input type="button" onclick=\''.'window.open("http://www.techiehelpdesk.com/open.php", "_blank")'.'\' value="Open New Ticket" style="padding: 3px 0px; width: 140px"></div>
			</div>
			<div class="clr"><h3>Statistics</h3>'.countrypop().pagepop().sspop().'</div>
			<div class="clr">
			<div class="vtbacklink"><a href="options-general.php?page=critical-site-intel-stats/visitor-tracking.php">[Reset Filtering]</a></div>
			<div class="etitle"><h3>Tracked Entries</h3></div></div>
			<div class="entries"><table class="wp-list-table widefat plugins">'.entries().'</table></div></div>';
		}
	}
}
?>