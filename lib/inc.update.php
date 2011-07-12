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

	$tx_ = array();
	$tx_in = array();
	$tx_out = array();

	foreach($blk['tx'] as $tx) {
		$txId = hex2bits($tx['hash']);
		$tx_[] = "(B'$txId', B'$blkBits')";

		foreach($tx['in'] as $n => $in) {
			if(isset($in['coinbase'])) continue;

			$prevN = $in['prev_out']['n'];
			$prevOut = hex2bits($in['prev_out']['hash']);
			$tx_in[] = "(B'$txId', $n, $prevN, B'$prevOut')";
		}

		foreach($tx['out'] as $n => $out) {
			if(preg_match($r = '%^OP_DUP OP_HASH160 ([0-9a-f]{40}) OP_EQUALVERIFY OP_CHECKSIG( OP_CHECKSIG)*( OP_NOP)?$%', $out['scriptPubKey'])) {
				$address = "B'".hex2bits(preg_replace($r, '$1', $out['scriptPubKey']))."'";
			} else if(preg_match($r = '%^([0-9a-f]{130}) OP_CHECKSIG$%', $out['scriptPubKey'])) {
				$pubKey = preg_replace($r, '$1', $out['scriptPubKey']);
				$address = "B'".hex2bits(Bitcoin::addressToHash160(Bitcoin::pubKeyToAddress($pubKey)))."'";
			} else {
				$address = 'NULL';
				trigger_error('Unknown scriptPubKey type: '.$out['scriptPubKey'], E_USER_NOTICE);
			}
			$amount = btc2satoshi($out['value']);

			$tx_out[] = "(B'$txId', $n, $amount, $address)";
		}
	}

	pg_query("INSERT INTO blocks(hash, time, previous_hash, number) VALUES(B'$blkBits', $time, $prevBits, $number);");
	
	if($forceQueries) {
		foreach($tx_ as $v) pg_query("INSERT INTO transactions(transaction_id, block) VALUES $v");
		foreach($tx_out as $v) pg_query("INSERT INTO tx_out(transaction_id, n, amount, address) VALUES $v");
		foreach($tx_in as $v) pg_query("INSERT INTO tx_in(transaction_id, n, previous_n, previous_out) VALUES $v");
	} else {
		pg_query("INSERT INTO transactions(transaction_id, block) VALUES ".implode(',', $tx_).";");
		if(count($tx_out) > 0) pg_query("INSERT INTO tx_out(transaction_id, n, amount, address) VALUES ".implode(',', $tx_out).";");
		if(count($tx_in) > 0) pg_query("INSERT INTO tx_in(transaction_id, n, previous_n, previous_out) VALUES ".implode(',', $tx_in).";");
	}

}
