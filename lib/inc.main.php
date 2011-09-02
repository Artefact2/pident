<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

$GLOBALS['conf'] = parse_ini_file(__DIR__.'/../config.ini');
$GLOBALS['pg'] = pg_connect($GLOBALS['conf']['postgresql_connection_string']);

const HEADER = '<link type="text/css" rel="stylesheet" href="/theme.css">
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
<script type="text/javascript" src="/jquery-color/jquery.color.js"></script>
<script type="text/javascript" src="/lib/inc.main.js"></script>
<meta charset="utf-8" />';
const FOOTER = '<footer style="margin-top: 5em;">
<hr />
<p style="text-align: right; margin: 0; padding: 0.2em;">
pident (Artefact2)
- <a href="https://github.com/Artefact2/pident">View source code (WTFPL)</a>
- Donate to <a href="/address/1666R5kdy7qK2RDALPJQ6Wt1czdvn61CQR">1666R5kdy7qK2RDALPJQ6Wt1czdvn61CQR</a> !
</p>
</footer>';
const VERSION = '0.5';

require __DIR__.'/inc.cache.php';
require __DIR__.'/inc.bitcoin.php';
require __DIR__.'/inc.utils.php';
require __DIR__.'/inc.pools.php';
require __DIR__.'/inc.score.php';
require __DIR__.'/inc.html.php';
require __DIR__.'/inc.update.php';
require __DIR__.'/inc.factoids.php';

define('TONAL', isset($GLOBALS['conf']['tonal_override']) ?
	$GLOBALS['conf']['tonal_override'] :
	preg_match($GLOBALS['conf']['tonal_regexp'], (isset($_SERVER[$GLOBALS['conf']['tonal_server_var']]) ? 
		$_SERVER[$GLOBALS['conf']['tonal_server_var']] : 
		'CLI'
	))
);
if(TONAL) require __DIR__.'/inc.tonal.php';

date_default_timezone_set('UTC');

//header('Content-Type: text/plain');

//var_dump($_SERVER);
//var_dump($GLOBALS['conf']);

//die();
