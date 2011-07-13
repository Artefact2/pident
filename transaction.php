<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

$txid = @array_pop(explode('/', $_SERVER['REQUEST_URI']));

list($block, $time, $blockNum, $foundBy, $size, $transactions) = fetchTransactions(null, $txid);
list($totalGenerated, $transactionsHTML) = formatTransactionsTable($transactions);


echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Transaction $txid</title>
</head>
<body>
<h1>Transaction <a href='/tx/$txid'>$txid</a></h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
<ul>
<li>Appeared in block <a href='/block/$block'>$block</a></li>
<li>Found at: $time UTC</li>
</ul>
$transactionsHTML
";

echo FOOTER."
</body>
</html>
";
