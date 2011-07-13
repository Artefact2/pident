#!/usr/bin/env php
<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

/* Update wrong coinbase */

$req = pg_query("
SELECT hash
FROM blocks
WHERE coinbase = E'\\\\x00'
");

while($r = pg_fetch_row($req)) {
	$bits = $r[0];
	$hex = bits2hex($bits);
	$blk = json_decode(shell_exec('bitcoind getblockbyhash '.$hex), true);

	$coinbase = $blk['tx'][0]['in'][0]['coinbase'];
	pg_query("UPDATE blocks SET coinbase = E'\\\\x$coinbase' WHERE hash = B'$bits';");
}
