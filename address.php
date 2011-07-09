<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

$address = @array_pop(explode('/', $_SERVER['REQUEST_URI']));
$hash160 = Bitcoin::addressToHash160($address);

list(, $firstBlock, $in, $out) = fetchAddressTransactions($hash160);

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Address $address</title>
</head>
<body>
<h1>Address <a href='/address/$address'>$address</a></h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
<ul>
<li>First seen in block <a href='/block/$firstBlock'>$firstBlock</a></li>
<li>Jump to : <a href='#in'>received coins</a> | <a href='#out'>sent coins</a></li>
</ul>
";

echo "<h2 id='in'>Received coins</h2>\n";
echo formatAddressTransactions($in, true)."\n";

echo "<h2 id='out'>Sent coins</h2>\n";
echo formatAddressTransactions($out, false)."\n";

echo FOOTER."
</body>
</html>
";
