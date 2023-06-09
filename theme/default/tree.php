<?php
/**
* copyright            : (C) 2001-2021 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id$
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}/* Handle poll votes if any are present. */
function register_vote(&$options, $poll_id, $opt_id, $mid)
{
	/* Invalid option or previously voted. */
	if (!isset($options[$opt_id]) || q_singleval('SELECT id FROM fud30_poll_opt_track WHERE poll_id='. $poll_id .' AND user_id='. _uid)) {
		return;
	}

	if (db_li('INSERT INTO fud30_poll_opt_track(poll_id, user_id, ip_addr, poll_opt) VALUES('. $poll_id .', '. _uid .', '. (!_uid ? _esc(get_ip()) : 'null') .', '. $opt_id .')', $a)) {
		q('UPDATE fud30_poll_opt SET votes=votes+1 WHERE id='. $opt_id);
		q('UPDATE fud30_poll SET total_votes=total_votes+1 WHERE id='. $poll_id);
		$options[$opt_id][1] += 1;
		q('UPDATE fud30_msg SET poll_cache='. _esc(serialize($options)) .' WHERE id='. $mid);
	}

	return 1;
}

$GLOBALS['__FMDSP__'] = array();

/* Needed for message threshold & reveling messages. */
if (isset($_GET['rev'])) {
	$_GET['rev'] = htmlspecialchars((string)$_GET['rev']);
	foreach (explode(':', $_GET['rev']) as $v) {
		$GLOBALS['__FMDSP__'][(int)$v] = 1;
	}
	if ($GLOBALS['FUD_OPT_2'] & 32768) {
		define('reveal_lnk', '/'. $_GET['rev']);
	} else {
		define('reveal_lnk', '&amp;rev='. $_GET['rev']);
	}
} else {
	define('reveal_lnk', '');
}

/* Initialize buddy & ignore list for registered users. */
if (_uid) {
	if ($usr->buddy_list) {
		$usr->buddy_list = unserialize($usr->buddy_list);
	}
	if ($usr->ignore_list) {
		$usr->ignore_list = unserialize($usr->ignore_list);
		if (isset($usr->ignore_list[1])) {
			$usr->ignore_list[0] =& $usr->ignore_list[1];
		}
	}

	/* Handle temporarily un-hidden users. */
	if (isset($_GET['reveal'])) {
		$_GET['reveal'] = htmlspecialchars((string)$_GET['reveal']);
		foreach(explode(':', $_GET['reveal']) as $v) {
			$v = (int) $v;
			if (isset($usr->ignore_list[$v])) {
				$usr->ignore_list[$v] = 0;
			}
		}
		if ($GLOBALS['FUD_OPT_2'] & 32768) {
			define('unignore_tmp', '/'. $_GET['reveal']);
		} else {
			define('unignore_tmp', '&amp;reveal='. $_GET['reveal']);
		}
	} else {
		define('unignore_tmp', '');
	}
} else {
	define('unignore_tmp', '');
	if (isset($_GET['reveal'])) {
		unset($_GET['reveal']);
	}
}

$_SERVER['QUERY_STRING_ENC'] = htmlspecialchars($_SERVER['QUERY_STRING']);

function make_tmp_unignore_lnk($id)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && strpos($_SERVER['QUERY_STRING_ENC'], '?') === false) {
		$_SERVER['QUERY_STRING_ENC'] .= '?1=1';
	}

	if (!isset($_GET['reveal'])) {
		return $_SERVER['QUERY_STRING_ENC'] .'&amp;reveal='. $id;
	} else {
		return str_replace('&amp;reveal='. $_GET['reveal'], unignore_tmp .':'. $id, $_SERVER['QUERY_STRING_ENC']);
	}
}

function make_reveal_link($id)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && strpos($_SERVER['QUERY_STRING_ENC'], '?') === false) {
		$_SERVER['QUERY_STRING_ENC'] .= '?1=1';
	}

	if (empty($GLOBALS['__FMDSP__'])) {
		return $_SERVER['QUERY_STRING_ENC'] .'&amp;rev='. $id;
	} else {
		return str_replace('&amp;rev='. $_GET['rev'], reveal_lnk .':'. $id, $_SERVER['QUERY_STRING_ENC']);
	}
}

/* Draws a message, needs a message object, user object, permissions array,
 * flag indicating wether or not to show controls and a variable indicating
 * the number of the current message (needed for cross message pager)
 * last argument can be anything, allowing forms to specify various vars they
 * need to.
 */
function tmpl_drawmsg($obj, $usr, $perms, $hide_controls, &$m_num, $misc)
{
	$o1 =& $GLOBALS['FUD_OPT_1'];
	$o2 =& $GLOBALS['FUD_OPT_2'];
	$a = (int) $obj->users_opt;
	$b =& $usr->users_opt;
	$MOD =& $GLOBALS['MOD'];

	$next_page = $next_message = $prev_message = '';
	/* Draw next/prev message controls. */
	if (!$hide_controls && $misc) {
		/* Tree view is a special condition, we only show 1 message per page. */
		if ($_GET['t'] == 'tree' || $_GET['t'] == 'tree_msg') {
			$prev_message = $misc[0] ? '<a href="javascript://" onclick="fud_tree_msg_focus('.$misc[0].', \''.s.'\', \'utf-8\'); return false;"><img src="/uni-ideas/theme/default/images/up.png" title="Go to previous message" alt="Go to previous message" width="16" height="11" /></a>' : '';
			$next_message = $misc[1] ? '<a href="javascript://" onclick="fud_tree_msg_focus('.$misc[1].', \''.s.'\', \'utf-8\'); return false;"><img alt="Go to previous message" title="Go to next message" src="/uni-ideas/theme/default/images/down.png" width="16" height="11" /></a>' : '';
		} else {
			/* Handle previous link. */
			if (!$m_num && $obj->id > $obj->root_msg_id) { /* prev link on different page */
				$prev_message = '<a href="/uni-ideas/index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] - $misc[1]).reveal_lnk.unignore_tmp.'"><img src="/uni-ideas/theme/default/images/up.png" title="Go to previous message" alt="Go to previous message" width="16" height="11" /></a>';
			} else if ($m_num) { /* Inline link, same page. */
				$prev_message = '<a href="javascript://" onclick="chng_focus(\'#msg_num_'.$m_num.'\');"><img alt="Go to previous message" title="Go to previous message" src="/uni-ideas/theme/default/images/up.png" width="16" height="11" /></a>';
			}

			/* Handle next link. */
			if ($obj->id < $obj->last_post_id) {
				if ($m_num && !($misc[1] - $m_num - 1)) { /* next page link */
					$next_message = '<a href="/uni-ideas/index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] + $misc[1]).reveal_lnk.unignore_tmp.'"><img alt="Go to previous message" title="Go to next message" src="/uni-ideas/theme/default/images/down.png" width="16" height="11" /></a>';
					$next_page = '<a href="/uni-ideas/index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] + $misc[1]).reveal_lnk.unignore_tmp.'">Next Page <img src="/uni-ideas/theme/default/images/goto.gif" width="9" height="9" alt="" /></a>';
				} else {
					$next_message = '<a href="javascript://" onclick="chng_focus(\'#msg_num_'.($m_num + 2).'\');"><img alt="Go to next message" title="Go to next message" src="/uni-ideas/theme/default/images/down.png" width="16" height="11" /></a>';
				}
			}
		}
		++$m_num;
	}

	$user_login = $obj->user_id ? $obj->login : $GLOBALS['ANON_NICK'];

	/* Check if the message should be ignored and it is not temporarily revelead. */
	if ($usr->ignore_list && !empty($usr->ignore_list[$obj->poster_id]) && !isset($GLOBALS['__FMDSP__'][$obj->id])) {
		return !$hide_controls ? '<tr>
	<td>
		<table border="0" cellspacing="0" cellpadding="0" class="MsgTable">
		<tr>
			<td class="MsgIg al">
				<a name="msg_num_'.$m_num.'"></a>
				<a name="msg_'.$obj->id.'"></a>
				'.($obj->user_id ? 'Message by <a href="/uni-ideas/index.php?t=usrinfo&amp;'._rsid.'&amp;id='.$obj->user_id.'">'.$obj->login.'</a> is ignored' : $GLOBALS['ANON_NICK'].' is ignored' )  .'&nbsp;
				[<a href="/uni-ideas/index.php?'. make_reveal_link($obj->id).'">reveal message</a>]&nbsp;
				[<a href="/uni-ideas/index.php?'.make_tmp_unignore_lnk($obj->poster_id).'">reveal all messages by '.$user_login.'</a>]&nbsp;
				[<a href="/uni-ideas/index.php?t=ignore_list&amp;del='.$obj->poster_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">stop ignoring this user</a>]</td>
				<td class="MsgIg" align="right">'.$prev_message.$next_message.'
			</td>
		</tr>
		</table>
	</td>
</tr>' : '<tr class="MsgR1 GenText">
	<td><a name="msg_num_'.$m_num.'"></a> <a name="msg_'.$obj->id.'"></a>Post by '.$user_login.' is ignored&nbsp;</td>
</tr>';
	}

	if ($obj->user_id && !$hide_controls) {
		$custom_tag = $obj->custom_status ? '<br />'.$obj->custom_status : '';
		$c = (int) $obj->level_opt;

		if ($obj->avatar_loc && $a & 8388608 && $b & 8192 && $o1 & 28 && !($c & 2)) {
			if (!($c & 1)) {
				$level_name =& $obj->level_name;
				$level_image = $obj->level_img ? '&nbsp;<img src="/uni-ideas/images/'.$obj->level_img.'" alt="" />' : '';
			} else {
				$level_name = $level_image = '';
			}
		} else {
			$level_image = $obj->level_img ? '&nbsp;<img src="/uni-ideas/images/'.$obj->level_img.'" alt="" />' : '';
			$obj->avatar_loc = '';
			$level_name =& $obj->level_name;
		}
		$avatar = ($obj->avatar_loc || $level_image) ? '<td class="avatarPad wo">'.$obj->avatar_loc.$level_image.'</td>' : '';
		$dmsg_tags = ($custom_tag || $level_name) ? '<div class="ctags">'.$level_name.$custom_tag.'</div>' : '';

		if (($o2 & 32 && !($a & 32768)) || $b & 1048576) {
			$online_indicator = (($obj->time_sec + $GLOBALS['LOGEDIN_TIMEOUT'] * 60) > __request_timestamp__) ? '<img src="/uni-ideas/theme/default/images/online.png" alt="'.$obj->login.' is currently online" title="'.$obj->login.' is currently online" width="16" height="16" />&nbsp;' : '<img src="/uni-ideas/theme/default/images/offline.png" alt="'.$obj->login.' is currently offline" title="'.$obj->login.' is currently offline" width="16" height="16" />&nbsp;';
		} else {
			$online_indicator = '';
		}

		$user_link = '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'">'.$user_login.'</a>';

		$location = $obj->location ? '<br /><b>Location: </b>'.(strlen($obj->location) > $GLOBALS['MAX_LOCATION_SHOW'] ? substr($obj->location, 0, $GLOBALS['MAX_LOCATION_SHOW']) . '...' : $obj->location) : '';

		if (_uid && _uid != $obj->user_id) {
			$buddy_link	= !isset($usr->buddy_list[$obj->user_id]) ? '<a href="/uni-ideas/index.php?t=buddy_list&amp;add='.$obj->user_id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">add to buddy list</a><br />' : '<a href="/uni-ideas/index.php?t=buddy_list&amp;del='.$obj->user_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">remove from buddy list</a><br />';
			$ignore_link	= !isset($usr->ignore_list[$obj->user_id]) ? '<a href="/uni-ideas/index.php?t=ignore_list&amp;add='.$obj->user_id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">ignore all messages by this user</a>' : '<a href="/uni-ideas/index.php?t=ignore_list&amp;del='.$obj->user_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">stop ignoring messages by this user</a>';
			$dmsg_bd_il	= $buddy_link.$ignore_link.'<br />';
		} else {
			$dmsg_bd_il = '';
		}

		/* Show im buttons if need be. */
		if ($b & 16384) {
			$im = '';
			if ($obj->icq) {
				$im .= '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$obj->poster_id.'&amp;'._rsid.'#icq_msg"><img title="'.$obj->icq.'" src="/uni-ideas/theme/default/images/icq.png" alt="" /></a>';
			}
			if ($obj->facebook) {
				$im .= '<a href="https://www.facebook.com/'.$obj->facebook.'"><img alt="" src="/uni-ideas/theme/default/images/facebook.png" title="'.$obj->facebook.'" /></a>';
			}
			if ($obj->yahoo) {
				$im .= '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$obj->yahoo.'&amp;.src=pg"><img alt="" src="/uni-ideas/theme/default/images/yahoo.png" title="'.$obj->yahoo.'" /></a>';
			}
			if ($obj->jabber) {
				$im .=  '<img src="/uni-ideas/theme/default/images/jabber.png" title="'.$obj->jabber.'" alt="" />';
			}
			if ($obj->google) {
				$im .= '<img src="/uni-ideas/theme/default/images/google.png" title="'.$obj->google.'" alt="" />';
			}
			if ($obj->skype) {
				$im .=  '<a href="callto://'.$obj->skype.'"><img src="/uni-ideas/theme/default/images/skype.png" title="'.$obj->skype.'" alt="" /></a>';
			}
			if ($obj->twitter) {
				$im .=  '<a href="https://twitter.com/'.$obj->twitter.'"><img src="/uni-ideas/theme/default/images/twitter.png" title="'.$obj->twitter.'" alt="" /></a>';
			}
			if ($im) {
				$dmsg_im_row = $im.'<br />';
			} else {
				$dmsg_im_row = '';
			}
		} else {
			$dmsg_im_row = '';
		}
	} else {
		$user_link = $obj->user_id ? $user_login : $user_login;
		$dmsg_tags = $dmsg_im_row = $dmsg_bd_il = $location = $online_indicator = $avatar = '';
	}

	/* Display message body.
	 * If we have message threshold & the entirity of the post has been revelead show a
	 * preview otherwise if the message body exists show an actual body.
	 * If there is no body show a 'no-body' message.
	 */
	if (!$hide_controls && $obj->message_threshold && $obj->length_preview && $obj->length > $obj->message_threshold && !isset($GLOBALS['__FMDSP__'][$obj->id])) {
		$msg_body = '<span class="MsgBodyText">'.read_msg_body($obj->offset_preview, $obj->length_preview, $obj->file_id_preview).'</span>
...<br /><br /><div class="ac">[ <a href="/uni-ideas/index.php?'.make_reveal_link($obj->id).'">Show the rest of the message</a> ]</div>';
	} else if ($obj->length) {
		$msg_body = '<span class="MsgBodyText">'.read_msg_body($obj->foff, $obj->length, $obj->file_id).'</span>';
	} else {
		$msg_body = 'No Message Body';
	}

	/* Draw file attachments if there are any. */
	$drawmsg_file_attachments = '';
	if ($obj->attach_cnt && !empty($obj->attach_cache)) {
		$atch = unserialize($obj->attach_cache);
		if (!empty($atch)) {
			foreach ($atch as $v) {
				$sz = $v[2] / 1024;
				$drawmsg_file_attachments .= '<li>
	<img alt="" src="/uni-ideas/images/mime/'.$v[4].'" class="at" />
	<span class="GenText fb">Attachment:</span> <a href="/uni-ideas/index.php?t=getfile&amp;id='.$v[0].'&amp;'._rsid.'" title="'.$v[1].'">'.$v[1].'</a>
	<br />
	<span class="SmallText">(Size: '.($sz < 1000 ? number_format($sz, 2).'KB' : number_format($sz/1024, 2).'MB').', Downloaded '.convertPlural($v[3], array(''.$v[3].' time',''.$v[3].' times')).')</span>
</li>';
			}
			$drawmsg_file_attachments = '<ul class="AttachmentsList">
	'.$drawmsg_file_attachments.'
</ul>';
		}
		/* Append session to getfile. */
		if (_uid) {
			if ($o1 & 128 && !isset($_COOKIE[$GLOBALS['COOKIE_NAME']])) {
				$msg_body = str_replace('<img src="index.php?t=getfile', '<img src="index.php?t=getfile&amp;S='. s, $msg_body);
				$tap = 1;
			}
			if ($o2 & 32768 && (isset($tap) || $o2 & 8192)) {
				$pos = 0;
				while (($pos = strpos($msg_body, '<img src="index.php/fa/', $pos)) !== false) {
					$pos = strpos($msg_body, '"', $pos + 11);
					$msg_body = substr_replace($msg_body, _rsid, $pos, 0);
				}
			}
		}
	}

	if ($obj->poll_cache) {
		$obj->poll_cache = unserialize($obj->poll_cache);
	}

	/* Handle poll votes. */
	if (!empty($_POST['poll_opt']) && ($_POST['poll_opt'] = (int)$_POST['poll_opt']) && !($obj->thread_opt & 1) && $perms & 512) {
		if (register_vote($obj->poll_cache, $obj->poll_id, $_POST['poll_opt'], $obj->id)) {
			$obj->total_votes += 1;
			$obj->cant_vote = 1;
		}
		unset($_GET['poll_opt']);
	}

	/* Display poll if there is one. */
	if ($obj->poll_id && $obj->poll_cache) {
		/* We need to determine if we allow the user to vote or see poll results. */
		$show_res = 1;

		if (isset($_GET['pl_view']) && !isset($_POST['pl_view'])) {
			$_POST['pl_view'] = $_GET['pl_view'];
		}

		/* Various conditions that may prevent poll voting. */
		if (!$hide_controls && !$obj->cant_vote &&
			(!isset($_POST['pl_view']) || $_POST['pl_view'] != $obj->poll_id) &&
			($perms & 512 && (!($obj->thread_opt & 1) || $perms & 4096)) &&
			(!$obj->expiry_date || ($obj->creation_date + $obj->expiry_date) > __request_timestamp__) &&
			/* Check if the max # of poll votes was reached. */
			(!$obj->max_votes || $obj->total_votes < $obj->max_votes)
		) {
			$show_res = 0;
		}

		$i = 0;

		$poll_data = '';
		foreach ($obj->poll_cache as $k => $v) {
			++$i;
			if ($show_res) {
				$length = ($v[1] && $obj->total_votes) ? round($v[1] / $obj->total_votes * 100) : 0;
				$poll_data .= '<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').'">
	<td>'.$i.'.</td>
	<td>'.$v[0].'</td>
	<td><img src="/uni-ideas/theme/default/images/poll_pix.gif" alt="" height="10" width="'.$length.'" /> '.$v[1].' / '.$length.'%</td>
</tr>';
			} else {
				$poll_data .= '<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').'">
	<td>'.$i.'.</td>
	<td colspan="2"><label><input type="radio" name="poll_opt" value="'.$k.'" />&nbsp;&nbsp;'.$v[0].'</label></td>
</tr>';
			}
		}

		if (!$show_res) {
			$poll = '<br />
<form action="/uni-ideas/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING']).'#msg_'.$obj->id.'" method="post">'._hs.'
<table cellspacing="1" cellpadding="2" class="PollTable">
<tr>
	<th class="nw" colspan="3">'.$obj->poll_name.'<span class="ptp">[ '.$obj->total_votes.' '.convertPlural($obj->total_votes, array('vote','votes')).' ]</span></th>
</tr>
'.$poll_data.'
<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').' ar">
	<td colspan="3">
		<input type="submit" class="button" name="pl_vote" value="Vote" />
		&nbsp;'.($obj->total_votes ? '<input type="submit" class="button" name="pl_res" value="View Results" />' : '' )  .'
	</td>
</tr>
</table>
<input type="hidden" name="pl_view" value="'.$obj->poll_id.'" />
</form>
<br />';
		} else {
			$poll = '<br />
<table cellspacing="1" cellpadding="2" class="PollTable">
<tr>
	<th class="nw" colspan="3">'.$obj->poll_name.'<span class="vt">[ '.$obj->total_votes.' '.convertPlural($obj->total_votes, array('vote','votes')).' ]</span></th>
</tr>
'.$poll_data.'
</table>
<br />';
		}

		if (($p = strpos($msg_body, '{POLL}')) !== false) {
			$msg_body = substr_replace($msg_body, $poll, $p, 6);
		} else {
			$msg_body = $poll . $msg_body;
		}
	}

	/* Determine if the message was updated and if this needs to be shown. */
	if ($obj->update_stamp) {
		if ($obj->updated_by != $obj->poster_id && $o1 & 67108864) {
			$modified_message = '<p class="fl">[Updated on: '.utf8_encode(strftime('%a, %d %B %Y %H:%M', $obj->update_stamp)).'] by Moderator</p>';
		} else if ($obj->updated_by == $obj->poster_id && $o1 & 33554432) {
			$modified_message = '<p class="fl">[Updated on: '.utf8_encode(strftime('%a, %d %B %Y %H:%M', $obj->update_stamp)).']</p>';
		} else {
			$modified_message = '';
		}
	} else {
		$modified_message = '';
	}

	if ($_GET['t'] != 'tree' && $_GET['t'] != 'msg') {
		$lnk = d_thread_view;
	} else {
		$lnk =& $_GET['t'];
	}

	$rpl = '';
	if (!$hide_controls) {

		/* Show reply links, eg: [message #1 is a reply to message #2]. */
		if ($o2 & 536870912) {
			if ($obj->reply_to && $obj->reply_to != $obj->id) {
				$rpl = '<span class="SmallText">[<a href="/uni-ideas/index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->id.'&amp;'._rsid.'#msg_'.$obj->id.'">message #'.$obj->id.'</a> is a reply to <a href="/uni-ideas/index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->reply_to.'&amp;'._rsid.'#msg_'.$obj->reply_to.'">message #'.$obj->reply_to.'</a>]</span>';
			} else {
				$rpl = '<span class="SmallText">[<a href="/uni-ideas/index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->id.'&amp;'._rsid.'#msg_'.$obj->id.'">message #'.$obj->id.'</a>]</span>';
			}
		}

		/* Little trick, this variable will only be available if we have a next link leading to another page. */
		if (empty($next_page)) {
			$next_page = '&nbsp;';
		}

		// Edit button if editing is enabled, EDIT_TIME_LIMIT has not transpired, and there are no replies.
		if (_uid && 
			($perms & 16 ||
				(_uid == $obj->poster_id && 
					(!$GLOBALS['EDIT_TIME_LIMIT'] ||
					__request_timestamp__ - $obj->post_stamp < $GLOBALS['EDIT_TIME_LIMIT'] * 60
					) &&
				(($GLOBALS['FUD_OPT_3'] & 1024) || $obj->id == $obj->last_post_id))
			)
		   )
		{
			$edit_link = '<a href="/uni-ideas/index.php?t=post&amp;msg_id='.$obj->id.'&amp;'._rsid.'"><img alt="" src="/uni-ideas/theme/default/images/msg_edit.gif" width="71" height="18" /></a>&nbsp;&nbsp;&nbsp;&nbsp;';
		} else {
			$edit_link = '';
		}

		if (!($obj->thread_opt & 1) || $perms & 4096) {
			$reply_link = '<a href="/uni-ideas/index.php?t=post&amp;reply_to='.$obj->id.'&amp;'._rsid.'"><img alt="" src="/uni-ideas/theme/default/images/msg_reply.gif" width="71" height="18" /></a>&nbsp;';
			$quote_link = '<a href="/uni-ideas/index.php?t=post&amp;reply_to='.$obj->id.'&amp;quote=true&amp;'._rsid.'"><img alt="" src="/uni-ideas/theme/default/images/msg_quote.gif" width="71" height="18" /></a>';
		} else {
			$reply_link = $quote_link = '';
		}
	}

	return '<tr><td class="MsgSpacer"><table cellspacing="0" cellpadding="0" class="MsgTable">
<tr>
<td colspan="2" class="MsgR1"><table cellspacing="0" cellpadding="0" class="ContentTable"><tr><td class="MsgR1 vt al MsgSubText"><a name="msg_num_'.$m_num.'"></a><a name="msg_'.$obj->id.'"></a>'.($obj->icon && !$hide_controls ? '<img src="images/message_icons/'.$obj->icon.'" alt="'.$obj->icon.'" />&nbsp;&nbsp;' : '' )  .$obj->subject.$rpl.'</td>
<td class="MsgR1 vt ar"><span class="DateText">'.utf8_encode(strftime('%a, %d %B %Y %H:%M', $obj->post_stamp)).'</span> '.$prev_message.$next_message.'</td></tr></table></td></tr>

<tr class="MsgR2">
<td class="MsgR2" width="15%" valign="top">
<table cellspacing="0" cellpadding="0" class="ContentTable"><tr class="MsgR2"><td class="msgud">'.$online_indicator.$user_link.(!$hide_controls ? ($obj->disp_flag_cc && $GLOBALS['FUD_OPT_3'] & 524288 ? '&nbsp;&nbsp;<img src="images/flags/'.$obj->disp_flag_cc.'.png" border="0" width="16" height="11" title="'.$obj->flag_country.'" alt="'.$obj->flag_country.'"/>' : '' )  .($obj->user_id ? '</td></tr><tr class="MsgR2">'.$avatar.'</tr><tr class="MsgR2"><td class="msgud">'.$dmsg_tags.'</td></tr><tr class="MsgR2"> <td class="msgud">Messages:'.$obj->posted_msg_count.'<br />
Registered:'.utf8_encode(strftime('%B %Y', $obj->join_date)).' '.$location : '' )   : '' )  .'</td></tr><tr class="MsgR2"><td class="msgud">'.$dmsg_bd_il.$dmsg_im_row.(!$hide_controls ? (($obj->host_name && $o1 & 268435456) ? 'From:'.$obj->host_name.'<br />' : '' )  .(($b & 1048576 || $usr->md || $o1 & 134217728) ? 'IP: <a href="/uni-ideas/index.php?t=ip&amp;ip='.$obj->ip_addr.'&amp;'._rsid.'" target="_blank">'.$obj->ip_addr.'</a>' : '' )   : '' )  .'</td></tr></table></td>

<td class="MsgR3" width="85%" valign="top">'.$msg_body.$drawmsg_file_attachments.'
'.$modified_message.(!$hide_controls ? (($obj->sig && $o1 & 32768 && $obj->msg_opt & 1 && $b & 4096 && !($a & 67108864)) ? '<br /><br /><hr class="sig" />'.$obj->sig : '' )  .'<p class="fr"><a href="/uni-ideas/index.php?t=report&amp;msg_id='.$obj->id.'&amp;'._rsid.'" rel="nofollow">Report message to a moderator</a></p>' : '' )  .'
</td></tr>
'.(!$hide_controls ? '<tr>
	<td colspan="2" class="MsgToolBar">
		<table border="0" cellspacing="0" cellpadding="0" class="wa">
		<tr>
			<td class="al nw">
				'.($obj->user_id ? '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'"><img alt="" src="/uni-ideas/theme/default/images/msg_about.gif" /></a>&nbsp;'.(($o1 & 4194304 && $a & 16) ? '<a href="/uni-ideas/index.php?t=email&amp;toi='.$obj->user_id.'&amp;'._rsid.'" rel="nofollow"><img alt="" src="/uni-ideas/theme/default/images/msg_email.gif" width="71" height="18" /></a>&nbsp;' : '' )  .($o1 & 1024 ? '<a href="/uni-ideas/index.php?t=ppost&amp;toi='.$obj->user_id.'&amp;rmid='.$obj->id.'&amp;'._rsid.'"><img alt="Send a private message to this user" title="Send a private message to this user" src="/uni-ideas/theme/default/images/msg_pm.gif" width="71" height="18" /></a>' : '' )   : '' )  .'
				'.(($GLOBALS['FUD_OPT_4'] & 4 && $perms & 1024 && $obj->poster_id > 0 && !$obj->cant_karma && $obj->poster_id != $usr->id) ? '
    <span id=karma_link_'.$obj->id.' class="SmallText">Rate author:
	<a href="javascript://" onclick="changeKarma('.$obj->id.','.$obj->poster_id.',\'up\',\''.s.'\',\''.$usr->sq.'\');" class="karma up">+1</a>
	<a href="javascript://" onclick="changeKarma('.$obj->id.','.$obj->poster_id.',\'down\',\''.s.'\',\''.$usr->sq.'\');" class="karma down">-1</a>
    </span>
' : '' )  .'
			</td>
			<td class="GenText wa ac">'.$next_page.'</td>
			<td class="nw ar">
				'.($perms & 32 ? '<a href="/uni-ideas/index.php?t=mmod&amp;del='.$obj->id.'&amp;'._rsid.'"><img alt="" src="/uni-ideas/theme/default/images/msg_delete.gif" width="71" height="18" /></a>&nbsp;' : '' )  .'
				'.$edit_link.'
				'.$reply_link.'
				'.$quote_link.'
			</td>
		</tr>
		</table>
	</td>
</tr>' : '' )  .'
</table></td></tr>';
}function read_msg_body($off, $len, $id)
{
	if ($off == -1) {	// Fetch from DB and return.
		return q_singleval('SELECT data FROM fud30_msg_store WHERE id='. $id);
	}

	if (!$len) {	// Empty message.
		return;
	}

	// Open file if it's not already open.
	if (!isset($GLOBALS['__MSG_FP__'][$id])) {
		$GLOBALS['__MSG_FP__'][$id] = fopen($GLOBALS['MSG_STORE_DIR'] .'msg_'. $id, 'rb');
	}

	// Read from file.
	fseek($GLOBALS['__MSG_FP__'][$id], $off);
	return fread($GLOBALS['__MSG_FP__'][$id], $len);
}function is_notified($user_id, $thread_id)
{
	return q_singleval('SELECT * FROM fud30_thread_notify WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}

function thread_notify_add($user_id, $thread_id)
{
	db_li('INSERT INTO fud30_thread_notify (user_id, thread_id) VALUES ('. $user_id .', '. (int)$thread_id .')', $ret);
}

function thread_notify_del($user_id, $thread_id)
{
	q('DELETE FROM fud30_thread_notify WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}

function thread_bookmark_add($user_id, $thread_id)
{
	db_li('INSERT INTO fud30_bookmarks (user_id, thread_id) VALUES ('. $user_id .', '. (int)$thread_id .')', $ret);
}

function thread_bookmark_del($user_id, $thread_id)
{
	q('DELETE FROM fud30_bookmarks WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
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
}function &get_all_read_perms($uid, $mod)
{
	$limit = array(0);

	$r = uq('SELECT resource_id, group_cache_opt FROM fud30_group_cache WHERE user_id='. _uid);
	while ($ent = db_rowarr($r)) {
		$limit[$ent[0]] = $ent[1] & 2;
	}
	unset($r);

	if (_uid) {
		if ($mod) {
			$r = uq('SELECT forum_id FROM fud30_mod WHERE user_id='. _uid);
			while ($ent = db_rowarr($r)) {
				$limit[$ent[0]] = 2;
			}
			unset($r);
		}

		$r = uq('SELECT resource_id FROM fud30_group_cache WHERE resource_id NOT IN ('. implode(',', array_keys($limit)) .') AND user_id=2147483647 AND '. q_bitand('group_cache_opt', 2) .' > 0');
		while ($ent = db_rowarr($r)) {
			if (!isset($limit[$ent[0]])) {
				$limit[$ent[0]] = 2;
			}
		}
		unset($r);
	}

	return $limit;
}

function perms_from_obj($obj, $adm)
{
	$perms = 1|2|4|8|16|32|64|128|256|512|1024|2048|4096|8192|16384|32768|262144;

	if ($adm || $obj->md) {
		return $perms;
	}

	return ($perms & $obj->group_cache_opt);
}

function make_perms_query(&$fields, &$join, $fid='')
{
	if (!$fid) {
		$fid = 'f.id';
	}

	if (_uid) {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $fid .' LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id='. $fid .' ';
		$fields = ' COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS group_cache_opt ';
	} else {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id='. $fid .' ';
		$fields = ' g1.group_cache_opt ';
	}
}function get_prev_next_th_id($frm_id, $th, &$prev, &$next)
{
	$next = $prev = '';
	$id = q_singleval('SELECT seq FROM fud30_tv_'. $frm_id .' WHERE thread_id='. $th);
	if (!$id) {
		return;
	}

	$nn = $np = 0;

	$c = uq('SELECT m.id, m.subject, tv.seq, t.moved_to FROM fud30_tv_'. $frm_id .' tv INNER JOIN fud30_thread t ON tv.thread_id=t.id INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE tv.seq IN('. ($id - 1) .', '. ($id + 1) .')');
	while ($r = db_rowarr($c)) {
		if ($r[2] < $id) {
			if ($r[3]) { /* Moved topic, let's try to find another, */
				$np = 1; continue;
			}
			$prev = '<tr>
	<td class="ar GenText">Previous Topic:</td>
	<td class="GenText al"><a href="/uni-ideas/index.php?t='.$_GET['t'].'&amp;goto='.$r[0].'&amp;'._rsid.'#msg_'.$r[0].'">'.$r[1].'</a></td>
</tr>';
		} else {
			if ($r[3]) { /* Moved topic, let's try to find another, */
				$nn = 1; continue;
			}
			$next = '<tr>
	<td class="GenText ar">Next Topic:</td>
	<td class="GenText al"><a href="/uni-ideas/index.php?t='.$_GET['t'].'&amp;goto='.$r[0].'&amp;'._rsid.'#msg_'.$r[0].'">'.$r[1].'</a></td>
</tr>';
		}		
	}
	unset($c);

	if ($np) {
		$r = db_saq(q_limit('SELECT m.id, m.subject FROM fud30_tv_'. $frm_id .' tv INNER JOIN fud30_thread t ON tv.thread_id=t.id INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE tv.seq IN('. ($id - 10) .', '. ($id - 2) .') ORDER BY tv.seq ASC', 1));
		$prev = '<tr>
	<td class="ar GenText">Previous Topic:</td>
	<td class="GenText al"><a href="/uni-ideas/index.php?t='.$_GET['t'].'&amp;goto='.$r[0].'&amp;'._rsid.'#msg_'.$r[0].'">'.$r[1].'</a></td>
</tr>';
	}
	if ($nn) {
		$r = db_saq(q_limit('SELECT m.id, m.subject FROM fud30_tv_'. $frm_id .' tv INNER JOIN fud30_thread t ON tv.thread_id=t.id INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE tv.seq IN('. ($id + 2) .', '. ($id + 10) .') ORDER BY tv.seq DESC', 1));
		$next = '<tr>
	<td class="GenText ar">Next Topic:</td>
	<td class="GenText al"><a href="/uni-ideas/index.php?t='.$_GET['t'].'&amp;goto='.$r[0].'&amp;'._rsid.'#msg_'.$r[0].'">'.$r[1].'</a></td>
</tr>';
	}
}function th_lock($id, $lck)
{
	q('UPDATE fud30_thread SET thread_opt=('. (!$lck ? q_bitand('thread_opt', ~1) : q_bitor('thread_opt', 1)) .') WHERE id='. $id);
}

function th_inc_view_count($id)
{
	global $plugin_hooks;
	if (isset($plugin_hooks['CACHEGET'], $plugin_hooks['CACHESET'])) {
		// Increment view counters in cache.
		$th_views = call_user_func($plugin_hooks['CACHEGET'][0], 'th_views');
		$th_views[$id] = (!empty($th_views) && array_key_exists($id, $th_views)) ? $th_views[$id]+1 : 1;

		if ($th_views[$id] > 10 || count($th_views) > 100) {
			call_user_func($plugin_hooks['CACHESET'][0], 'th_views', array());	// Clear cache.
			// Start delayed database updating.
			foreach($th_views as $id => $views) {
				q('UPDATE fud30_thread SET views=views+'. $views .' WHERE id='. $id);
			}
		} else {
			call_user_func($plugin_hooks['CACHESET'][0], 'th_views', $th_views);
		}
	} else {
		// No caching plugins available.
		q('UPDATE fud30_thread SET views=views+1 WHERE id='. $id);
	}
}

function th_inc_post_count($id, $r, $lpi=0, $lpd=0)
{
	if ($lpi && $lpd) {
		q('UPDATE fud30_thread SET replies=replies+'. $r .', last_post_id='. $lpi .', last_post_date='. $lpd .' WHERE id='. $id);
	} else {
		q('UPDATE fud30_thread SET replies=replies+'. $r .' WHERE id='. $id);
	}
}

	if ($FUD_OPT_3 & 2) {
		std_error('disabled');
	}

	if (!isset($_GET['th']) || !($th = (int)$_GET['th'])) {
		$th = 0;
	}
	if (!isset($_GET['mid']) || !($mid = (int)$_GET['mid'])) {
		$mid = 0;
	}

	if (isset($_GET['goto'])) {
		if (($mid = (int)$_GET['goto']) && !$th) {
			$th = q_singleval('SELECT thread_id FROM fud30_msg WHERE id='. $mid);
		} else if ($_GET['goto'] == 'end' && $th) {
			$mid = q_singleval('SELECT last_post_id FROM fud30_thread WHERE id='. $th);
		} else if ($th) {
			$mid = (int)$_GET['goto'];
		} else {
			invl_inp_err();
		}
	}
	if (!$th) {
		invl_inp_err();
	}
	if (!$mid && isset($_GET['unread']) && _uid) {
		$mid = q_singleval(q_limit('SELECT m.id FROM fud30_msg m LEFT JOIN fud30_read r ON r.thread_id=m.thread_id AND r.user_id='. _uid .' WHERE m.thread_id='. $th .' AND m.apr=1 AND m.post_stamp > r.last_view AND m.post_stamp > '. $usr->last_read .' ORDER BY m.post_stamp ASC', 1));
		if (!$mid) {
			$mid = q_singleval('SELECT root_msg_id FROM fud30_thread WHERE id='. $th);
		}
	}

	$RSS = ($FUD_OPT_2 & 1048576 ? '<link rel="alternate" type="application/rss+xml" title="Syndicate this forum (XML)" href="/uni-ideas/feed.php?mode=m&amp;th='.$th.'&amp;basic=1" />
' : '' )  ;

	/* We create a BIG object frm, which contains data about forum,
	 * category, current thread, subscriptions, permissions, moderation status,
	 * rating possibilites and if we will need to update last_view field for registered user.
	 */
	make_perms_query($fields, $join);

	$frm = db_sab('SELECT
			c.id AS cat_id,
			f.name,
			m.subject,
			t.tdescr, t.id, t.forum_id, t.replies, t.rating, t.n_rating, t.root_msg_id, t.moved_to, t.thread_opt, t.root_msg_id, t.last_post_date, '.
			(_uid ? ' tn.thread_id AS subscribed, tb.thread_id AS bookmarked, mo.forum_id AS md, tr.thread_id AS cant_rate, r.last_view, r2.last_view AS last_forum_view, ' : ' 0 AS md, 1 AS cant_rate, ').'
			m2.thread_id AS last_thread,
			'. $fields .'
		FROM fud30_thread t
			INNER JOIN fud30_msg		m ON m.id=t.root_msg_id
			INNER JOIN fud30_forum		f ON f.id=t.forum_id
			INNER JOIN fud30_cat		c ON f.cat_id=c.id
			INNER JOIN fud30_msg 		m2 ON f.last_post_id=m2.id
			'.(_uid ? 'LEFT  JOIN fud30_thread_notify 	tn ON tn.user_id='. _uid .' AND tn.thread_id='. $th .'
			LEFT  JOIN fud30_bookmarks          tb ON tb.user_id='. _uid .' AND tb.thread_id='. $th .'
			LEFT  JOIN fud30_mod 		mo ON mo.user_id='. _uid .' AND mo.forum_id=t.forum_id
			LEFT  JOIN fud30_thread_rate_track 	tr ON tr.thread_id='. $th .' AND tr.user_id='. _uid .'
			LEFT  JOIN fud30_read 		r ON r.thread_id=t.id AND r.user_id='. _uid .'
			LEFT  JOIN fud30_forum_read 	r2 ON r2.forum_id=t.forum_id AND r2.user_id='. _uid : '')
			. $join .'
		WHERE t.id='. $th);

	if (!$frm) { /* Bad thread, terminate request. */
		invl_inp_err();
	}

	if ($frm->moved_to) { /* Moved thread, we could handle it, but this case is rather rare, so it's cleaner to redirect. */
		if ($FUD_OPT_2 & 32768) {
			header('Location: /uni-ideas/index.php/mv/tree/'. $frm->root_msg_id .'/'. _rsidl .'#msg_'. $frm->root_msg_id);
		} else {
			header('Location: /uni-ideas/index.php?t=tree&goto='. $frm->root_msg_id .'&'. _rsidl .'#msg_'. $frm->root_msg_id);
		}
		exit;
	}

	$MOD = ($is_a || $frm->md);
	$perms = perms_from_obj($frm, $MOD);

	if (!($perms & 2)) {
		if (!isset($_GET['logoff'])) {
			std_error('login');
		}
		if ($FUD_OPT_2 & 32768) {
			header('Location: /uni-ideas/index.php/i/'. _rsidl);
		} else {
			header('Location: /uni-ideas/index.php?t=index&'. _rsidl);
		}
		exit;
	}

	if (_uid) {
		/* Deal with thread subscriptions. */
		if (isset($_GET['notify'], $_GET['opt']) && sq_check(0, $usr->sq)) {
			if (($frm->subscribed = ($_GET['opt'] == 'on'))) {
				thread_notify_add(_uid, $th);
			} else {
				thread_notify_del(_uid, $th);
			}
		}

		/* Deal with bookmarks. */
		if (isset($_GET['bookmark'], $_GET['opt']) && sq_check(0, $usr->sq)) {
			if (($frm->bookmarked = ($_GET['opt'] == 'on'))) {
				thread_bookmark_add(_uid, $th);
			} else {
				thread_bookmark_del(_uid, $th);
			}
		}

		$subscribe_status = $frm->subscribed ? '| <a href="/uni-ideas/index.php?t=tree&amp;th='.$th.'&amp;notify='.$usr->id.'&amp;'._rsid.'&amp;opt=off&amp;mid='.$mid.'&amp;SQ='.$GLOBALS['sq'].'" title="Stop receiving notifications about new messages in this topic">Unsubscribe from topic</a>&nbsp;' : '| <a href="/uni-ideas/index.php?t=tree&amp;th='.$th.'&amp;notify='.$usr->id.'&amp;'._rsid.'&amp;opt=on&amp;mid='.$mid.'&amp;SQ='.$GLOBALS['sq'].'" title="Receive notification about new messages inside this topic">Subscribe to topic</a>&nbsp;';
		$bookmark_status  = $frm->bookmarked ? '| <a href="/uni-ideas/index.php?t=tree&amp;th='.$th.'&amp;bookmark='.$usr->id.'&amp;'._rsid.'&amp;opt=off&amp;mid='.$mid.'&amp;SQ='.$GLOBALS['sq'].'" title="Remove this topic from your list of bookmarks">Remove from bookmarks</a>&nbsp;' : '| <a href="/uni-ideas/index.php?t=tree&amp;th='.$th.'&amp;bookmark='.$usr->id.'&amp;'._rsid.'&amp;opt=on&amp;mid='.$mid.'&amp;SQ='.$GLOBALS['sq'].'" title="Add this topic to your list of bookmarks">Bookmark topic</a>&nbsp;';
	} else {
		if (__fud_cache($frm->last_post_date)) {
			return;
		}
		$subscribe_status = $bookmark_status = '';
	}

	if (!$mid) {
		$mid = $frm->root_msg_id;
	}

	$msg_obj = db_sab('SELECT
		m.*, COALESCE(m.flag_cc, u.flag_cc) AS disp_flag_cc, COALESCE(m.flag_country, u.flag_country) AS disp_flag_country,
		t.thread_opt, t.root_msg_id, t.last_post_id, t.forum_id,
		f.message_threshold,
		u.id AS user_id, u.alias AS login, u.avatar_loc, u.email, u.posted_msg_count, u.join_date, u.location,
		u.sig, u.custom_status, u.icq, u.jabber, u.facebook, u.yahoo, u.google, u.skype, u.twitter, u.last_visit AS time_sec, u.users_opt,
		l.name AS level_name, l.level_opt, l.img AS level_img,
		p.max_votes, p.expiry_date, p.creation_date, p.name AS poll_name, p.total_votes,
		'.(_uid ? ' pot.id AS cant_vote ' : ' 1 AS cant_vote ').'
	FROM
		fud30_msg m
		INNER JOIN fud30_thread t ON m.thread_id=t.id
		INNER JOIN fud30_forum f ON t.forum_id=f.id
		LEFT JOIN fud30_users u ON m.poster_id=u.id
		LEFT JOIN fud30_level l ON u.level_id=l.id
		LEFT JOIN fud30_poll p ON m.poll_id=p.id'.
		(_uid ? ' LEFT JOIN fud30_poll_opt_track pot ON pot.poll_id=p.id AND pot.user_id='._uid : ' ').'
	WHERE
		m.id='. $mid .' AND m.apr=1 AND m.thread_id='. $th);

	if (!$msg_obj) { // Invalid message id.
		invl_inp_err();
	}

	if (!isset($_GET['prevloaded'])) {
		th_inc_view_count($th);
	}
	if (_uid) {
		if ($frm->last_view < $msg_obj->post_stamp) {
			user_register_thread_view($th, $msg_obj->post_stamp, $mid);
		}
		if ($frm->last_forum_view < $msg_obj->post_stamp) {
			user_register_forum_view($frm->forum_id);
		}
	}
	ses_update_status($usr->sid, 'Browsing topic (tree view) <a href="/uni-ideas/index.php?t=tree&amp;th='.$frm->id.'&amp;'._rsid.'">'.$frm->subject.'</a>', $frm->id);

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
	}function tmpl_create_forum_select($frm_id, $mod)
{
	if (!isset($_GET['t']) || ($_GET['t'] != 'thread' && $_GET['t'] != 'threadt')) {
		$dest = t_thread_view;
	} else {
		$dest = $_GET['t'];
	}

	if ($mod) { /* Admin optimization. */
		$c = uq('SELECT f.id, f.name, c.id FROM fud30_fc_view v INNER JOIN fud30_forum f ON f.id=v.f INNER JOIN fud30_cat c ON f.cat_id=c.id WHERE f.url_redirect IS NULL ORDER BY v.id');
	} else {
		$c = uq('SELECT f.id, f.name, c.id
			FROM fud30_fc_view v
			INNER JOIN fud30_forum f ON f.id=v.f
			INNER JOIN fud30_cat c ON f.cat_id=c.id
			INNER JOIN fud30_group_cache g1 ON g1.user_id='. (_uid ? '2147483647' : '0') .' AND g1.resource_id=f.id '.
			(_uid ? ' LEFT JOIN fud30_mod mm ON mm.forum_id=f.id AND mm.user_id='. _uid .' LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id WHERE mm.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 1) .' > 0 '  : ' WHERE '. q_bitand('g1.group_cache_opt', 1) .' > 0 AND f.url_redirect IS NULL ').
			'ORDER BY v.id');
	}
	$f = array($frm_id => 1);

	$frmcount = 0;
	$oldc = $selection_options = '';
	while ($r = db_rowarr($c)) {
		if ($oldc != $r[2]) {
			foreach ($GLOBALS['cat_cache'] as $k => $i) {
				if ($r[2] != $k && $i[0] >= $GLOBALS['cat_cache'][$r[2]][0]) {
					continue;
				}
	
				$selection_options .= '<option disabled="disabled">- '.($tabw = ($i[0] ? str_repeat('&nbsp;&nbsp;&nbsp;', $i[0]) : '')).$i[1].'</option>';
				if ($k == $r[2]) {
					break;
				}
			}
			$oldc = $r[2];
		}
		$selection_options .= '<option value="'.$r[0].'"'.(isset($f[$r[0]]) ? ' selected="selected"' : '').'>'.$tabw.'&nbsp;&nbsp;'.$r[1].'</option>';
		$frmcount++;
	}
	unset($c);
	
	return ($frmcount > 1 ? '
<span class="SmallText fb">Goto Forum:</span>
<form action="/uni-ideas/index.php" id="frmquicksel" method="get">
	<input type="hidden" name="t" value="'.$dest.'" />
	'._hs.'
	<select class="SmallText" name="frm_id">
		'.$selection_options.'
	</select>&nbsp;&nbsp;
	<input type="submit" class="button small" name="frm_goto" value="Go" />
</form>
' : '' ) ;
}if (!isset($th)) {
	$th = 0;
}
if (!isset($frm->id)) {
	$frm = new stdClass();	// Initialize to prevent 'strict standards' notice.
	$frm->id = 0;
}require $GLOBALS['FORUM_SETTINGS_PATH'] .'cat_cache.inc';

function draw_forum_path($cid, $fn='', $fid=0, $tn='')
{
	global $cat_par, $cat_cache;

	$data = '';
	do {
		$data = '&nbsp;&raquo; <a href="/uni-ideas/index.php?t=i&amp;cat='.$cid.'&amp;'._rsid.'">'.$cat_cache[$cid][1].'</a>'. $data;
	} while (($cid = $cat_par[$cid]) > 0);

	if ($fid) {
		$data .= '&nbsp;&raquo; <a href="/uni-ideas/index.php?t='.t_thread_view.'&amp;frm_id='.$fid.'&amp;'._rsid.'">'.$fn.'</a>';
	} else if ($fn) {
		$data .= '&nbsp;&raquo; <strong>'.$fn.'</strong>';
	}

	return '<a href="/uni-ideas/index.php?t=i&amp;'._rsid.'">Home</a>'.$data.($tn ? '&nbsp;&raquo; <strong>'.$tn.'</strong>' : '');
}

	$TITLE_EXTRA = ': '.$frm->name.' &raquo; '.$frm->subject;

	if ($perms & 4096) {
		$lock_thread = !($frm->thread_opt & 1) ? '<a href="/uni-ideas/index.php?t=mmod&amp;'._rsid.'&amp;th='.$th.'&amp;lock=1&amp;SQ='.$GLOBALS['sq'].'">Lock Topic</a>&nbsp;|&nbsp;' : '<a href="/uni-ideas/index.php?t=mmod&amp;'._rsid.'&amp;th='.$th.'&amp;unlock=1&amp;SQ='.$GLOBALS['sq'].'">Unlock Topic</a>&nbsp;|&nbsp;';
	} else {
		$lock_thread = '';
	}

	$tree = new stdClass();
	$tree->kiddie_count=0;
	$stack = $arr = array();
	$c = uq('SELECT m.poster_id, m.subject, m.reply_to, m.id, m.poll_id, m.attach_cnt, m.post_stamp, m.icon, m.file_id, m.length, m.foff, u.alias, u.last_visit FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id LEFT JOIN fud30_users u ON m.poster_id=u.id WHERE m.thread_id='. $th .' AND m.apr=1 ORDER BY m.reply_to ASC, m.id');
	while ($r = db_rowobj($c)) {
		$arr[$r->id] = $r;
		
		if ($r->reply_to > 0) {
			if (!isset( $arr[$r->reply_to]->kiddie_count) ) { $arr[$r->reply_to]->kiddie_count=0; }
			$arr[$r->reply_to]->kiddie_count++;
			$arr[$r->reply_to]->kiddies[] = &$arr[$r->id];
		} else 	if ($r->reply_to == 0) {
			$tree->kiddie_count++;
			$tree->kiddies[] = &$arr[$r->id];
		}
	}
	unset($c);

	$prev_msg = $next_msg = 0;
	$rev = isset($_GET['rev']) ? $_GET['rev'] : '';
	$reveal = isset($_GET['reveal']) ? $_GET['reveal'] : '';
	$tree_data = '';

	if($arr) {
		if (isset($tree->kiddies)) {
			reset($tree->kiddies);
		}
		$stack[0] = &$tree;
		$stack_cnt = isset($tree->kiddie_count) ? $tree->kiddie_count : 0;
		$j = $lev = $prev_id = 0;

		while ($stack_cnt > 0) {
			$cur = &$stack[$stack_cnt-1];

			if (isset($cur->subject) && empty($cur->sub_shown)) {
				$tree_data .= '<tr class="'.($cur->id % 2 ? 'RowStyleA' : 'RowStyleB' )  .'" id="b'.$cur->id.'">
	<td>'.((_uid && $cur->post_stamp > $usr->last_read && $cur->post_stamp > $frm->last_view) ? '<img id="msgIm'.$cur->id.'" src="/uni-ideas/theme/default/images/unread.png" alt="Unread Message" title="Unread Message" width="22" height="22" />' : '<img src="/uni-ideas/theme/default/images/read.png" alt="Read Message" title="Read Message" width="22" height="22" />' ) .'</td>
	<td>'.($cur->icon ? '<img src="/uni-ideas/images/message_icons/'.$cur->icon.'" alt="'.$cur->icon.'" />' : '' ) .'</td>
	<td class="Gentext nw wa vt" style="padding-left: '.(15 * ($lev - 1)).'px">
		<a href="/uni-ideas/index.php?t=tree&amp;th='.$th.'&amp;mid='.$cur->id.'&amp;'._rsid.'&amp;rev='.$rev.'&amp;reveal='.$reveal.'" onclick="fud_tree_msg_focus('.$cur->id.', \''.s.'\', \'utf-8\'); return false;" class="big">'.$cur->subject.'</a>
		<div class="TopBy">By: '.($cur->poster_id ? '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$cur->poster_id.'&amp;'._rsid.'">'.filter_var($cur->alias, FILTER_SANITIZE_STRING).'</a>' : $GLOBALS['ANON_NICK'].'' ) .' on '.utf8_encode(strftime('%a, %d %B %Y %H:%M', $cur->post_stamp)).'</div>
	</td>
</tr>';

				$cur->sub_shown = 1;

				if ($cur->id == $mid) {
					$prev_msg = $prev_id;
				}
				if ($prev_id == $mid) {
					$next_msg = $cur->id;
				}

				$prev_id = $cur->id;
			}

			if (!isset($cur->kiddie_count)) {
				$cur->kiddie_count = 0;
			}

			if ($cur->kiddie_count && isset($cur->kiddie_pos)) {
				++$cur->kiddie_pos;
			} else {
				$cur->kiddie_pos = 0;
			}

			if ($cur->kiddie_pos < $cur->kiddie_count) {
				++$lev;
				$stack[$stack_cnt++] = &$cur->kiddies[$cur->kiddie_pos];
			} else { // Unwind the stack if needed.
				unset($stack[--$stack_cnt]);
				--$lev;
			}

			unset($cur);
		}
	}
	$n = 0; $_GET['start'] = '';
	$usr->md = $frm->md;

	get_prev_next_th_id($frm->forum_id, $th, $prev_thread_link, $next_thread_link);

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
<?php echo $admin_cp; ?>
<a name="page_top"> </a>
<?php echo draw_forum_path($frm->cat_id, $frm->name, $frm->forum_id, $frm->subject); ?>
<?php echo ($frm->tdescr ? ' <span class="small">('.$frm->tdescr.')</span>' : ''); ?>  
<span id="threadRating"><?php echo ($FUD_OPT_2 & 4096 && $frm->rating ? '&nbsp;('.($MOD ? '<a href="javascript://" onclick="window_open(\'/uni-ideas/index.php?t=ratingtrack&amp;'._rsid.'&amp;th='.$frm->id.'\', \'th_rating_track\', 300, 400);">' : '' )  .'<img src="/uni-ideas/theme/default/images/'.$frm->rating.'stars.gif" title="'.$frm->rating.' from '.convertPlural($frm->n_rating, array(''.$frm->n_rating.' vote',''.$frm->n_rating.' votes')).'" alt=""/>'.($MOD ? '</a>' : '' )  .') '.convertPlural($frm->n_rating, array(''.$frm->n_rating.' Vote',''.$frm->n_rating.' Votes')).'' : ''); ?></span>
<table cellspacing="0" cellpadding="0" border="0" class="wa">
<tr>
	<td class="GenText al">
		<span id="ShowLinks">
<span class="GenText fb">Show:</span>
<a href="/uni-ideas/index.php?t=selmsg&amp;date=today&amp;<?php echo _rsid; ?>&amp;frm_id=<?php echo (isset($frm->forum_id) ? $frm->forum_id.'' : $frm->id.'' )  .'&amp;th='.$th.'" title="Show all messages that were posted today" rel="nofollow">Today&#39;s Messages</a>
'.(_uid ? '<b>::</b> <a href="/uni-ideas/index.php?t=selmsg&amp;unread=1&amp;'._rsid.'&amp;frm_id='.(isset($frm->forum_id) ? $frm->forum_id.'' : $frm->id.'' )  .'" title="Show all unread messages" rel="nofollow">Unread Messages</a>&nbsp;' : ''); ?>
<?php echo (!$th ? '<b>::</b> <a href="/uni-ideas/index.php?t=selmsg&amp;reply_count=0&amp;'._rsid.'&amp;frm_id='.(isset($frm->forum_id) ? $frm->forum_id.'' : $frm->id.'' )  .'" title="Show all messages, which have no replies" rel="nofollow">Unanswered Messages</a>&nbsp;' : ''); ?>
<b>::</b> <a href="/uni-ideas/index.php?t=polllist&amp;<?php echo _rsid; ?>" rel="nofollow">Polls</a>
<b>::</b> <a href="/uni-ideas/index.php?t=mnav&amp;<?php echo _rsid; ?>" rel="nofollow">Message Navigator</a>
</span>
		<br />
		<?php echo (($frm->replies && $perms & 2048) ? '<a href="/uni-ideas/index.php?t=split_th&amp;'._rsid.'&amp;th='.$th.'">Split Topic</a>&nbsp;|&nbsp;' : ''); ?>
		<?php echo ($perms & 8192 ? ($frm->replies ? '<a href="/uni-ideas/index.php?t=movemsg&amp;th='.$th.'">Move messages</a>&nbsp;|&nbsp;' : '' )  .'<a href="javascript://" onclick="window_open(\'/uni-ideas/index.php?t=mvthread&amp;'._rsid.'&amp;th='.$th.'\', \'th_move\', 300, 400);">Move</a>&nbsp;|&nbsp;' : '' ) .$lock_thread.'
		'.($FUD_OPT_2 & 1073741824 ? '<a href="/uni-ideas/index.php?t=remail&amp;th='.$th.'&amp;'._rsid.'" title="Send the URL to this page to your friend(s) via e-mail" rel="nofollow">E-mail to friend</a>&nbsp;' : ''); ?>
		<?php echo $subscribe_status.$bookmark_status; ?>
	</td>
	<td class="vb ar">
		<a href="/uni-ideas/index.php?t=msg&amp;th=<?php echo $th; ?>&amp;<?php echo _rsid; ?>#msg_<?php echo $mid; ?>"><img alt="Return to the default flat view" title="Return to the default flat view" src="/uni-ideas/theme/default/images/flat_view.gif" /></a>&nbsp;<a href="/uni-ideas/index.php?t=post&amp;frm_id=<?php echo $frm->forum_id; ?>&amp;<?php echo _rsid; ?>"><img alt="Create a new topic" src="/uni-ideas/theme/default/images/new_thread.gif" /></a><?php echo ((!($frm->thread_opt & 1) || $perms & 4096) ? '&nbsp;<a href="/uni-ideas/index.php?t=post&amp;th_id='.$th.'&amp;reply_to='.$mid.'&amp;'._rsid.'"><img alt="Submit Reply" src="/uni-ideas/theme/default/images/post_reply.gif" /></a>' : ''); ?>
	</td>
</tr>
</table>
<div><table cellspacing="0" cellpadding="0" id="msgTbl" class="ContentTable"><?php echo tmpl_drawmsg($msg_obj, $usr, $perms, false, $n, array($prev_msg, $next_msg)); ?></table></div>
<div class="ac GenText">[<a href="javascript://" onclick="chng_focus('tree_view');" title="View the message list">Message index</a>]</div>
<a name="tree_view">&nbsp;</a>
<table cellspacing="1" cellpadding="2" class="MsgTable">
	<?php echo $tree_data; ?>
</table>
<?php echo ((!empty($prev_thread_link) || !empty($next_thread_link)) ? '
<table border="0" cellspacing="1" cellpadding="1" align="right">
	'.$prev_thread_link.'
	'.$next_thread_link.'
</table>
' : ''); ?>
<?php echo (($FUD_OPT_2 & 4096 && $perms & 1024 && !$frm->cant_rate) ? '<form id="RateFrm" action="post">
	<select class="SmallText" onchange="if (this.value) topicVote(this.value, '.$frm->id.', \''.s.'\', \''.$usr->sq.'\');">
		<option>Rate Topic</option>
		<option value="1">1 Worst</option>
		<option value="2">2</option>
		<option value="3">3</option>
		<option value="4">4</option>
		<option value="5">5 Best</option>
	</select>
</form>' : ''); ?>
<?php echo tmpl_create_forum_select((isset($frm->forum_id) ? $frm->forum_id : $frm->id), $usr->users_opt & 1048576); ?>
<br /><div class="wa ac">-=] <a href="javascript://" onclick="chng_focus('page_top');">Back to Top</a> [=-</div>
<div class="ar SmallText"><?php echo ($FUD_OPT_2 & 1048576 ? '[ <a href="/uni-ideas/index.php?t=help_index&amp;section=boardusage#syndicate">Syndicate this forum (XML)</a> ] [ <a href="/uni-ideas/feed.php?mode=m&amp;th='.$th.'&amp;basic=1"><img src="/uni-ideas/theme/default/images/rss.gif" title="Syndicate this forum (XML)" alt="RSS" width="16" height="16" /></a> ]' : ''); ?> <?php echo ($FUD_OPT_2 & 2097152 ? '[ <a href="/uni-ideas/pdf.php?msg='.$mid.'&amp;'._rsid.'"><img src="/uni-ideas/theme/default/images/pdf.gif" title="Generate printable PDF" alt="PDF" /></a> ]' : ''); ?></div>
<br />  
<?php echo $page_stats; ?>
<script defer="defer">
	var cur_msg = <?php echo $mid; ?>;
	jQuery('#b' + cur_msg).removeClass().addClass('RowStyleC');
</script>
<?php echo (!empty($_GET['srch']) ? '
<script defer="defer">
	highlightSearchTerms("'.filter_var($_GET['srch'], FILTER_SANITIZE_STRING).'");
</script>
' : ''); ?>
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
