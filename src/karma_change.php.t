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

/*{PRE_HTML_PHP}*/

	if (!_uid) {			// User must be logged in.
		std_error('login');
	}
	if (!($FUD_OPT_4 & 4)) {	// KARMA must be enabled.
		std_error('access');
	}

/*{POST_HTML_PHP}*/

	if (isset($_GET['karma_msg_id'], $_GET['sel_number'])) {
		switch ($_GET['sel_number']) {
		    case 'up' : $rt = 1;
				break;
		    case 'down': $rt = -1;
				break;
		    default: $rt = 0;
		}

		$msg = (int) $_GET['karma_msg_id'];

		/* Security check whether the user has permission to rate topic/karma in the forum */
		if (!q_singleval(q_limit('SELECT m1.id
				FROM {SQL_TABLE_PREFIX}msg m1 JOIN {SQL_TABLE_PREFIX}thread t ON m1.thread_id=t.id
				LEFT JOIN {SQL_TABLE_PREFIX}mod m ON t.forum_id=m.forum_id AND m.user_id='. _uid .'
				INNER JOIN {SQL_TABLE_PREFIX}group_cache g1 ON g1.user_id='. (_uid ? 2147483647 : 0) .' AND g1.resource_id=t.forum_id
				'.(_uid ? ' LEFT JOIN {SQL_TABLE_PREFIX}group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=t.forum_id ' : '').'
				WHERE m1.id='. $msg . ($is_a ? '' : ' AND (m.id IS NOT NULL OR '. q_bitand(_uid ? 'COALESCE(g2.group_cache_opt, g1.group_cache_opt)' : 'g1.group_cache_opt', 1024) .' > 0)'), 1))) {
			std_error('access');
		}

		$poster_id = db_saq('SELECT poster_id FROM {SQL_TABLE_PREFIX}msg WHERE id='. $msg);
		$karma = db_saq('SELECT karma FROM {SQL_TABLE_PREFIX}users WHERE id='. $poster_id[0]);
               
		/* Check if user already voted for this specific message */
		if (!q_singleval('SELECT user_id FROM {SQL_TABLE_PREFIX}karma_rate_track WHERE msg_id = '. $msg .' AND user_id = '. _uid)) {
               
		   if (db_li('INSERT INTO {SQL_TABLE_PREFIX}karma_rate_track (msg_id, user_id, poster_id, stamp, rating) VALUES('. $msg .', '. _uid .', '. $poster_id[0] .', '. __request_timestamp__ .', '. $rt .')', $ef)) {

			$new_karma = (int)$karma[0] + $rt;
			q('UPDATE {SQL_TABLE_PREFIX}users SET karma='. $new_karma .' WHERE id='. $poster_id[0]);

                   }
		} else { /* user already voted, don't change karma */
		   $new_karma = (int)$karma[0];
		}
		if ($is_a) {
		  $MOD = 1;
		} else {
		  $MOD = 0;
		}

		$obj = new StdClass;
		$obj->id = $msg;
		$obj->karma = $new_karma;
		exit('{TEMPLATE: karma_show}');
	}

