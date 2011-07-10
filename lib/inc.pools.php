<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

function curl_get_uri($uri) {
	$c = curl_init($uri);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_USERAGENT, $GLOBALS['conf']['user_agent']);
	curl_setopt($c, CURLOPT_FAILONERROR, true);
	curl_setopt($c, CURLOPT_COOKIEJAR, "cookies.txt");
	curl_setopt($c, CURLOPT_COOKIEFILE, "cookies.txt");
	$result = curl_exec($c);

	if($result === false) {
		trigger_error('curl_get_uri(): '.curl_error($c), E_USER_WARNING);
	}

	curl_close($c);

	return $result;
}

function curl_send_request($uri, $postFields) {
	$c = curl_init($uri);

	curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($postFields, '', '&'));
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_HEADER, false);
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);

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
		return $matches[1];
	},
	'MtRed' => function() {
		$login = curl_get_uri($lUri = 'https://mtred.com/user/login.html');
		preg_match_all('%<input type="hidden" value="([^"]+)" name="YII_CSRF_TOKEN" />%', $login, $matches);

		$post['YII_CSRF_TOKEN'] = $matches[1][0];
		$post['UserLogin[username]'] = $GLOBALS['conf']['mtred']['username'];
		$post['UserLogin[password]'] = $GLOBALS['conf']['mtred']['password'];
		$post['UserLogin[rememberMe]'] = '1';
		$post['yt0'] = 'Login';

		curl_send_request($lUri, $post);

		$blockPage = '';
		$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/1.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/2.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/3.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/4.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/5.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/6.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/7.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/8.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/9.html');
		//$blockPage .= curl_get_uri('https://mtred.com/blocks/index/url/blocks.html/Found_page/10.html');

		preg_match_all('%<tr class="(odd|even)"><td>([0-9a-f]{64})</td>%', $blockPage, $matches);

		$txBits = array();
		foreach($matches[2] as $tx) {
			$txBits[] = 'B\''.hex2bits($tx).'\'';
		}
		$blocks = pg_query('SELECT DISTINCT block FROM transactions WHERE transaction_id IN ('.implode(',', $txBits).')');
		$hashs = array();
		while($r = pg_fetch_row($blocks)) {
			$hashs[] = bits2hex($r[0]);
		}

		return $hashs;
	},
	'BTCGuild' => function() {
		$main = curl_get_uri('https://www.btcguild.com/blocks.php');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		$blocks = array();
		foreach($blocksN[1] as $bN) {
			$blk = json_decode(shell_exec('bitcoind getblockbycount '.$bN), true);
			$blocks[] = $blk['hash'];
		}

		return $blocks;
	},
	'Bitcoins.lc' => function() {
		$main = curl_get_uri('http://www.bitcoins.lc/round-information');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		$blocks = array();
		foreach($blocksN[1] as $bN) {
			$blk = json_decode(shell_exec('bitcoind getblockbycount '.$bN), true);
			$blocks[] = $blk['hash'];
		}

		return $blocks;
	},
	'Mineco.in' => function() {
		$main = curl_get_uri('https://mineco.in/blocks');
		preg_match_all("%href=\"http://blockexplorer.com/block/([0-9a-f]{64})\"%", $main, $matches);
		return $matches[1];
	},
	'Ozco.in' => function() {
		$main = curl_get_uri('https://ozco.in/blocks.php');
		preg_match_all('%href="http://blockexplorer.com/b/([0-9]+)"%', $main, $blocksN);

		$blocks = array();
		foreach($blocksN[1] as $bN) {
			$blk = json_decode(shell_exec('bitcoind getblockbycount '.$bN), true);
			$blocks[] = $blk['hash'];
		}

		return $blocks;
	},
	'Slush' => function() {
		$main = curl_get_uri('http://mining.bitcoin.cz/stats/');
		preg_match_all('%href=\'http://blockexplorer.com/block/([0-9a-f]{64})\'%', $main, $matches);
		return $matches[1];
	},
	'Eligius' => function() {
		$main = curl_get_uri('http://eligius.st/~artefact2/blocks/');
		preg_match_all('%title="([0-9a-fA-F]{64})"%', $main, $matches);
		return array_map('strtolower', $matches[1]);
	},
	'BTCMine' => function() {
		$main = curl_get_uri('http://btcmine.com/stats/');
		$main = str_replace(array(' ', "\n"), '', $main);
		preg_match_all('%<trclass="(even|odd)"><td>[0-9]+</td><td>([0-9]+)</td>%', $main, $matches);
		$blocksN = $matches[2];

		$blocks = array();
		foreach($blocksN as $bN) {
			$blk = json_decode(shell_exec('bitcoind getblockbycount '.$bN), true);
			$blocks[] = $blk['hash'];
		}

		return $blocks;
	},
	'Bitcoinpool' => function() {
		$main = curl_get_uri('http://www.bitcoinpool.com/index.php?do=blocks');
		preg_match_all('%<a class="clink".+">([0-9]+)</a>%', $main, $matches);

		$blocks = array();
		foreach($matches[1] as $bN) {
			$blk = json_decode(shell_exec('bitcoind getblockbycount '.$bN), true);
			$blocks[] = $blk['hash'];
		}

		return $blocks;
	},
);
