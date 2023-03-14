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

	$login = $GLOBALS['r']->login;
	$GLOBALS['account_accepted'] = 'Your account &#39;'.$login.'&#39; was approved by the administrator and is now active. You may now access the '.$GLOBALS['FORUM_TITLE'].'&#39; forum as a confirmed, registered user.';
	$GLOBALS['account_rejected'] = 'Your account &#39;'.$login.'&#39; was declined by the administrator. If you would like to know the reason behind this decision, feel free to contact the administrator at &#39;'.$GLOBALS['ADMIN_EMAIL'].'&#39;.';

	$GLOBALS['account_accepted_s'] = 'Your account request for &#39;'.$GLOBALS['FORUM_TITLE'].'&#39; was accepted.';
	$GLOBALS['account_rejected_s'] = 'Your account request for &#39;'.$GLOBALS['FORUM_TITLE'].'&#39; was declined.';
?>
