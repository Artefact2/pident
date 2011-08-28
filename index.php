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

$factoids = cacheFetch('factoids', $success);
if($success && count($factoids) > 0) {
	$f = formatRandomFactoid($factoids);
	echo "<h2 id='factoid'>Random <a href='/factoids'>factoid</a></h2><p>$f</p>";
}

echo "<h2>Most recent blocks <small><a href='/more'>(see more…)</a></small></h2>\n";
list(, $output) = formatRecentBlocks(25, 0);
echo $output;

list($output, $props) = formatPools();
echo "<h2>Pools I'm aware of</h2>\n";
echo $output;

list($svg, $legend) = formatPoolSizeChart($props);
echo "<h2>Pool size pie chart <small><a href='http://caniuse.com/svg-html5'>(you need a SVG-enabled browser)</a></small></h2>\n";
echo "<table id='psize-pie-chart'>
<tr>
<td>$svg</td>
<td>$legend</td>
</tr>
</table>\n";

echo FOOTER."
</body>
</html>
";

