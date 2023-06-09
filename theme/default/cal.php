<?php
/**
* copyright            : (C) 2001-2013 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id$
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

if (!($FUD_OPT_3 & 134217728)) {	// Calender is disabled.
	std_error('disabled');
}

ses_update_status($usr->sid, 'Browsing the forum calendar');

$TITLE_EXTRA = ': Calendar';

/** Draw a calendar.
  * This function is called from a template to insert a calender where it's needed.
  */
function draw_calendar($year, $month, $size = 'large', $highlight_y = '', $highlight_m = '', $highlight_d = '') {
	// Full or abbreviated days.
	if ($size == 'large') {
		$weekdays = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	} else {
		$weekdays = array('S','M','Tu','W','Th','F','S');
	}
	// WEEK START ON MONDAY: $weekdays = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday', 'Sunday');

	// Get events for this month.
	$events = get_events($year, $month);

	// Table headings.
	$calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';
	$calendar .= '<tr class="calendar-row"><td class="calendar-day-head">'. implode('</td><td class="calendar-day-head">', $weekdays).'</td></tr>';
	$calendar .= '<tr class="calendar-row">';

	// Days and weeks vars.
	$running_day = date('w', mktime(0, 0, 0, $month, 1, $year));
	// WEEK START ON MONDAY: $running_day = date('w', mktime(0, 0, 0, $month, 1, $year)) - 1;
	$days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));
	$days_in_this_week = 1;
	$day_counter = 0;

	// Print "blank" days until the first of the current week.
	for($x = 0; $x < $running_day; $x++) {
		$calendar .= '<td class="calendar-day-np">&nbsp;</td>';
		$days_in_this_week++;
	}

	// Keep going with days.
	for ($day = 1; $day <= $days_in_month; $day++) {
		if ($size == 'large') {
			$calendar .= '<td class="calendar-day"><div style="position:relative; height:100px;">';
		} else {
			$calendar .= '<td class="calendar-day"><div style="position:relative;">';
		}

		// Count events so we know if we need to link to the day.
		$event_day = sprintf('%04d%02d%02d', $year, $month, $day);
		$event_count = 0;
		if (isset($events[$event_day])) {
			foreach($events[$event_day] as $event) {
				$event_count++;
			}
		}
		
		// Add in the day number.
		$calendar .= '<div class="day-number">';
		if ($year == $highlight_y && $month == $highlight_m && $day == $highlight_d) {
			$calendar .= '<b><i>*</i></b>';
		}
		if ($event_count > 0) {
			$calendar .= '<a href="/uni-ideas/index.php?t=cal&amp;view=d&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'" rel="nofollow">'. $day .'</a>';
		} else {
			$calendar .= $day;
		}
		$calendar .= '</div>';

		// Add in events.
		if (isset($events[$event_day])) {
			if ($size == 'large') {
				foreach($events[$event_day] as $event) {
					$calendar .= '<div class="event">'. $event .'</div>';
				}
			} else {
				$calendar .= str_repeat('<p>&nbsp;</p>', 2);
			}
		} else {
			$calendar .= str_repeat('<p>&nbsp;</p>', 2);
		}

		$calendar .= '</div></td>';
		if ($running_day == 6) {
			$calendar .= '</tr>';
			if (($day_counter+1) != $days_in_month) {
				$calendar .= '<tr class="calendar-row">';
			}
			$running_day = -1;
			$days_in_this_week = 0;
		};
		$days_in_this_week++; $running_day++; $day_counter++;
	};

	// Finish the rest of the days in the week.
	if($days_in_this_week < 8) {
		for($x = 1; $x <= (8 - $days_in_this_week); $x++) {
			$calendar .= '<td class="calendar-day-np">&nbsp;</td>';
		}
	}

	// Finalize and return calendar.
	$calendar .= '</tr></table>';
	return $calendar;
}

/** Fetch events and birthdays from database. */
function get_events($year, $month, $day = 0) {
	$events = array();
	
	// Defined events.
	$c = uq('SELECT event_day, descr, link FROM fud30_calendar WHERE (event_month=\''. $month .'\' AND event_year=\''. $year .'\') OR (event_month=\'*\' AND event_year=\''. $year .'\') OR (event_month=\''. $month .'\' AND event_year=\'*\') OR (event_month=\'*\' AND event_year=\'*\')');
	while ($r = db_rowarr($c)) {
		if (empty($r[2])) {
			$events[ sprintf('%04d%02d%02d', $year, $month, $r[0]) ][] = $r[1];
		} else {
			$events[ sprintf('%04d%02d%02d', $year, $month, $r[0]) ][] = '<a href="'. $r[2] .'">'. $r[1] .'</a>';
		}
	}

	// Get list of birthdays (MMDDYYYY).
	if ($GLOBALS['FUD_OPT_3'] & 268435456) {
		// Number of birthdays per day of the month.
		if ($day == 0) {
			$c = uq('SELECT substr(birthday, 3, 2), count(*) FROM fud30_users WHERE birthday LIKE '. _esc(sprintf('%02d', $month) .'%') .' GROUP BY substr(birthday, 3, 2)');
			while ($r = db_rowarr($c)) {
				$dd        = $r[0];
				$birthdays = $r[1];
				$events[ $year . $month . $dd ][] = convertPlural($birthdays, array(''.$birthdays.' birthday',''.$birthdays.' birthdays'));
			}
		} else {
			// Full list of birthdays for a specific day.
			$c = uq('SELECT id, alias, birthday FROM fud30_users WHERE birthday LIKE '. _esc(sprintf('%02d%02d', $month, $day) .'%'));
			while ($r = db_rowarr($c)) {
				$yyyy = substr($r[2], 4);
				$mm   = substr($r[2], 0, 2);
				$dd   = substr($r[2], 2, 2);
				$age  = ($yyyy > 0) ? $year - $yyyy : 0;
				$user = '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$r[0].'&amp;'._rsid.'">'.$r[1].'</a>';
				$events[ $year . $mm . $dd ][] = 'Birthday: '.$user.' '.($age ? '('.convertPlural($age, array(''.$age.' year',''.$age.' years')).' old).' : '' ) ;
			}
		}
	}

	return $events;
}

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/uni-ideas/theme/default/images/icon/chat.png" alt="" width="16" height="16" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/uni-ideas/theme/default/images/icon/chat.png" alt="" width="15" height="11" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}

// Get calendar settings.
$day    = isset($_GET['day'])   ? (int)$_GET['day']   : (int)date('d');
$month  = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year   = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$view   = isset($_GET['view'])  ? $_GET['view']  : 'm';	// Default to month view.
$months = array('January','February','March','April','May','June','July','August','September','October','November','December');
$cur_year = (int)date('Y');

// Build a 'month dropdown' that can be used in templates.
$select_month_control = '<select name="month" id="month">';
for($m = 1; $m <= 12; $m++) {
	$select_month_control .= '<option value="'. $m .'"'. ($m != $month ? '' : ' selected="selected"') .'>'. $months[ date('n', mktime(0,0,0,$m,1,$year)) - 1 ] .'</option>';
}
$select_month_control .= '</select>';

// Build a 'year dropdown' that can be used in templates.
$select_year_control = '<select name="year" id="year">';
for($x = $cur_year; $x < $cur_year+3; $x++) {
	$select_year_control .= '<option value="'. $x .'"'. ($x != $year ? '' : ' selected="selected"') .'>'. $x .'</option>';
}
$select_year_control .= '</select>';

// Navigation to next/previous days/months/years.
if ($view == 'y') {
	$next_year  = $year + 1;
	$prev_year  = $year - 1;
}

if ($view == 'm') {
	$next_year  = $month != 12 ? $year : $year + 1;
	$prev_year  = $month !=  1 ? $year : $year - 1;
	$next_month = $month != 12 ? $month + 1 : 1;
	$prev_month = $month !=  1 ? $month - 1 : 12;
}

if ($view == 'd') {
	$tomorrow  = mktime(0, 0, 0, $month, $day+1, $year);
	$yesterday = mktime(0, 0, 0, $month, $day-1, $year);
	
	$next_day   = date('d', $tomorrow);
	$prev_day   = date('d', $yesterday);
	$next_month = date('m', $tomorrow);
	$prev_month = date('m', $yesterday);
	$next_year  = date('Y', $tomorrow);
	$prev_year  = date('Y', $yesterday);

	$events = get_events($year, $month, $day);

	$event_day = sprintf('%04d%02d%02d', $year, $month, $day);
	$events_for_day = '';
	if (isset($events[$event_day])) {
		foreach($events[$event_day] as $event) {
			$events_for_day .= '<li><div class="event">'.$event.'</div></li>';
		}
	}
}

// Limit calendar to current year and 3 years in future.
// This is required to prevent bots from seeing an infinite number of pages.
if ($next_year >= $cur_year+3) $next_year = $next_month = $next_day = null;
if ($prev_year < $cur_year)    $prev_year = $prev_month = $prev_day = null;

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>' : '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>';
} else {
	$page_stats = '';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="<?php echo (!empty($META_DESCR) ? $META_DESCR.'' : $GLOBALS['FORUM_DESCR'].''); ?>" />
	<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
	<link rel="search" type="application/opensearchdescription+xml" title="<?php echo $GLOBALS['FORUM_TITLE']; ?> Search" href="/uni-ideas/open_search.php" />
	<?php echo $RSS; ?>
	<link rel="stylesheet" href="/uni-ideas/theme/default/forum.css" media="screen" title="Default Forum Theme" />
	<link rel="stylesheet" href="/uni-ideas/js/ui/jquery-ui.css" media="screen" />
	<script src="/uni-ideas/js/jquery.js"></script>
	<script async src="/uni-ideas/js/ui/jquery-ui.js"></script>
	<script src="/uni-ideas/js/lib.js"></script>
	<link rel="stylesheet" href="/UNI-Ideas/theme/default/style.css">
	<link rel="icon" type="image" href="/uni-ideas/theme/default/images/faviconx.png"/>
		<style>
		*{
			font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif
		}
		.nav {
			list-style-type: none;
			overflow: hidden;
			background-color: #ffffff;
			height: 54px;
		}

		.nav :hover{
			border-bottom: 2px solid #fa4d1d;
		}

		.menu {
			float: left;
		}

		.menu a {
			display: block;
			color: black;
			text-align: center;
			padding: 14px 16px;
			text-decoration: none;
			font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
			font-weight: bold;
			font-size: 15px;
			text-transform: uppercase;
		}

		.menu a:hover {
			background-color: #ffffff;
			color: #fa4d1d ;
		}
		.search_input{
			color: #ffffff;
			border-bottom: 2px solid #fa4d1d;
			border-color: none;
			background: none;
			border-radius: 15px;
			width: 270px;
			height: 30px;
		}

		.content{
			background-color: #ffffff;
			border-radius: 0px;
			padding: 20px;
			margin: 0px;	
			margin-top: 2px;
		}
		.tr{
			color: #fa4d1d;
		}
		
		.wa {
			background-color: while;
		}

		.SmallText{
			color: white;
		}
		.footer{
			background-color: #0F2026;
			border-radius: 0%;
			color: #ffffff;
		}
		.footer a{
			text-decoration: none;
			font-weight: bold ;
			color: #fa4d1d;
		}
		.logo_foot{
			display: flex;
			justify-content: center;
		}
		.logo_foot span{
			font-weight: bold;
			font-size: 20px;
			
		}
		.logo_foot img{
	
			height: 30px;
		}
	</style>
</head>
<body style="background-color: #ffffff;">
<!--HEADER-->
<div class="header" style="background-color: #0F2026; border: none;">

 
  <a href="/uni-ideas/" title="Home">
    <img class="headimg" style="margin: 7px 0;" src="/uni-ideas/theme/default/images/logomain.png" alt="" align="left" height="95"/>
    <span class="headtitle" style="margin: 30px 0;font-size: 40px;"><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
  </a><br />
  <span class="headdescr" style="font-size: 15px;"><?php echo $GLOBALS['FORUM_DESCR']; ?><br /><br /></span>
</div>

<!--Nav bar-->
<div>
	<div class="nav">
		
		<?php echo ($FUD_OPT_4 & 16 ? '<div class="menu"><a href="/uni-ideas/index.php?t=blog&amp;'._rsid.'" title="Blog"><img src="/uni-ideas/theme/default/images/blog.png" alt="" width="16" height="16" /> Blog</a></div>' : ''); ?>
		<?php echo ($FUD_OPT_4 & 8 ? '<div class="menu"><a href="/uni-ideas/index.php?t=page&amp;'._rsid.'" title="Pages"><img src="/uni-ideas/theme/default/images/pages.png" alt="" width="16" height="16" /> Pages</a></div>' : ''); ?>
		<?php echo ($FUD_OPT_3 & 134217728 ? '<div class="menu"><a href="/uni-ideas/index.php?t=cal&amp;'._rsid.'" title="Calendar"><img src="/uni-ideas/theme/default/images/calendar.png" alt="" width="16" height="16" /> Calendar</a></div>' : ''); ?>
		<div class="menu"><a href="/uni-ideas/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img src="/uni-ideas/theme/default/images/icon/home.png" alt="" width="16" height="16" /> Home</a></div>

		<?php echo ($FUD_OPT_1 & 16777216 ? ' <div class="menu"><a href="/uni-ideas/index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Search"><img src="/uni-ideas/theme/default/images/icon/magnifier.png" alt="" width="16" height="16" /> Search</a></div>' : ''); ?>
		<div class="menu"><a accesskey="h" href="/uni-ideas/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img src="/uni-ideas/theme/default/images/icon/help-web-button.png" alt="" width="16" height="16" /> Help</a></div>
		<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<div class="menu"><a href="/uni-ideas/index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Members"><img src="/uni-ideas/theme/default/images/icon/group.png" alt="" width="16" height="16" /> Members</a></div>' : ''); ?>
		<div class="menu"><?php echo $ucp_private_msg; ?></div>
		
		<?php echo (__fud_real_user__ ? '<div class="menu"><a href="/uni-ideas/index.php?t=uc&amp;'._rsid.'" title="Access the user control panel"><img src="/uni-ideas/theme/default/images/icon/home.png" alt="" width="16" height="16" /> Control Panel</a></div>' : ($FUD_OPT_1 & 2 ? '<div class="menu"><a href="/uni-ideas/index.php?t=register&amp;'._rsid.'" title="Register"><img src="/uni-ideas/theme/default/images/icon/new-user.png" alt="" width="16" height="18" /> Register</a></div>' : '')).'
		'.(__fud_real_user__ ? '<div class="menu"><a href="/uni-ideas/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Logout"><img src="/uni-ideas/theme/default/images/icon/profile-user.png" alt="" width="16" height="16" /> Logout [ '.filter_var($usr->alias, FILTER_SANITIZE_STRING).' ]</a></div>' : '<div class="menu"><a href="/uni-ideas/index.php?t=login&amp;'._rsid.'" title="Login"><img src="/uni-ideas/theme/default/images/icon/profile-user.png" alt="" width="16" height="16" /> Login</a></div>'); ?>
		<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<div class="menu"><a href="/uni-ideas/adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Administration"><img src="/uni-ideas/theme/default/images/icon/configuration.png" alt="" width="16" height="16" /> Administration</a></div>' : ''); ?>
	</ul>
</div>
<table cellspacing="1" cellpadding="2" class="ContentTable">
<?php echo ($view == 'y' ? '
<tr>
	<th colspan="3">
		<h2>
		'.($prev_year ? '&nbsp;<a href="/uni-ideas/index.php?t=cal&amp;view=y&amp;year='.$prev_year.'" class="control" rel="nofollow">&laquo;</a>' : '' )  .'
		&nbsp;'.$year.'&nbsp;
		'.($next_year ? '<a href="/uni-ideas/index.php?t=cal&amp;view=y&amp;year='.$next_year.'" class="control" rel="nofollow">&raquo;</a>' : '' )  .'
		</h2>
	</th>
</tr>
<tr>
	<td width="33%" class="vt"><h4>'.$months[0].' '.$year.'</h4>'.draw_calendar($year, 1, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[1].' '.$year.'</h4>'.draw_calendar($year, 2, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[2].' '.$year.'</h4>'.draw_calendar($year, 3, 'small', $year, $month, $day).'</td>
</tr>
<tr>
	<td width="33%" class="vt"><h4>'.$months[3].' '.$year.'</h4>'.draw_calendar($year, 4, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[4].' '.$year.'</h4>'.draw_calendar($year, 5, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[5].' '.$year.'</h4>'.draw_calendar($year, 6, 'small', $year, $month, $day).'</td>
</tr>
<tr>
	<td width="33%" class="vt"><h4>'.$months[6].' '.$year.'</h4>'.draw_calendar($year, 7, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[7].' '.$year.'</h4>'.draw_calendar($year, 8, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[8].' '.$year.'</h4>'.draw_calendar($year, 9, 'small', $year, $month, $day).'</td>
</tr>
<tr>
	<td width="33%" class="vt"><h4>'.$months[9].'  '.$year.'</h4>'.draw_calendar($year, 10, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[10].' '.$year.'</h4>'.draw_calendar($year, 11, 'small', $year, $month, $day).'</td>
	<td width="33%" class="vt"><h4>'.$months[11].' '.$year.'</h4>'.draw_calendar($year, 12, 'small', $year, $month, $day).'</td>
</tr>
' : ''); ?>

<?php echo ($view == 'm' ? '
<tr>
	<th width="35%" class="al">
		'.($prev_month ? '<a href="/uni-ideas/index.php?t=cal&amp;view=m&amp;year='.$prev_year.'&amp;month='.$prev_month.'" class="control" rel="nofollow">&laquo;</a>' : '' )  .'
	</th>
	<th class="ac">
		<h2>'.$months[$month-1].' <a href="/uni-ideas/index.php?t=cal&amp;view=y&amp;year='.$year.'" class="control" rel="nofollow">'.$year.'</a></h2>
	</th>
	<th width="35%" class="ar">
		'.($next_month ? '<a href="/uni-ideas/index.php?t=cal&amp;view=m&amp;year='.$next_year.'&amp;month='.$next_month.'" class="control" rel="nofollow">&raquo;</a>' : '' )  .'
	</th>
</tr>
<tr class="ac">
	<td colspan="3">
		'.draw_calendar($year, $month, 'large', $year, $month, $day).'
	</td>
</tr>
<tr>
	<td class="ac" colspan="3">
		<form method="get" action="index.php">
		<b>Jump to:</b><input type="hidden" name="t" value="cal" />
		<br />'.$select_month_control.' '.$select_year_control.' <input type="submit" name="submit" value="Go" />
		</form>
	</td>
</tr>
' : ''); ?>

<?php echo ($view == 'd' ? '
<tr>
	<th colspan="2">
		<h2>
		'.($prev_day ? '<a href="/uni-ideas/index.php?t=cal&amp;view=d&amp;year='.$prev_year.'&amp;month='.$prev_month.'&amp;day='.$prev_day.'" class="control" rel="nofollow">&laquo;</a>' : '' )  .'
		'.$day.' <a href="/uni-ideas/index.php?t=cal&amp;view=m&amp;month='.$month.'&amp;year='.$year.'"class="control" rel="nofollow">'.$months[$month-1].'</a> <a href="/uni-ideas/index.php?t=cal&amp;view=y&amp;year='.$year.'" class="control" rel="nofollow">'.$year.'</a>
		'.($next_day ? '<a href="/uni-ideas/index.php?t=cal&amp;view=d&amp;year='.$next_year.'&amp;month='.$next_month.'&amp;day='.$next_day.'" class="control" rel="nofollow">&raquo;</a>' : '' )  .'
		</h2>
	</th>
</tr>
<tr>
	<td class="RowStyleB vt" width="55%">
		<h3>Events for day</h3>
		'.($events_for_day ? '<ul>'.$events_for_day.'</ul>' : '<p>No events for day.</p>' )  .'
		<br /><br />
		<form method="get" action="index.php">
		Jump to: <input type="hidden" name="t" value="cal" /><input type="hidden" name="view" value="'.$view.'" />
		'.$select_month_control.' '.$select_year_control.' 
		<input type="hidden" name="day" value="'.$day.'" /><input type="submit" name="submit" value="Go" />
		</form>
	</td>
	<td class="ac" width="45%"> 
		<h4><a href="/uni-ideas/index.php?t=cal&amp;view=m&amp;month='.$month.'&amp;year='.$year.'" class="control">'.$months[$month-1].' '.$year.'</a></h4>
		'.draw_calendar($year, $month, 'small', $year, $month, $day).'
	</td>
</tr>
' : ''); ?>

</table>
<br />  
<?php echo $page_stats; ?>
<?php echo (!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : ''); ?>
</td></tr></table>

</div>
  <!-- Footer -->
</div>
<div class="footer ac">
	<div class="logo_foot">
		<img  src="/uni-ideas/theme/default/images/logomain.png" alt="" />
		<span><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
	</div>
	<a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
	<b> | </b>
	<a href="/uni-ideas/index.php?t=index&amp;<?php echo _rsid; ?>">Home page <img src="/uni-ideas/theme/default/images/icon/homefooter.png"/></a>
	<p class="SmallText">Powered by: Mây Trắng Groups<br />Copyright &copy;2023 <a href="https://github.com/zolmkoz/UNI-Ideas">UNI-Ideas</a></p>
</div>

</body></html>
