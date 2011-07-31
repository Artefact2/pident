<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

function formatFactoid($factoids, $key) {
	$f = $factoids[$key];

	if($key == 'average_block_size') {
		$avg = formatSize($f);
		return "The average size of a block is <strong>$avg</strong>.";
	} else if($key == 'average_recent_block_size') {
		$avg = formatSize($f);
		return "The average size of recent blocks is <strong>$avg</strong>.";
	} else if($key == 'average_transaction_count') {
		$avg = formatNumber($f, 2);
		return "Blocks contain, in average, <strong>$avg</strong> transactions.";
	} else if($key == 'transaction_count') {
		$count = formatInt($f);
		return "The block chain contains <strong>$count</strong> different transactions.";
	} else if($key == 'block_with_most_transactions') {
		$number = $f[0];
		$count = formatInt($f[1]);
		$fNum = formatInt($f[0]);
		return "The block with the most transactions is block <a href='/b/$number'>$fNum</a>, and it contains <strong>$count</strong> transactions.";
	} else if($key == 'biggest_block') {
		$number = $f[0];
		$fNum = formatInt($f[0]);
		$size = formatSize($f[1]);
		return "The biggest block in the entire block chain is block <a href='/b/$number'>$fNum</a>. Its size is <strong>$size</strong>.";
	} else if($key == 'average_transaction_count_recent') {
		$avg = formatNumber($f, 2);
		return "Recent blocks contain, in average, <strong>$avg</strong> transactions.";
	} else if($key == 'average_generated_btc_recent') {
		$avg = formatSatoshi(bcadd($f, 0, 0));
		$unit = TONAL ? 'TBC' : 'BTC';
		return "Recent blocks generate, in average, <strong>$avg $unit</strong>, including transaction fees.";
	} else if($key == 'maximum_generated_btc') {
		$amount = formatSatoshi(bcadd($f[1], 0, 0));
		$unit = TONAL ? 'TBC' : 'BTC';
		$number = $f[0];
		$fNum = formatInt($f[0]);
		return "The block who generated the most $unit ever is block <a href='/b/$number'>$fNum</a>: it generated <strong>$amount $unit</strong>!";
	} else if($key == 'maximum_generated_btc_recent') {
		$amount = formatSatoshi(bcadd($f[1], 0, 0));
		$unit = TONAL ? 'TBC' : 'BTC';
		$number = $f[0];
		$fNum = formatInt($f[0]);
		return "The block who generated the most $unit recently is block <a href='/b/$number'>$fNum</a>. It generated <strong>$amount $unit</strong>.";
	} else if($key == 'biggest_transaction_ever') {
		$number = $f[0];
		$fNum = formatInt($f[0]);
		$txId = bits2hex($f[1]);
		$size = formatSize($f[2]);
		return "The biggest transaction ever is transaction <a href='/tx/$txId'>$txId</a>. It appeared in block <a href='/b/$number'>$fNum</a> and its size is <strong>$size</strong>.";
	} else if($key == 'biggest_transaction_recent') {
		$number = $f[0];
		$fNum = formatInt($f[0]);
		$txId = bits2hex($f[1]);
		$size = formatSize($f[2]);
		return "The biggest transaction made recently is transaction <a href='/tx/$txId'>$txId</a>. It appeared in block <a href='/b/$number'>$fNum</a> and its size is <strong>$size</strong>.";
	} else if($key == 'largest_transaction') {
		$txId = bits2hex($f[0]);
		$amount = formatSatoshi($f[1]);
		$unit = TONAL ? 'TBC' : 'BTC';
		$percent = $f[1] / (pow(10, 8) * 210000. * 50. * 2.);
		$percent = formatNumber((TONAL ? 0x100 : 100) * $percent, 2);
		return "Transaction <a href='/tx/$txId'>$txId</a> is the transaction that moved the most $unit ever: <strong>$amount $unit</strong> were transferred! That's about $percent % of all the $unit it is possible to generate.";
	} else if($key == 'address_count') {
		$count = $f;
		$zeroes = log($count / pow(2, 160), TONAL ? 16 : 10);
		$zeroes = floor(-$zeroes);
		$zeroes -= 2;
		$prop = str_repeat('0', $zeroes).'1';
		$prop = substr($prop, 0, 1).'.'.substr($prop, 1);
		$fCount = formatInt($count);
		return "There are <strong>$fCount</strong> different addresses in the block chain. That's less than $prop % of all the addresses that can be generated.";
	} else if($key == 'most_popular_address') {
		$address = Bitcoin::hash160ToAddress(bits2hex($f[0]));
		$count = formatInt($f[1]);
		return "The address <a href='/address/$address'>$address</a> is very popular! It appeared in <strong>$count</strong> transactions.";
	}

	else {
		trigger_error('Unknown factoid key: '.$key, E_USER_WARNING);
	}
}

function formatRandomFactoid($factoids) {
	$k = array_keys($factoids);
	$c = count($k);
	
	return formatFactoid($factoids, $k[mt_rand(0, $c - 1)]);
}
