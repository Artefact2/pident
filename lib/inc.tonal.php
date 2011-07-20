<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

mb_internal_encoding('UTF-8');

$GLOBALS['__tonal_alphabet'] = array(
	'0', '1', '2', '3',
	'4', '5', '6', '7',
	'8', '', '9', '',
	'', '', '', '',
);

$GLOBALS['__tonal_names'] = array(
	'noll', 'an', 'de', 'ti',
	'go', 'su', 'by', 'ra',
	'me', 'ni', 'ko', 'hu',
	'vy', 'la', 'po', 'fy',
);

$GLOBALS['__tonal_reverse_alphabet'] = array_flip($GLOBALS['__tonal_alphabet']);
$GLOBALS['__tonal_reverse_names'] = array_flip($GLOBALS['__tonal_names']);

function tonalFormatInteger($i, $separators = true) {
	$i = $i;
	$result = '';
	if(bccomp($i, 0) < 0) {
		$i = bcmul($i, -1);
		$result = '-';
	}

	do {
		$result = $GLOBALS['__tonal_alphabet'][bcmod($i, 16)].$result;
		$i = bcdiv($i, 16, 0);
	} while(bccomp($i, 0) !== 0);

	if(!$separators) return $result;

	$c = mb_strlen($result);
	$fResult = mb_substr($result, -4);
	for($i = $c - 4; $i > 0; $i -= 4) {
		$over = max(0, -($i - 4));
		$fResult = mb_substr($result, $i - 4 + $over, 4 - $over).','.$fResult;
	}

	return $fResult;
}

function tonalParseInteger($t) {
	$c = mb_strlen($t);
	$z = 1;
	$r = 0;
	for($i = ($c - 1); $i >= 0; --$i) {
		$r = bcadd($r, bcmul($z, $GLOBALS['__tonal_reverse_alphabet'][mb_substr($t, $i, 1)]));
		$z = bcmul(16, $z);
	}

	return $r;
}

function tonalNumberFormat($n, $decimals = 0) {
	$intPart = tonalFormatInteger(bcadd($n, 0, 0));
	if($decimals == 0) return $intPart;

	$leftover = bcsub($n, bcadd($n, 0, 0));
	$decPart = tonalFormatInteger(bcmul($leftover, bcpow(16, $decimals), 0));
	$decPart = str_repeat($GLOBALS['__tonal_alphabet'][0], $decimals - mb_strlen($decPart)).$decPart;

	return $intPart.'.'.$decPart;
}

function tonalFormatSatoshi($satoshis) {
	$decPart = tonalFormatInteger(bcmod($satoshis, 65536));
	$decPart = str_repeat($GLOBALS['__tonal_alphabet'][0], 4 - mb_strlen($decPart)).$decPart;
	$intPart = tonalFormatInteger(bcdiv($satoshis, 65536, 0));

	$zeros = 0;
	while($zeros < 4 && mb_substr($decPart, 3 - $zeros, 1) == $GLOBALS['__tonal_alphabet'][0]) {
		$zeros++;
	}

	if($zeros > 0) {
		$bz = mb_substr($decPart, 0, 4 - $zeros);
		$az = mb_substr($decPart, 4 - $zeros, $zeros);

		if($bz == '') $az = '.'.$az;
		else $bz = '.'.$bz;

		$decPart = "$bz<span class='bz'>$az</span>";
	} else $decPart = '.'.$decPart;

	return $intPart.$decPart;
}
