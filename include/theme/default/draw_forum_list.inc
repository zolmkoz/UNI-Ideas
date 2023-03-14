<?php
/**
* copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id$
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	$collapse = $usr->cat_collapse_status ? unserialize($usr->cat_collapse_status) : array();
	$cat_id = !empty($_GET['cat'])    ? (int) $_GET['cat']    : 0;
	$frm_id = !empty($_GET['frm_id']) ? (int) $_GET['frm_id'] : 0;

	if ($cat_id && !empty($collapse[$cat_id])) {
		$collapse[$cat_id] = 0;
	}

	require $FORUM_SETTINGS_PATH .'idx.inc';
	if (!isset($cidxc[$cat_id])) {
		$cat_id = 0;
	}

	$cbuf = $forum_list_table_data = $cat_path = '';

	if ($cat_id) {
		$cid = $cat_id;
		while (($cid = $cidxc[$cid][4]) > 0) {
			$cat_path = '&nbsp;&raquo; <a href="/uni-ideas/index.php?t=i&amp;cat='.$cid.'&amp;'._rsid.'">'.$cidxc[$cid][1].'</a>'. $cat_path;
		}
		$cat_path = '<br />
<a href="/uni-ideas/index.php?t=i&amp;'._rsid.'">Home</a>
'.$cat_path.'&nbsp;&raquo; <b>'.$cidxc[$cat_id][1].'</b>';
	}

	/* List of fetched fields & their ids
	  0	msg.subject,
	  1	msg.id AS msg_id,
	  2	msg.post_stamp,
	  3	users.id AS user_id,
	  4	users.alias
	  5	forum.cat_id,
	  6	forum.forum_icon
	  7	forum.id
	  8	forum.last_post_id
	  9	forum.moderators
	  10	forum.name
	  11	forum.descr
	  12	forum.url_redirect
	  13	forum.post_count
	  14	forum.thread_count
	  15	forum_read.last_view
	  16	is_moderator
	  17	read perm
	  18	is the category using compact view
	*/
	$c = uq('SELECT
				m.subject, m.id, m.post_stamp,
				u.id, u.alias,
				f.cat_id, f.forum_icon, f.id, f.last_post_id, f.moderators, f.name, f.descr, f.url_redirect, f.post_count, f.thread_count,
				'. (_uid ? 'fr.last_view, mo.id, COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS group_cache_opt' : '0,0,g1.group_cache_opt') .',
				c.cat_opt
			FROM fud30_fc_view v
			INNER JOIN fud30_cat c ON c.id=v.c
			INNER JOIN fud30_forum f ON f.id=v.f
			INNER JOIN fud30_group_cache g1 ON g1.user_id='. (_uid ? 2147483647 : 0) .' AND g1.resource_id=f.id
			LEFT JOIN fud30_msg m ON f.last_post_id=m.id
			LEFT JOIN fud30_users u ON u.id=m.poster_id '.
			(_uid ? ' LEFT JOIN fud30_forum_read fr ON fr.forum_id=f.id AND fr.user_id='. _uid .' LEFT JOIN fud30_mod mo ON mo.user_id='. _uid .' AND mo.forum_id=f.id LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id' : '').
			' WHERE f.parent = '. $frm_id .
			((!$is_a || $cat_id) ?  ' AND ' : '') .
			($is_a ? '' : (_uid ? ' (mo.id IS NOT NULL OR ('. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 1) .' > 0))' : ' ('. q_bitand('g1.group_cache_opt', 1) .' > 0)')) .
			($cat_id ? ($is_a ? '' : ' AND ') .' v.c IN('. implode(',', ($cf = $cidxc[$cat_id][5])) .') ' : '') .' ORDER BY v.id');

	$post_count = $thread_count = $last_msg_id = $cat = 0;
	while ($r = db_rowarr($c)) {
		/* Increase thread & post count. */
		$post_count += $r[13];
		$thread_count += $r[14];

		$cid = (int) $r[5];

		if ($cat != $cid && !$frm_id) {
			if ($cbuf) { /* If previous category was using compact view, print forum row. */
				if (empty($collapse[$i[4]])) { /* Only show if parent is not collapsed as well. */
					$forum_list_table_data .= '<tr class="row child-c'.$cat.'">
	<td class="RowStyleA wo hide2">&nbsp;</td>
	<td class="RowStyleB ac wo hide2">&nbsp;</td>
	<td  class="RowStyleA wa" colspan="4">Available Forums:'.$cbuf.'</td>
</tr>';
				}
				$cbuf = '';
			}

			foreach ($cidxc as $k => $i) {
				/* 2nd check ensures that we don't end up displaying categories without any children. */ 
				if (($cat_id && !isset($cf[$k])) || ($cid != $k && $i[4] >= $cidxc[$cid][4])) {
					continue;
				}

				/* If parent category is collapsed, hide child category. */
				if ($i[4] && !empty($collapse[$i[4]])) {
					$collapse[$k] = 1;
				}

				if ($k == $cid) {
					break;	// Got it!
				}
			}
			$cat = $cid;
			if ($i[3] & 1 && $k != $cat_id && !($i[3] & 4)) {
				if (!isset($collapse[$k])) {
					$collapse[$k] = !($i[3] & 2);
				}
				$forum_list_table_data .= '<tr id="c'.$r[5].'" style="display: table-row;">
	<td class="CatDesc '.(empty($collapse[$cid]) ? 'expanded' : 'collapsed' )  .'" colspan="5" style="padding-left: '.($i[0] ? $i[0] * 20 : '0').'px;">
		<a href="/uni-ideas/index.php?t=index&amp;cat='.$k.'&amp;'._rsid.'" class="CatLink">'.$i[1].'</a> '.$i[2].'
	</td>
	<td class="CatDesc hide1">
	'.(key($cidxc) ? '<a href="javascript://" onclick=\'nextCat("c'.$k.'")\'><img src="/uni-ideas/theme/default/images/down.png" alt="" width="16" height="11" border="0" style="vertical-align: top; float: right;" /></a>' : '' )  .'
	'.($cat ? '<a href="javascript://" onclick=\'prevCat("c'.$k.'")\'><img src="/uni-ideas/theme/default/images/up.png" border="0" alt="" width="16" height="11" style="vertical-align: top; float: right;" /></a>' : '' )  .'
</td>
</tr>';
			} else {
				if ($i[3] & 4) {
					++$i[0];
				}
				$forum_list_table_data .= '<tr id="c'.$r[5].'" style="display: table-row;">
	<td class="CatDesc CatLockPad" colspan="5" style="padding-left: '.($i[0] ? $i[0] * 20 : '0').'px;">
		<span class="CatLockedName"><a href="/uni-ideas/index.php?t=index&amp;cat='.$k.'&amp;'._rsid.'" class="CatLink">'.$i[1].'</a></span> '.$i[2].'
	</td>
	<td class="CatDesc hide1">
	'.(key($cidxc) ? '<a href="javascript://" onclick=\'nextCat("c'.$k.'")\'><img src="/uni-ideas/theme/default/images/down.png" alt="" width="16" height="11" border="0" style="vertical-align: top; float: right;" /></a>' : '' )  .'
	'.($cat ? '<a href="javascript://" onclick=\'prevCat("c'.$k.'")\'><img src="/uni-ideas/theme/default/images/up.png" border="0" alt="" width="16" height="11" style="vertical-align: top; float: right;" /></a>' : '' )  .'
</td>
</tr>';
			}
		}

		/* Compact category view (ignore when expanded). */
		if ($r[18] & 4 && $cat_id != $cid) {
			$cbuf .= '&nbsp; '.(_uid && $r[15] < $r[2] && $usr->last_read < $r[2] ? '**' : '' )  .'
<a href="'.(empty($r[12]) ? '/uni-ideas/index.php?t='.t_thread_view.'&amp;frm_id='.$r[7].'&amp;'._rsid.'' : ''.$r[12].'' )  .'">'.$r[10].'</a>';
			continue;
		}

		/* Visible forum with no 'read' permission. */
		if (!($r[17] & 2) && !$is_a && !$r[16]) {
			$forum_list_table_data .= '<tr style="display: '.(empty($collapse[$cid]) ? 'table-row' : 'none' )  .'" class="child-c'.$r[5].'">
	<td class="RowStyleA" colspan="6">'.$r[10].($r[11] ? '<br />'.$r[11] : '').'</td>
</tr>';
			continue;
		}

		/* Code to determine the last post id for 'latest' forum message. */
		if ($r[8] > $last_msg_id) {
			$last_msg_id = $r[8];
		}

		if (!_uid) { /* Anon user. */
			$forum_read_indicator = '<img title="Only registered forum members can track read &amp; unread messages" src="/uni-ideas/theme/default/images/existing_content.png" alt="Only registered forum members can track read &amp; unread messages" width="30" height="30" />';
		} else if ($r[15] < $r[2] && $usr->last_read < $r[2]) {
			$forum_read_indicator = '<img title="New messages" src="/uni-ideas/theme/default/images/new_content.png" alt="New messages" width="30" height="30" />';
		} else {
			$forum_read_indicator = '<img title="No new messages" src="/uni-ideas/theme/default/images/existing_content.png" alt="No new messages" width="30" height="30" />';
		}

		if ($r[9] && ($mods = unserialize($r[9]))) {
			$moderators = '';	// List of forum moderators.
			$modcount = 0;		// Use singular or plural message form.

			foreach($mods as $k => $v) {
				$moderators .= '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$k.'&amp;'._rsid.'">'.$v.'</a> &nbsp;';
				$modcount++;
			}
			$moderators = '<div class="TopBy"><b>'.convertPlural($modcount, array('Moderator','Moderators')).':</b> '.$moderators.'</div>';
		} else {
			$moderators = '&nbsp;';
		}

		$forum_list_table_data .= '<tr style="display: '.(empty($collapse[$cid]) ? 'table-row' : 'none' )  .'" class="row child-c'.$r[5].'">
	<td class="RowStyleA wo hide2">'.($r[6] ? '<img src="/uni-ideas/images/forum_icons/'.$r[6].'" alt="Forum Icon" />' : '&nbsp;' ) .'</td>
	<td class="RowStyleB ac wo hide2">'.(empty($r[12]) ? ''.$forum_read_indicator.'' : '<img title="Redirection" src="/uni-ideas/theme/default/images/moved.png" alt="" />' )  .'</td>
	<td class="RowStyleA wa"><a href="'.(empty($r[12]) ? '/uni-ideas/index.php?t='.t_thread_view.'&amp;frm_id='.$r[7].'&amp;'._rsid.'' : ''.$r[12].'' )  .'" class="big">'.$r[10].'</a>'.($r[11] ? '<br />'.$r[11] : '').$moderators.'</td>
	<td class="RowStyleB ac hide1">'.(empty($r[12]) ? ''.$r[13].'' : '--' )  .'</td>
	<td class="RowStyleB ac hide1">'.(empty($r[12]) ? ''.$r[14].'' : '--' )  .'</td>
	<td class="RowStyleA ac nw hide2">'.(empty($r[12]) ? ''.($r[8] ? '<span class="DateText">'.utf8_encode(strftime('%a, %d %B %Y', $r[2])).'</span><br />By: '.($r[3] ? '<a href="/uni-ideas/index.php?t=usrinfo&amp;id='.$r[3].'&amp;'._rsid.'">'.$r[4].'</a>' : ''.$GLOBALS['ANON_NICK'].'' ) .' <a href="/uni-ideas/index.php?t='.d_thread_view.'&amp;goto='.$r[8].'&amp;'._rsid.'#msg_'.$r[8].'"><img title="'.$r[0].'" src="/uni-ideas/theme/default/images/goto.gif" alt="'.$r[0].'" width="9" height="9" /></a>' : 'n/a' ) .'' : '--' )  .'</td>
</tr>';
	}
	unset($c);

	if ($cbuf) { /* If previous category was using compact view, print forum row. */
		$forum_list_table_data .= '<tr class="row child-c'.$cat.'">
	<td class="RowStyleA wo hide2">&nbsp;</td>
	<td class="RowStyleB ac wo hide2">&nbsp;</td>
	<td  class="RowStyleA wa" colspan="4">Available Forums:'.$cbuf.'</td>
</tr>';
	}

?>
