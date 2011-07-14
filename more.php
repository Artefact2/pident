<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

const RECENT = 500;

declareCache('more');

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>More recent blocks</title>
</head>
<body>
<h1>".RECENT." most recent blocks</h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
";

echo formatRecentBlocks(RECENT);

echo FOOTER."
</body>
</html>
";

