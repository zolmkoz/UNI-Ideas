<?php
/**
* copyright            : (C) 2001-2017 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id$
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function tmpl_draw_select_opt($values, $names, $selected)
{
	$vls = explode("\n", $values);
	$nms = explode("\n", $names);

	if (count($vls) != count($nms)) {
		exit("FATAL ERROR: inconsistent number of values inside a select<br />\n");
	}

	$options = '';
	foreach ($vls as $k => $v) {
		$options .= '<option value="'.$v.'"'.($v == $selected ? ' selected="selected"' : '' )  .'>'.$nms[$k].'</option>';
	}

	return $options;
}$folders = array(1=>'Inbox', 2=>'Saved', 4=>'Draft', 3=>'Sent', 5=>'Trash');

function tmpl_cur_ppage($folder_id, $folders, $msg_subject='')
{
	if (!$folder_id || (!$msg_subject && $_GET['t'] == 'ppost')) {
		$user_action = 'Writing a Private Message';
	} else {
		$user_action = $msg_subject ? '<a href="/uni-ideas/index.php?t=pmsg&amp;folder_id='.$folder_id.'&amp;'._rsid.'">'.$folders[$folder_id].'</a> &raquo; '.$msg_subject : 'Browsing <b>'.$folders[$folder_id].'</b> folder';
	}

	return '<span class="GenText"><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'">Private Messaging</a>&nbsp;&raquo;&nbsp;'.$user_action.'</span><br /><img src="/uni-ideas/blank.gif" alt="" height="4" width="1" /><br />';
}$GLOBALS['recv_user_id'] = array();

class fud_pmsg
{
	var	$id, $to_list, $ouser_id, $duser_id, $pdest, $ip_addr, $host_name, $post_stamp, $icon, $fldr,
		$subject, $attach_cnt, $pmsg_opt, $length, $foff, $login, $ref_msg_id, $body;

	function add($track='')
	{
		$this->post_stamp = __request_timestamp__;
		$this->ip_addr = get_ip();
		$this->host_name = $GLOBALS['FUD_OPT_1'] & 268435456 ? _esc(get_host($this->ip_addr)) : 'NULL';

		if ($this->fldr != 1) {
			$this->read_stamp = $this->post_stamp;
		}

		if ($GLOBALS['FUD_OPT_3'] & 32768) {
			$this->foff = $this->length = -1;
		} else {
			list($this->foff, $this->length) = write_pmsg_body($this->body);
		}

		$this->id = db_qid('INSERT INTO fud30_pmsg (
			ouser_id,
			duser_id,
			pdest,
			to_list,
			ip_addr,
			host_name,
			post_stamp,
			icon,
			fldr,
			subject,
			attach_cnt,
			read_stamp,
			ref_msg_id,
			foff,
			length,
			pmsg_opt
			) VALUES(
				'. $this->ouser_id .',
				'. ($this->duser_id ? $this->duser_id : $this->ouser_id) .',
				'. (isset($GLOBALS['recv_user_id'][0]) ? (int)$GLOBALS['recv_user_id'][0] : '0') .',
				'. ssn($this->to_list) .',
				\''. $this->ip_addr .'\',
				'. $this->host_name .',
				'. $this->post_stamp .',
				'. ssn($this->icon) .',
				'. $this->fldr .',
				'. _esc($this->subject) .',
				'. (int)$this->attach_cnt .',
				'. $this->read_stamp .',
				'. ssn($this->ref_msg_id) .',
				'. (int)$this->foff .',
				'. (int)$this->length .',
				'. $this->pmsg_opt .'
			)');

		if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
			$fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc($this->body) .')');
			q('UPDATE fud30_pmsg SET length='. $fid .' WHERE id='. $this->id);
		}

		if ($this->fldr == 3 && !$track) {
			$this->send_pmsg();
		}
	}

	function send_pmsg()
	{
		$this->pmsg_opt |= 16|32;
		$this->pmsg_opt &= 16|32|1|2|4;

		foreach($GLOBALS['recv_user_id'] as $v) {
			$id = db_qid('INSERT INTO fud30_pmsg (
				to_list,
				ouser_id,
				ip_addr,
				host_name,
				post_stamp,
				icon,
				fldr,
				subject,
				attach_cnt,
				foff,
				length,
				duser_id,
				ref_msg_id,
				pmsg_opt
			) VALUES (
				'. ssn($this->to_list).',
				'. $this->ouser_id .',
				\''. $this->ip_addr .'\',
				'. $this->host_name .',
				'. $this->post_stamp .',
				'. ssn($this->icon) .',
				1,
				'. _esc($this->subject) .',
				'. (int)$this->attach_cnt .',
				'. $this->foff .',
				'. $this->length .',
				'. $v .',
				'. ssn($this->ref_msg_id) .',
				'. $this->pmsg_opt .')');

			if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
				$fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc($this->body) .')');
				q('UPDATE fud30_pmsg SET length='. $fid .' WHERE id='. $id);
			}

			$GLOBALS['send_to_array'][] = array($v, $id);
			$um[$v] = $id;
		}
		$c =  uq('SELECT id, email FROM fud30_users WHERE id IN('. implode(',', $GLOBALS['recv_user_id']) .') AND users_opt>=64 AND '. q_bitand('users_opt', 64) .' > 0');

		$from = reverse_fmt($GLOBALS['usr']->alias);
		$subject = reverse_fmt($this->subject);

		while ($r = db_rowarr($c)) {
			/* Do not send notifications about messages sent to self. */
			if ($r[0] == $this->ouser_id) {
				continue;
			}
			send_pm_notification($r[1], $um[$r[0]], $subject, $from);
		}
		unset($c);
	}

	function sync()
	{
		$this->post_stamp = __request_timestamp__;
		$this->ip_addr    = get_ip();
		$this->host_name  = $GLOBALS['FUD_OPT_1'] & 268435456 ? _esc(get_host($this->ip_addr)) : 'NULL';

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			if ($fid = q_singleval('SELECT length FROM fud30_pmsg WHERE id='. $this->id .' AND foff!=-1')) {
				q('DELETE FROM fud30_msg_store WHERE id='. $this->length);
			}
			$this->foff = $this->length = -1;
		} else {
			list($this->foff, $this->length) = write_pmsg_body($this->body);
		}

		q('UPDATE fud30_pmsg SET
			to_list='. ssn($this->to_list) .',
			icon='. ssn($this->icon) .',
			ouser_id='. $this->ouser_id .',
			duser_id='. $this->ouser_id .',
			post_stamp='. $this->post_stamp .',
			subject='. _esc($this->subject) .',
			ip_addr=\''. $this->ip_addr .'\',
			host_name='. $this->host_name .',
			attach_cnt='. (int)$this->attach_cnt .',
			fldr='. $this->fldr .',
			foff='. (int)$this->foff .',
			length='. (int)$this->length .',
			pmsg_opt='. $this->pmsg_opt .'
		WHERE id='. $this->id);

		if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
			$fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc($this->body) .')');
			q('UPDATE fud30_pmsg SET length='. $fid .' WHERE id='. $this->id);
		}

		if ($this->fldr == 3) {
			$this->send_pmsg();
		}
	}
}

function write_pmsg_body($text)
{
	if (($ll = !db_locked())) {
		db_lock('fud30_fl_pm WRITE');
	}

	$fp = fopen($GLOBALS['MSG_STORE_DIR'] .'private', 'ab');
	if (!$fp) {
		exit("FATAL ERROR: cannot open private message store<br />\n");
	}

	fseek($fp, 0, SEEK_END);
	if (!($s = ftell($fp))) {
		$s = __ffilesize($fp);
	}

	if (($len = fwrite($fp, $text)) !== strlen($text)) {
		exit("FATAL ERROR: system has ran out of disk space<br />\n");
	}
	fclose($fp);

	if ($ll) {
		db_unlock();
	}

	if (!$s) {
		@chmod($GLOBALS['MSG_STORE_DIR'] .'private', ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0644));
	}

	return array($s, $len);
}

function read_pmsg_body($offset, $length)
{
	if ($length < 1) {
		return;
	}

	if ($GLOBALS['FUD_OPT_3'] & 32768 && $offset == -1) {
		return q_singleval('SELECT data FROM fud30_msg_store WHERE id='. $length);
	}

	$fp = fopen($GLOBALS['MSG_STORE_DIR'].'private', 'rb');
	fseek($fp, $offset, SEEK_SET);
	$str = fread($fp, $length);
	fclose($fp);

	return $str;
}

function pmsg_move($mid, $fid, $validate)
{
	if (!$validate && !q_singleval('SELECT id FROM fud30_pmsg WHERE duser_id='. _uid .' AND id='. $mid)) {
		return;
	}

	q('UPDATE fud30_pmsg SET fldr='. $fid .' WHERE duser_id='. _uid .' AND id='. $mid);
}

function pmsg_del($mid, $fldr=0)
{
	if (!$fldr && !($fldr = q_singleval('SELECT fldr FROM fud30_pmsg WHERE duser_id='. _uid .' AND id='. $mid))) {
		return;
	}

	if ($fldr != 5) {
		pmsg_move($mid, 5, 0);
	} else {
		if ($GLOBALS['FUD_OPT_3'] & 32768 && ($fid = q_singleval('SELECT length FROM fud30_pmsg WHERE id='. $mid .' AND foff=-1'))) {
			q('DELETE FROM fud30_msg_store WHERE id='. $fid);
		}
		q('DELETE FROM fud30_pmsg WHERE id='.$mid);
		$c = uq('SELECT id FROM fud30_attach WHERE message_id='. $mid .' AND attach_opt=1');
		while ($r = db_rowarr($c)) {
			@unlink($GLOBALS['FILE_STORE'] . $r[0] .'.atch');
		}
		unset($c);
		q('DELETE FROM fud30_attach WHERE message_id='. $mid .' AND attach_opt=1');
	}
}

function send_pm_notification($email, $pid, $subject, $from)
{
	send_email($GLOBALS['NOTIFY_FROM'], $email, '['.$GLOBALS['FORUM_TITLE'].'] New Private Message Notification', 'You have a new private message titled "'.$subject.'", from "'.$from.'", in the forum "'.$GLOBALS['FORUM_TITLE'].'".\nTo view the message, click here: http://localhost/uni-ideas/index.php?t=pmsg_view&id='.$pid.'\n\nTo stop future notifications, disable "Private Message Notification" in your profile.');
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
}include $GLOBALS['FORUM_SETTINGS_PATH'] .'ip_filter_cache';
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'login_filter_cache';
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'email_filter_cache';

function is_ip_blocked($ip)
{
	if (empty($GLOBALS['__FUD_IP_FILTER__'])) {
		return;
	}
	$block =& $GLOBALS['__FUD_IP_FILTER__'];
	list($a,$b,$c,$d) = explode('.', $ip);

	if (!isset($block[$a])) {
		return;
	}
	if (isset($block[$a][$b][$c][$d])) {
		return 1;
	}

	if (isset($block[$a][256])) {
		$t = $block[$a][256];
	} else if (isset($block[$a][$b])) {
		$t = $block[$a][$b];
	} else {
		return;
	}

	if (isset($t[$c])) {
		$t = $t[$c];
	} else if (isset($t[256])) {
		$t = $t[256];
	} else {
		return;
	}

	if (isset($t[$d]) || isset($t[256])) {
		return 1;
	}
}

function is_login_blocked($l)
{
	foreach ($GLOBALS['__FUD_LGN_FILTER__'] as $v) {
		if (preg_match($v, $l)) {
			return 1;
		}
	}
	return;
}

function is_email_blocked($addr)
{
	if (empty($GLOBALS['__FUD_EMAIL_FILTER__'])) {
		return;
	}
	$addr = strtolower($addr);
	foreach ($GLOBALS['__FUD_EMAIL_FILTER__'] as $k => $v) {
		if (($v && (strpos($addr, $k) !== false)) || (!$v && preg_match($k, $addr))) {
			return 1;
		}
	}
	return;
}

function is_allowed_user(&$usr, $simple=0)
{
	/* Check if the ban expired. */
	if (($banned = $usr->users_opt & 65536) && $usr->ban_expiry && $usr->ban_expiry < __request_timestamp__) {
		q('UPDATE fud30_users SET users_opt = '. q_bitand('users_opt', ~65536) .' WHERE id='. $usr->id);
		$usr->users_opt ^= 65536;
		$banned = 0;
	} 

	if ($banned || is_email_blocked($usr->email) || is_login_blocked($usr->login) || is_ip_blocked(get_ip())) {
		$ban_expiry = (int) $usr->ban_expiry;
		$ban_reason = $usr->ban_reason;
		if (!$simple) { // On login page we already have anon session.
			ses_delete($usr->sid);
			$usr = ses_anon_make();
		}
		setcookie($GLOBALS['COOKIE_NAME'].'1', 'd34db33fd34db33fd34db33fd34db33f', ($ban_expiry ? $ban_expiry : (__request_timestamp__ + 63072000)), $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
		if ($banned) {
			error_dialog('ERROR: You have been banned.', 'Your account was '.($ban_expiry ? 'temporarily banned until '.utf8_encode(strftime('%a, %d %B %Y %H:%M', $ban_expiry)) : 'permanently banned' )  .' from accessing the site, due to a violation of the forum&#39;s rules.
<br />
<br />
<span class="GenTextRed">'.$ban_reason.'</span>');
		} else {
			error_dialog('ERROR: Your account has been filtered out.', 'Your account has been blocked from accessing the forum due to one of the installed user filters.');
		}
	}

	if ($simple) {
		return;
	}

	if ($GLOBALS['FUD_OPT_1'] & 1048576 && $usr->users_opt & 262144) {
		error_dialog('ERROR: Your account is not yet confirmed', 'We have not received a confirmation from your parent and/or legal guardian, which would allow you to post messages. If you lost your COPPA form, <a href="/uni-ideas/index.php?t=coppa_fax&amp;'._rsid.'">view it again</a>.');
	}

	if ($GLOBALS['FUD_OPT_2'] & 1 && !($usr->users_opt & 131072)) {
		std_error('emailconf');
	}

	if ($GLOBALS['FUD_OPT_2'] & 1024 && $usr->users_opt & 2097152) {
		error_dialog('Unverified Account', 'The administrator had chosen to review all accounts manually prior to activation. Until your account has been validated by the administrator you will not be able to utilize the full capabilities of your account.');
	}
}function validate_email($email)
{
	$bits = explode('@', $email);
	if (count($bits) != 2) {
		return 1;
	}
	$doms = explode('.', $bits[1]);
	$last = array_pop($doms);

	// Validate domain extension 2-4 characters A-Z
	if (!preg_match('!^[A-Za-z]{2,4}$!', $last)) {
		return 1;
	}

	// (Sub)domain name 63 chars long max A-Za-z0-9_
	foreach ($doms as $v) {
		if (!$v || strlen($v) > 63 || !preg_match('!^[A-Za-z0-9_-]+$!', $v)) {
			return 1;
		}
	}

	// Now the hard part, validate the e-mail address itself.
	if (!$bits[0] || strlen($bits[0]) > 255 || !preg_match('!^[-A-Za-z0-9_.+{}~\']+$!', $bits[0])) {
		return 1;
	}
}

function encode_subject($text)
{
	/* HTML entities check. */
	if (strpos($subj, '&') !== false) {
		$subj = html_entity_decode($subj);
	}

	$text = htmlspecialchars($text);  // Prevent XSS like <img src="1" onerror="alert()">
	
	if (preg_match('![\x7f-\xff]!', $text)) {
		$text = '=?utf-8?B?'. base64_encode($text) .'?=';
	}

	return $text;
}

function send_email($from, $to, $subj, $body, $header='', $munge_newlines=1)
{
	if (empty($to)) {
		return 0;
	}

	if ($header) {
		$header = "\n" . str_replace("\r", '', $header);
	}
	$extra_header = '';
	if (strpos($header, 'MIME-Version') === false) {
		$extra_header = "\nMIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit". $header;
	}
	$addronly = preg_replace('/.*</', '<', $from);	// RFC 2822 Return-Path: <...>
	$header = 'From: '. $from ."\nReturn-Path: ". $addronly ."\nUser-Agent: FUDforum/". $GLOBALS['FORUM_VERSION'] . $extra_header . $header;

	$subj = encode_subject($subj);
	$body = str_replace("\r", '', $body);
	if ($munge_newlines) {
		$body = str_replace('\n', "\n", $body);
	}

	// Call PRE mail plugins.
	if (defined('plugins')) {
		list($to, $subj, $body, $header) = plugin_call_hook('PRE_MAIL', array($to, $subj, $body, $header));
	}

	if (defined('fud_logging')) {
		if (!function_exists('logaction')) {
			fud_use('logaction.inc');
		}
		logaction(_uid, 'SEND EMAIL', 0, 'To=['. implode(',', (array)$to) .']<br />Subject=['. $subj .']<br />Headers=['. str_replace("\n", '<br />', htmlentities($header)) .']<br />Message=['. $body .']');
	}

	if ($GLOBALS['FUD_OPT_1'] & 512) {
		if (!class_exists('fud_smtp')) {
			fud_use('smtp.inc');
		}
		$smtp = new fud_smtp;
		$smtp->msg = str_replace(array('\n', "\n."), array("\n", "\n.."), $body);
		$smtp->subject = $subj;
		$smtp->to = $to;
		$smtp->from = $from;
		$smtp->headers = $header;
		$smtp->send_smtp_email();
		return 1;
	}

	foreach ((array)$to as $email) {
		if (!@mail($email, $subj, $body, $header)) {
			fud_logerror('Your system didn\'t accept E-mail ['. $subj .'] to ['. $email .'] for delivery.', 'fud_errors', $header ."\n\n". $body);
			return -1;
		}
	}
	
	return 1;
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
}function get_host($ip)
{
	if (!$ip || $ip == '0.0.0.0') {
		return;
	}

	$name = gethostbyaddr($ip);

	if ($name == $ip) {
		$name = substr($name, 0, strrpos($name, '.')) .'*';
	} else if (substr_count($name, '.') > 1) {
		$name = '*'. substr($name, strpos($name, '.')+1);
	}

	return $name;
}class fud_smtp
{
	var $fs, $last_ret, $msg, $subject, $to, $from, $headers;

	function get_return_code($cmp_code='250')
	{
		if (!($this->last_ret = @fgets($this->fs, 515))) {
			return;
		}
		if ((int)$this->last_ret == $cmp_code) {
			return 1;
		}
		return;
	}

	function wts($string)
	{
		/* Write to stream. */
		fwrite($this->fs, $string ."\r\n");
	}

	function open_smtp_connex()
	{
		if( !($this->fs = @fsockopen($GLOBALS['FUD_SMTP_SERVER'], $GLOBALS['FUD_SMTP_PORT'], $errno, $errstr, $GLOBALS['FUD_SMTP_TIMEOUT'])) ) {
			fud_logerror('ERROR: SMTP server at '. $GLOBALS['FUD_SMTP_SERVER'] ." is not available<br />\n". ($errno ? "Additional Problem Info: $errno -> $errstr <br />\n" : ''), 'fud_errors');
			return;
		}
		if (!$this->get_return_code(220)) {	// 220 == Ready to speak SMTP.
			return;
		}

		$es = strpos($this->last_ret, 'ESMTP') !== false;
		$smtp_srv = $_SERVER['SERVER_NAME'];
		if ($smtp_srv == 'localhost' || $smtp_srv == '127.0.0.1' || $smtp_srv == '::1') {
			$smtp_srv = 'FUDforum SMTP server';
		}

		$this->wts(($es ? 'EHLO ' : 'HELO ') . $smtp_srv);
		if (!$this->get_return_code()) {
			return;
		}

		/* Scan all lines and look for TLS support. */
		$tls = false;
		if ($es) {
			while($str = @fgets($this->fs, 515)) {
				if (substr($str, 0, 12) == '250-STARTTLS') $tls = true;
				if (substr($str, 3,  1) == ' ') break;	// Done reading if 4th char is a space.

			}
		}

		/* Do SMTP Auth if needed. */
		if ($GLOBALS['FUD_SMTP_LOGIN']) {
			if ($tls) {
				/*  Initiate TSL communication with server. */
				$this->wts('STARTTLS');
				if (!$this->get_return_code(220)) {
					return;
				}
				/* Encrypt the connection. */
				if (!stream_socket_enable_crypto($this->fs, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
					return false;
				} 
				/* Say hi again. */
				$this->wts(($es ? 'EHLO ' : 'HELO ').$smtp_srv);
				if (!$this->get_return_code()) {
					return;
				}
				/* we need to scan all other lines */
				while($str = @fgets($this->fs, 515)) {
					if (substr($str, 3, 1) == ' ') break;
				}
			}

			$this->wts('AUTH LOGIN');
			if (!$this->get_return_code(334)) {
				return;
			}
			$this->wts(base64_encode($GLOBALS['FUD_SMTP_LOGIN']));
			if (!$this->get_return_code(334)) {
				return;
			}
			$this->wts(base64_encode($GLOBALS['FUD_SMTP_PASS']));
			if (!$this->get_return_code(235)) {
				return;
			}
		}

		return 1;
	}

	function send_from_hdr()
	{
		$this->wts('MAIL FROM: <'. $GLOBALS['NOTIFY_FROM'] .'>');
		return $this->get_return_code();
	}

	function send_to_hdr()
	{
		$this->to = (array) $this->to;

		foreach ($this->to as $to_addr) {
			$this->wts('RCPT TO: <'. $to_addr .'>');
			if (!$this->get_return_code()) {
				return;
			}
		}
		return 1;
	}

	function send_data()
	{
		$this->wts('DATA');
		if (!$this->get_return_code(354)) {
			return;
		}

		/* This is done to ensure what we comply with RFC requiring each line to end with \r\n */
		$this->msg = preg_replace('!(\r)?\n!si', "\r\n", $this->msg);

		if( empty($this->from) ) $this->from = $GLOBALS['NOTIFY_FROM'];

		$this->wts('Subject: '. $this->subject);
		$this->wts('Date: '. date('r'));
		$this->wts('To: '. (count($this->to) == 1 ? $this->to[0] : $GLOBALS['NOTIFY_FROM']));
		$this->wts($this->headers ."\r\n");
		$this->wts($this->msg);
		$this->wts('.');

		return $this->get_return_code();
	}

	function close_connex()
	{
		$this->wts('QUIT');
		fclose($this->fs);
	}

	function send_smtp_email()
	{
		if (!$this->open_smtp_connex()) {
			if ($this->last_ret) {
				fud_logerror('Open SMTP connection - invalid return code: '. $this->last_ret, 'fud_errors');
			}
			return false;
		}
		if (!$this->send_from_hdr()) {
			fud_logerror('Send "From:" header - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}
		if (!$this->send_to_hdr()) {
			fud_logerror('Send "To:" header - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}
		if (!$this->send_data()) {
			fud_logerror('Send data - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}

		$this->close_connex();
		return true;
	}
}

	// PM_ENABLED?
	if (!($FUD_OPT_1 & 1024)) {
		error_dialog('ERROR: Private Messaging Disabled', 'You cannot use the private messaging system. It has been disabled by the administrator.');
	}

	if (__fud_real_user__) {
		is_allowed_user($usr);
	} else {
		std_error('login');
	}

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
			$tabs .= $pg == $tab ? '' : '
			<td  style="border: none"><div style="background-color: #fff;border: none;margin-left: 5px 20px">&ensp;|&ensp;
			<a style="text-decoration: none; color: #000; font-weight: bold;font-size: 17px;border: none;width: 100px" href="'.$tab_url.'">'.$tab_name.'</a></div></td>';
		}

		$tabs = '<table cellspacing="1" cellpadding="0" style="border: 1px solid #ccc">
					<tr style="border: none">
						'.$tabs.'
					</tr>
</table>';
	}
}

	/* Empty trash. */
	if (isset($_POST['btn_trash'])) {
		$c = q('SELECT id FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=5');
		while ($r = db_rowarr($c)) {
			pmsg_del((int)$r[0], 5);
		}
		unset($c, $_POST['sel'], $_GET['sel']); /* Prevent message selection cofusion. */
		if (isset($_POST['old_folder_id'])) {
			$_GET['folder_id'] = $_POST['old_folder_id'];
		}
	}

	/* Moving or deleting a message. */
	if (isset($_POST['sel']) || isset($_GET['sel'])) {
		if (!empty($_POST['btn_pdf'])) {
			header('Location: /uni-ideas/pdf.php?sel[]='. implode('&sel[]=', $_POST['sel']) .'&'. _rsidl);
			exit;
		}
		$sel = isset($_POST['sel']) ? (array)$_POST['sel'] : (array)$_GET['sel'];
		$move_to = (!isset($_POST['btn_delete']) && isset($_POST['moveto'], $folders[$_POST['moveto']])) ? (int) $_POST['moveto'] : 0;

		if (!$move_to && !isset($_POST['old_folder_id'])) {
			$_POST['old_folder_id'] = q_singleval('SELECT fldr FROM fud30_pmsg WHERE id='. (int)$sel[0]);
		}

		foreach ($sel as $m) {
			if ($move_to) {
				pmsg_move((int)$m, $move_to, 0);
			} else {
				pmsg_del((int)$m);
			}
		}
		if (!$move_to && isset($_POST['old_folder_id'])) {
			$_GET['folder_id'] = $_POST['old_folder_id'];
		}
	}

	if (isset($_GET['folder_id'], $folders[(int)$_GET['folder_id']])) {
		$folder_id = (int) $_GET['folder_id'];
	} else if (isset($_POST['folder_id'], $folders[(int)$_POST['folder_id']])) {
		$folder_id = (int) $_POST['folder_id'];
	} else {
		$folder_id = 1;
	}

	ses_update_status($usr->sid, 'Using private messaging');

	$select_options_cur_folder = tmpl_draw_select_opt(implode("\n", array_keys($folders)), implode("\n", $folders), $folder_id);

	if ($GLOBALS['FUD_OPT_3'] & 32768) {
		$disk_usage  = q_singleval('SELECT SUM(length) FROM fud30_pmsg WHERE foff>0 AND duser_id='. _uid);
		$disk_usage += q_singleval('SELECT SUM(LENGTH(data)) FROM fud30_pmsg p INNER JOIN fud30_msg_store m ON p.length=m.id WHERE foff<0 AND duser_id='. _uid);
	} else {
		$disk_usage = q_singleval('SELECT SUM(length) FROM fud30_pmsg WHERE duser_id='. _uid);
	}
	if ($usr->users_opt & 524288) {
		$ms = $MAX_PMSG_FLDR_SIZE_PM;
	} else if ($usr->users_opt & 1048576) {
		$ms = $MAX_PMSG_FLDR_SIZE_AD;
	} else {
		$ms = $MAX_PMSG_FLDR_SIZE;
	}

	$percent_full = ceil($disk_usage / $ms * 100);
	$full_indicator = ceil($percent_full * 1.69);

	if ($percent_full < 90) {
		$full_indicator = '<td class="pmSn"><img src="/uni-ideas/blank.gif" alt="" width="'.$full_indicator.'" height="8" /></td>';
	} else if ($percent_full >= 90 && $percent_full < 100) {
		$full_indicator = '<td class="pmSa"><img src="/uni-ideas/blank.gif" alt="" width="'.$full_indicator.'" height="8" /></td>';
	} else {
		$full_indicator = '<td class="pmSf"><img src="/uni-ideas/blank.gif" alt="" width="'.$full_indicator.'" height="8" /></td>';
	}

	// Sort field.
	if (isset($_GET['o'])) {
		switch ($_GET['o']) {
			case 'subject':
				$o = 'p.subject';
				break;
			case 'alias':
				$o = 'u.alias';
				break;
			case 'alias2':
				$o = 'u2.alias';
				break;
			default:
				$o = 'p.post_stamp';
		}
	} else {
		$o = 'post_stamp';
	}

	// Sort order.
	if (isset($_GET['s']) && $_GET['s'] == 'a') {
		$s = 'ASC';
	} else {
		$s = 'DESC';
	}

	$ttl = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr='. $folder_id);
	$count = $usr->posts_ppg ? $usr->posts_ppg : $POSTS_PER_PAGE;
	$start = (empty($_GET['start']) || $_GET['start'] >= $ttl) ? 0 : (int)$_GET['start'];

	$c = uq(q_limit('SELECT p.id, p.read_stamp, p.post_stamp, p.duser_id, p.ouser_id, p.subject, p.pmsg_opt, p.fldr, p.pdest, p.to_list,
			u.users_opt, u.alias, u.last_visit AS time_sec,
			u2.users_opt AS users_opt2, u2.alias AS alias2, u2.last_visit AS time_sec2
		FROM fud30_pmsg p
		INNER JOIN fud30_users u ON p.ouser_id=u.id
		LEFT JOIN fud30_users u2 ON p.pdest=u2.id
		WHERE duser_id='. _uid .' AND fldr='. $folder_id .' ORDER BY '. $o .' '. $s,
		$count, $start));

	$private_msg_entry = '';
	while ($obj = db_rowobj($c)) {
		switch ($obj->fldr) {
			case 1:
			case 2:
				$action = '<a href="/uni-ideas/index.php?t=ppost&amp;'._rsid.'&amp;reply='.$obj->id.'"><img width="110px" height="40px" src="/uni-ideas/theme/default/images/icon/reply.png" alt="" /></a>&nbsp;<a href="/uni-ideas/index.php?t=ppost&amp;quote='.$obj->id.'&amp;'._rsid.'"><img width="120px" height="40px" src="/uni-ideas/theme/default/images/icon/qqqqqo.png" alt="" /></a>&nbsp;<a href="/uni-ideas/index.php?t=ppost&amp;forward='.$obj->id.'&amp;'._rsid.'"><img width="120px" height="40px" src="/uni-ideas/theme/default/images/icon/forward.png" alt="" /></a>';
				break;
			case 3:
				$obj->users_opt = $obj->users_opt2;
				$obj->alias = $obj->alias2;
				$obj->time_sec = $obj->time_sec2;
				$obj->ouser_id = $obj->pdest;
				$action = '';
				break;
			case 5:
				$action = '<a href="/uni-ideas/index.php?t=ppost&amp;'._rsid.'&amp;forward='.$obj->id.'"><img src="/uni-ideas/theme/default/images/msg_forward.gif" alt="" /></a>';
				break;
			case 4:
				$action = '<a href="/uni-ideas/index.php?t=ppost&amp;'._rsid.'&amp;msg_id='.$obj->id.'"><img src="/uni-ideas/theme/default/images/msg_edit.gif" alt="" /></a>';
				break;
		}

		if ($FUD_OPT_2 & 32768 && !empty($_SERVER['PATH_INFO'])) {
			$goto = $folder_id != 4 ? '/uni-ideas/index.php/pmv/'. $obj->id .'/'. _rsid : '/uni-ideas/index.php/pmm/msg_id/'. $obj->id .'/'. _rsid;
		} else {
			$goto = $folder_id != 4 ? '/uni-ideas/index.php?t=pmsg_view&amp;'. _rsid .'&amp;id='. $obj->id : '/uni-ideas/index.php?t=ppost&amp;'. _rsid .'&amp;msg_id='. $obj->id;
		}


		if ($obj->pmsg_opt & 64) {
			$msg_type ='<span class="SmallText">(replied)</span>&nbsp;';
		} else if ($obj->pmsg_opt & 32) {
			$msg_type = '';
		} else {
			$msg_type ='<span class="SmallText">(forwarded)</span>&nbsp;';
		}

		$private_msg_entry .= '
		
		<div class="w3-responsive">
			<table class="w3-table-all" style="border: none; background-color:#fff">
				<tr>
					<td width="5%" ><input type="checkbox" name="sel[]" value="'.$obj->id.'" /></td>
					<td width="30%">'.$msg_type.'<a style="text-decoration: none; color: #0F2026; font-weight: bold;font-size: 15px" href="'.$goto.'">'.$obj->subject.'</a>'.(($obj->pmsg_opt & 4 && $obj->pmsg_opt & 16 && $obj->duser_id == _uid && $obj->ouser_id != _uid) ? '<span class="SmallText">&nbsp;&nbsp;[<a href="/uni-ideas/index.php?t=pmsg_view&amp;'._rsid.'&amp;dr=1&amp;id='.$obj->id.'" title="Do not send a confirmation that you&#39;ve read this message">deny receipt</a>]</span>' : '' ) .'</td>
					<td width="10%"><span class="glyphicon glyphicon-cloud-upload" style="color: #FA4D1D; font-size: 14px;">&nbsp;</span><a style="text-decoration: none; color: #0F2026; font-weight: bold;font-size: 15px" href="/uni-ideas/index.php?t=usrinfo&amp;'._rsid.'&amp;id='.$obj->ouser_id.'" title="'.char_fix(htmlspecialchars($obj->to_list)).'">'.filter_var($obj->alias, FILTER_SANITIZE_STRING).'</a></td>
					<td width="18%" style="text-decoration: none; color: #0F2026; font-weight: bold;font-size: 15px;"><span class="glyphicon glyphicon-time" style="color: #FA4D1D; font-size: 14px;">&nbsp;</span>'.utf8_encode(strftime('%a, %d %B %Y %H:%M', $obj->post_stamp)).'</td>
					<td width="18%">'.$action.'</td>
				</tr>
			</table>
		</div>
		';
	}
	unset($c);

	if (!$private_msg_entry) {
		$private_msg_entry = '<tr class="RowStyleC"><td colspan="6" class="ac">There are no messages in this folder.</td></tr>';
		$private_tools = '';
	} else {
		if ($folder_id == 5) {
			$btn_action = 'Restore To:';
			$btn_del_name = 'btn_trash';
			$btn_del_title = 'Empty Trash';
		} else {
			$btn_action = 'Move To:';
			$btn_del_name = 'btn_delete';
			$btn_del_title = 'Delete';
		}
		$tmp = $folders;
		unset($tmp[$folder_id]);
		$moveto_list = tmpl_draw_select_opt(implode("\n", array_keys($tmp)), implode("\n", $tmp), 2);
		$private_tools = '
		<table class="w3-table-all" style="border: none;margin-left:80%">
			<tr style="border: none">
				<td colspan="3" >
					 <input type="submit" style="width: 100px; height: 35px; border: 1px solid #CF0A0A; background-color: #CF0A0A;border-radius: 10px; font-weight: bold; font-size: 17px;color:#fff" class="bnt" name="'.$btn_del_name.'" value="'.$btn_del_title.'" />'.($FUD_OPT_2 & 2097152 ? 
					'<input type="submit" style="width: 170px; height: 35px; margin-left: 10px; border: 1px solid #39AEA9; background-color: #39AEA9;border-radius: 10px; font-weight: bold; font-size: 17px;color:#fff" class="bnt" name="btn_pdf" value="Make PDF Archive" />' : '' )  .'
				</td>
			</tr>
			<tr>
			</tr>
		</table>
		';
	}

	if ($FUD_OPT_2 & 32768) {
		$page_pager = tmpl_create_pager($start, $count, $ttl, '/uni-ideas/index.php/pdm/'. $folder_id .'/0/'. strtolower($s[0]) .'/'. $o .'/', '/'. _rsid);
	} else {
		$page_pager = tmpl_create_pager($start, $count, $ttl, '/uni-ideas/index.php?t=pmsg&amp;s='. strtolower($s[0]) .'&amp;o='. $o .'&amp;folder_id='. $folder_id .'&amp;'. _rsid);
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
	<link rel="stylesheet" href="/UNI-Ideas/theme/default/forum.css">
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
		.table-1 {
			width: 100%;
			background-color: #eee;
			border: none;
			color: #fff;
		}
		.hero-image {
			background-image: url("/uni-ideas/theme/default/images/3.png");
			height: 800px;
			
			background-repeat: no-repeat;
			background-size: 100%;
			position: relative;
		}

		.hero-text {
			text-align: center;
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			color: white;
		}
		.bnt-find{
			color: white;
			background-color: #0F2026;
			font-size: 20px;
			border-radius: 10px;
			width: 85px;
			height: 25px;
		}
		.bnt-find:hover{
			background-color: #fa4d1d;
		}

		td.pmSn {
			background: #E64848;
		}

		td.pmSa {
			background: #C21010;
		}

		td.pmSf {
			background: #FF1E00;
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

		<?php echo ($FUD_OPT_4 & 8 ? '<div class="menu"><a href="/uni-ideas/index.php?t=page&amp;'._rsid.'" title="Pages"><img src="/uni-ideas/theme/default/images/pages.png" alt="" width="16" height="16" /> Pages</a></div>' : ''); ?>
		<?php echo ($FUD_OPT_3 & 134217728 ? '<div class="menu"><a href="/uni-ideas/index.php?t=cal&amp;'._rsid.'" title="Calendar"><img src="/uni-ideas/theme/default/images/calendar.png" alt="" width="16" height="16" /> Calendar</a></div>' : ''); ?>
		<div class="menu"><a href="/uni-ideas/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img src="/uni-ideas/theme/default/images/icon/home.png" alt="" width="16" height="16" /> Home</a></div>
		<?php echo ($FUD_OPT_4 & 16 ? '<div class="menu"><a href="/uni-ideas/index.php?t=blog&amp;'._rsid.'" title="Blog"><img src="/uni-ideas/theme/default/images/icon/blogging.png" alt="" width="16" height="16" /> Blog</a></div>' : ''); ?>
		<?php echo ($FUD_OPT_1 & 16777216 ? ' <div class="menu"><a href="/uni-ideas/index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Search"><img src="/uni-ideas/theme/default/images/icon/magnifier.png" alt="" width="16" height="16" /> Search</a></div>' : ''); ?>
		<div class="menu"><a accesskey="h" href="/uni-ideas/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img src="/uni-ideas/theme/default/images/icon/help-web-button.png" alt="" width="16" height="16" /> Help</a></div>
		<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<div class="menu"><a href="/uni-ideas/index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Members"><img src="/uni-ideas/theme/default/images/icon/group.png" alt="" width="16" height="16" /> Members</a></div>' : ''); ?>
		<div class="menu"><?php echo $ucp_private_msg; ?></div>
		
		<?php echo (__fud_real_user__ ? '<div class="menu"><a href="/uni-ideas/index.php?t=uc&amp;'._rsid.'" title="Access the user control panel"><img src="/uni-ideas/theme/default/images/icon/home.png" alt="" width="16" height="16" /> Control Panel</a></div>' : ($FUD_OPT_1 & 2 ? '<div class="menu"><a href="/uni-ideas/index.php?t=register&amp;'._rsid.'" title="Register"><img src="/uni-ideas/theme/default/images/icon/new-user.png" alt="" width="16" height="18" /> Register</a></div>' : '')).'
		'.(__fud_real_user__ ? '<div class="menu"><a href="/uni-ideas/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Logout"><img src="/uni-ideas/theme/default/images/icon/profile-user.png" alt="" width="16" height="16" /> Logout [ '.filter_var($usr->alias, FILTER_SANITIZE_STRING).' ]</a></div>' : '<div class="menu"><a href="/uni-ideas/index.php?t=login&amp;'._rsid.'" title="Login"><img src="/uni-ideas/theme/default/images/icon/profile-user.png" alt="" width="16" height="16" /> Login</a></div>'); ?>
		<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<div class="menu"><a href="/uni-ideas/adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Administration"><img src="/uni-ideas/theme/default/images/icon/configuration.png" alt="" width="16" height="16" /> Administration</a></div>' : ''); ?>
	</ul>
</div>
<br />

<div class="hero-image">
</div>



<table cellspacing="1" cellpadding="2" class="table-1">
	<tr>
		<td colspan="3" style = "font-size:20px; color:#000;">Your private message folders are <?php echo $percent_full; ?>% full.</td>
	</tr>
	<tr>
		<td colspan="3" >
			<table cellspacing="0" cellpadding="1" border="0">
				<tr><?php echo $full_indicator; ?></tr>
			</table>
		</td>
	</tr>
	<tr >
		<td width="58" style="font-weight: bold;font-size:17px;color:#000;">0%</td>
		<td  width="58" style="font-weight: bold;font-size:17px;color:#000;">50%</td>
		<td  width="58" style="font-weight: bold;font-size:17px;color:#000;">100%</td>
	</tr>
</table>

<br/>





<form action="/uni-ideas/index.php?t=pmsg" method="post" id="priv_frm"><?php echo _hs; ?>
<table cellspacing="1" cellpadding="2" class="w3-table-all" style="border: none;padding: 0px 0px">
<tr>
	<td colspan="3" style="padding: 0px;"><?php echo ($private_tools ? '
		<a href="/uni-ideas/index.php?t=ppost&amp;<?php echo _rsid; ?>">
			<img style="width: 120px;height:50px" src="/uni-ideas/theme/default/images/icon/newmes.png" alt="" /></a>' : '&nbsp;' )  .'</td>
	<td colspan="3" style="color: #2B3467; font-weight: bold;font-size: 20px">
		Folder:
		<select style="font-size: 15px; width: 200px" name="folder_id" onchange="document.forms[\'priv_frm\'].submit();">
		'.$select_options_cur_folder.'
		</select> 
		<input type="submit" style="width:80px;height:30px;color: #0F2026;" class="button" name="sel_folder" value="Go" />
	</td>
</tr>





<tr style="background-color:#0F2026;color:#fff">
	<th width="5%" style="font-size: 20px">Select &nbsp; <input type="checkbox" name="toggle" title="all/none" onclick="jQuery(\'input:checkbox\').prop(\'checked\', this.checked);" /> </th>
	<th width="30%"><a style="text-decoration: none; color: #fff; font-weight: bold;font-size: 20px" href="/uni-ideas/index.php?t=pmsg&amp;folder_id='.$folder_id.'&amp;'._rsid.'&amp;o=subject&amp;s='.($o=='p.subject' && $s=='ASC' ? 'd' : 'a'); ?>" class="thLnk">Subject</a></th>
	<th width="10%"><a style="text-decoration: none; color: #fff; font-weight: bold;font-size: 20px" href="/uni-ideas/index.php?t=pmsg&amp;folder_id=<?php echo $folder_id; ?>&amp;<?php echo _rsid; ?>&amp;o=<?php echo ($folder_id == 3 ? 'alias2' : 'alias' )  .'&amp;s='.(($o=='u.alias' || $o=='u2.alias') && $s=='ASC' ? 'd' : 'a' )  .'" class="thLnk">'.($folder_id == 3 ? 'Recipient' : 'Author'); ?></a></th>
	<th width="18%"><a style="text-decoration: none; color: #fff; font-weight: bold;font-size: 20px" href="/uni-ideas/index.php?t=pmsg&amp;folder_id=<?php echo $folder_id; ?>&amp;<?php echo _rsid; ?>&amp;o=post_stamp&amp;s=<?php echo ($o=='p.post_stamp' && $s=='ASC' ? 'd' : 'a'); ?>" class="thLnk">Time</a></th>
	<th width="17%" style="color: #fff; font-weight: bold;font-size: 20px">Action</th>
</tr>






<?php echo $private_msg_entry; ?>
<?php echo $private_tools; ?>
</table>
</form>








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
