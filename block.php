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

list($block, $time, $foundBy, $transactions) = fetchTransactions($block, null);
list($totalGenerated, $transactionsHTML) = formatTransactionsTable($transactions);

$foundBy = prettyPool($foundBy);

$req = "
SELECT previous_hash
FROM blocks
WHERE hash = B'$bits'
";
$previous = pg_fetch_row(pg_query($req));
if(!isset($previous[0])) {
	$previous = 'N/A (this is the genesis block!)';
} else {
	$previous = bits2hex($previous[0]);
	$previous = "<a href='/block/$previous'>$previous</a>";
}

$req = "
SELECT hash
FROM blocks
WHERE previous_hash = B'$bits'
";
$next = array();
$req = pg_query($req);
while($r = pg_fetch_row($req)) {
	$hash = bits2hex($r[0]);
	$next[] = "<a href='/block/$hash'>$hash</a>";
}
$next = (count($next) > 0 ? implode(', ', $next) : 'N/A (this block is either at the top of the chain, or invalid)');

echo "<!DOCTYPE html>
<html>
<head>
<title>Block $block</title>
<link type=\"text/css\" rel=\"stylesheet\" href=\"/theme.css\">
<meta charset=\"utf-8\" />
</head>
<body>
<h1>Block <a href='/block/$block'>$block</a></h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
<ul>
<li>Previous block: $previous</li>
<li>Next block(s):  $next</li>
<li>Generated BTC: $totalGenerated (includes transaction fees)</li>
<li>Found at: $time UTC</li>
<li>Found by: $foundBy</li>
</ul>
$transactionsHTML
";

echo FOOTER."
</body>
</html>
";
