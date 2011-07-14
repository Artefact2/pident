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

function declareCache($name, $identifier = null, $expires = null) {
	if(!$GLOBALS['conf']['caching']) return;

	$name = 'page_'.$name;
	$fName = $identifier ? ($name.'_'.$identifier) : $name;
	$fMeta = $fName.'.metadata';

	$meta = cacheFetch($fMeta, $success);
	if($success && ($meta['last_modified'] + $meta['expires']) >= time()) {
		// Maybe we don't even have to send the cached result…
		if((isset($meta['set_expires']) && $meta['set_expires'] && isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) 
			&& strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $meta['last_modified'])
			|| (isset($_SERVER['HTTP_IF_NONE_MATCH'])
			&& $_SERVER['HTTP_IF_NONE_MATCH'] == $meta['etag'])) {
			header('HTTP/1.1 304 Not Modified', 304, true);
			die();
		}

		// We have a cached result, yet it is not in the browser's cache.
		header('Pragma: public');
		if(isset($meta['set_expires']) && $meta['set_expires']) 
			header('Expires: '.date('r', $meta['last_modified'] + $meta['expires']));
		header('ETag: '.$meta['etag']);
		die(file_get_contents(getCacheFile($fName)));
	}

	$GLOBALS['cache']['fName'] = $fName;
	$GLOBALS['cache']['fMeta'] = $fMeta;
	if($expires !== null) $GLOBALS['cache']['expires'] = $expires;

	ob_start('finalizeCache');
}

function declareCacheExpiration($interval, $forceExpires = false) {
	$GLOBALS['cache']['expires'] = $interval;
	$GLOBALS['cache']['set_expires'] = $forceExpires;
}

function finalizeCache($buffer) {
	$cacheInfo =& $GLOBALS['cache'];

	$cacheInfo['etag'] = sha1($buffer);
	$cacheInfo['last_modified'] = time();
	$cacheInfo['expires'] = isset($cacheInfo['expires']) ? $cacheInfo['expires'] : 3600;

	cacheStore($cacheInfo['fMeta'], $cacheInfo);
	file_put_contents(getCacheFile($cacheInfo['fName']), $buffer);
	
	header('Pragma: public');
	if(isset($cacheInfo['set_expires']) && $cacheInfo['set_expires']) 
		header('Expires: '.date('r', $cacheInfo['last_modified'] + $cacheInfo['expires']));
	header('ETag: '.$cacheInfo['etag']);
	return $buffer;
}

function invalidateCache($name, $identifier = null, $glob = false) {
	$name = 'page_'.$name;
	$fName = $identifier ? ($name.'_'.$identifier) : $name;
	$fMeta = getCacheFile($fName.'.metadata');
	$fName = getCacheFile($fName);

	if(!$glob) {
		if(file_exists($fName)) unlink($fName);
		if(file_exists($fMeta)) unlink($fMeta);
	} else {
		$a = glob($fName);
		$b = glob($fMeta);

		foreach($a as $d) unlink($d);
		foreach($b as $d) unlink($d);
	}
}

