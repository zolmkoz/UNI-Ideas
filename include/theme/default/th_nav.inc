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

function get_prev_next_th_id($frm_id, $th, &$prev, &$next)
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
}
?>