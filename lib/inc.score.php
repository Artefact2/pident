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

function normalizeScore($rawScore, $avg) {
	return $rawScore / $avg;
}

function identifyPool($normalized) {
	$v = array_values($normalized);
	$p = array_keys($normalized);

	$confidence = C_NOT_SURE_AT_ALL;

	if(count($v) == 0) {
		return null;
	} else if(count($v) == 1) {
		/* We have no other pool to compare against */
		return array($v[0] > 0.2 ? C_WILD_GUESS : C_NOT_SURE_AT_ALL, $p[0]);
	} else if($v[0] > 0.5 && ($d = ($v[0] / $v[1])) > 2) {
		return array($d > 5 ? C_MOST_LIKELY : C_PROBABLY, $p[0]);
	} else if($v[0] > 0.2 && ($d = ($v[0] / $v[1])) > 3) {
		return array($d > 6 ? C_MOST_LIKELY : C_PROBABLY, $p[0]);
	} else if(($d = ($v[0] / $v[1])) > 5) {
		return array($d > 10 ? C_MOST_LIKELY : C_PROBABLY, $p[0]);
	} else if($v[0] > 0.7) {
		return array(C_WILD_GUESS, $p[0]);
	}

	return null;
}
