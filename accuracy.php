<?php
/* Author : Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

require __DIR__.'/lib/inc.main.php';

declareCache('accuracy');

$accuracy = cacheFetch('accuracy', $success);
if(!$success) {
	header('Content-Type: text/plain');
	die('Accuracy data is not available. FUUUUUUUUU-');
}

echo "<!DOCTYPE html>
<html>
<head>
".HEADER."
<title>Score accuracy</title>
</head>
<body>
<h1>Score accuracy</h1>
<p id='back'><a href='/'>&larr; Back to the main page</a></p>
<p class='notice'>The results below may be biased, since the tests was only done on blocks found by their respective pool. These testing conditions do not represent exactly the <em>unknown block</em> scenario.</p>
";

$cols = "<tr>
<th rowspan='2'>&#9650; Pool</th>
<th colspan='5' title='Blocks found by the pool, with scores successfully indicating it was found by this pool.'><span>Accuracy</span></th>
<th rowspan='2' title='Number of blocks tested.'><span>Sample size</span></th>
</tr>
<tr>
<th>Most likely</th>
<th>Probably</th>
<th>Wild guess</th>
<th>Not sure at all</th>
<th title='Blocks found by the pool, but with scores indicating it was found by another pool.'><span>False positives</span></th>
</tr>";

$rows = '';
ksort($accuracy);
foreach($accuracy as $pool => $a) {
	$rows .= "<tr>\n";
	$rows .= "<td>".prettyPool($pool)."</td>\n";

	$p = @round(100 * (
		$a['accuracy'][C_MOST_LIKELY]
	) / $a['sample_size']);
	$rows .= "<td style='background-color: rgba(166, 230, 132, ".number_format($p / 100, 2).");'>$p %</td>\n";

	$p = @round(100 * (
		$a['accuracy'][C_MOST_LIKELY] + $a['accuracy'][C_PROBABLY]
	) / $a['sample_size']);
	$rows .= "<td style='background-color: rgba(176, 230, 100, ".number_format($p / 100, 2).");'>$p %</td>\n";

	$p = @round(100 * (
		$a['accuracy'][C_MOST_LIKELY] + $a['accuracy'][C_PROBABLY] + $a['accuracy'][C_WILD_GUESS]
	) / $a['sample_size']);
	$rows .= "<td style='background-color: rgba(186, 230, 70, ".number_format($p / 100, 2).");'>$p %</td>\n";

	$p = @round(100 * (
		$a['accuracy'][C_MOST_LIKELY] + $a['accuracy'][C_PROBABLY] + $a['accuracy'][C_WILD_GUESS] + $a['accuracy'][C_NOT_SURE_AT_ALL]
	) / $a['sample_size']);
	$rows .= "<td style='background-color: rgba(196, 230, 60, ".number_format($p / 100, 2).");'>$p %</td>\n";

	$p = @round(100 * $a['false_positives'] / $a['sample_size']);
	$rows .= "<td style='background-color: rgba(255, 80, 80, ".number_format($p / 100, 2).");'>$p %</td>\n";

	$rows .= "<td>".$a['sample_size']."</td>\n";
	$rows .= "</tr>\n";
}

echo "<table id='accuracy'>
<thead>
$cols
</thead>
<tbody>
$rows
</tbody>
</table>
";

echo FOOTER."
</body>
</html>
";

