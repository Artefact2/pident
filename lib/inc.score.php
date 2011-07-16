<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

const C_NOT_SURE_AT_ALL = "not sure at all";
const C_WILD_GUESS = "wild guess";
const C_PROBABLY = "probably";
const C_MOST_LIKELY = "most likely";

function fetchScores($block) {
	static $avgs = null;
	if($avgs === null) {
		$req = pg_query('SELECT * FROM scores_pool_averages;');
		$avgs = array();
		while($r = pg_fetch_assoc($req)) {
			$pool = $r['pool'];
			unset($r['pool']);
			$avgs[$pool] = $r;
		}
	}

	$bits = hex2bits($block);

	list($size, $number, $coinbase, $txCount) = pg_fetch_row(pg_query("
	SELECT size, number, coinbase, COUNT(transaction_id)
	FROM blocks
	JOIN blocks_transactions ON blocks.hash = blocks_transactions.block
	WHERE hash = B'$bits'
	GROUP BY hash, size, number, coinbase
	"));

	$req = pg_query("
	SELECT pool, score_total
	FROM scores_blocks
	JOIN blocks ON scores_blocks.block = blocks.hash
	WHERE block = B'$bits'
	");
	$scores = array();
	while($r = pg_fetch_row($req)) {
		$scores[$r[0]] = $r[1];
	}

	$rawCoinbases = array();
	$coinbases = array();
	$req = pg_query("
	SELECT found_by, MAX(number)
	FROM blocks
	WHERE number < $number
	GROUP BY found_by
	");
	while($r = pg_fetch_row($req)) {
		$rawCoinbases[$r[1]] = $r[0];
	}
	$req = pg_query("
	SELECT number, coinbase
	FROM blocks
	WHERE number IN (".implode(',', array_keys($rawCoinbases)).")
	");
	while($r = pg_fetch_row($req)) {
		$coinbases[$rawCoinbases[$r[0]]] = $r[1];
	}
	

	$normalized = array();
	foreach($avgs as $pool => $a) {
		$score = isset($scores[$pool]) ? $scores[$pool] : 0;
		$coinbase2 = isset($coinbases[$pool]) ? $coinbases[$pool] : null;

		$normalized[$pool] = normalizeScore($score, $size, $txCount, null, $coinbase, $coinbase2, $a);
	}

	arsort($normalized);
	return $normalized;
}

function normalizeScore($rawScore, $size, $txCount, $genCount, $coinbase, $coinbase2, $avg) {
	$score = 0;
	$norm = 0;

	$norm += 10;
	if($rawScore > $avg['score_average']) {
		$n = $rawScore / $avg['score_average'];
		$rawScore = $avg['score_average'] / $n;
	}
	$score += 10 / (1 + abs($rawScore - $avg['score_average']) / $avg['score_stddev']);

	$norm += 3;
	$score += 3 / (1 + abs($size - $avg['block_size_average']) / $avg['block_size_stddev']);

	$norm += 3;
	$score += 3 / (1 + abs($txCount - $avg['transaction_count_average']) / $avg['transaction_count_stddev']);

	if($coinbase2 !== null) {
		$norm += 7;
		$score += 7 / (1 + abs(levenshtein(substr($coinbase, 2), substr($coinbase2, 2)) - $avg['coinbase_distance_average']) / $avg['coinbase_distance_stddev']);
	}

	return $score / $norm;
}

function identifyPool($normalized) {
	$v = array_values($normalized);
	$p = array_keys($normalized);

	if(count($v) == 0) {
		return null;
	} else if(count($v) == 1) {
		/* We have no other pool to compare against */
		return array($v[0] > 0.6 ? C_PROBABLY : C_WILD_GUESS, $p[0]);
	} else {
		$pool = $p[0];
		$diff = $v[0] / $v[1];
		$n = $v[0];

		if($n > 0.95) return array(C_MOST_LIKELY, $pool);
		if($diff > 1.2) return array(C_MOST_LIKELY, $pool);
		else if($diff > 1.15) return array(C_PROBABLY, $pool);
		else if($diff > 1.1) return array(C_WILD_GUESS, $pool);
		else if($diff > 1.05) return array(C_NOT_SURE_AT_ALL, $pool);
	}

	return null;
}
