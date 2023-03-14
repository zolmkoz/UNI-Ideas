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

function tmpl_drawpmsg($obj, $usr, $mini)
{
	$o1 =& $GLOBALS['FUD_OPT_1'];
	$o2 =& $GLOBALS['FUD_OPT_2'];
	$a = (int) $obj->users_opt;
	$b =& $usr->users_opt;

	if (!$mini) {
		$custom_tag = $obj->custom_status ? '<br />'.$obj->custom_status.'' : '';
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
			$obj->login = $obj->alias;
			$online_indicator = (($obj->last_visit + $GLOBALS['LOGEDIN_TIMEOUT'] * 60) > __request_timestamp__) ? '<img src="/uni-ideas/theme/default/images/online.png" alt="'.$obj->login.' is currently online" title="'.$obj->login.' is currently online" />' : '<img src="/uni-ideas/theme/default/images/offline.png" alt="'.$obj->login.' is currently offline" title="'.$obj->login.' is currently offline" />';
		} else {
			$online_indicator = '';
		}

		if ($obj->location) {
			if (strlen($obj->location) > $GLOBALS['MAX_LOCATION_SHOW']) {
				$location = substr($obj->location, 0, $GLOBALS['MAX_LOCATION_SHOW']) .'...';
			} else {
				$location = $obj->location;
			}
			$location = '<br /><b>Location:</b> '.$location;
		} else {
			$location = '';
		}
		$usr->buddy_list = $usr->buddy_list ? unserialize($usr->buddy_list) : array();
		if ($obj->user_id != _uid && $obj->user_id > 0) {
			$buddy_link = !isset($usr->buddy_list[$obj->user_id]) ? '<a href="/uni-ideas/index.php?t=buddy_list&amp;'._rsid.'&amp;add='.$obj->user_id.'&amp;SQ='.$GLOBALS['sq'].'">add to buddy list</a><br />' : '<br />[<a href="/uni-ideas/index.php?t=buddy_list&amp;del='.$obj->user_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">remove from buddy list</a>]';
		} else {
			$buddy_link = '';
		}
		/* Show im buttons if need be. */
		if ($b & 16384) {
			$im = '';
			if ($obj->icq) {
				$im .= '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'#icq_msg"><img src="/uni-ideas/theme/default/images/icq.png" alt="" title="'.$obj->icq.'" /></a>&nbsp;';
			}
			if ($obj->facebook) {
				$im .= '<a href="https://www.facebook.com/'.$obj->facebook.'"><img src="/uni-ideas/theme/default/images/facebook.png" title="'.$obj->facebook.'" alt="" /></a>&nbsp;';
			}
			if ($obj->yahoo) {
				$im .= '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$obj->yahoo.'&amp;.src=pg"><img src="/uni-ideas/theme/default/images/yahoo.png" alt="" title="'.$obj->yahoo.'" /></a>&nbsp;';
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
				$dmsg_im_row = ''.$im.'<br />';
			} else {
				$dmsg_im_row = '';
			}
		} else {
			$dmsg_im_row = '';
		}
		if ($obj->ouser_id != _uid) {
			$user_profile = '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'"><img src="/uni-ideas/theme/default/images/msg_about.gif" alt="" /></a>';
			$email_link = ($o1 & 4194304 && $a & 16) ? '<a href="/uni-ideas/index.php?t=email&amp;toi='.$obj->user_id.'&amp;'._rsid.'" rel="nofollow"><img src="/uni-ideas/theme/default/images/msg_email.gif" alt="" /></a>' : '';
			$private_msg_link = '<a href="/uni-ideas/index.php?t=ppost&amp;toi='.$obj->user_id.'&amp;'._rsid.'"><img title="Send a private message to this user" src="/uni-ideas/theme/default/images/msg_pm.gif" alt="" /></a>';
		} else {
			$user_profile = $email_link = $private_msg_link = '';
		}
		$msg_toolbar = '<tr><td colspan="2" class="MsgToolBar"><table border="0" cellspacing="0" cellpadding="0" class="wa"><tr>
<td class="nw al">'.$user_profile.'&nbsp;'.$email_link.'&nbsp;'.$private_msg_link.'</td>
<td class="nw ar"><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'&amp;btn_delete=1&amp;sel='.$obj->id.'&amp;SQ='.$GLOBALS['sq'].'"><img src="/uni-ideas/theme/default/images/msg_delete.gif" alt="" /></a>&nbsp;'.($obj->fldr == 4 ? '<a href="/uni-ideas/index.php?t=ppost&amp;msg_id='.$obj->id.'&amp;'._rsid.'"><img src="/uni-ideas/theme/default/images/msg_edit.gif" alt="" /></a>&nbsp;&nbsp;&nbsp;&nbsp;' : '' )  .($obj->fldr == 1 ? '<a href="/uni-ideas/index.php?t=ppost&amp;reply='.$obj->id.'&amp;'._rsid.'"><img src="/uni-ideas/theme/default/images/msg_reply.gif" alt="" /></a>&nbsp;<a href="/uni-ideas/index.php?t=ppost&amp;quote='.$obj->id.'&amp;'._rsid.'"><img src="/uni-ideas/theme/default/images/msg_quote.gif" alt="" /></a>&nbsp;' : '' )  .'<a href="/uni-ideas/index.php?t=ppost&amp;forward='.$obj->id.'&amp;'._rsid.'"><img src="/uni-ideas/theme/default/images/msg_forward.gif" alt="" /></a></td>
</tr></table></td></tr>';
	} else {
		$dmsg_tags = $dmsg_im_row = $user_profile = $msg_toolbar = $buddy_link = $avatar = $online_indicator = $host_name = $location = '';
	}
	if ($obj->length > 0) {
		$msg_body = read_pmsg_body($obj->foff, $obj->length);
	} else {
		$msg_body = 'No Message Body';
	}

	$msg_body = $obj->length ? read_pmsg_body($obj->foff, $obj->length) : 'No Message Body';

	$file_attachments = '';
	if ($obj->attach_cnt) {
		$c = uq('SELECT a.id, a.original_name, a.dlcount, m.icon, a.fsize FROM fud30_attach a LEFT JOIN fud30_mime m ON a.mime_type=m.id WHERE a.message_id='. $obj->id .' AND attach_opt=1');
		while ($r = db_rowobj($c)) {
			$sz = $r->fsize/1024;
			$sz = $sz<1000 ? number_format($sz, 2) .'KB' : number_format($sz / 1024 ,2) .'MB';
			if(!$r->icon) {
				$r->icon = 'unknown.gif';
			}
			$file_attachments .= '<li>
	<img alt="" src="/uni-ideas/images/mime/'.$r->icon.'" class="at" />
	<span class="GenText fb">Attachment:</span> <a href="/uni-ideas/index.php?t=getfile&amp;id='.$r->id.'&amp;'._rsid.'&amp;private=1" title="'.$r->original_name.'">'.$r->original_name.'</a>
	<br />
	<span class="SmallText">(Size: '.$sz.', Downloaded '.convertPlural($r->dlcount, array(''.$r->dlcount.' time',''.$r->dlcount.' times')).')</span>
</li>';
		}
		unset($c);
		if ($file_attachments) {
			$file_attachments = '<ul class="AttachmentsList">
	'.$file_attachments.'
</ul>';
			/* Append session to getfile. */
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

	return '<tr><td><table cellspacing="0" cellpadding="0" class="MsgTable">
<tr>
<td colspan="2">
  <table cellspacing="0" cellpadding="0" class="ContentTable"><tr>
  <td class="MsgR1 vt al MsgSubText">'.(!$mini && $obj->icon ? '<img src="/uni-ideas/images/message_icons/'.$obj->icon.'" alt="" />&nbsp;&nbsp;' : '' )  .$obj->subject.'</td>
  <td class="MsgR1 vt ar DateText">'.utf8_encode(strftime('%a, %d %B %Y %H:%M', $obj->post_stamp)).'</td>
  </tr></table>
</tr>

<tr class="MsgR2">
<td class="MsgR2" width="15%" valign="top">
<table cellspacing="0" cellpadding="0" class="ContentTable"><tr class="MsgR2">'.$online_indicator.'<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'">'.filter_var($obj->alias, FILTER_SANITIZE_STRING).'</a>'.(!$mini ? ''.($obj->user_id ? '<br />'.$avatar.'<tr class="MsgR2"><td class="msgud">'.$dmsg_tags.'</td></tr><tr class="MsgR2"> <td class="msgud">Messages:'.$obj->posted_msg_count.'<br />
Registered:'.utf8_encode(strftime('%B %Y', $obj->join_date)).' '.$location.'' : '' )  .'' : '' )  .'<br />'.$buddy_link.'<br /></td></tr><tr class="MsgR2"><td class="msgud">'.$dmsg_im_row.'</td></tr></table></td>

<td class="MsgR3" width="85%" valign="top">'.$msg_body.$file_attachments.(($obj->sig && $o1 & 32768 && $obj->pmsg_opt & 1 && $b & 4096) ? '<br /><br /><hr class="sig" />'.$obj->sig.'' : '' )  .'</td></tr>'.$msg_toolbar.'
<tr><td class="MsgR2 ac" colspan="2">'.$GLOBALS['dpmsg_prev_message'].' '.$GLOBALS['dpmsg_next_message'].'</td></tr>
</table></td></tr>';
}
?>
