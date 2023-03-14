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

/** Log error and redirect to the error template. */
function error_dialog($title, $msg, $level='WARN', $ses=null)
{
	if (!$ses) {
		$ses = (int) $GLOBALS['usr']->sid;
	}

	// Log the error.
	if (defined('fud_logging') || $level !== 'INFO') {
		// Build error string.
		$error_msg  = '[Error] '. $title .'<br />';
		$error_msg .= '[Message to User] '. trim($msg) .'<br />';
		$error_msg .= '[User IP] '. get_ip() .'<br />';
		$error_msg .= '[Requested URL] http://';
		$error_msg .= isset($_SERVER['HTTP_HOST']) ? htmlspecialchars($_SERVER['HTTP_HOST']) : '';
		$error_msg .= isset($_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['REQUEST_URI']) : '';

		// Mask out sensitive data.
		unset($_POST['password']);
		unset($_POST['quick_password']);
		$error_msg .= !empty($_POST) ? '<br />[Post-Data] '. htmlspecialchars(serialize($_POST)) : '';
		$error_msg .= '<br />';

		if (isset($_SERVER['HTTP_REFERER'])) {
			$error_msg .= '[Referring URL] '. htmlspecialchars($_SERVER['HTTP_REFERER']) .'<br />';
		} else if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$error_msg .= '[User Agent] '. htmlspecialchars($_SERVER['HTTP_USER_AGENT']) .'<br />';
		}

		fud_logerror($error_msg, 'fud_errors');
	}

	// No need to redirect, we just want to log the error.
	if ($level == 'LOG&RETURN') {
		return;
	}

	// Store persistently.
	ses_putvar($ses, array('er_msg' => $msg, 'err_t' => $title));

	// Redirect to error template.
	if (is_int($ses)) {
		if ($GLOBALS['FUD_OPT_2'] & 32768) {
			header('Location: /uni-ideas/index.php/e/'. _rsidl);
		} else {
			header('Location: /uni-ideas/index.php?t=error&'. _rsidl);
		}
	} else {
		if ($GLOBALS['FUD_OPT_2'] & 32768) {
			header('Location: /uni-ideas/index.php/e/0/'. $ses);
		} else {
			header('Location: /uni-ideas/index.php?t=error&S='. $ses);
		}
	}
	exit;
}

/** Signal standard errors. */
function std_error($type)
{
	if (!isset($_SERVER['HTTP_REFERER'])) {
		$_SERVER['HTTP_REFERER'] = 'unknown';
	}

	$ses_id = s;
	$usr_d = new stdClass();
	$usr_d->email = $GLOBALS['usr']->email;

	if ($type == 'login') {
		if (__fud_real_user__) {
			$type = 'perms';
		} else {
			ses_anonuser_auth($GLOBALS['usr']->sid, '<fieldset><legend>ERROR: You are not logged-in</legend>To access this page you must be a member of this forum.</fieldset><br />');
		}
	}

	$err_array = array(
'ERR_disabled'=>array('Disabled feature', 'This feature has been disabled by the system administrator.'),
'ERR_access'=>array('ERROR: Insufficient Permissions', 'You do not have the required permissions to perform this action.'),
'ERR_registration_disabled'=>array('Registration Disabled', 'The administrator has disabled registration. Please try again later.'),
'ERR_user'=>array('ERROR: No such user', 'There is no such user'),
'ERR_perms'=>array('Permission Denied!', 'Your user permissions do not allow you to perform the action you are attempting.'),
'ERR_systemerr'=>array('ERROR: System Error', 'A system error has occurred. Please report it to the <a href="mailto:'.$GLOBALS['ADMIN_EMAIL'].'">webmaster</a>.'),
'ERR_emailconf'=>array('ERROR: Your account is not yet confirmed', 'You have not confirmed your account via e-mail yet.<br /><table border="0"><tr><td><ol><li>If you have not received a confirmation e-mail, <a href="/uni-ideas/index.php?t=reset&amp;email='.$usr_d->email.'&amp;S='.$ses_id.'">request it again</a>.</li><li>If '.$usr_d->email.' is not your correct e-mail address, <a href="/uni-ideas/index.php?t=register&amp;S='.$ses_id.'">specify the correct address</a>.</li></ol></td></tr></table>')
);

	if (isset($err_array['ERR_'. $type])) {
		$err = $err_array['ERR_'. $type];
		error_dialog($err[0], $err[1]);
	}
	error_dialog('Critical error', 'An error has occurred. Unfortunately, there is also a problem with the error reporting system.');
}

/** Signal an invalid input error. */
function invl_inp_err()
{
	error_dialog('Invalid Input', 'You have followed an invalid link. Please notify the <a href="mailto:'.$GLOBALS['ADMIN_EMAIL'].'">webmaster</a> how you got here.', 'INFO');
}
?>
