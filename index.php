<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

$version = VERSION;

echo "<!DOCTYPE html>
<html>
<head>
<title>pident index page</title>
<link type=\"text/css\" rel=\"stylesheet\" href=\"/theme.css\">
<meta charset=\"utf-8\" />
</head>
<body>
<h1>pident — pool-biased blockchain representation <small>(version $version!)</small></h1>
";

echo "<h2>Most recent blocks</h2>\n";
echo formatRecentBlocks(25);

echo FOOTER."
</body>
</html>
";

