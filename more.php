<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

const BLOCKS_PER_PAGE = 500;

$page = getPageNumber('p');

declareCache('more', 'page'.$page);

list($maxPage, $output) = formatRecentBlocks(BLOCKS_PER_PAGE, $page);
$fPage = formatInt($page).' of '.formatInt($maxPage);

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Block list (page $fPage)</title>
</head>
<body>
<h1>Block list (page $fPage)</h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
$output";

echo FOOTER."
</body>
</html>
";

