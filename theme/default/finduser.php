<?php
/**
* copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id$
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function pager_replace(&$str, $s, $c)
{
	$str = str_replace(array('%s', '%c'), array($s, $c), $str);
}

function tmpl_create_pager($start, $count, $total, $arg, $suf='', $append=1, $js_pager=0, $no_append=0)
{
	if (!$count) {
		$count =& $GLOBALS['POSTS_PER_PAGE'];
	}
	if ($total <= $count) {
		return;
	}

	$upfx = '';
	if ($GLOBALS['FUD_OPT_2'] & 32768 && (!empty($_SERVER['PATH_INFO']) || strpos($arg, '?') === false)) {
		if (!$suf) {
			$suf = '/';
		} else if (strpos($suf, '//') !== false) {
			$suf = preg_replace('!/+!', '/', $suf);
		}
	} else if (!$no_append) {
		$upfx = '&amp;start=';
	}

	$cur_pg = ceil($start / $count);
	$ttl_pg = ceil($total / $count);

	$page_pager_data = '';

	if (($page_start = $start - $count) > -1) {
		if ($append) {
			$page_first_url = $arg . $upfx . $suf;
			$page_prev_url = $arg . $upfx . $page_start . $suf;
		} else {
			$page_first_url = $page_prev_url = $arg;
			pager_replace($page_first_url, 0, $count);
			pager_replace($page_prev_url, $page_start, $count);
		}

		$page_pager_data .= !$js_pager ? '&nbsp;<a href="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="'.$page_prev_url.'" accesskey="p" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;' : '&nbsp;<a href="javascript://" onclick="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_prev_url.'" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;';
	}

	$mid = ceil($GLOBALS['GENERAL_PAGER_COUNT'] / 2);

	if ($ttl_pg > $GLOBALS['GENERAL_PAGER_COUNT']) {
		if (($mid + $cur_pg) >= $ttl_pg) {
			$end = $ttl_pg;
			$mid += $mid + $cur_pg - $ttl_pg;
			$st = $cur_pg - $mid;
		} else if (($cur_pg - $mid) <= 0) {
			$st = 0;
			$mid += $mid - $cur_pg;
			$end = $mid + $cur_pg;
		} else {
			$st = $cur_pg - $mid;
			$end = $mid + $cur_pg;
		}

		if ($st < 0) {
			$start = 0;
		}
		if ($end > $ttl_pg) {
			$end = $ttl_pg;
		}
		if ($end - $start > $GLOBALS['GENERAL_PAGER_COUNT']) {
			$end = $start + $GLOBALS['GENERAL_PAGER_COUNT'];
		}
	} else {
		$end = $ttl_pg;
		$st = 0;
	}

	while ($st < $end) {
		if ($st != $cur_pg) {
			$page_start = $st * $count;
			if ($append) {
				$page_page_url = $arg . $upfx . $page_start . $suf;
			} else {
				$page_page_url = $arg;
				pager_replace($page_page_url, $page_start, $count);
			}
			$st++;
			$page_pager_data .= !$js_pager ? '<a href="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;' : '<a href="javascript://" onclick="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;';
		} else {
			$st++;
			$page_pager_data .= !$js_pager ? $st.'&nbsp;&nbsp;' : $st.'&nbsp;&nbsp;';
		}
	}

	$page_pager_data = substr($page_pager_data, 0 , strlen((!$js_pager ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;')) * -1);

	if (($page_start = $start + $count) < $total) {
		$page_start_2 = ($st - 1) * $count;
		if ($append) {
			$page_next_url = $arg . $upfx . $page_start . $suf;
			// $page_last_url = $arg . $upfx . $page_start_2 . $suf;
			$page_last_url = $arg . $upfx . floor($total-1/$count)*$count . $suf;
		} else {
			$page_next_url = $page_last_url = $arg;
			pager_replace($page_next_url, $upfx . $page_start, $count);
			pager_replace($page_last_url, $upfx . $page_start_2, $count);
		}
		$page_pager_data .= !$js_pager ? '&nbsp;&nbsp;<a href="'.$page_next_url.'" accesskey="n" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="'.$page_last_url.'" class="PagerLink">&raquo;</a>' : '&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_next_url.'" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_last_url.'" class="PagerLink">&raquo;</a>';
	}

	return !$js_pager ? '<span class="SmallText fb">Pages ('.$ttl_pg.'): ['.$page_pager_data.']</span>' : '<span class="SmallText fb">Pages ('.$ttl_pg.'): ['.$page_pager_data.']</span>';
}$GLOBALS['__revfs'] = array('&quot;', '&lt;', '&gt;', '&amp;');
$GLOBALS['__revfd'] = array('"', '<', '>', '&');

function reverse_fmt($data)
{
	$s = $d = array();
	foreach ($GLOBALS['__revfs'] as $k => $v) {
		if (strpos($data, $v) !== false) {
			$s[] = $v;
			$d[] = $GLOBALS['__revfd'][$k];
		}
	}

	return $s ? str_replace($s, $d, $data) : $data;
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
}function draw_user_link($login, $type, $custom_color='')
{
	if ($custom_color) {
		return '<span style="color: '.$custom_color.'">'.$login.'</span>';
	}

	switch ($type & 1572864) {
		case 0:
		default:
			return $login;
		case 1048576:
			return '<span class="adminColor">'.$login.'</span>';
		case 524288:
			return '<span class="modsColor">'.$login.'</span>';
	}
}

	if (!$is_a && !($FUD_OPT_1 & 8388608) && (!($FUD_OPT_1 & 4194304) || !_uid)) {
		std_error((!_uid ? 'login' : 'disabled'));
	}

	if (isset($_GET['js_redr'])) {
		define('plain_form', 1);
		$is_a = 0;
	}

	$TITLE_EXTRA = ': Find User';

	ses_update_status($usr->sid, 'Searching for users');

if (_uid) {
	$admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

	if ($usr->users_opt & 524288 || $is_a) {	// is_mod or admin.
		if ($is_a) {
			// Approval of custom Avatars.
			if ($FUD_OPT_1 & 32 && ($avatar_count = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND '. q_bitand('users_opt', 16777216) .' > 0'))) {
				$custom_avatar_queue = '| <a href="/uni-ideas/adm/admavatarapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Custom Avatar Queue</a> <span class="GenTextRed">('.$avatar_count.')</span>';
			}

			// All reported messages.
			if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report')) {
				$reported_msgs = '| <a href="/uni-ideas/index.php?t=reported&amp;'._rsid.'" rel="nofollow">Reported Messages</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// All thread exchange requests.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange')) {
				$thr_exch = '| <a href="/uni-ideas/index.php?t=thr_exch&amp;'._rsid.'">Topic Exchange</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			// All account approvals.
			if ($FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
				$accounts_pending_approval = '| <a href="/uni-ideas/adm/admuserapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Accounts Pending Approval</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
			} else {
				$accounts_pending_approval = '';
			}

			$q_limit = '';
		} else {
			// Messages reported in moderated forums.
			if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id='. _uid)) {
				$reported_msgs = '| <a href="/uni-ideas/index.php?t=reported&amp;'._rsid.'" rel="nofollow">Reported Messages</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// Thread move requests in moderated forums.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id='. _uid .' AND te.frm=m.forum_id')) {
				$thr_exch = '| <a href="/uni-ideas/index.php?t=thr_exch&amp;'._rsid.'">Topic Exchange</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			$q_limit = ' INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id='. _uid;
		}

		// Messages requiring approval.
		if ($approve_count = q_singleval('SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id '. $q_limit .' WHERE m.apr=0 AND f.forum_opt>=2')) {
			$mod_que = '<a href="/uni-ideas/index.php?t=modque&amp;'._rsid.'">Moderation Queue</a> <span class="GenTextRed">('.$approve_count.')</span>';
		}
	} else if ($usr->users_opt & 268435456 && $FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
		$accounts_pending_approval = '| <a href="/uni-ideas/adm/admuserapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Accounts Pending Approval</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
	} else {
		$accounts_pending_approval = '';
	}
	if ($is_a || $usr->group_leader_list) {
		$group_mgr = '| <a href="/uni-ideas/index.php?t=groupmgr&amp;'._rsid.'">Group Manager</a>';
	}

	if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
		$admin_cp = '<br /><span class="GenText fb">Admin:</span> '.$mod_que.' '.$reported_msgs.' '.$thr_exch.' '.$custom_avatar_queue.' '.$group_mgr.' '.$accounts_pending_approval.'<br />';
	}
} else {
	$admin_cp = '';
}/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/uni-ideas/theme/default/images/icon/chat.png" alt="" width="16" height="16" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/uni-ideas/theme/default/images/icon/chat.png" alt="" width="15" height="11" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}

	if (!isset($_GET['start']) || !($start = (int)$_GET['start'])) {
		$start = 0;
	}

	if (isset($_GET['pc'])) {
		$ord = 'posted_msg_count '. ($_GET['pc'] % 2 ? 'ASC' : 'DESC');
	} else if (isset($_GET['us'])) {
		$ord = 'alias '. ($_GET['us'] % 2 ? 'DESC' : 'ASC');
	} else if (isset($_GET['rd'])) {
		$ord = 'join_date '. ($_GET['rd'] % 2 ? 'DESC' : 'ASC');
	} else if (isset($_GET['fl'])) {
		$ord = 'flag_cc '. ($_GET['fl'] % 2 ? 'DESC' : 'ASC');
	} else if (isset($_GET['lv'])) {
		$ord = 'last_visit '. ($_GET['lv'] % 2 ? 'DESC' : 'ASC');
	} else {
		$ord = 'id DESC';
	}
	$usr_login = !empty($_GET['usr_login']) ? trim((string)$_GET['usr_login']) : '';

	if ($usr_login) {
		$qry = 'alias LIKE '. _esc(char_fix(htmlspecialchars(addcslashes($usr_login.'%','\\')))) .' AND';
	} else {
		$qry = '';
	}

	$find_user_data = '';
	// Exclude anonymous (id=0) and spider users (users_opt&1073741824).
	$c = uq(q_limit('SELECT /*!40000 SQL_CALC_FOUND_ROWS */ flag_cc, flag_country, home_page, users_opt, alias, join_date, posted_msg_count, id, custom_color, last_visit FROM fud30_users WHERE '. $qry .' id>1 AND '. q_bitand('users_opt', 1073741824) .' = 0 ORDER BY '. $ord,
			$MEMBERS_PER_PAGE, $start));
	while ($r = db_rowobj($c)) {
		$find_user_data .= '
		
		<tr class="'.alt_var('finduser_alt','RowStyleA','RowStyleB').'">
		'.($GLOBALS['FUD_OPT_3'] & 524288 ? '<td>'.($r->flag_cc ? '<img src="/uni-ideas/images/flags/'.$r->flag_cc.'.png" border="0" width="16" height="11" alt="'.$r->flag_country.'" title="'.$r->flag_country.'" />' : '' )  .'</td>' : '' )  .'
			<td class="nw GenText" style="text-align:center"><a style="text-decoration: none;font-size: 15px" href="/uni-ideas/index.php?t=usrinfo&amp;id='.$r->id.'&amp;'._rsid.'">'.draw_user_link($r->alias, $r->users_opt, $r->custom_color).'</a>'.($r->users_opt & 131072 ? '' : '&nbsp;&nbsp;(unconfirmed user)' ) .'</td>
			<td class="ac nw hide2" style="text-align:center;font-size: 15px">'.$r->posted_msg_count.'</td>
        	<td class="DateText nw hide2" style="text-align:center;font-size: 15px">'.utf8_encode(strftime('%a, %d %B %Y', $r->join_date)).'</td>
        	<td class="nw GenText" style="text-align:center;font-size: 15px">
				<a style="text-decoration: none;font-size: 15px" style="text-decoration: none;" href="/uni-ideas/index.php?t=showposts&amp;id='.$r->id.'&amp;'._rsid.'">
					<img  alt="" src="/uni-ideas/theme/default/images/postimg123.bak.png" /></a>
					'.(($FUD_OPT_2 & 1073741824 && $r->users_opt & 16) ? '
				<a style="text-decoration: none;font-size: 15px" href="/uni-ideas/index.php?t=email&amp;toi='.$r->id.'&amp;'._rsid.'" rel="nofollow">
					<img style="margin-top: 2px" src="/uni-ideas/theme/default/images/ttt.png" alt="" /></a>' : '' ) .'
					'.(($FUD_OPT_1 & 1024 && _uid) ? '
				<a style="text-decoration: none;font-size: 15px" href="/uni-ideas/index.php?t=ppost&amp;'._rsid.'&amp;toi='.$r->id.'"><img src="/uni-ideas/theme/default/images/msg_pm.gif" alt="" /></a>' : '' ) .'
					'.($r->home_page ? '
				<a style="text-decoration: none;font-size: 15px" href="'.$r->home_page.'" rel="nofollow"><img alt="" src="/uni-ideas/theme/default/images/homepage.gif" /></a>' : '' ) .'
			</td>
			'.($is_a ? '

			<td class="SmallText nw">
				<a href="/uni-ideas/adm/admuser.php?usr_id='.$r->id.'&amp;S='.s.'&amp;act=1&amp;SQ='.$GLOBALS['sq'].'">Edit</a> ||
				<a href="/uni-ideas/adm/admuser.php?usr_id='.$r->id.'&amp;S='.s.'&amp;act=del&amp;f=1&amp;SQ='.$GLOBALS['sq'].'">Delete</a> ||'.($r->users_opt & 65536 ? '
				<a href="/uni-ideas/adm/admuser.php?act=block&amp;usr_id='.$r->id.'&amp;S='.s.'&amp;SQ='.$GLOBALS['sq'].'">UnBan</a>' : '
				<a href="/uni-ideas/adm/admuser.php?act=block&amp;usr_id='.$r->id.'&amp;S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Ban</a>' ) .'
			</td>' : '' ) .'
		</tr>';
	}
	unset($c);
	if (!$find_user_data) {
		$find_user_data = '
			<tr class="RowStyleA">
				<td colspan="'.($is_a ? '5' : '4' )  .'" class="wa GenText">No Such User</td>
			</tr>';
	}

	$pager = '';
	if (($total = (int) q_singleval('SELECT /*!40000 FOUND_ROWS(), */ -1')) < 0) {
		$total = q_singleval('SELECT count(*) FROM fud30_users WHERE '. $qry .' id > 1 AND '. q_bitand('users_opt', 1073741824) .' = 0');
	}
	if ($total > $MEMBERS_PER_PAGE) {
		if ($FUD_OPT_2 & 32768) {
			$pg = '/uni-ideas/index.php/ml/';

			if (isset($_GET['pc'])) {
				$pg .= (int)$_GET['pc'] .'/';
			} else if (isset($_GET['us'])) {
				$pg .= (int)$_GET['us'] .'/';
			} else if (isset($_GET['rd'])) {
				$pg .= (int)$_GET['rd'] .'/';
			} else if (isset($_GET['fl'])) {
				$pg .= ($_GET['fl']+6) .'/';
			} else if (isset($_GET['lv'])) {
				$pg .= (int)$_GET['lv'] .'/';
			} else {
				$pg .= '0/';
			}

			$ul = $usr_login ? urlencode($usr_login) : 0;
			$pg2 = '/'. $ul .'/';

			if (isset($_GET['js_redr'])) {
				$pg2 .= '1/';
			}
			$pg2 .= _rsid;

			$pager = tmpl_create_pager($start, $MEMBERS_PER_PAGE, $total, $pg, $pg2);
		} else {
			$pg = '/uni-ideas/index.php?t=finduser&amp;'. _rsid .'&amp;';
			if ($usr_login) {
				$pg .= 'usr_login='. urlencode($usr_login) .'&amp;';
			}
			if (isset($_GET['pc'])) {
				$pg .= 'pc='. (int)$_GET['pc'] .'&amp;';
			}
			if (isset($_GET['us'])) {
				$pg .= 'us='. (int)$_GET['us'] .'&amp;';
			}
			if (isset($_GET['rd'])) {
				$pg .= 'rd='. (int)$_GET['rd'] .'&amp;';
			}
			if (isset($_GET['fl'])) {
				$pg .= 'fl='. (int)$_GET['fl'] .'&amp;';
			}
			if (isset($_GET['lv'])) {
				$pg .= 'lv='. (int)$_GET['lv'] .'&amp;';
                        }
			if (isset($_GET['js_redr'])) {
				$pg .= 'js_redr='. urlencode($_GET['js_redr']) .'&amp;';
			}
			$pager = tmpl_create_pager($start, $MEMBERS_PER_PAGE, $total, $pg);
		}
	}

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
	<link rel="icon" type="image" href="/uni-ideas/theme/default/images/faviconx.png"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<script src="/uni-ideas/js/jquery.js"></script>
	<link rel="stylesheet" href="/UNI-Ideas/theme/default/style.css">
	<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
	<script async src="/uni-ideas/js/ui/jquery-ui.js"></script>
	<script src="/uni-ideas/js/lib.js"></script>
	<link rel="stylesheet" href="/UNI-Ideas/theme/default/style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
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
		.n-login{
			width: 30%;
			border-color: black;
			border-radius: 5px;
		}
		.bnt-find{
			color: white;
			background-color: #0F2026;
			font-size: 15px;
			border-radius: 9px;
			width: 70px;
			border-color: #ffffff;
		}
		.bnt-find:hover{
			background-color: #fa4d1d;
		}
		.adminColor {
			font-weight: bold;
			color: #FA4D1D;
			font-size: 15px;
		}
		.modsColor {
			color: green;
			font-weight: bold;
			font-size: 15px;
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
<br /><?php echo $admin_cp; ?>

<form method="get" id="fufrm" action="/uni-ideas/index.php"><?php echo _hs; ?>
	<div class="w3-container">
		<div class="w3-responsive">
			<table class="w3-table-all" style="border: none;">
				<tr style="border: none;border-top: 2px solid #000;font-weight: bold; font-size: 17px;">
					<td>
						<input type="hidden" name="t" value="finduser" />
					</td>
					<td>
						<input style="width: 30%; margin-left:50%" type="text" name="usr_login" class="n-login" tabindex="1" value="<?php echo char_fix(htmlspecialchars($usr_login)); ?>" />
						<input style="width: 7%;" type="submit" class="bnt-find" tabindex="2" name="btn_submit" value="Find" /></td>
					</td>
				</tr>
					
			</table>
		</div>
	</div>
	<input type="hidden" name="t" value="finduser" />
</form>

	<!-- <table cellspacing="1" cellpadding="2" class="ContentTable">
	<tr>
		<th colspan="3" style="font-size: 25px;"></th>
	</tr>
	<tr class="RowStyleA">
		<td style="color: #0F2026;font-size:15px; font-weight:bold">By Login:</td>
		<td >
			<input type="text" name="usr_login" class="n-login" tabindex="1" value="<?php echo char_fix(htmlspecialchars($usr_login)); ?>" />
			<input type="submit" class="bnt-find" tabindex="2" name="btn_submit" value="Find" /></td>
			
	</tr>
	</table> -->
	<!-- <input type="hidden" name="t" value="finduser" /> -->


<div class="w3-responsive">
	<table class="w3-table-all" style="border: none;">
		<tr>
			<?php echo ($GLOBALS['FUD_OPT_3'] & 524288 ? '
				<th><a class="thLnk" href="/uni-ideas/index.php?t=finduser&amp;usr_login='.urlencode($usr_login).'&amp;'._rsid.'&amp;fl='.(isset($_GET['fl']) && !($_GET['fl'] % 2) ? '1' : '2' )  .'&amp;btn_submit=Find" rel="nofollow">Flag</a></th>' : ''); ?>
				<th style="width: 15%;text-align:center">
					<a style="text-decoration: none; font-size: 25px;color:#ffffff"  class="thLnk" href="/uni-ideas/index.php?t=finduser&amp;usr_login=<?php echo urlencode($usr_login); ?>&amp;us=<?php echo (isset($_GET['us']) && !($_GET['us'] % 2) ? '1' : '2' )  .'&amp;btn_submit=Find&amp;'._rsid.'" rel="nofollow">User Information</a></th>
				<th style="width: 15%;text-align:center">
					<a style="text-decoration: none; font-size: 25px;" href="/uni-ideas/index.php?t=finduser&amp;usr_login='.urlencode($usr_login).'&amp;'._rsid.'&amp;pc='.(isset($_GET['pc']) && !($_GET['pc'] % 2) ? '1' : '2' )  .'&amp;btn_submit=Find" class="thLnk" rel="nofollow">Message Count</a></th>
				<th style="width: 15%;text-align:center">
					<a style="text-decoration: none; font-size: 25px;text-align: left" href="/uni-ideas/index.php?t=finduser&amp;usr_login='.urlencode($usr_login).'&amp;'._rsid.'&amp;rd='.(isset($_GET['rd']) && !($_GET['rd'] % 2) ? '1' : '2' )  .'&amp;btn_submit=Find" class="thLnk" rel="nofollow">Join Date</a></th>
				<th style="width: 15%;font-size: 25px;text-align:center">Action</th>
					'.($is_a ? '
				<th class="nw">Admin Opts.</th>
				' : ''); ?>
		</tr>
		<?php echo $find_user_data; ?>
	</table>
</div>


<?php echo $pager; ?>
<?php echo $page_stats; ?>
<script>
	document.forms['fufrm'].usr_login.focus();
</script>
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
