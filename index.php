<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

declareCache('index');

$version = VERSION;

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>pident index page</title>
</head>
<body>
<h1>pident — pool-biased blockchain representation <small>(version $version!)</small></h1>
";

echo "<h2>Most recent blocks <small><a href='/more'>(see more…)</a></small></h2>\n";
list(, $output) = formatRecentBlocks(25, 0);
echo $output;

echo "<h2>Pools I'm aware of</h2>\n";
echo formatPools();

echo FOOTER."
</body>
</html>
";

