<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

function insertBlock($number, $blk, $forceQueries = false) {
	$blkBits = hex2bits($blk['hash']);
	$time = $blk['time'];
	$prevBits = (preg_match('%^0{64}$%', $blk['prev_block'])) ? 'NULL' : "B'".hex2bits($blk['prev_block'])."'";
	$blkSize = $blk['size'];

	$txBits = array();
	$txAlreadyHave = array();
	$tx_ = array();
	$tx_in = array();
	$tx_out = array();

	foreach($blk['tx'] as $tx) {
		$txBits[$tx['hash']] = hex2bits($tx['hash']);
	}
	$txReq = implode(',', array_map(function($b) { return "B'$b'"; }, $txBits));
	$req = pg_query("
	SELECT transaction_id
	FROM transactions
	WHERE transaction_id IN ($txReq)
	");
	while($r = pg_fetch_row($req)) {
		$txAlreadyHave[$r[0]] = true;
	}

	$coinbase = $blk['tx'][0]['in'][0]['coinbase'];
	$coinbase = "E'\\\\x$coinbase'";

	foreach($blk['tx'] as $tx) {
		$txId = $txBits[$tx['hash']];
		if(isset($txAlreadyHave[$txId])) continue;

		$size = $tx['size'];
		$tx_[] = "(B'$txId', $size)";

		foreach($tx['in'] as $n => $in) {
			if(isset($in['coinbase'])) {
				continue;
			}

			$prevN = $in['prev_out']['n'];
			$prevOut = hex2bits($in['prev_out']['hash']);
			$tx_in[] = "(B'$txId', $n, $prevN, B'$prevOut')";
		}

		foreach($tx['out'] as $n => $out) {
			if(preg_match($r = '%.*([0-9a-f]{130}).*$%U', $out['scriptPubKey'])) {
				$pubKey = preg_replace($r, '$1', $out['scriptPubKey']);
				$address = "B'".hex2bits(Bitcoin::addressToHash160(Bitcoin::pubKeyToAddress($pubKey)))."'";
				$type = 'PubKey';
			} else if(preg_match($r = '%^.*([0-9a-f]{40}).*$%U', $out['scriptPubKey'])) {
				$address = "B'".hex2bits(preg_replace($r, '$1', $out['scriptPubKey']))."'";
				$type = 'Address';
			} else {
				$address = 'NULL';
				$type = 'Unknown';
				trigger_error('Unknown scriptPubKey type: '.$out['scriptPubKey'], E_USER_ERROR);
			}
			$amount = btc2satoshi($out['value']);

			$tx_out[] = "(B'$txId', $n, $amount, $address, '$type')";
		}
	}

	$block = "INSERT INTO blocks(hash, time, previous_hash, number, coinbase, size)
	VALUES (B'$blkBits', $time, $prevBits, $number, $coinbase, $blkSize)";	
	$block_trans = "INSERT INTO blocks_transactions(block, transaction_id)
	VALUES ".implode(',', array_map(function($b) use($blkBits) { return "(B'$blkBits', B'$b')"; }, $txBits));
	if(count($tx_) > 0) {
		$trans = "INSERT INTO transactions(transaction_id, size) VALUES ".implode(',', $tx_);
	} else $trans = '';
	if(count($tx_out) > 0) 
		$out = "INSERT INTO tx_out(transaction_id, n, amount, address, type) VALUES ".implode(',', $tx_out);
	else $out = '';
	if(count($tx_in) > 0) 
		$in = "INSERT INTO tx_in(transaction_id, n, previous_n, previous_out) VALUES ".implode(',', $tx_in);
	else $in = '';
		
	pg_query("
	BEGIN;
	$block;
	$trans;
	$out;
	$in;
	$block_trans;
	COMMIT;
	");
}
