<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

function fetchAddressTransactions($hash160) {
	$bits = hex2bits($hash160);
	$firstBlock = null;

	$req = "
	SELECT transaction_id, address_from, amount, block, number
	FROM address_from
	WHERE address = B'$bits'
	ORDER BY number ASC
	";
	$in = array();
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$in[$r[0]]['amount'] = $r[2];
		$in[$r[0]]['block'] = bits2hex($r[3]);
		$in[$r[0]]['blockNumber'] = $r[4];
		if($r[1]) $in[$r[0]]['addresses'][$r[1]] = true;

		if($firstBlock === null) {
			$firstBlock = $in[$r[0]]['block'];
		}
	}

	if($firstBlock === null) {
		header('Content-Type: text/plain');
		header('HTTP/1.1 404 Not Found', true, 404);
		die('Address not found. U mad bro?');
	}

	$req = "
	SELECT transaction_id, address_to, amount, block, transaction_id_from, number
	FROM address_to
	WHERE address = B'$bits'
	ORDER BY number ASC
	";
	$out = array();
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$out[$r[0]]['amount'][$r[4]] = $r[2];
		$out[$r[0]]['block'] = bits2hex($r[3]);
		$out[$r[0]]['blockNumber'] = $r[5];
		$out[$r[0]]['addresses'][$r[1]] = true;
	}

	return array($firstBlock, $in, $out);
}

function fetchTransactions($blockHash, $txHash = null) {
	$bits = hex2bits($blockHash ?: $txHash);

	if($blockHash) {
		$req = "
		SELECT time, found_by, number, size, coinbase
		FROM blocks
		WHERE blocks.hash = B'$bits'
		";

		$req = pg_query($req);
		$r = pg_fetch_row($req);
		if($r == false || !isset($r[0])) {
			header('Content-Type: text/plain', true, 404);
			header('HTTP/1.1 404 Not Found', true, 404);
			die('Block not found. U jelly?');
		}
		$time = date('Y-m-d H:i:s', $r[0]);
		$foundBy = $r[1];
		$number = $r[2];
		$size = $r[3];
		$block = $blockHash;
		$coinbase = $r[4];

		$condition = "block = B'$bits'";
	} else {
		$req = "
		SELECT block, time, number, blocks.size
		FROM blocks_transactions
		JOIN blocks ON blocks_transactions.block= blocks.hash
		WHERE transaction_id = B'$bits'
		ORDER BY blocks.number ASC
		LIMIT 1
		";

		$req = pg_query($req);
		$r = pg_fetch_row($req);
		if($r == false || !isset($r[0])) {
			header('Content-Type: text/plain', true, 404);
			header('HTTP/1.1 404 Not Found', true, 404);
			die('Transaction not found. U mad bro?');
		}
		$block = bits2hex($r[0]);
		$time = date('Y-m-d H:i:s', $r[1]);
		$foundBy = null;
		$number = $r[2];
		$size = $r[3];
		$coinbase = null;

		$condition = "transactions.transaction_id = B'$bits'";
	}

	$req = "
	SELECT DISTINCT transactions.transaction_id, address, amount, is_payout, n, transactions.size
	FROM blocks_transactions
	JOIN transactions ON blocks_transactions.transaction_id = transactions.transaction_id
	JOIN tx_out ON tx_out.transaction_id = transactions.transaction_id
	WHERE $condition
	";
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$transactions[bits2hex($r[0])]['out'][] = array(
			Bitcoin::hash160ToAddress(bits2hex($r[1])), $r[2], $r[3], $r[4], $r[5]
		);
	}

	$req = "
	SELECT DISTINCT transactions.transaction_id, tx_out.address, tx_out.amount, tx_out.transaction_id
	FROM blocks_transactions
	JOIN transactions ON transactions.transaction_id = blocks_transactions.transaction_id
	JOIN tx_in ON tx_in.transaction_id = transactions.transaction_id
	JOIN tx_out ON tx_out.transaction_id = tx_in.previous_out AND tx_out.n = tx_in.previous_n
	WHERE $condition
	";
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$transactions[bits2hex($r[0])]['in'][] = array(
			Bitcoin::hash160ToAddress(bits2hex($r[1])), $r[2]
		);
	}

	return array($block, $time, $number, $foundBy, $size, $coinbase, $transactions);
}

function formatTransactionsTable($transactions) {
	$cols = "<tr>
<th>#</th>
<th>Transaction id</th>
<th>Size</th>
<th>Fee</th>
<th>From (amount)</th>
<th>To (amount)</th>
</tr>";

	$totalGenerated = null;

	$rows = array();
	foreach($transactions as $id => $tx) {

		$cmp = function($a, $b) { return bccomp($b[1], $a[1]); };
		if(isset($tx['in']) && is_array($tx['in'])) usort($tx['in'], $cmp);
		else $tx['in'] = array();
		if(isset($tx['out']) && is_array($tx['out'])) {
			usort($tx['out'], $cmp);
			$size = formatSize($tx['out'][0][4]);
		} else {
			$tx['out'] = array();
			$size = 'N/A';
		}

		$fee = "0";
		$a = array();
		$b = array();
		foreach($tx['in'] as $d) {
			list($address, $amount) = $d;
			$fee = bcadd($fee, $amount);
			$amount = formatSatoshi($amount);
			$a[] = "<tr>\n<td class='addr'><a href='/address/$address#out_$id'>$address</a></td>\n<td class='amount'>$amount</td>\n</tr>\n";
		}
		foreach($tx['out'] as $d) {
			list($address, $amount, $isPayout) = $d;
			if($address == '') $address = '<em>&ltsome address?&gt;</em>';
			$fee = bcsub($fee, $amount);
			$amount = formatSatoshi($amount);
			$b[] = "<tr>\n<td class='addr'><a href='/address/$address#in_$id'>$address</a></td>\n<td class='amount'>$amount</td>\n</tr>\n";
		}

		if($generated = (bccomp($fee, '0') < 0)) {
			$totalGenerated = formatSatoshi(-$fee);
			$fee = 'N/A';
			$unit = TONAL ? 'TBC' : 'BTC';
			$a = array("<tr><td>Generated $unit: ".$totalGenerated."</td></tr>");
		} else $fee = formatSatoshi($fee);

		$row = "<tr id='$id'>\n"
			."<td><a href='#$id'>#</a></td>\n"
			."<td><a href='/tx/$id' title='$id'>".substr($id, 0, 14)."…</a></td>\n"
			."<td>$size</td>\n"
			."<td>".$fee."</td>\n"
			."<td class='inout'>\n<table>\n".implode('', $a)."</table>\n</td>\n"
			."<td class='inout'>\n<table>\n".implode('', $b)."</table>\n</td>\n"
			."</tr>\n";

		if($generated) {
			array_unshift($rows, $row);
		} else {
			array_push($rows, $row);
		}
	}
	$rows = implode('', $rows);

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
<th colspan='2'>&#9650; Block</th>
<th>$label</th>
<th>Amount</th>
</tr>";


	$rows = '';
	foreach($data as $id => $tx) {
		$id = bits2hex($id);
		$block = $tx['block'];
		$blockNum = $tx['blockNumber'];
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

		$blockNumber = (TONAL ? 't' : '').urlencode(formatInt($blockNum, false));

		$rows .= "<tr id='$prefix$id'>\n";
		$rows .= "<td><a href='#$prefix$id'>#</a></td>\n";
		$rows .= "<td><a href='/tx/$id' title='$id'>".substr($id, 0, 14)."…</a></td>\n";
		$rows .= "<td><a href='/b/$blockNumber'>".formatInt($blockNum)."</a></td>\n";
		$rows .= "<td><a href='/block/$block#$id'>".$block."</a></td>\n";
		$rows .= "<td>\n".$a."\n</td>\n";
		$rows .= "<td>\n".formatSatoshi($amount)."\n</td>\n";
		$rows .= "</tr>\n";
	}

	if(!$rows) {
		$rows = "<tr><td colspan='6'>No transactions yet.</td></tr>\n";
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

function formatRecentBlocks($n, $page = 0, $foundBy = null, $recentScores = 0) {
	if($foundBy !== null) {
		$cond = "found_by = '$foundBy'";
	} else $cond = 'true';

	if($page > 0) {
		$blockCount = pg_fetch_row(pg_query('SELECT COUNT(hash) FROM blocks WHERE '.$cond.';'));
		$blockCount = $blockCount[0];

		$offset = ($page - 1) * $n;
		if($offset >= $blockCount) {
			header('Content-Type: text/plain');
			header('HTTP/1.1 404 Not Found', true, 404);
			die('Invalid page number. Kthxbai!');
		}

		$maxPage = ceil($blockCount / $n);
		$pagination = ($maxPage > 1) ? "<tr><td colspan='6'>".makePagination('p', $page, $n, $blockCount, '&lt; Newer blocks', 'Older blocks &gt;')."</td></tr>\n" : '';
	} else {
		$offset = 0;
		$pagination = '';
		$maxPage = 1;
	}

	$req = pg_query("
	SELECT blocks.hash, time, found_by, number, size
	FROM blocks
	WHERE $cond
	ORDER BY number DESC
	LIMIT $n OFFSET $offset
	");

	$cols = "<tr>
<th title='Do not trust this value, it is based on the local time of the node which found the block.'><span>When</span></th>
<th colspan='2'>&#9660; Block</th>
<th>Size</th>
<th>Found by</th>
<th>See scores</th>
</tr>";

	$rows = '';
	$now = time();
	$i = 0;
	$blacklist = array();
	while($r = pg_fetch_row($req)) {
		$block = bits2hex($r[0]);
		$blkNum = $r[3];
		$rows .= "<tr>\n";

		if($i++ < $recentScores && !$r[2]) {
			$scores = fetchScores($block);
			$guess = identifyPool($scores);
			if($guess !== null) list($confidence, $pool) = $guess;
			if($guess === null || isset($blacklist[$pool])) {
				$pool = 'N/A';
			} else {
				$pool = prettyPool($pool)." <small>($confidence)</small>";
			}
		} else {
			$blacklist[$r[2]] = true;
			$pool = prettyPool($r[2]);
		}
		
		$blockNumber = (TONAL ? 't' : '').urlencode(formatInt($blkNum, false));

		$rows .= "<td>".prettyDuration($now - $r[1], 2)." ago</td>\n";
		$rows .= "<td><a href='/b/$blockNumber'>".formatInt($blkNum)."</a></td>";
		$rows .= "<td><a href='/block/$block'>$block</a></td>\n";
		$rows .= "<td>".formatSize($r[4])."</td>\n";
		$rows .= "<td>$pool</td>\n";
		$rows .= "<td><a href='/score/$block'>Scores</a></td>\n";

		$rows .= "</tr>\n";
	}

	return array($maxPage, "<table>
<thead>
$pagination$cols
</thead>
<tfoot>
$cols$pagination
</tfoot>
<tbody>
$rows
</tbody>
</table>
");
}

function formatPools() {
	$backlog = $GLOBALS['conf']['maximum_backlog'];
	$threshold = pg_fetch_row(pg_query('SELECT MAX(number) FROM blocks;'));
	$threshold = $threshold[0] - $backlog;
	$props = array();

	$req = pg_query("
	SELECT found_by, COUNT(hash), MIN(number), MAX(number)
	FROM blocks
	WHERE found_by IS NOT NULL
	AND number >= $threshold
	GROUP BY found_by
	ORDER BY found_by ASC
	");

	$other = 1.00;

	$rows = array();
	while($r = pg_fetch_row($req)) {
		$row = "<tr>\n";

		$pool = $r[0];
		$count = $r[1];
		$prettyPool = prettyPool($pool);

		$lag = ($r[2] - $threshold) / $backlog;
		$endLag = ($r[3] - $threshold) / $backlog;
		if($lag > 0.2 || $endLag < 0.8) $info = ' <span title="We do not have enough data over this timespan to give an accurate result. This will solve itself after some time.">(inaccurate)</span>';
		else $info = '';

		$mtbb = ($r[3] - $r[2]) / $r[1];
		$prop = $r[1] / max($r[3] - $r[2] + $mtbb, $backlog);
		$opacity = round(1 - cos($prop * M_PI), 2);
		$fProp = TONAL ? tonalNumberFormat(0x100 * $prop, $prop < 1/0x100 ? 2 : 1) : 
			number_format(100 * $prop, $prop < 0.01 ? 2 : 1);
		$fProp = ($info ? '~' : '').$fProp.' %';

		$other -= $prop;
		$props[$pool] = $prop;

		$row .= "<td>$prettyPool</td>\n";
		$row .= "<td><a href='/pool/$pool'>".formatInt($count)."</a>$info</td>\n";
		$row .= "<td><span class='pool prop' style='background-color: rgba(255, 255, 127, $opacity);'>$fProp</span></td>\n";

		$row .= "</tr>\n";

		$rows[] = array($prop, $row);
	}
	                                             /* Meh */
	usort($rows, function($a, $b) { return (int)(100000000 * ($b[0] - $a[0])); }); 
	$fRows = '';
	foreach($rows as $row) $fRows .= $row[1];

	$fOther = TONAL ? tonalNumberFormat(0x100 * $other, $other < 1/0x100 ? 2 : 1) : 
		number_format(100 * $other, $other < 0.01 ? 2 : 1);
	$fRows .= "<tr class='p_other'>\n<td>Other / Solo</td>\n<td>N/A</td>\n<td>~$fOther %</td>\n</tr>\n";
	
	return array("<table>
<thead>
<tr>
<th>Pool</th>
<th>Blocks found recently</th>
<th>&#9660; Pool size <small>(relative to the whole network)</small></th>
</tr>
</thead>
<tbody>
$fRows
</tbody>
</table>
", $props);
}

function formatScores($block) {
	$normalized = fetchScores($block);

	$rows = '';
	foreach($normalized as $pool => $nScore) {
		$rows .= "<tr>\n";

		$rows .= "<td>".prettyPool($pool)."</td>\n";
		$rows .= "<td>".($nScore >= 0 ? (TONAL ? tonalNumberFormat($nScore, 3) : number_format($nScore, 3)) : 'N/A')."</td>\n";

		$rows .= "</tr>\n";
	}
	
	return "<table>
<thead>
<tr>
<th>Pool</th>
<th>&#9660; Normalized score</th>
</tr>
</thead>
<tbody>
$rows
</tbody>
</table>
";
}

function formatPoolSizeChart($props) {
	arsort($props);

	$parts = array();
	$legend = array();

	$tZero = null;
	foreach($props as $pool => $prop) {
		$prop = 2 * M_PI * $prop;
		if($tZero === null) $tZero = -$prop / 2;
		$parts[] = array($prop, $color = extractPoolColor($pool));
		$legend[$pool] = $color;
	}

	$svg = getSVGPie($tZero, $parts, 'width: 40em; height: 40em;');
	$fLegend = "<ul>\n";

	foreach($legend as $pool => $color) {
		$fLegend .= "<li><span style='color: $color;'>&#9632;</span> $pool</li>\n";
	}

	$fLegend .= "</ul>";

	return array($svg, $fLegend);
}
