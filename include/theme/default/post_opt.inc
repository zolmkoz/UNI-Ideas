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

function tmpl_post_options($arg, $perms=0)
{
	$post_opt_html		= '<b>HTML</b> code is <b>off</b>';
	$post_opt_fud		= '<b>BBcode</b> is <b>off</b>';
	$post_opt_images 	= '<b>Images</b> are <b>off</b>';
	$post_opt_smilies	= '<b>Smilies</b> are <b>off</b>';
	$edit_time_limit	= '';

	if (is_int($arg)) {
		if ($arg & 16) {
			$post_opt_fud = '<a href="/uni-ideas/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
		} else if (!($arg & 8)) {
			$post_opt_html = '<b>HTML</b> is <b>on</b>';
		}
		if ($perms & 16384) {
			$post_opt_smilies = '<a href="/uni-ideas/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
		}
		if ($perms & 32768) {
			$post_opt_images = '<b>Images</b> are <b>on</b>';
		}
		if ($GLOBALS['EDIT_TIME_LIMIT'] >= 0) {	// Time limit enabled,
			$edit_time_limit = $GLOBALS['EDIT_TIME_LIMIT'] ? '<br /><b>Editing Time Limit</b>: '.$GLOBALS['EDIT_TIME_LIMIT'].' minutes' : '<br /><b>Editing Time Limit</b>: Unlimited';
		}
	} else if ($arg == 'private') {
		$o =& $GLOBALS['FUD_OPT_1'];

		if ($o & 4096) {
			$post_opt_fud = '<a href="/uni-ideas/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
		} else if (!($o & 2048)) {
			$post_opt_html = '<b>HTML</b> is <b>on</b>';
		}
		if ($o & 16384) {
			$post_opt_images = '<b>Images</b> are <b>on</b>';
		}
		if ($o & 8192) {
			$post_opt_smilies = '<a href="/uni-ideas/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
		}
	} else if ($arg == 'sig') {
		$o =& $GLOBALS['FUD_OPT_1'];

		if ($o & 131072) {
			$post_opt_fud = '<a href="/uni-ideas/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
		} else if (!($o & 65536)) {
			$post_opt_html = '<b>HTML</b> is <b>on</b>';
		}
		if ($o & 524288) {
			$post_opt_images = '<b>Images</b> are <b>on</b>';
		}
		if ($o & 262144) {
			$post_opt_smilies = '<a href="/uni-ideas/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
		}
	}

	return 'Forum Options:<br /><span class="SmallText">
'.$post_opt_html.'<br />
'.$post_opt_fud.'<br />
'.$post_opt_images.'<br />
'.$post_opt_smilies.$edit_time_limit.'</span>';
}
?>
