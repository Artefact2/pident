#!/usr/bin/env php
<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

ini_set('memory_limit', '512M');

/* Update gen transactions */

$max = trim(shell_exec('bitcoind getblockcount'));
$txBits = array();
for($i = 0; $i <= $max; ++$i) {
	$json = json_decode(shell_exec('bitcoind getblockbycount '.$i), true);

	$txBits[] = "B'".hex2bits($json['tx'][0]['hash'])."'";

	echo "\rgot txid for block $i...";
}

$in = implode(',', $txBits);
pg_query("
UPDATE transactions
SET is_generation = true
WHERE transaction_id IN ($in)
");

echo "\n";
