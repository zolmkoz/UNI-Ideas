<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id$
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function buddy_add($user_id, $bud_id)
{
	q('INSERT INTO fud30_buddy (bud_id, user_id) VALUES ('. $bud_id .', '. $user_id .')');
	return buddy_rebuild_cache($user_id);
}

function buddy_delete($user_id, $bud_id)
{
	q('DELETE FROM fud30_buddy WHERE user_id='. $user_id .' AND bud_id='. $bud_id);
	return buddy_rebuild_cache($user_id);
}

function buddy_rebuild_cache($uid)
{
	$arr = array();
	$q = uq('SELECT bud_id FROM fud30_buddy WHERE user_id='. $uid);
	while ($ent = db_rowarr($q)) {
		$arr[$ent[0]] = 1;
	}
	unset($q);

	if ($arr) {
		q('UPDATE fud30_users SET buddy_list='. _esc(serialize($arr)) .' WHERE id='. $uid);
		return $arr;
	}
	q('UPDATE fud30_users SET buddy_list=NULL WHERE id='. $uid);
}function check_return($returnto)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
		if (!$returnto || !strncmp($returnto, '/er/', 4)) {
			header('Location: /uni-ideas/index.php/i/'. _rsidl);
		} else if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
			header('Location: /uni-ideas/index.php'. $returnto);
		} else {
			header('Location: /uni-ideas/index.php?'. $returnto);
		}
	} else if (!$returnto || !strncmp($returnto, 't=error', 7)) {
		header('Location: /uni-ideas/index.php?t=index&'. _rsidl);
	} else if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
		header('Location: /uni-ideas/index.php?'. $returnto .'&S='. s);
	} else {
		header('Location: /uni-ideas/index.php?'. $returnto);
	}
	exit;
}function alt_var($key)
{
	if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
		$args = func_get_args(); unset($args[0]);
		$GLOBALS['_ALTERNATOR_'][$key] = array('p' => 2, 't' => func_num_args(), 'v' => $args);
		return $args[1];
	}
	$k =& $GLOBALS['_ALTERNATOR_'][$key];
	if ($k['p'] == $k['t']) {
		$k['p'] = 1;
	}
	return $k['v'][$k['p']++];
}

	if (!_uid) {
		std_error('login');
	}

	if (isset($_POST['add_login']) && is_string($_POST['add_login'])) {
		if (!($buddy_id = q_singleval('SELECT id FROM fud30_users WHERE alias='. _esc(char_fix(htmlspecialchars($_POST['add_login'])))))) {
			error_dialog('Unable to add user', 'The user you tried to add to your buddy list was not found.');
		}
		if ($buddy_id == _uid) {
			error_dialog('Info', 'You cannot add yourself to your buddy list');
		}
		if (q_singleval('SELECT id FROM fud30_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			error_dialog('Info', 'Cannot add users who ignore you to your buddy list.');
		}

		if (!empty($usr->buddy_list)) {
			$usr->buddy_list = unserialize($usr->buddy_list);
		}

		if (!isset($usr->buddy_list[$buddy_id]) && !q_singleval('SELECT id FROM fud30_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			$usr->buddy_list = buddy_add(_uid, $buddy_id);
		} else {
			error_dialog('Info', 'You already have this user on your buddy list');
		}
	}

	/* incomming from message display page (add buddy link) */
	if (isset($_GET['add']) && ($_GET['add'] = (int)$_GET['add'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		if (!empty($usr->buddy_list)) {
			$usr->buddy_list = unserialize($usr->buddy_list);
		}

		if (($buddy_id = q_singleval('SELECT id FROM fud30_users WHERE id='. $_GET['add'])) && !isset($usr->buddy_list[$buddy_id]) && _uid != $buddy_id && !q_singleval('SELECT id FROM fud30_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			buddy_add(_uid, $buddy_id);
		}
		check_return($usr->returnto);
	}

	if (isset($_GET['del']) && ($_GET['del'] = (int)$_GET['del'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		buddy_delete(_uid, $_GET['del']);
		/* needed for external links to this form */
		if (isset($_GET['redr'])) {
			check_return($usr->returnto);
		}
	}

	ses_update_status($usr->sid, 'Browsing own buddy list');

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/uni-ideas/theme/default/images/icon/chat.png" alt="" width="16" height="16" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/uni-ideas/theme/default/images/icon/chat.png" alt="" width="15" height="11" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}$tabs = '';
if (_uid) {
	$tablist = array(
'Notifications'=>'uc',
'Account Settings'=>'register',
'Subscriptions'=>'subscribed',
'Bookmarks'=>'bookmarked',
'Referrals'=>'referals',
'Buddy List'=>'buddy_list',
'Ignore List'=>'ignore_list',
'Show Own Posts'=>'showposts'
);

	if (!($FUD_OPT_2 & 8192)) {
		unset($tablist['Referrals']);
	}

	if (isset($_POST['mod_id'])) {
		$mod_id_chk = $_POST['mod_id'];
	} else if (isset($_GET['mod_id'])) {
		$mod_id_chk = $_GET['mod_id'];
	} else {
		$mod_id_chk = null;
	}

	if (!$mod_id_chk) {
		if ($FUD_OPT_1 & 1024) {
			$tablist['Private Messaging'] = 'pmsg';
		}
		$pg = ($_GET['t'] == 'pmsg_view' || $_GET['t'] == 'ppost') ? 'pmsg' : $_GET['t'];

		foreach($tablist as $tab_name => $tab) {
			$tab_url = '/uni-ideas/index.php?t='. $tab . (s ? '&amp;S='. s : '');
			if ($tab == 'referals') {
				if (!($FUD_OPT_2 & 8192)) {
					continue;
				}
				$tab_url .= '&amp;id='. _uid;
			} else if ($tab == 'showposts') {
				$tab_url .= '&amp;id='. _uid;
			}
			$tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="'.$tab_url.'">'.$tab_name.'</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="'.$tab_url.'">'.$tab_name.'</a></div></td>';
		}

		$tabs = '<table cellspacing="1" cellpadding="0" class="tab">
<tr>
	'.$tabs.'
</tr>
</table>';
	}
}

	$c = uq('SELECT b.bud_id, u.id, u.alias, u.join_date, u.birthday, '. q_bitand('u.users_opt', 32768) .', u.posted_msg_count, u.home_page, u.last_visit AS time_sec
		FROM fud30_buddy b INNER JOIN fud30_users u ON b.bud_id=u.id WHERE b.user_id='. _uid);

	$buddies = '';
	/* Result index
	 * 0 - bud_id	1 - user_id	2 - login	3 - join_date	4 - birthday	5 - users_opt	6 - msg_count
	 * 7 - home_page	8 - last_visit
	 */

	if (($r = db_rowarr($c))) {
		$dt = getdate(__request_timestamp__);
		$md = sprintf('%02d%02d', $dt['mon'], $dt['mday']);

		do {
			if ((!($r[5] & 32768) && $FUD_OPT_2 & 32) || $is_a) {
				$online_status = (($r[8] + $LOGEDIN_TIMEOUT * 60) > __request_timestamp__) ? '<img src="/uni-ideas/theme/default/images/online.png" title="'.$r[2].' is currently online" alt="'.$r[2].' is currently online" />' : '<img src="/uni-ideas/theme/default/images/offline.png" title="'.$r[2].' is currently offline" alt="'.$r[2].' is currently offline" />';
			} else {
				$online_status = '';
			}

			if ($r[4] && substr($r[4], 0, 4) == $md) {
				$age = $dt['year'] - (int)substr($r[4], 4);
				$bday_indicator = '<img src="/uni-ideas/blank.gif" alt="" width="10" height="1" /><img src="/uni-ideas/theme/default/images/bday.gif" alt="" />Today '.$r[2].' turns '.$age;
			} else {
				$bday_indicator = '';
			}

			$buddies .= '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="ac">'.$online_status.'</td>
	<td class="GenText wa">
		'.($FUD_OPT_1 & 1024 ? '<a href="/uni-ideas/index.php?t=ppost&amp;'._rsid.'&amp;toi='.urlencode($r[0]).'">'.$r[2].'</a>' : '<a href="/uni-ideas/index.php?t=email&amp;toi='.$r[1].'&amp;'._rsid.'" rel="nofollow">'.$r[2].'</a>' ) .'&nbsp;
		<span class="SmallText">(<a href="/uni-ideas/index.php?t=buddy_list&amp;'._rsid.'&amp;del='.$r[0].'&amp;SQ='.$GLOBALS['sq'].'">remove</a>)</span>&nbsp;
		'.$bday_indicator.'
	</td>
	<td class="ac">'.$r[6].'</td>
	<td class="ac nw">'.utf8_encode(strftime('%a, %d %B %Y %H:%M', $r[3])).'</td>
	<td class="GenText nw">
		<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$r[1].'&amp;'._rsid.'"><img src="/uni-ideas/theme/default/images/msg_about.gif" alt="" /></a>&nbsp;
		<a href="/uni-ideas/index.php?t=showposts&amp;'._rsid.'&amp;id='.$r[1].'"><img src="/uni-ideas/theme/default/images/show_posts.gif" alt="" /></a>
		'.($r[7] ? '<a href="'.$r[7].'"><img src="/uni-ideas/theme/default/images/homepage.gif" alt="" /></a>' : '' ) .'
	</td>
</tr>';
		} while (($r = db_rowarr($c)));
		$buddies = '<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th>Status</th>
	<th>My Buddies</th>
	<th class="nw ac">Message Count</th>
	<th class="ac nw">Registered on</th>
	<th class="ac nw">Action</th>
</tr>
'.$buddies.'
</table>';
	}
	unset($c);

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
	</style>
</head>
<body style="background-color: #0F2026;">
<!--HEADER-->
<div class="header" style="background-color: #0F2026; border: none;">

  <?php echo ($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
		<div class="headsearch">
		<form id="headsearch" method="get" action="/uni-ideas/index.php">'._hs.'
		<input type="hidden" name="t" value="search" />
		<br>
		<br>
		<input class = "search_input" type="search" name="srch" value="" size="50" placeholder="Forum Search" /></label>
		<input type="image" src="/uni-ideas/theme/default/images/search.png" title="Search" name="btn_submit">&nbsp;
		</form>
		</div>
  ' : ''); ?>
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
<?php echo $tabs; ?>
<?php echo $buddies; ?>
<br /><br />
<form id="buddy_add" action="/uni-ideas/index.php?t=buddy_list" method="post"><?php echo _hs; ?><div class="ctb">
<table cellspacing="1" cellpadding="2" class="MiniTable">
<tr>
	<th class="nw">Add Buddy</th>
</tr>
<tr class="RowStyleA">
	<td class="GenText nw Smalltext">
		Enter the login of the user you wish to add.
		<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304)) ? '<br />Or use the <a href="javascript://" onclick="javascript: window_open(&#39;/uni-ideas/index.php?t=pmuserloc&amp;'._rsid.'&amp;js_redr=buddy_add.add_login&amp;overwrite=1&#39;, &#39;user_list&#39;, 400,250);">Find User</a> feature to find a person.' : ''); ?>
		<br /><br />
		<input type="text" tabindex="1" name="add_login" id="add_login" value="" maxlength="100" size="25" />
		<input tabindex="2" type="submit" class="button" name="submit" value="Add" />
	</td>
</tr>
</table>
</div></form>
<br /><div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo utf8_encode(strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__)); ?></span></div>
<?php echo $page_stats; ?>
<script>
	document.forms['buddy_add'].add_login.focus();
</script>

<style>
	.ui-autocomplete-loading { background: white url("/uni-ideas/theme/default/images/ajax-loader.gif") right center no-repeat; }
</style>
<script>
	jQuery(function() {
		jQuery("#add_login").autocomplete({
			source: "index.php?t=autocomplete&lookup=alias", minLength: 1
		});
	});
</script>
<?php echo (!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : ''); ?>
</td></tr></table>

</div>
<div class="footer ac">
	<b>.::</b>
	<a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
	<b>::</b>
	<a href="/uni-ideas/index.php?t=index&amp;<?php echo _rsid; ?>">Home</a>
	<b>::.</b>
	<p class="SmallText">Powered by: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br />Copyright &copy;2001-2022 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>
