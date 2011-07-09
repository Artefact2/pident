<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

function fetchAddressTransactions($hash160) {
	$bits = hex2bits($hash160);

	$req = "
	SELECT time, hash
	FROM tx_out
	JOIN transactions ON transactions.transaction_id = tx_out.transaction_id
	JOIN blocks ON transactions.block = blocks.hash
	WHERE address = B'$bits'
	ORDER BY time ASC
	LIMIT 1
	";
	$req = pg_query($req);
	$r = pg_fetch_row($req);
	if($r == false || !isset($r[0])) {
		header('Content-Type: text/plain', true, 404);
		die('Address not found. Problem?');
	}
	$firstSeen = date('Y-m-d H:i:s', $r[0]);
	$firstBlock = bits2hex($r[1]);

	$req = "
	SELECT transaction_id, address_from, amount, block
	FROM address_from
	WHERE address = B'$bits'
	";
	$in = array();
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$in[$r[0]]['amount'] = $r[2];
		$in[$r[0]]['block'] = bits2hex($r[3]);
		if($r[1]) $in[$r[0]]['addresses'][$r[1]] = true;
	}

	$req = "
	SELECT transaction_id, address_to, amount, block, transaction_id_from
	FROM address_to
	WHERE address = B'$bits'
	";
	$out = array();
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$out[$r[0]]['amount'][$r[4]] = $r[2];
		$out[$r[0]]['block'] = bits2hex($r[3]);
		$out[$r[0]]['addresses'][$r[1]] = true;
	}

	return array($firstSeen, $firstBlock, $in, $out);
}

function fetchTransactions($blockHash, $txHash = null) {
	$bits = hex2bits($blockHash ?: $txHash);

	if($blockHash) {
		$req = "
		SELECT time, found_by
		FROM blocks
		WHERE blocks.hash = B'$bits'
		";

		$req = pg_query($req);
		$r = pg_fetch_row($req);
		if($r == false || !isset($r[0])) {
			header('Content-Type: text/plain', true, 404);
			die('Block not found. U jelly?');
		}
		$time = date('Y-m-d H:i:s', $r[0]);
		$foundBy = $r[1];
		$block = $blockHash;

		$condition = "block = B'$bits'";
	} else {
		$req = "
		SELECT block, time
		FROM transactions
		LEFT JOIN blocks ON blocks.hash = transactions.block
		WHERE transaction_id = B'$bits'
		";

		$req = pg_query($req);
		$r = pg_fetch_row($req);
		if($r == false || !isset($r[0])) {
			header('Content-Type: text/plain', true, 404);
			die('Transaction not found. U mad bro?');
		}
		$block = bits2hex($r[0]);
		$time = date('Y-m-d H:i:s', $r[1]);
		$foundBy = null;

		$condition = "transactions.transaction_id = B'$bits'";
	}

	$req = "
	SELECT transactions.transaction_id, address, amount
	FROM transactions
	LEFT JOIN tx_out ON tx_out.transaction_id = transactions.transaction_id
	WHERE $condition
	";
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$transactions[bits2hex($r[0])]['out'][] = array(
			Bitcoin::hash160ToAddress(bits2hex($r[1])), $r[2]
		);
	}

	$req = "
	SELECT transactions.transaction_id, tx_out.address, tx_out.amount
	FROM transactions
	LEFT JOIN tx_in ON tx_in.transaction_id = transactions.transaction_id
	LEFT JOIN tx_out ON tx_out.transaction_id = tx_in.previous_out AND tx_out.n = tx_in.previous_n
	WHERE $condition
	";
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$transactions[bits2hex($r[0])]['in'][] = array(
			Bitcoin::hash160ToAddress(bits2hex($r[1])), $r[2]
		);
	}

	return array($block, $time, $foundBy, $transactions);
}

function formatTransactionsTable($transactions) {
	$cols = "<tr>
<th>#</th>
<th>Transaction id</th>
<th>Fee</th>
<th>From (amount)</th>
<th>To (amount)</th>
</tr>";


	ob_start();
	foreach($transactions as $id => $tx) {
		echo "<tr id='$id'>\n";
		echo "<td><a href='#$id'>#</a></td>\n";

		usort($tx['in'], $cmp = function($a, $b) { return bccomp($b[1], $a[1]); });	usort($tx['out'], $cmp);

		$fee = "0";
		$a = array();
		$b = array();
		foreach($tx['in'] as $d) {
			list($address, $amount) = $d;
			$fee = bcadd($fee, $amount);
			$amount = formatSatoshi($amount);
			$a[] = "<a href='/address/$address#out_$id'>$address</a>: $amount";
		}
		foreach($tx['out'] as $d) {
			list($address, $amount) = $d;
			if($address == '') $address = '<em>&ltsome address?&gt;</em>';
			$fee = bcsub($fee, $amount);
			$amount = formatSatoshi($amount);
			$b[] = "<a href='/address/$address#in_$id'>$address</a>: $amount";
		}

		if(bccomp($fee, '0') < 0) {
			$totalGenerated = formatSatoshi(-$fee);
			$fee = 'N/A';
			$a = array('Generated BTC: '.$totalGenerated);
		} else $fee = formatSatoshi($fee);

		echo "<td><a href='/tx/$id' title='$id'>".substr($id, 0, 7)."…</a></td>\n";
		echo "<td>".$fee."</td>\n";

		echo "<td>\n".implode("<br />\n", $a)."\n</td>\n";
		echo "<td>\n".implode("<br />\n", $b)."\n</td>\n";

		echo "</tr>\n";
	}

	$rows = ob_get_clean();

	$code = "<table>
<thead>
$cols
</thead>
<tfoot>
$cols
</tfoot>
<tbody>
$rows
</tbody>
</table>
";

	return array($totalGenerated, $code);
}

function formatAddressTransactions($data, $in = true) {
	$label = $in ? 'From' : 'To';
	$prefix = $in ? 'in_' : 'out_';
	$addrPrefix = $in ? 'out_' : 'in_';

	$cols = "<tr>
<th>#</th>
<th>Transaction id</th>
<th>Block</th>
<th>$label</th>
<th>Amount</th>
</tr>";


	ob_start();
	foreach($data as $id => $tx) {
		$id = bits2hex($id);
		$block = $tx['block'];
		$addresses = isset($tx['addresses']) ? $tx['addresses'] : array();
		
		if($in) {
			$amount = $tx['amount'];
		} else {
			$amount = '0';
			foreach($tx['amount'] as $a) {
				$amount = bcadd($amount, $a);
			}
		}

		$a = array();
		foreach($addresses as $address => $true) {
			$address = Bitcoin::hash160ToAddress(bits2hex($address));
			$a[] = "<a href='/address/$address#$addrPrefix$id'>$address</a>";
		}
		$a = (count($a) > 0 ? implode("<br />\n", $a) : ($in ? '(Generated coins)' : '(No address)'));

		echo "<tr id='$prefix$id'>\n";
		echo "<td><a href='#$prefix$id'>#</a></td>\n";
		echo "<td><a href='/tx/$id' title='$id'>".substr($id, 0, 7)."…</a></td>\n";
		echo "<td><a href='/block/$block#$id'>".$block."</a></td>\n";
		echo "<td>\n".$a."\n</td>\n";
		echo "<td>\n".formatSatoshi($amount)."\n</td>\n";
		echo "</tr>\n";
	}

	$rows = ob_get_clean();
	if(!$rows) {
		$rows = "<tr><td colspan='5'>No transactions yet.</td></tr>\n";
	}

	return "<table id='_$prefix'>
<thead>
$cols
</thead>
<tfoot>
$cols
</tfoot>
<tbody>
$rows
</tbody>
</table>
";
}

function formatRecentBlocks($n) {
	$req = pg_query("
	SELECT hash, time, found_by
	FROM blocks
	ORDER BY time DESC
	LIMIT $n
	");

	$cols = "<tr>
<th>&#9660; When</th>
<th>Block</th>
<th>Found by</th>
</tr>";

	$rows = '';
	$now = time();
	while($r = pg_fetch_row($req)) {
		$block = bits2hex($r[0]);
		$rows .= "<tr>\n";

		$rows .= "<td>".prettyDuration($now - $r[1], 2)." ago</td>\n";
		$rows .= "<td><a href='/block/$block'>$block</a></td>\n";
		$rows .= "<td>".prettyPool($r[2])."</td>\n";

		$rows .= "</tr>\n";
	}

	return "<table>
<thead>
$cols
</thead>
<tfoot>
$cols
</tfoot>
<tbody>
$rows
</tbody>
</table>
";
}
