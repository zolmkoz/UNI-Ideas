<?php
/**
* copyright            : (C) 2001-2013 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id$
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

defined('_hs')   or define('_hs', '');
defined('_rsid') or define('_rsid', '');

/** Forum is disabled. Display reason and hang up. */
function exit_forum_disabled($format='html')
{
	$TITLE_EXTRA = $RSS = null;

	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 1800');	// 30 minutes.
	header('Connection: Close');
	header('Content-type: text/'. $format .'; charset=utf-8');

	if ($format == 'xml') {
		exit('<?xml version="1.0" encoding="utf-8"?>
<error>
	<message>'.$GLOBALS['DISABLED_REASON'].'</message>
</error>');
	} else {
		exit('<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="'.(!empty($META_DESCR) ? ''.$META_DESCR.'' : ''.$GLOBALS['FORUM_DESCR'].'' ) .'" />
	<title>'.$GLOBALS['FORUM_TITLE'].$TITLE_EXTRA.'</title>
	<link rel="search" type="application/opensearchdescription+xml" title="'.$GLOBALS['FORUM_TITLE'].' Search" href="/uni-ideas/open_search.php" />
	'.$RSS.'
	<link rel="stylesheet" href="/uni-ideas/theme/default/forum.css" media="screen" title="Default Forum Theme" />
	<link rel="stylesheet" href="/uni-ideas/js/ui/jquery-ui.css" media="screen" />
	<script src="/uni-ideas/js/jquery.js"></script>
	<script async src="/uni-ideas/js/ui/jquery-ui.js"></script>
	<script src="/uni-ideas/js/lib.js"></script>
</head>
<body>
<!--  -->
<div class="header">
  '.($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="/uni-ideas/index.php">'._hs.'
      <input type="hidden" name="t" value="search" />
      <br /><label accesskey="f" title="Forum Search">Forum Search:<br />
      <input type="search" name="srch" value="" size="20" placeholder="Forum Search" /></label>
      <input type="image" src="/uni-ideas/theme/default/images/search.png" title="Search" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : '' ) .'
  <a href="/uni-ideas/" title="Home">
    <img class="headimg" src="/uni-ideas/theme/default/images/header.gif" alt="" align="left" height="80" />
    <span class="headtitle">'.$GLOBALS['FORUM_TITLE'].'</span>
  </a><br />
  <span class="headdescr">'.$GLOBALS['FORUM_DESCR'].'<br /><br /></span>
</div>
<div class="content">

<!-- Table for sidebars. -->
<table width="100%"><tr><td>
<br />
<h2>'.$GLOBALS['DISABLED_REASON'].'</h2>
<br />
<small>Administrators can login <a href="/uni-ideas/adm/admloginuser.php">here</a>.</small>
<br /><br />
'.(!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : '' ) .'
</td></tr></table>

</div>
<div class="footer ac">
	<b>.::</b>
	<a href="mailto:'.$GLOBALS['ADMIN_EMAIL'].'">Contact</a>
	<b>::</b>
	<a href="/uni-ideas/index.php?t=index&amp;'._rsid.'">Home</a>
	<b>::.</b>
	<p class="SmallText">Powered by: FUDforum '.$GLOBALS['FORUM_VERSION'].'.<br />Copyright &copy;2001-2022 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>');
	}
}

/** User is banned. Notify and hang up. */
function exit_user_banned()
{
	$TITLE_EXTRA = $RSS = null;

	header('HTTP/1.1 403 Forbidden');
	header('Status: 403 Forbidden');
	header('Connection: Close');
	header('Content-type: text/html; charset=utf-8');
	exit('<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="'.(!empty($META_DESCR) ? ''.$META_DESCR.'' : ''.$GLOBALS['FORUM_DESCR'].'' ) .'" />
	<title>'.$GLOBALS['FORUM_TITLE'].$TITLE_EXTRA.'</title>
	<link rel="search" type="application/opensearchdescription+xml" title="'.$GLOBALS['FORUM_TITLE'].' Search" href="/uni-ideas/open_search.php" />
	'.$RSS.'
	<link rel="stylesheet" href="/uni-ideas/theme/default/forum.css" media="screen" title="Default Forum Theme" />
	<link rel="stylesheet" href="/uni-ideas/js/ui/jquery-ui.css" media="screen" />
	<script src="/uni-ideas/js/jquery.js"></script>
	<script async src="/uni-ideas/js/ui/jquery-ui.js"></script>
	<script src="/uni-ideas/js/lib.js"></script>
</head>
<body>
<!--  -->
<div class="header">
  '.($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="/uni-ideas/index.php">'._hs.'
      <input type="hidden" name="t" value="search" />
      <br /><label accesskey="f" title="Forum Search">Forum Search:<br />
      <input type="search" name="srch" value="" size="20" placeholder="Forum Search" /></label>
      <input type="image" src="/uni-ideas/theme/default/images/search.png" title="Search" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : '' ) .'
  <a href="/uni-ideas/" title="Home">
    <img class="headimg" src="/uni-ideas/theme/default/images/header.gif" alt="" align="left" height="80" />
    <span class="headtitle">'.$GLOBALS['FORUM_TITLE'].'</span>
  </a><br />
  <span class="headdescr">'.$GLOBALS['FORUM_DESCR'].'<br /><br /></span>
</div>
<div class="content">

<!-- Table for sidebars. -->
<table width="100%"><tr><td>
<br />
Your prior actions have resulted in you being permanently banned from this forum. Goodbye.
<br /><br />
'.(!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : '' ) .'
</td></tr></table>

</div>
<div class="footer ac">
	<b>.::</b>
	<a href="mailto:'.$GLOBALS['ADMIN_EMAIL'].'">Contact</a>
	<b>::</b>
	<a href="/uni-ideas/index.php?t=index&amp;'._rsid.'">Home</a>
	<b>::.</b>
	<p class="SmallText">Powered by: FUDforum '.$GLOBALS['FORUM_VERSION'].'.<br />Copyright &copy;2001-2022 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>');
}

?>
