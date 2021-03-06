<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

const BLOCK_HASHES = 1; /* Function returns block hashes */
const BLOCK_NUMBERS = 2; /* Function returns block numbers */
const BLOCK_GENTXID = 3; /* Function returns generation transaction IDs */

function curl_get_uri($uri, $headers = array()) {
	$c = curl_init($uri);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_USERAGENT, $GLOBALS['conf']['user_agent']);
	curl_setopt($c, CURLOPT_FAILONERROR, true);
	curl_setopt($c, CURLOPT_COOKIEJAR, "cookies.txt");
	curl_setopt($c, CURLOPT_COOKIEFILE, "cookies.txt");
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($c);

	if($result === false) {
		trigger_error('curl_get_uri(): '.curl_error($c), E_USER_WARNING);
	}

	curl_close($c);

	return $result;
}

function curl_simulate_xmlhttprequest($uri) {
	$c = curl_init($uri);
}

function curl_send_request($uri, $postFields, $headers = array()) {
	$c = curl_init($uri);

	curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($postFields, '', '&'));
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_HEADER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);

	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_COOKIEJAR, "cookies.txt");
	curl_setopt($c, CURLOPT_COOKIEFILE, "cookies.txt");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_USERAGENT, $GLOBALS['conf']['user_agent']);
	curl_setopt($c, CURLOPT_FAILONERROR, true);
	$result = curl_exec($c);

	if($result === false) {
		trigger_error('curl_send_request(): '.curl_error($c), E_USER_WARNING);
	}

	curl_close($c);

	return $result;
}

$foundBy = array(
	'DeepBit' => function() {
		$main = curl_get_uri('https://deepbit.net/stats');
		preg_match_all("%href='/stats/([0-9]+)'%", $main, $matches);

		$p = $main;
		$p .= curl_get_uri('https://deepbit.net/stats/'.$matches[1][0]);

		preg_match_all("%href='http://blockexplorer.com/block/([0-9a-f]{64})'%", $p, $matches);
		return array(BLOCK_HASHES, $matches[1]);
	},
	'MtRed' => function() {
		$blockPage = curl_get_uri('https://mtred.com/blocks.html');
		preg_match_all('%href="http://blockexplorer.com/tx/([0-9a-f]{64})"%', $blockPage, $matches);
		return array(BLOCK_GENTXID, $matches[1]);
	},
	'BTCGuild' => function() {
		$main = curl_get_uri('https://www.btcguild.com/blocks.php');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		return array(BLOCK_NUMBERS, $blocksN[1]);
	},
	'Bitcoins.lc' => function() {
		$main = curl_get_uri('http://www.bitcoins.lc/round-information');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		return array(BLOCK_NUMBERS, $blocksN[1]);
	},
	'Mineco.in' => function() {
		$main = curl_get_uri('https://mineco.in/blocks');
		preg_match_all("%href=\"http://blockexplorer.com/block/([0-9a-f]{64})\"%", $main, $matches);
		return array(BLOCK_HASHES, $matches[1]);
	},
	'Ozco.in' => function() {
		if(!$GLOBALS['conf']['ozcoin']['username'] || !$GLOBALS['conf']['ozcoin']['password']) {
			return array(-1, array());
		}

		$login = curl_get_uri('https://ozco.in');
		
		$post['username'] = $GLOBALS['conf']['ozcoin']['username'];
		$post['password'] = $GLOBALS['conf']['ozcoin']['password'];

		curl_send_request('https://ozco.in/login.php', $post);

		$main = curl_get_uri('https://ozco.in/blocks.php');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		return array(BLOCK_NUMBERS, $blocksN[1]);
	},
	'Slush' => function() {
		$main = curl_get_uri('http://mining.bitcoin.cz/stats/');
		preg_match_all('%href=\'http://blockexplorer.com/block/([0-9a-f]{64})\'%', $main, $matches);
		return array(BLOCK_HASHES, $matches[1]);
	},
	'Eligius' => function() {
		$main = curl_get_uri('http://eligius.st/~artefact2/blocks/');
		preg_match_all('%title="([0-9a-fA-F]{64})"%', $main, $matches);
		return array(BLOCK_HASHES, array_map('strtolower', $matches[1]));
	},
	'BTCMine' => function() {
		$main = curl_get_uri('http://btcmine.com/stats/');
		$main = str_replace(array(' ', "\n"), '', $main);
		preg_match_all('%<trclass="(even|odd)"><td>[0-9]+</td><td>([0-9]+)</td>%', $main, $matches);

		return array(BLOCK_NUMBERS, $matches[2]);
	},
	'Bitcoinpool' => function() {
		$main = curl_get_uri('http://www.bitcoinpool.com/index.php?do=blocks');
		$main = str_replace(array(' ', "\n", "\t"), '', $main);
		$main = preg_replace('%<tr.+Invalid</td></tr>%U', '', $main);
		preg_match_all('%<aclass="clink".+>([0-9]+)</a></td>%U', $main, $matches);

		return array(BLOCK_NUMBERS, $matches[1]);
	},
	'ArsBitcoin' => function() {
		$main = curl_get_uri('https://arsbitcoin.com/blocks.php');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		return array(BLOCK_NUMBERS, $blocksN[1]);
	},
	'BitPit' => function() {
		$main = curl_get_uri('https://pool.bitp.it/rounds');
		preg_match_all('%href="http://(blockexplorer|pident.artefact2).com/b/([0-9]+)"%', $main, $blocksN);

		return array(BLOCK_NUMBERS, $blocksN[2]);
	},
	'EclipseMC' => function() {
		if(!($api = $GLOBALS['conf']['eclipsemc_apikey'])) {
			return array(-1, array());
		}

		$json = json_decode(curl_get_uri("https://eclipsemc.com/api.php?key=$api&action=blockstats"), true);
		$blocks = array();
		foreach($json['blocks'] as $blk) {
			$blocks[] = $blk['id'];
		}

		return array(BLOCK_NUMBERS, $blocks);
	},
	'TripleMining' => function() {
		$main = curl_get_uri('https://www.triplemining.com/stats');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		return array(BLOCK_NUMBERS, $blocksN[1]);
	},
	'RFCPool' => function() {
		$rounds = json_decode(curl_get_uri('https://www.rfcpool.com/api/pool/blocks'), true);
		$hashs = array();
		foreach($rounds['blocks'] as $r) {
			$hashs[] = $r['hash'];
		}

		return array(BLOCK_HASHES, $hashs);
	},
	'BTCMP' => function() {
		curl_get_uri('http://btcmp.com/');
		$cookies = file('cookies.txt');
		$sessionId = false;
		foreach($cookies as $cookie) {
			// The line we are looking for looks like this :
			// btcmp.com	FALSE	/	FALSE	1311939896	session_id	035b9cb1511ee713f9484d93ab92276bf997446c

			if(strpos($cookie, 'btcmp.com') !== 0) continue;
			if(strpos($cookie, 'session_id') === false) continue;

			$k = explode("\t", $cookie);
			$sessionId = trim(array_pop($k));
			break;
		}

		assert('$sessionId !== false');

		$p = curl_send_request('http://btcmp.com/methods/pool/list_blocks', 
			array('limit' => 60, '_token' => $sessionId), 
			array('X-Requested-With: XMLHttpRequest')
		);

		$json = json_decode($p, true);

		$blks = array();
		foreach($json['blockstats'] as $blk) {
			$blks[] = $blk['blockhash'];
		}
		
		return array(BLOCK_HASHES, $blks);
	},
	'PolMine' => function() {
		$blockPage = curl_get_uri('http://polmine.pl/?action=statistics');
		preg_match_all('%href=\'http://blockexplorer.com/tx/([0-9a-f]{64})\'%', $blockPage, $matches);
		return array(BLOCK_GENTXID, $matches[1]);
	},
	'MainframeMC' => function() {
		if(!$GLOBALS['conf']['mmc']['username'] || !$GLOBALS['conf']['mmc']['password']) {
			return array(-1, array());
		}

		$login = curl_get_uri('http://mining.mainframe.nl/stats');
		
		$post['username'] = $GLOBALS['conf']['mmc']['username'];
		$post['password'] = $GLOBALS['conf']['mmc']['password'];

		curl_send_request('http://mining.mainframe.nl/login', $post);

		$main = curl_get_uri('http://mining.mainframe.nl/statsAuth');
		$main = explode('Blocks Found', $main, 2);
		$main = array_pop($main);

		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		return array(BLOCK_NUMBERS, $blocksN[1]);
	},
	'NoFeeMining' => function() {
		$main = curl_get_uri('https://www.nofeemining.com/');
		$main = explode('Block Stats', $main, 2);
		$main = array_pop($main);

		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		return array(BLOCK_NUMBERS, $blocksN[1]);
	},
	'BitMinter' => function() {
		$page = curl_get_uri('https://bitminter.com/blocks');
		preg_match_all('%href="/block/([0-9]+)"%', $page, $matches); /* FIXME: filter invalid blocks */
		return array(BLOCK_NUMBERS, $matches[1]);
	},
	'SimpleCoin' => function() {
		$page = curl_get_uri('http://simplecoin.us/blocks.php');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $page, $matches); /* FIXME: filter invalids */
		return array(BLOCK_NUMBERS, $matches[1]);
	},
	'BTCServ' => function() {
		$page = curl_get_uri('http://btcserv.net/pool/round-stats/');
		$page = str_replace(array(',', ' ', "\n", "\t"), '', $page);
		preg_match_all('%<tr><td>([0-9]+)</td><td>%', $page, $matches);
		return array(BLOCK_NUMBERS, $matches[1]);
	},
);

/* Accurate methods */
$identifyPayouts['Eligius'] = function($blk) {
	return identifyGenerationAddresses($blk, false);
};

/* Somewhat working methods */
$identifyPayouts['DeepBit'] = function($blk) { 
	return identifyWithCriteria($blk, 2, 
		function($fee) { return bccomp($fee, 0) == 0; },
		function($out) { return $out == 2; },
		function($in) { return $in == 1; }	
	);
};

/* Maybe working? */
$identifyPayouts['Bitcoins.lc'] = function($blk) {
	return identifyWithCriteria($blk, 2, function($x) { return bccomp($x, 0) == 0; });
};

/* Callback to use when none is specified */
$fallbackIdentify = function($blk) {
	return identifyWithCriteria($blk, 8, function($x) { return bccomp($x, 0) == 0; });
};

/* Pools will be processed in this order, possibly overwriting conflicts if any.
 * So put the most trusted pools at the end of the list, and the least trusted in the beginning !
 */
$poolsTrust = array(
	/* Known to report wrong block numbers sometimes */
	'ArsBitcoin',    // Clearly reports incorrect block numbers (even for valid rounds), sometimes.
	'Bitcoins.lc',
	'Bitcoinpool',
	'EclipseMC',

	/* ??? */
	'BTCServ',
	'SimpleCoin',
	'BitMinter',
	'BTCMine',
	'BTCGuild',
	'BitPit',
	'Mineco.in',
	'Ozco.in',
	'MainframeMC',
	'NoFeeMining',
	'Slush',
	'TripleMining',
	'RFCPool',
	'BTCMP',

	/* (Somewhat) trusted */
	'PolMine',       // Uses generation TxIds, not troublesome even if invalid
	'MtRed',         // ^
	'DeepBit',       // Shows blocks hashes directly, okay even if invalid
	'Eligius',       // ^
);

/* --------------------------------------------------------------------------------------------------- */

function identifyGenerationAddresses($blk, $includePubkeyAddress = false) {
	$bits = hex2bits($blk);

	$genTxId = pg_fetch_row(pg_query("
	SELECT DISTINCT tx_out.transaction_id
	FROM tx_out
	LEFT JOIN tx_in ON tx_in.transaction_id = tx_out.transaction_id
	JOIN blocks_transactions ON blocks_transactions.transaction_id = tx_out.transaction_id
	WHERE block = B'$bits'
	AND tx_in.transaction_id IS NULL
	"));

	$genTxIdBits = $genTxId[0];
	$cond = $includePubkeyAddress ? '' : 'AND n <> 0';

	$gen = pg_query("
	UPDATE tx_out SET is_payout = true
	WHERE transaction_id = B'$genTxIdBits'
	$cond
	");
}

function identifyEverything($blk) {
	$bits = hex2bits($blk);
	pg_query("
	UPDATE tx_out SET is_payout = true
	FROM blocks_transactions
	WHERE tx_out.transaction_id = blocks_transactions.transaction_id
	AND block = B'$bits'
	");
}

function identifyWithCriteria($blk, $numDecimals = 8, $feeCallback = null, $outNumCallback = null, $inNumCallback = null) {
	list($block, $time, $number, $foundBy, $size, $coinbase, $transactions) = fetchTransactions($blk, null);

	$payouts = array();
	foreach($transactions as $id => $tx) {
		if(!isset($tx['in'])) $tx['in'] = array();

		if($outNumCallback !== null) {
			if(!call_user_func($outNumCallback, count($tx['out']))) continue;
		}
		if($inNumCallback !== null) {
			if(!call_user_func($inNumCallback, count($tx['in']))) continue;
		}

		$fee = '0';
		foreach($tx['in'] as $in) {
			$fee = bcadd($fee, $in[1], 0);
		}
		foreach($tx['out'] as $out) {
			$fee = bcsub($fee, $out[1], 0);
		}

		if($feeCallback !== null && !call_user_func($feeCallback, $fee)) continue;

		foreach($tx['out'] as $out) {
			$n = $out[3];
			$amount = $out[1];

			$mod = bcpow(10, 8 - $numDecimals);
			if(bccomp(bcmod($amount, $mod), '0') == 0) {
				$payouts[$id][] = $n;
			}
		}
	}

	if(count($payouts) == 0) return;

	$where = array();
	foreach($payouts as $txId => $ns) {
		$where[] = "(transaction_id = B'".hex2bits($txId)."' AND n IN (".implode(',', $ns)."))";
	}
	$where = implode(' OR ', $where);

	pg_query("
	UPDATE tx_out SET is_payout = true
	WHERE $where
	");
}
