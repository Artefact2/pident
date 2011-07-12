<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

const CACHE_DIRECTORY = '../cache';

function getCacheFile($name) {
	return __DIR__.'/'.CACHE_DIRECTORY.'/'.$name;
}

function cacheFetch($name, &$success) {
	$f = getCacheFile($name);
	if(!file_exists($f)) {
		$success = false;
		return null;
	}

	$c = file_get_contents($f);
	if($c === false) {
		$success = false;
		return null;
	}

	$success = true;
	return unserialize($c);
}

function cacheStore($name, $data) {
	$f = getCacheFile($name);

	return file_put_contents($f, serialize($data));
}
