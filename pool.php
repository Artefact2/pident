<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

$pool = htmlspecialchars(@array_pop(explode('/', $_SERVER['REQUEST_URI'])));

declareCache('pool', $pool);

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Pool $pool</title>
</head>
<body>
<h1>Pool <a href='/pool/$pool'>$pool</a> (most recent blocks)</h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
";

echo formatRecentBlocks(100, $pool);

echo FOOTER."
</body>
</html>
";

