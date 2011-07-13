<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

function hex2bits($hex) {
	if($hex == '') return '';

	static $trans = array(
		'0' => '0000',
		'1' => '0001',
		'2' => '0010',
		'3' => '0011',
		'4' => '0100',
		'5' => '0101',
		'6' => '0110',
		'7' => '0111',
		'8' => '1000',
		'9' => '1001',
		'a' => '1010',
		'b' => '1011',
		'c' => '1100',
		'd' => '1101',
		'e' => '1110',
		'f' => '1111',
	);

	$bits = '';
	$digits = str_split(strtolower($hex), 1);

	foreach($digits as $d) {
		$bits .= $trans[$d];
	}

	return $bits;
}

function bits2hex($bits) {
	if($bits == '') return '';

	static $trans = array(
		'0000' => '0',
		'0001' => '1',
		'0010' => '2',
		'0011' => '3',
		'0100' => '4',
		'0101' => '5',
		'0110' => '6',
		'0111' => '7',
		'1000' => '8',
		'1001' => '9',
		'1010' => 'a',
		'1011' => 'b',
		'1100' => 'c',
		'1101' => 'd',
		'1110' => 'e',
		'1111' => 'f',
	);

	$hex = '';
	$digits = str_split($bits, 4);

	foreach($digits as $d) {
		$hex .= $trans[$d];
	}

	return $hex;
}

function bchack($number) {
	if(!is_float($number)) return $number;
	$parts = explode('e', strtolower($number));
	if(count($parts) == 2) {
		return bcmul($parts[0], bcpow(10, $parts[1]), 8);
	} else return $number;
}

function btc2satoshi($btc) {
	return bcmul(bchack($btc), "100000000", 0);
}

function formatBTC($btc) {
	return formatSatoshi(btc2satoshi($btc));
}

function formatSatoshi($s) {
	$num = number_format(bcdiv($s, "100000000", 8), 8);
	$i = 0;
	$k = strlen($num) - 1;
	while($i < 6 && $num[$k - $i] == '0') $i += 1;
	$i -= 1;
	return substr($num, 0, $k - $i).($i == -1 ? '' : (
		'<span class="bz">'.substr($num, $k - $i).'</span>'
	));
}

function prettyDuration($duration, $precision = 4) {
	if($duration < 60) return "a few seconds";
	else if($duration < 300) return "a few minutes";

	$units = array("month" => 30.5 * 86400, "week" => 7*86400, "day" => 86400, "hour" => 3600, "minute" => 60);

	$r = array();
	foreach($units as $u => $d) {
		$num = floor($duration / $d);
		if($num >= 1) {
			$plural = $num > 1 ? 's' : '';
			$r[] = $num.' '.$u.$plural;
			$duration %= $d;
		}
	}

	$prefix = '';
	while(count($r) > $precision) {
		$prefix = 'about ';
		array_pop($r);
	}

	if(count($r) > 1) {
		$ret = array_pop($r);
		$ret = implode(', ', $r).' and '.$ret;
		return $prefix.$ret;
	} else return $prefix.$r[0];
}

function prettyPool($p) {
	if(!$p) return 'N/A';
	else {
		$c = extractColor($p, 200, 256);
		return "<span class='pool' style='background-color: $c;'>$p</span>";
	}
}

function extractColor($seed, $min = 0, $max = 256) {
	assert('$min < $max');

	$seed = sha1($seed);
	$red = hexdec(substr($seed, 20, 2));
	$green = hexdec(substr($seed, 12, 2));
	$blue = hexdec(substr($seed, 4, 2));

	$gray = 0.3 * $red + 0.59 * $green + 0.11 * $blue;
	if($gray >= $min && $gray <= $max) {
		return "rgb($red, $green, $blue)";
	} else return extractColor($seed, $min, $max);
}

function formatSize($b) {
	if($b >= 10000) {
		$b /= 1000;
		$unit = 'kB';
	} else {
		$unit = 'bytes';
	}

	return round($b, 2).' '.$unit;
}

function formatCoinbase($coinbase) {
	$coinbase = substr($coinbase, 2);

	$ascii = pack('H*', $coinbase);
	$k = strlen($ascii);
	$sane = '';
	for($i = 0; $i < $k; ++$i) {
		$n = ord($ascii[$i]);
		if($n >= 0x20 && $n <= 0x7F) {
			$sane .= $ascii[$i];
		} else $sane .= '&#5987;';
	}

	return "<code>$coinbase</code> ($sane)";
}

