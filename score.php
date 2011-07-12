<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

$block = @array_pop(explode('/', $_SERVER['REQUEST_URI']));
$bits = hex2bits($block);

$blockNumber = pg_fetch_row(pg_query('SELECT number FROM blocks WHERE hash = B\''.$bits.'\''));
$totalCount = pg_fetch_row(pg_query('SELECT MAX(number) FROM blocks;'));

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Score $block</title>
</head>
<body>
<h1>Score <a href='/score/$block'>$block</a></h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
<p class='notice'>The scoring feature of pident is still <strong>experimental</strong>. The results are not guaranteed in any way, use at your own risk !</p>
";

if(($totalCount[0] - $blockNumber[0]) > $GLOBALS['conf']['tau']) {
	echo "<p class='warning'>This block is old. The score calculations are probably utterly wrong.</p>";
}

echo formatScores($block);

echo FOOTER."
</body>
</html>
";
