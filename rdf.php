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

	/* Permanent redirect (301) for backward compatibility. 
	 * New file is feed.php to support RDF, Atom and RSS feeds.
	 */
	header('Status: 301');
	header('Location: /uni-ideas/feed.php?'. $_SERVER['QUERY_STRING']);
?>
