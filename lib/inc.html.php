<?PHP
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
	ORDER BY number ASC
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

	return array($firstSeen, $firstBlock, $in, $out);
}

function fetchTransactions($blockHash, $txHash = null) {
	$bits = hex2bits($blockHash ?: $txHash);

	if($blockHash) {
		$req = "
		SELECT time, found_by, number
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
		$number = $r[2];
		$block = $blockHash;

		$condition = "block = B'$bits'";
	} else {
		$req = "
		SELECT block, time, number
		FROM transactions
		JOIN blocks ON blocks.hash = transactions.block
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
		$number = $r[2];

		$condition = "transactions.transaction_id = B'$bits'";
	}

	$req = "
	SELECT transactions.transaction_id, address, amount, is_payout, n
	FROM transactions
	LEFT JOIN tx_out ON tx_out.transaction_id = transactions.transaction_id
	WHERE $condition
	";
	$req = pg_query($req);
	while($r = pg_fetch_row($req)) {
		$transactions[bits2hex($r[0])]['out'][] = array(
			Bitcoin::hash160ToAddress(bits2hex($r[1])), $r[2], $r[3], $r[4]
		);
	}

	$req = "
	SELECT transactions.transaction_id, tx_out.address, tx_out.amount
	FROM transactions
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

	return array($block, $time, $number, $foundBy, $transactions);
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

		$cmp = function($a, $b) { return bccomp($b[1], $a[1]); };
		if(isset($tx['in']) && is_array($tx['in'])) usort($tx['in'], $cmp);
		else $tx['in'] = array();
		if(isset($tx['out']) && is_array($tx['out'])) usort($tx['out'], $cmp);
		else $tx['out'] = array();

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
			list($address, $amount, $isPayout) = $d;
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
<th colspan='2'>&#9650; Block</th>
<th>$label</th>
<th>Amount</th>
</tr>";


	ob_start();
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

		echo "<tr id='$prefix$id'>\n";
		echo "<td><a href='#$prefix$id'>#</a></td>\n";
		echo "<td><a href='/tx/$id' title='$id'>".substr($id, 0, 7)."…</a></td>\n";
		echo "<td><a href='/b/$blockNum'>$blockNum</a></td>\n";
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

function formatRecentBlocks($n, $foundBy = null, $recentScores = 0) {
	if($foundBy !== null) {
		$cond = "found_by = '$foundBy'";
	} else $cond = 'true';

	$req = pg_query("
	SELECT hash, time, found_by, number
	FROM blocks
	WHERE $cond
	ORDER BY number DESC
	LIMIT $n
	");

	$cols = "<tr>
<th title='Do not trust this value, it is based on the local time of the node which found the block.'><span>When</span></th>
<th colspan='2'>&#9660; Block</th>
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

		$pool = prettyPool($r[2]);
		if($i++ < $recentScores && $pool == 'N/A') {
			list($avgs, $scores, $normalized) = fetchScores($block);
			$v = array_values($normalized);
			$p = array_keys($normalized);

			if(count($v) == 0) {
				$v = array(0);
				$p = array('N/A');
			}

			if(isset($blacklist[$p[0]])) {
				// Ignore it
			} else if($v[0] > 1.3) {
				$label = 'Certainely';
			} else if($v[0] > 0.9) {
				$label = 'Probably';
			} else if($v[0] > 0.7) {
				$label = 'Maybe';
			}

			if(isset($label)) {
				$pool = "<a href='/score/$block'>$label</a> ".prettyPool($p[0]);
			} else {
				$pool = "<a href='/score/$block'>N/A</a>";
			}
			unset($label);
		} else {
			$blacklist[$r[2]] = true;
		}

		$rows .= "<td>".prettyDuration($now - $r[1], 2)." ago</td>\n";
		$rows .= "<td><a href='/b/$blkNum'>$blkNum</a></td>";
		$rows .= "<td><a href='/block/$block'>$block</a></td>\n";
		$rows .= "<td>$pool</td>\n";
		$rows .= "<td><a href='/score/$block'>Scores</a></td>\n";

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

function formatPools() {
	$backlog = $GLOBALS['conf']['maximum_backlog'];
	$threshold = pg_fetch_row(pg_query('SELECT MAX(number) FROM blocks;'));
	$threshold = $threshold[0] - $backlog;

	$req = pg_query("
	SELECT found_by, COUNT(hash), MIN(number), MAX(number)
	FROM blocks
	WHERE found_by IS NOT NULL
	AND number >= $threshold
	GROUP BY found_by
	ORDER BY found_by ASC
	");

	$rows = '';
	while($r = pg_fetch_row($req)) {
		$rows .= "<tr>\n";

		$pool = $r[0];
		$count = $r[1];
		$prettyPool = prettyPool($pool);

		$lag = ($r[2] - $threshold) / $backlog;
		$endLag = ($r[3] - $threshold) / $backlog;
		if($lag > 0.2 || $endLag < 0.8) $info = ' <span title="We do not have enough data over this timespan to give an accurate result. This will solve itself after some time.">(inaccurate)</span>';
		else $info = '';

		$mtbb = ($r[3] - $r[2]) / $r[1];
		$prop = $r[1] / max($r[3] - $r[2] + $mtbb, 120);
		$opacity = round(1 - cos($prop * M_PI), 2);
		$prop = ($info ? '~' : '').number_format(100 * $prop, 1).' %';

		$rows .= "<td>$prettyPool</td>\n";
		$rows .= "<td><a href='/pool/$pool'>$count</a>$info</td>\n";
		$rows .= "<td><span class='pool prop' style='background-color: rgba(255, 255, 127, $opacity);'>$prop</span></td>\n";

		$rows .= "</tr>\n";
	}
	
	return "<table>
<thead>
<tr>
<th>Pool</th>
<th>Blocks found recently</th>
<th>Pool size <small>(relative to the whole network)</small></th>
</tr>
</thead>
<tbody>
$rows
</tbody>
</table>
";
}

function fetchScores($block) {
	static $avgs = null;
	if($avgs === null) {
		$req = pg_query('SELECT pool, average_score FROM scores_pool_averages;');
		$avgs = array();
		while($r = pg_fetch_row($req)) {
			$avgs[$r[0]] = $r[1];
		}
	}

	$bits = hex2bits($block);
	$req = pg_query("SELECT pool, score_total FROM scores_blocks WHERE block = B'$bits'");
	$scores = array();
	while($r = pg_fetch_row($req)) {
		$scores[$r[0]] = $r[1];
	}

	$normalized = array();
	foreach($scores as $pool => $score) {
		$normalized[$pool] = isset($avgs[$pool]) ? ($scores[$pool] / $avgs[$pool]) : -1;
	}

	arsort($normalized);

	return array($avgs, $scores, $normalized);
}

function formatScores($block) {
	list($avgs, $scores, $normalized) = fetchScores($block);

	$rows = '';
	foreach($normalized as $pool => $nScore) {
		$rows .= "<tr>\n";

		$rows .= "<td>".prettyPool($pool)."</td>\n";
		$rows .= "<td>".($nScore >= 0 ? number_format($nScore, 3) : 'N/A')."</td>\n";
		$rows .= "<td>".number_format($scores[$pool], 3)."</td>\n";
		$rows .= "<td>".(isset($avgs[$pool]) ? number_format($avgs[$pool], 3) : 'N/A')."</td>\n";

		$rows .= "</tr>\n";
	}
	
	return "<table>
<thead>
<tr>
<th>Pool</th>
<th>&#9660; Normalized score</th>
<th>Raw score</th>
<th>Average score of found blocks</th>
</tr>
</thead>
<tbody>
$rows
</tbody>
</table>
";
}
