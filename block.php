<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

$block = @array_pop(explode('/', $_SERVER['REQUEST_URI']));

if(isset($_GET['redirect'])) {
	if(substr($block, 0, 1) == 't') {
		$block = urldecode(substr($block, 1));
		if(!preg_match('%(0|([1-9][0-9]+))%u', $block)) {
			$block = -42;
		} else {
			$block = tonalParseInteger($block);
		}
	} else if(!preg_match('%^[0-9]+$%', $block)) {
		$block = -42;
	}

	$blocks = pg_query("SELECT hash FROM blocks WHERE number = $block");
	$hashs = array();
	while($r = pg_fetch_row($blocks)) $hashs[] = bits2hex($r[0]);

	if(count($hashs) == 1) {
		header('Location: /block/'.$hashs[0]);
	} else if(count($hashs) == 0) {
		header('Content-Type: text/plain', 404, true);
		echo "There is no block with this number. Problem?";
	} else {
		$fNumber = formatInt($block);
		echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Ambiguous block $fNumber</title>
</head>
<body>
<h1>Ambiguous block <a href='/b/$block'>$fNumber</a></h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
<p>There are more than one block with number $fNumber. This is often caused by invalid blocks that haven't been pruned from the blockchain yet. Did you mean one of those blocks ?</p>
<ul>
";

		foreach($hashs as $h) {
			echo "<li>Block <a href='/block/$h'>$h</a></li>\n";
		}

		echo "</ul>\n".FOOTER."
</body>
</html>
";
	}

	die();
}

declareCache('block', $block, 86400);

$bits = hex2bits($block);

list($block, $time, $number, $foundBy, $size, $coinbase, $transactions) = fetchTransactions($block, null);
list($totalGenerated, $transactionsHTML) = formatTransactionsTable($transactions);

$maxBlock = pg_fetch_row(pg_query('SELECT MAX(number) FROM blocks;'));
if($maxBlock[0] - $number > 5000) {
	/* This is an old block, not likely to change anymore */
	declareCacheExpiration(86400, true);
}

$foundBy = prettyPool($foundBy);
$size = formatSize($size);
$coinbase = formatCoinbase($coinbase);

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

$unit = TONAL ? 'TBC' : 'BTC';
$number = (TONAL ? 't' : '').formatInt($number, false);
$uriNumber = urlencode($number);

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Block $number - $block</title>
</head>
<body>
<h1>Block <a href='/block/$block'>$block</a></h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
<ul>
<li>Short URI of this block: <strong><a href='/b/$uriNumber'>/b/$number</a></strong></li>
<li>Previous block: $previous</li>
<li>Next block(s):  $next</li>
<li>Generated $unit: $totalGenerated (includes transaction fees)</li>
<li>Block size: $size</li>
<li>Coinbase: $coinbase</li>
<li title='Do not trust this value, it is based on the local time of the node which found the block.'><span>Found at: $time UTC</span></li>
<li>Found by: $foundBy</li>
</ul>
$transactionsHTML
";

echo FOOTER."
</body>
</html>
";
