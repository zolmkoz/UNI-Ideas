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
}class fud_user
{
	var $id, $login, $alias, $passwd, $salt, $plaintext_passwd,
	    $name, $email, $location, $occupation, $interests, $topics_per_page,
	    $icq, $facebook, $yahoo, $jabber, $google, $skype, $twitter,
	    $avatar, $avatar_loc, $posts_ppg, $time_zone, $birthday, $home_page,
	    $sig, $bio, $posted_msg_count, $last_visit, $last_event, $conf_key,
	    $user_image, $join_date, $theme, $last_read,
	    $mod_list, $mod_cur, $level_id, $karma, $u_last_post_id, $users_opt, $cat_collapse_status,
	    $ignore_list, $buddy_list,
	    $custom_fields;
}

function make_alias($text)
{
	if (strlen($text) > $GLOBALS['MAX_LOGIN_SHOW']) {
		$text = substr($text, 0, $GLOBALS['MAX_LOGIN_SHOW']);
	}
	return char_fix(str_replace(array(']', '['), array('&#93;','&#91;'), htmlspecialchars($text)));
}

function generate_salt()
{
	return substr(md5(uniqid(mt_rand(), true)), 0, 9);
}

class fud_user_reg extends fud_user
{
	function html_fields()
	{
		foreach(array('name', 'location', 'occupation', 'interests', 'bio') as $v) {
			if ($this->{$v}) {
				$this->{$v} = char_fix(htmlspecialchars($this->$v));
			}
		}
	}

	/** Deprecated: Please use add(). */
	function add_user()
	{
		return $this->add();
	}

	/** Add a new user account. */
	function add()
	{
		// Track referer.
		if (isset($_COOKIE['frm_referer_id']) && (int)$_COOKIE['frm_referer_id']) {
			$ref_id = (int)$_COOKIE['frm_referer_id'];
		} else {
			$ref_id = 0;
		}

		// Geneate password & salt (if not supplied).
		if (empty($this->passwd) && empty($this->plaintext_passwd)) {
			$this->plaintext_passwd = substr(md5(get_random_value()), 0, 8);
		}
		if (!empty($this->plaintext_passwd)) {
			$this->salt  = generate_salt();
			$this->passwd = sha1($this->salt . sha1($this->plaintext_passwd));
		}

		$o2 =& $GLOBALS['FUD_OPT_2'];
		$this->alias = make_alias((!($o2 & 128) || !$this->alias) ? $this->login : $this->alias);

		/* This is used when utilities create users (aka nntp/mlist/xmlagg imports). */
		if ($this->users_opt == -1) {
			$this->users_opt = 4|16|128|256|512|2048|4096|8192|16384|131072|4194304;

			if (!($o2 & 4)) {	// Flat thread listing/Tree message listing.
				$this->users_opt ^= 128;	// Unset default_topic_view=MSG.
			}
			if (!($o2 & 8)) {	// Tree thread listing/Flat message listing.
				$this->users_opt ^= 256;	//  Unset default_message_view=MSG.
			}
			if ($o2 & 1) {	// Unset EMAIL_CONFIRMATION (no confirmation email now).
				$o2 ^= 1;
			}
			$registration_ip = '::1';
		} else {
			$registration_ip = get_ip();
		}

		/* No user options? Initialize to sensible values. */
		if (empty($this->users_opt)) {
			$this->users_opt = 2|4|16|32|64|128|256|512|2048|4096|8192|16384|131072|4194304;
		}

		if (empty($this->theme)) {
			$this->theme = q_singleval(q_limit('SELECT id FROM fud30_themes WHERE theme_opt>=2 AND '. q_bitand('theme_opt', 2) .' > 0', 1));
		}
		if (empty($this->topics_per_page)) {
			$this->topics_per_page = $GLOBALS['THREADS_PER_PAGE'];
		}
		if (empty($this->posts_ppg)) {
			$this->posts_ppg =& $GLOBALS['POSTS_PER_PAGE'];
		}
		if (empty($this->join_date)) {
			$this->join_date = __request_timestamp__;
		}
		if (empty($this->time_zone)) {
			$this->time_zone =& $GLOBALS['SERVER_TZ'];
		}

		if ($o2 & 1) {	// EMAIL_CONFIRMATION
			$this->conf_key = md5(implode('', (array)$this) . __request_timestamp__ . getmypid());
		} else {
			$this->conf_key = '';
			$this->users_opt |= 131072;
		}
		$this->icq = (int)$this->icq ? (int)$this->icq : 'NULL';

		$this->html_fields();

		$flag = ret_flag($registration_ip);

		$this->id = db_qid('INSERT INTO
			fud30_users (
				login,
				alias,
				passwd,
				salt,
				name,
				email,
				avatar, 
				avatar_loc,
				icq,
				facebook,
				yahoo,
				jabber,
				google,
				skype,
				twitter,
				posts_ppg,
				time_zone,
				birthday,
				last_visit,
				conf_key,
				user_image,
				join_date,
				location,
				theme,
				occupation,
				interests,
				referer_id,
				last_read,
				sig,
				home_page,
				bio,
				users_opt,
				registration_ip,
				topics_per_page,
				flag_cc,
				flag_country,
				custom_fields
			) VALUES (
				'. _esc($this->login) .',
				'. _esc($this->alias) .',
				\''. $this->passwd .'\',
				\''. $this->salt .'\',
				'. _esc($this->name) .',
				'. _esc($this->email) .',
				'. (int)$this->avatar .',
				'. ssn($this->avatar_loc) .',
				'. $this->icq .',
				'. ssn(urlencode($this->facebook)) .',
				'. ssn(urlencode($this->yahoo)) .',
				'. ssn(htmlspecialchars($this->jabber)) .',
				'. ssn($this->google) .',
				'. ssn(urlencode($this->skype)) .',
				'. ssn(urlencode($this->twitter)) .',
				'. (int)$this->posts_ppg .',
				'. _esc($this->time_zone) .',
				'. ssn($this->birthday) .',
				'. __request_timestamp__ .',
				\''. $this->conf_key .'\',
				'. ssn(htmlspecialchars($this->user_image)) .',
				'. $this->join_date .',
				'. ssn($this->location) .',
				'. (int)$this->theme .',
				'. ssn($this->occupation) .',
				'. ssn($this->interests) .',
				'. (int)$ref_id .',
				'. __request_timestamp__ .',
				'. ssn($this->sig) .',
				'. ssn(htmlspecialchars($this->home_page)) .',
				'. ssn($this->bio) .',
				'. $this->users_opt .',
				'. _esc($registration_ip) .',
				'. (int)$this->topics_per_page .',
				'. ssn($flag[0]) .',
				'. ssn($flag[1]) .',
				'. _esc($this->custom_fields) .'
			)
		');

		return $this->id;
	}

	/** Deprecated: Please use sync(). Remove in FUDforum 3.1. */
	function sync_user()
	{
		$this->sync();
	}

	/** Change a user account. */
	function sync()
	{
		if (!empty($this->plaintext_passwd)) {
			if (empty($this->salt)) {
				$this->salt = generate_salt();
			}
			$passwd = 'passwd=\''. sha1($this->salt . sha1($this->plaintext_passwd)) .'\', salt=\''. $this->salt .'\', ';
		} else {
			$passwd = '';
		}

		$this->alias = make_alias((!($GLOBALS['FUD_OPT_2'] & 128) || !$this->alias) ? $this->login : $this->alias);
		$this->icq = (int)$this->icq ? (int)$this->icq : 'NULL';

		$rb_mod_list = (!($this->users_opt & 524288) && ($is_mod = q_singleval('SELECT id FROM fud30_mod WHERE user_id='. $this->id)) && (q_singleval('SELECT alias FROM fud30_users WHERE id='. $this->id) == $this->alias));

		$this->html_fields();

		q('UPDATE fud30_users SET '. $passwd .'
			name='. _esc($this->name) .',
			alias='. _esc($this->alias) .',
			email='. _esc($this->email) .',
			icq='. $this->icq .',
			facebook='. ssn(urlencode($this->facebook)) .',
			yahoo='. ssn(urlencode($this->yahoo)) .',
			jabber='. ssn(htmlspecialchars($this->jabber)) .',
			google='. ssn($this->google) .',
			skype='. ssn(urlencode($this->skype)) .',
			twitter='. ssn(urlencode($this->twitter)) .',
			posts_ppg='. (int)$this->posts_ppg .',
			time_zone='. _esc($this->time_zone) .',
			birthday='. ssn($this->birthday) .',
			user_image='. ssn(htmlspecialchars($this->user_image)) .',
			location='. ssn($this->location) .',
			occupation='. ssn($this->occupation) .',
			interests='. ssn($this->interests) .',
			avatar='. (int)$this->avatar .',
			theme='. (int)$this->theme .',
			avatar_loc='. ssn($this->avatar_loc) .',
			sig='. ssn($this->sig) .',
			home_page='. ssn(htmlspecialchars($this->home_page)) .',
			bio='. ssn($this->bio) .',
			users_opt='. (int)$this->users_opt .',
			topics_per_page='. (int)$this->topics_per_page .',
			custom_fields='. _esc($this->custom_fields) .'
		WHERE id='. $this->id);

		if ($rb_mod_list) {
			rebuildmodlist();
		}
	}

	/** Delete a user account. */
	static function delete($id)
	{
		q('DELETE FROM fud30_users WHERE id = '. (int)$id);
	}
}

function get_id_by_email($email)
{
	return q_singleval('SELECT id FROM fud30_users WHERE email='. _esc($email));
}

function get_id_by_login($login)
{
	return q_singleval('SELECT id FROM fud30_users WHERE login='. _esc($login));
}

function usr_email_unconfirm($id)
{
	$conf_key = md5(__request_timestamp__ . $id . get_random_value());
	q('UPDATE fud30_users SET users_opt='. q_bitand('users_opt', ~131072) .', conf_key=\''. $conf_key .'\' WHERE id='. $id);

	return $conf_key;
}

function &usr_reg_get_full($id)
{
	if (($r = db_sab('SELECT * FROM fud30_users WHERE id='. $id))) {
		if (!extension_loaded('overload')) {
			$o = new fud_user_reg;
			foreach ($r as $k => $v) {
				$o->{$k} = $v;
			}
			$r = $o;
		} else {
			aggregate_methods($r, 'fud_user_reg');
		}
	}
	return $r;
}

function user_login($id, $cur_ses_id, $use_cookies)
{
	/* Remove cookie so it does not confuse us. */
	if (!$use_cookies && isset($_COOKIE[$GLOBALS['COOKIE_NAME']])) {
		setcookie($GLOBALS['COOKIE_NAME'], '', __request_timestamp__-100000, $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
	}

	/* MULTI_HOST_LOGIN */
	if ($GLOBALS['FUD_OPT_2'] & 256 && ($s = db_saq('SELECT ses_id, sys_id FROM fud30_ses WHERE user_id='.$id))) {
		if ($use_cookies) {
			setcookie($GLOBALS['COOKIE_NAME'], $s[0], __request_timestamp__+$GLOBALS['COOKIE_TIMEOUT'], $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
		}
		if ($s[1]) {
			// Clear system ID, we don't use it with MULTI_HOST_LOGIN's.
			q('UPDATE fud30_ses SET sys_id=\'\' WHERE ses_id=\''. $s[0] .'\'');
		}
		return $s[0];
	}

	/* Only 1 login per account, 'remove' all other logins. */
	q('DELETE FROM fud30_ses WHERE user_id='. $id .' AND ses_id!=\''. $cur_ses_id .'\'');
	q('UPDATE fud30_ses SET user_id='. $id .', sys_id=\''. ses_make_sysid() .'\' WHERE ses_id=\''. $cur_ses_id .'\'');
	$GLOBALS['new_sq'] = regen_sq($id);

	/* Lookup country and flag. */
	if ($GLOBALS['FUD_OPT_3'] & 2097152) {	// UPDATE_GEOLOC_ON_LOGIN
		$flag = ret_flag();
	} else {
		$flag = '';	
	}
	
	q('UPDATE fud30_users SET last_used_ip=\''. get_ip() .'\', '. $flag .' sq=\''. $GLOBALS['new_sq'] .'\' WHERE id='. $id);

	return $cur_ses_id;
}

function rebuildmodlist()
{
	$tbl =& $GLOBALS['DBHOST_TBL_PREFIX'];
	$lmt =& $GLOBALS['SHOW_N_MODS'];
	$c = uq('SELECT u.id, u.alias, f.id FROM '. $tbl .'mod mm INNER JOIN '. $tbl .'users u ON mm.user_id=u.id INNER JOIN '. $tbl .'forum f ON f.id=mm.forum_id ORDER BY f.id,u.alias');
	$u = $ar = array();

	while ($r = db_rowarr($c)) {
		$u[] = $r[0];
		if ($lmt < 1 || (isset($ar[$r[2]]) && count($ar[$r[2]]) >= $lmt)) {
			continue;
		}
		$ar[$r[2]][$r[0]] = $r[1];
	}
	unset($c);

	q('UPDATE '. $tbl .'forum SET moderators=NULL');
	foreach ($ar as $k => $v) {
		q('UPDATE '. $tbl .'forum SET moderators='. ssn(serialize($v)) .' WHERE id='. $k);
	}
	q('UPDATE '. $tbl .'users SET users_opt='. q_bitand('users_opt', ~524288) .' WHERE users_opt>=524288 AND '. q_bitand('users_opt', 524288) .'>0');
	if ($u) {
		q('UPDATE '. $tbl .'users SET users_opt='. q_bitor('users_opt', 524288) .' WHERE id IN('. implode(',', $u) .') AND '. q_bitand('users_opt', 1048576) .'=0');
	}
}

/** Lookup geoip info (if enabled) and return SQL UPDATE fragment. */
function ret_flag($raw=0)
{
	if ($raw) {
		$ip = $raw;
	} else {
		$ip = get_ip();
	}

	if ($GLOBALS['FUD_OPT_3'] & 524288) {	// ENABLE_GEO_LOCATION.
		$val = db_saq('SELECT cc, country FROM fud30_geoip WHERE '. sprintf('%u', ip2long($ip)) .' BETWEEN ips AND ipe');
		if ($raw) {
			return $val ? $val : array(null,null);
		}
		if ($val) {
			return 'flag_cc='. _esc($val[0]) .', flag_country='. _esc($val[1]).',';
		}
	}
	if ($raw) {
		return array(null, null);
	}
}/** Log action to the forum's Action Log Viewer ACP. */
function logaction($user_id, $res, $res_id=0, $action=null)
{
	q('INSERT INTO fud30_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES('. __request_timestamp__ .', '. ssn($action) .', '. $user_id .', '. ssn($res) .', '. (int)$res_id .')');
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
}

	/* Remove old unconfirmed users. */
	if ($FUD_OPT_2 & 1) {
		$account_expiry_date = __request_timestamp__ - (86400 * $UNCONF_USER_EXPIRY);
		$list = db_all('SELECT id FROM fud30_users WHERE '. q_bitand('users_opt', 131072) .'=0 AND join_date<'. $account_expiry_date .' AND posted_msg_count=0 AND last_visit<'. $account_expiry_date .' AND id!=1 AND '. q_bitand('users_opt', 1048576) .'=0');

		if ($list) {
			fud_use('private.inc');
			fud_use('users_adm.inc', true);
			usr_delete($list);
		}
		unset($list);
	}

	/* Log user out and redirect to correct page. */
	if (!empty($_GET['logout']) && sq_check(0, $usr->sq)) {
		if ($usr->returnto) {
			parse_str($usr->returnto, $tmp);
			$page = isset($tmp['t']) ? $tmp['t'] : '';
		} else {
			$page = '';
		}

		switch ($page) {
			case 'register':
			case 'pmsg_view':
			case 'pmsg':
			case 'subscribed':
			case 'referals':
			case 'buddy_list':
			case 'ignore_list':
			case 'modque':
			case 'mvthread':
			case 'groupmgr':
			case 'post':
			case 'ppost':
			case 'finduser':
			case 'error':
			case 'uc':
			case '':
				$returnto = '';
				break;
			default:
				if ($page == 'msg' || $page == 'tree') {
					if (empty($tmp['th'])) {
						if (empty($tmp['goto']) || !q_singleval('SELECT t.forum_id
								FROM fud30_msg m
								INNER JOIN fud30_thread t ON m.thread_id=t.id
								INNER JOIN fud30_group_cache g ON g.user_id=0 AND g.resource_id=t.forum_id AND '. q_bitand('g.group_cache_opt', 2) .' > 0
								WHERE m.id='. (int)$tmp['goto'])) {
							$returnto = '';
							break;
						}
					} else {
						if (!q_singleval('SELECT t.forum_id
								FROM fud30_thread t
								INNER JOIN fud30_group_cache g ON g.user_id=0 AND g.resource_id=t.forum_id AND '. q_bitand('g.group_cache_opt', 2) .' > 0
								WHERE t.id='. (int)$tmp['th'])) {
							$returnto = '';
							break;
						}
					}
				} else if ($page == 'thread' || $page == 'threadt') {
					if (!q_singleval('SELECT id FROM fud30_group_cache WHERE user_id=0 AND resource_id='. (isset($tmp['frm_id']) ? (int)$tmp['frm_id'] : 0).' AND '. q_bitand('group_cache_opt', 2) .' > 0')) {
						$returnto = '';
						break;
					}
				}

				if (isset($tmp['S'])) {
					$returnto = str_replace('S='. $tmp['S'], '', $usr->returnto);
				} else {
					$returnto = $usr->returnto;
				}
				break;
		}

		ses_delete($usr->sid);
		if ($FUD_OPT_2 & 32768 && $returnto && $returnto[0] == '/') {
			header('Location: '.$GLOBALS['WWW_ROOT']. $returnto);
		} else {
			header('Location: '.$GLOBALS['WWW_ROOT'].'?'. str_replace(array('?', '&&'), array('&', '&'), $returnto));
		}
		exit;
	}

	/* Send logged in users to profile page if they are not logging out. */
	if (_uid) {
		if ($FUD_OPT_2 & 32768) {
			header('Location: /uni-ideas/index.php/re/'. _rsidl);
		} else {
			header('Location: /uni-ideas/index.php?t=register&'. _rsidl);
		}
		exit;
	}

/** Signal error against type=login or type=password field. */
function login_php_set_err($type, $val)
{
	$GLOBALS['_ERROR_']            = 1;
	$GLOBALS['_ERROR_MSG_'][$type] = $val;
}

/** Display login error. This function is called from the login template. */
function login_php_get_err($type)
{
	if (empty($GLOBALS['_ERROR_MSG_'][$type])) {
		return;
	}
	return '<span class="ErrorText">'.$GLOBALS['_ERROR_MSG_'][$type].'</span><br />';
}

/** Check for obvious username and password errors before we attempt to authenticate. */
function error_check()
{
	if (empty($_POST['login']) || !strlen($_POST['login'] = trim((string)$_POST['login']))) {
		login_php_set_err('login', 'Login name is required');
	}

	if (empty($_POST['password']) || !strlen($_POST['password'] = trim((string)$_POST['password']))) {
		login_php_set_err('password', 'Password is required');
	}

	return $GLOBALS['_ERROR_'];
}

	$_ERROR_ = 0;
	$_ERROR_MSG_ = array();

	/* Deal with quicklogin from if needed. */
	if (isset($_POST['quick_login']) && isset($_POST['quick_password'])) {
		$_POST['login']      = $_POST['quick_login'];
		$_POST['password']   = $_POST['quick_password'];
		$_POST['use_cookie'] = isset($_POST['quick_use_cookies']);
	}

	// Call authentication plugins.
	// Plugin should return 1 (allow access) or 0 (deny access).
	if (defined('plugins')) {
		$ok = plugin_call_hook('AUTHENTICATE');
		if (!empty($ok) && $ok != 1){
			login_php_set_err('login', 'plugin: Invalid login/password combination');
		}
	}

	// Call PRE authentication plugins.
	// If successfully autheticated, the plugin should return a full user object.
	// Return null to continue with FUDforum's default authentication.
	$usr_d = null;
	if (defined('plugins')) {
		$usr_d = plugin_call_hook('PRE_AUTHENTICATE', $usr_d);
	}

	if ($usr_d || isset($_POST['login']) && !error_check()) {
	
		/* Clear session variables. */
		if ($usr->data) {
			ses_putvar((int)$usr->sid, null);
		}

		/* Try to autenticate user. */
		if (!$usr_d && !($usr_d = db_sab('SELECT last_login, id, passwd, salt, login, email, users_opt, ban_expiry, ban_reason FROM fud30_users WHERE login='. _esc($_POST['login'])))) {
			/* Cannot login: user not in DB. */
			login_php_set_err('login', 'Invalid login/password combination');

		} else if (($usr_d->last_login + $MIN_TIME_BETWEEN_LOGIN) > __request_timestamp__) { 
			/* Flood control. */
			q('UPDATE fud30_users SET last_login='. __request_timestamp__ .' WHERE id='. $usr_d->id);
			login_php_set_err('login', 'This forum allows one login attempt every '.$MIN_TIME_BETWEEN_LOGIN.' seconds, please wait '.($usr_d->last_login + $MIN_TIME_BETWEEN_LOGIN - __request_timestamp__).' seconds before your next attempt.');

		/* Check password: No salt -> old md5() auth; with salt -> new sha1() auth. */
		} else if (!isset($usr_d->alias) && (empty($usr_d->salt) && $usr_d->passwd != md5($_POST['password']) || 
			  !empty($usr_d->salt) && $usr_d->passwd != sha1($usr_d->salt . sha1($_POST['password'])))) 
		{
			logaction($usr_d->id, 'WRONGPASSWD', 0, 'Invalid '. ($usr_d->users_opt & 1048576 ? 'FORUM ADMIN ' : '') .'password for login '. htmlspecialchars(_esc($_POST['login'])) .' from IP '. get_ip() .'.');
			q('UPDATE fud30_users SET last_login='. __request_timestamp__ .' WHERE id='. $usr_d->id);
			login_php_set_err('login', 'Invalid login/password combination');
		}

		if ($GLOBALS['_ERROR_'] != 1) {
			/* Is user allowed to login. */
			q('UPDATE fud30_users SET last_login='. __request_timestamp__ .' WHERE id='. $usr_d->id);
			$usr_d->users_opt = (int) $usr_d->users_opt;
			$usr_d->sid = $usr_d->id;
			is_allowed_user($usr_d, 1);

			$ses_id = user_login($usr_d->id, $usr->ses_id, ((empty($_POST['use_cookie']) && $FUD_OPT_1 & 128) ? false : true));

			if (!($usr_d->users_opt & 131072)) {
				error_dialog('ERROR: Your account is not yet confirmed', 'You have not confirmed your account via e-mail yet.<br /><table border="0"><tr><td><ol><li>If you have not received a confirmation e-mail, <a href="/uni-ideas/index.php?t=reset&amp;email='.$usr_d->email.'&amp;S='.$ses_id.'">request it again</a>.</li><li>If '.$usr_d->email.' is not your correct e-mail address, <a href="/uni-ideas/index.php?t=register&amp;S='.$ses_id.'">specify the correct address</a>.</li></ol></td></tr></table>', null, $ses_id);
			}
			if ($usr_d->users_opt & 2097152) {
				error_dialog('Unapproved Account', 'The administrator of the forum has chosen to confirm each new account manually before activation. Your account has not yet been confirmed, therefore you will not be able to access some of the features available to confirmed members.', null, $ses_id);
			}

			if (!empty($_POST['adm']) && $usr_d->users_opt & 1048576) {
				header('Location: /uni-ideas/adm/index.php?S='. $ses_id .'&SQ='. $new_sq);
				exit;
			}

			if (!$usr->returnto) { /* Nothing to do, send to front page. */
				check_return('');
			}

			if (s && ($sesp = strpos($usr->returnto, s)) !== false) { /* Replace old session with new session. */
				$usr->returnto = str_replace(s, $ses_id, $usr->returnto);
			}

			if ($usr->returnto[0] != '/') { /* No GET vars or no PATH_INFO. */
				$ret =& $usr->returnto;
				parse_str($ret, $args);
				$args['SQ'] = $new_sq;

				if ($FUD_OPT_1 & 128) { /* If URL sessions are supported. */
					$args['S'] = $ses_id;
				}

				$ret = '';
				foreach ($args as $k => $v) {
					$ret .= $k .'='. $v .'&';
				}
			} else { /* PATH_INFO url or GET url with no args. */
				if ($FUD_OPT_1 & 128 && $FUD_OPT_2 & 32768 && !$sesp) {
					if (preg_match('![a-z0-9]{32}!', $usr->returnto, $m)) {
						$usr->returnto = str_replace($m[0], $ses_id, $usr->returnto);
					}
				}
				$usr->returnto .= '?SQ='. $new_sq .'&S='. $ses_id;
			}

			check_return($usr->returnto);
		}
	}

	ses_update_status($usr->sid, 'Login Form', 0, 0);
	$TITLE_EXTRA = ': Login Form';

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/uni-ideas/theme/default/images/top_pm.png" alt="" width="16" height="16" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/uni-ideas/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/uni-ideas/theme/default/images/top_pm.png" alt="" width="15" height="11" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}

	/* Check if we have a 'password reset' message to display (from reset.php.t). */
	if (!empty($usr->data) && substr($usr->data, 0, 9) == 'resetmsg=') {
		$reset_login_notify = substr($usr->data, 9);
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
			background-color: #111;
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
		<div class="menu"><a href="/uni-ideas/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img src="/uni-ideas/theme/default/images/top_home.png" alt="" width="16" height="16" /> Home</a></div>

		<?php echo ($FUD_OPT_1 & 16777216 ? ' <div class="menu"><a href="/uni-ideas/index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Search"><img src="/uni-ideas/theme/default/images/top_search.png" alt="" width="16" height="16" /> Search</a></div>' : ''); ?>
		<div class="menu"><a accesskey="h" href="/uni-ideas/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img src="/uni-ideas/theme/default/images/top_help.png" alt="" width="16" height="16" /> Help</a></div>
		<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<div class="menu"><a href="/uni-ideas/index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Members"><img src="/uni-ideas/theme/default/images/top_members.png" alt="" width="16" height="16" /> Members</a></div>' : ''); ?>
		<div class="menu"><?php echo $ucp_private_msg; ?></div>
		
		<?php echo (__fud_real_user__ ? '<div class="menu"><a href="/uni-ideas/index.php?t=uc&amp;'._rsid.'" title="Access the user control panel"><img src="/uni-ideas/theme/default/images/top_profile.png" alt="" width="16" height="16" /> Control Panel</a></div>' : ($FUD_OPT_1 & 2 ? '<div class="menu"><a href="/uni-ideas/index.php?t=register&amp;'._rsid.'" title="Register"><img src="/uni-ideas/theme/default/images/top_register.png" alt="" width="16" height="18" /> Register</a></div>' : '')).'
		'.(__fud_real_user__ ? '<div class="menu"><a href="/uni-ideas/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Logout"><img src="/uni-ideas/theme/default/images/top_logout.png" alt="" width="16" height="16" /> Logout [ '.filter_var($usr->alias, FILTER_SANITIZE_STRING).' ]</a></div>' : '<div class="menu"><a href="/uni-ideas/index.php?t=login&amp;'._rsid.'" title="Login"><img src="/uni-ideas/theme/default/images/top_login.png" alt="" width="16" height="16" /> Login</a></div>'); ?>
		<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<div class="menu"><a href="/uni-ideas/adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Administration"><img src="/uni-ideas/theme/default/images/top_admin.png" alt="" width="16" height="16" /> Administration</a></div>' : ''); ?>
	</ul>
</div>
<form id="login" method="post" action="/uni-ideas/index.php?t=login"<?php echo ($FUD_OPT_3 & 256 ? ' autocomplete="off"' : '').'>
<div class="ctb">
<table cellspacing="1" cellpadding="2" class="DialogTable">
<tr>
	<th colspan="3">Login Form</th>
</tr>
<tr>
	<td class="RowStyleA GenText" colspan="3">
		'.(isset($reset_login_notify) ? $reset_login_notify.'' : '
		You are not logged in. This could be due to one of several reasons:
		<ol>
			<li class="GenText">Your cookie has expired, and you need to login to renew your cookie.</li>
			<li class="GenText">You do not have permission to access the requested resource as an anonymous user. You must login to gain permission.</li>
		</ol>'); ?>
	</td>
</tr>
<tr class="RowStyleB">
	<td class="GenText">Login:</td>
	<td><?php echo login_php_get_err('login'); ?><input type="text" tabindex="1" name="login" /></td>
	<td class="nw"><?php echo ($FUD_OPT_1 & 2 ? '<a href="/uni-ideas/index.php?t=register&amp;'._rsid.'">Want to register?</a>' : ''); ?></td>
</tr>
<tr class="RowStyleA">
	<td class="GenText">Password:</td>
	<td><?php echo login_php_get_err('password'); ?><input type="password" tabindex="2" name="password" /></td>
	<td class="nw"><?php echo ($FUD_OPT_4 & 2 ? '<a href="/uni-ideas/index.php?t=reset&amp;'._rsid.'">Forgot password</a>' : ''); ?></td>
</tr>
<?php echo ($FUD_OPT_1 & 128 ? '<tr class="RowStyleB">
	<td colspan="3" class="al">
		<label><input type="checkbox" name="use_cookie" value="Y" checked="checked" /> Use cookies<br /><span class="SmallText">If you&#39;re using a public terminal such as a computer in a library, school, or Internet cafe, it is recommended that you uncheck this option for greater security. If you leave this option selected then you will be automatically logged-into the forum the next time you visit.</span></label>
	</td>
</tr>' : ''); ?>
<tr>
	<td colspan="3" class="RowStyleA ar"><input type="submit" class="button" tabindex="3" value="Login" /></td>
</tr>
</table></div><?php echo _hs; ?><input type="hidden" name="adm" value="<?php echo (isset($_GET['adm']) ? '1' : ''); ?>" /></form>
<br /><div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo utf8_encode(strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__)); ?></span></div>
<script>
	document.forms['login'].login.focus();
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

