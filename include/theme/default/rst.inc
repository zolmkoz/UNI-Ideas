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

/* Needed by admuser.php, so that password resets can be sent in the appropriate languge. */

$GLOBALS['register_conf_subject']       = 'Registration Confirmation';
$GLOBALS['reset_newpass_title']         = 'Reset Password';
$GLOBALS['reset_confirmation']          = 'Thank you for registering.\n\nTo activate your account please go to the URL below:\n\n'.$url.'\n\nOnce your account is activated you will be logged-into the forum and\nredirected to the main page.\n\n\n\nIf you received this message in error, please ignore it. If you are receiving multiple copies of this e-mail, which you have not requested, please contact the forum administrator at '.$GLOBALS['ADMIN_EMAIL'].'\n\nThis request was initiated from: '.$_SERVER['REMOTE_ADDR'].'.\n\n';
$GLOBALS['reset_reset']                 = 'Hello,\n\nYou have requested for your password to be reset. To complete the process,\nplease go to this URL:\n\n'.$url.'\n\nNOTE: This forum stores the passwords in a one-way encryption mechanism, which means that\nonce you have entered your password it is encoded so that there is NO WAY to get it back.\nThis works by comparing the encoded version we have on record with the encoded version of what you type into the Login prompt.\n(If you are interested in how this mechanism works, read up on MD5 HASH algorithm)\n\nIf you received this message in error, please ignore it. If you are receiving multiple copies of this e-mail, which you have not requested, please contact the forum administrator at '.$GLOBALS['ADMIN_EMAIL'].'\n\nThis request was initiated from: '.$_SERVER['REMOTE_ADDR'].'.\n\n';
?>