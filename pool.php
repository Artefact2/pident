<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

$pool = htmlspecialchars(@array_pop(explode('/', $_SERVER['REQUEST_URI'])));
$pool = @array_shift(explode('?', $pool, 2));
$page = getPageNumber('p');

declareCache('pool', $pool.'_page'.$page);

list($maxPage, $output) = formatRecentBlocks(100, $page, $pool);
$fPage = $maxPage > 1 ? ' (page '.formatInt($page).' of '.formatInt($maxPage).')' : '';

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Blocks found by $pool$fPage</title>
</head>
<body>
<h1>Blocks found by <a href='/pool/$pool'>$pool</a>$fPage</h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
$output";

echo FOOTER."
</body>
</html>
";

