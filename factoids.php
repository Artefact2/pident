<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

declareCache('factoids');

$factoids = cacheFetch('factoids', $success);
if(!$success) {
	header('Content-Type: text/plain');
	die('Factoid list unavailable :-(');
}

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Factoid list</title>
</head>
<body>
<h1>Factoid list</h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
<ol id='factoids'>
";

foreach($factoids as $k => $dontCareAtAllAboutThisValue) {
	$f = formatFactoid($factoids, $k);
	echo "<li>$f</li>\n";
}

echo "</ol>\n".FOOTER."
</body>
</html>
";

