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
	}function ignore_add($user_id, $ignore_id)
{
	q('INSERT INTO fud30_user_ignore (ignore_id, user_id) VALUES ('. $ignore_id .', '. $user_id .')');
	q('DELETE FROM fud30_buddy WHERE user_id='. $ignore_id .' AND bud_id='. $user_id);
	if (db_affected()) {
		fud_use('buddy.inc');
		buddy_rebuild_cache($ignore_id);
	}

	return ignore_rebuild_cache($user_id);
}

function ignore_delete($user_id, $ignore_id)
{
	q('DELETE FROM fud30_user_ignore WHERE user_id='. $user_id .' AND ignore_id='. $ignore_id);
	return ignore_rebuild_cache($user_id);
}

function ignore_rebuild_cache($uid)
{
	$arr = array();
	$q = uq('SELECT ignore_id FROM fud30_user_ignore WHERE user_id='. $uid);
	while ($ent = db_rowarr($q)) {
		$arr[$ent[0]] = 1;
	}
	unset($q);

	if ($arr) {
		q('UPDATE fud30_users SET ignore_list='. _esc(serialize($arr)) .' WHERE id='. $uid);
		return $arr;
	}
	q('UPDATE fud30_users SET ignore_list=NULL WHERE id='. $uid);
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

function ignore_alias_fetch($al, &$is_mod)
{
	if (!($tmp = db_saq('SELECT id, '. q_bitand('users_opt', 1048576) .' FROM fud30_users WHERE alias='. _esc(char_fix(htmlspecialchars($al)))))) {
		return;
	}
	$is_mod = $tmp[1];

	return $tmp[0];
}

	if (isset($_POST['add_login']) && is_string($_POST['add_login'])) {
		if (!($ignore_id = ignore_alias_fetch($_POST['add_login'], $is_mod))) {
			error_dialog('User not found', 'The user you tried to add to your ignore list was not found.');
		}
		if ($is_mod) {
			error_dialog('Info', 'You cannot ignore this user');
		}
		if (!empty($usr->ignore_list)) {
			$usr->ignore_list = unserialize($usr->ignore_list);
		}
		if (!isset($usr->ignore_list[$ignore_id])) {
			ignore_add(_uid, $ignore_id);
		} else {
			error_dialog('Info', 'You already have this user on your ignore list');
		}
	}

	/* Incomming from message display page (ignore link). */
	if (isset($_GET['add']) && ($_GET['add'] = (int)$_GET['add'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		if (!empty($usr->ignore_list)) {
			$usr->ignore_list = unserialize($usr->ignore_list);
		}

		if (($ignore_id = q_singleval('SELECT id FROM fud30_users WHERE id='. $_GET['add'] .' AND '. q_bitand('users_opt', 1048576) .'=0')) && !isset($usr->ignore_list[$ignore_id])) {
			ignore_add(_uid, $ignore_id);
		}
		check_return($usr->returnto);
	}

	/* Anon user hack. */
	if (isset($_GET['del']) && $_GET['del'] === '0') {
		$_GET['del'] = 1;
	}

	if (isset($_GET['del']) && ($_GET['del'] = (int)$_GET['del'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		ignore_delete(_uid, $_GET['del']);
		/* Needed for external links to this form. */
		if (isset($_GET['redr'])) {
			check_return($usr->returnto);
		}
	}

	ses_update_status($usr->sid, 'Browsing own ignore list');

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

	$c = uq('SELECT ui.ignore_id, ui.id as ignoreent_id,
			u.id, u.alias AS login, u.join_date, u.posted_msg_count, u.home_page
		FROM fud30_user_ignore ui
		LEFT JOIN fud30_users u ON ui.ignore_id=u.id
		WHERE ui.user_id='. _uid);

	$ignore_list = '';
	if (($r = db_rowarr($c))) {
		do {
			$ignore_list .= $r[0] ? '<tr class="'.alt_var('ignore_alt','RowStyleA','RowStyleB').'">
	<td class="GenText wa"><a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$r[2].'&amp;'._rsid.'">'.$r[3].'</a>&nbsp;<span class="SmallText">(<a href="/uni-ideas/index.php?t=ignore_list&amp;del='.$r[0].'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">remove</a>)</span></td>
	<td class="ac">'.$r[5].'</td>
	<td class="ac nw">'.utf8_encode(strftime('%a, %d %B %Y %H:%M', $r[4])).'</td>
	<td class="GenText nw"><a href="/uni-ideas/index.php?t=showposts&amp;'._rsid.'&amp;id='.$r[2].'"><img src="/uni-ideas/theme/default/images/show_posts.gif" alt="" /></a> '.($FUD_OPT_2 & 1073741824 ? '<a href="/uni-ideas/index.php?t=email&amp;toi='.$r[2].'&amp;'._rsid.'" rel="nofollow"><img src="/uni-ideas/theme/default/images/msg_email.gif" alt="" /></a>' : '' ) .($r[6] ? '<a href="'.$r[6].'"><img src="/uni-ideas/theme/default/images/homepage.gif" alt="" /></a>' : '' ) .'</td>
</tr>' : '<tr class="'.alt_var('ignore_alt','RowStyleA','RowStyleB').'">
	<td colspan="4" class="wa GenText"><span class="anon">'.$GLOBALS['ANON_NICK'].'</span>&nbsp;<span class="SmallText">(<a href="/uni-ideas/index.php?t=ignore_list&amp;del='.$r[1].'&amp;'._rsid.'">remove</a>)</span></td>
</tr>';
		} while (($r = db_rowarr($c)));
		$ignore_list = '<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th>Ignored Users</th>
	<th class="nw ac">Message Count</th>
	<th class="nw ac">Registered on</th>
	<th class="nw ac">Action</th>
</tr>
'.$ignore_list.'
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
<?php echo $ignore_list; ?>
<br /><br />
<form id="buddy_add" action="/uni-ideas/index.php?t=ignore_list" method="post"><?php echo _hs; ?><div class="ctb">
<table cellspacing="1" cellpadding="2" class="MiniTable">
<tr>
	<th class="nw">Add Ignoramus</th>
</tr>
<tr class="RowStyleA">
	<td class="GenText nw Smalltext">
		Enter the login of the user you wish to add.<?php echo (($FUD_OPT_1 & (8388608|4194304)) ? '<br />Or use the <a href="javascript://" onclick="javascript: window_open(&#39;/uni-ideas/index.php?t=pmuserloc&amp;'._rsid.'&amp;js_redr=buddy_add.add_login&amp;overwrite=1&#39;, &#39;user_list&#39;, 400,250);">Find User</a> feature to find a person.' : ''); ?><br /><br />
		<input type="text" name="add_login" id="add_login" tabindex="1" value="" maxlength="100" size="25" />
		<input tabindex="2" type="submit" class="button" name="submit" value="Add" />
	</td>
</tr>
</table></div></form>
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
	<div class="logo_foot">
		<img  src="/uni-ideas/theme/default/images/logomain.png" alt="" />
		<span><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
	</div>
	<hr />

	
	<a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
	<b>|</b>
	<a href="/uni-ideas/index.php?t=index&amp;<?php echo _rsid; ?>">Home</a>
	
	<p class="SmallText">Powered by: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br />Copyright &copy;2001-2022 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>
