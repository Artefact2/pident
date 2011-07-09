<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

$GLOBALS['conf'] = parse_ini_file(__DIR__.'/../config.ini');
$GLOBALS['pg'] = pg_connect($GLOBALS['conf']['postgresql_connection_string']);

const FOOTER = '<footer style="margin-top: 5em;"><hr /><p style="text-align: right; margin: 0; padding: 0.2em;">pident (Artefact2) - <a href="https://github.com/Artefact2/pident">View source code (WTFPL)</a> - Donate to <a href="/address/1666R5kdy7qK2RDALPJQ6Wt1czdvn61CQR">1666R5kdy7qK2RDALPJQ6Wt1czdvn61CQR</a> !</p></footer>';
const VERSION = '0.1';

require __DIR__.'/inc.bitcoin.php';
require __DIR__.'/inc.utils.php';
require __DIR__.'/inc.pools.php';
require __DIR__.'/inc.score.php';
require __DIR__.'/inc.html.php';

date_default_timezone_set('UTC');
