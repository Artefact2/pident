#!/usr/bin/env php
<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

/* Update block numbers */
$max = trim(shell_exec('bitcoind getblockcount'));
$c = strlen($max);

for($i = 0; $i <= $max; ++$i) {
	$fI = str_pad($i, $c, '0', STR_PAD_LEFT);
	$blk = json_decode(shell_exec('bitcoind getblockbycount '.$i), true);

	echo "\r$fI/$max processing... (".$blk['hash'].")";

	$hash = hex2bits($blk['hash']);

	pg_query("UPDATE blocks SET number = $i WHERE hash = B'$hash'");
}

/* Update generation/OP_NOP addresses correctly.
$max = trim(shell_exec('bitcoind getblockcount'));
$c = strlen($max);

for($i = 0; $i <= $max; ++$i) {
	$fI = str_pad($i, $c, '0', STR_PAD_LEFT);
	$blk = json_decode(shell_exec('bitcoind getblockbycount '.$i), true);

	echo "\r$fI/$max processing... (".$blk['hash'].")";

	$sig = preg_replace('%^([0-9a-f]{130}) OP_CHECKSIG$%', '$1', $blk['tx'][0]['out'][0]['scriptPubKey']);
	$address = Bitcoin::pubKeyToAddress($sig);
	$bits = hex2bits(Bitcoin::addressToHash160($address));
	$txid = hex2bits($blk['tx'][0]['hash']);

	pg_query("UPDATE tx_out SET address = B'$bits' WHERE transaction_id = B'$txid' AND n = 0");
}
*/

/* Treat weird pubkeys
$nulls = pg_query('SELECT block, tx_out.transaction_id, n
FROM tx_out
LEFT JOIN transactions ON transactions.transaction_id = tx_out.transaction_id
WHERE address IS NULL');
while($r = pg_fetch_row($nulls)) {
	$blk = json_decode(shell_exec('bitcoind getblockbyhash '.bits2hex($r[0])), true);
	$txId = bits2hex($r[1]);
	$txBits = $r[1];
	$n = $r[2];

	foreach($blk['tx'] as $tx) {
		if($tx['hash'] != $txId) continue;

		$out = $tx['out'][$n];

		if(preg_match($r = '%^OP_DUP OP_HASH160 ([0-9a-f]{40}) OP_EQUALVERIFY OP_CHECKSIG( OP_CHECKSIG)*( OP_NOP)?$%', $out['scriptPubKey'])) {
			$address = "B'".hex2bits(preg_replace($r, '$1', $out['scriptPubKey']))."'";
		} else if(preg_match($r = '%^([0-9a-f]{130}) OP_CHECKSIG$%', $out['scriptPubKey'])) {
			$pubKey = preg_replace($r, '$1', $out['scriptPubKey']);
			$address = "B'".hex2bits(Bitcoin::addressToHash160(Bitcoin::pubKeyToAddress($pubKey)))."'";
		} else {
			$address = 'NULL';
			trigger_error('Unknown scriptPubKey type: '.$out['scriptPubKey'], E_USER_NOTICE);
		}

		if($address === NULL) continue;

		pg_query("UPDATE tx_out SET address = $address WHERE transaction_id = B'$txBits' AND n = $n");

		break;
	}
}
*/

/* Fix the "e" bug with bc causing zeroes to be inserted instead of very small values
$zeroes = pg_query("SELECT tx_out.transaction_id, n, block FROM tx_out JOIN transactions ON tx_out.transaction_id = transactions.transaction_id WHERE amount = 0");
$cache = array();

ini_set('memory_limit', '512M');
$i = 0;

while($z = pg_fetch_row($zeroes)) {
	$txBits = $z[0];
	$n = $z[1];
	$blockBits = $z[2];

	$hash = bits2hex($blockBits);
	$txHash = bits2hex($txBits);

	if(!isset($cache[$hash])) {
		$cache[$hash] = json_decode(shell_exec('bitcoind getblockbyhash '.$hash), true);
	}

	foreach($cache[$hash]['tx'] as $tx) {
		if($tx['hash'] !== $txHash) continue;

		$amount = btc2satoshi($tx['out'][$n]['value']);
		if($amount == 0) continue;

		pg_query("
		UPDATE tx_out
		SET amount = $amount
		AND tx_out.transaction_id = B'$txBits'
		AND tx_out.n = $n
		");

		break;
	}

	echo "\r".(++$i);
}

echo "\n";
*/

/* Update "previous block" of old blocks
$max = trim(shell_exec('bitcoind getblockcount'));
$c = strlen($max);

for($i = 1; $i <= $max; ++$i) {
	$fI = str_pad($i, $c, '0', STR_PAD_LEFT);
	$blk = json_decode(shell_exec('bitcoind getblockbycount '.$i), true);

	echo "\r$fI/$max processing... (".$blk['hash'].")";

	$prev = hex2bits($blk['prev_block']);
	$bits = hex2bits($blk['hash']);

	pg_query("UPDATE blocks SET previous_hash = B'$prev' WHERE hash = B'$bits'");
}
*/
