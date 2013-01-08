<?php
// convert w.txt => w_py.txt
//
// php mk_py_doct.php [input file] [output file]
// $Id$
//
set_time_limit(0);
ini_set('memory_limit', '128M');

if (!isset($_SERVER['argv'][2]))
{
	echo "Usage: {$_SERVER['argv'][0]} <input file> <output file>\n";
	echo "       {$_SERVER['argv'][0]} w.txt w_py.txt\n";
	exit(0);
}

// get the paramters
$input = $_SERVER['argv'][1];
$output = $_SERVER['argv'][2];

require 'getpy/my_Getpy.class.php';
$PY = new my_Getpy();

// fd
$fd = fopen($input, 'r');
$fw = fopen($output, 'w');
while ($line = fgets($fd, 256))
{
	list($word, $freq) = explode("\t", $line, 2);
	$word = trim($word);
	$len = strlen($word);
	if (($len%2) != 0 || $len > 8 || $len == 0) continue;
	$wpy = '';
	for ($i = 0; $i < $len; $i += 2)
	{
		$zh = substr($word, $i, 2);
		$tmp = $PY->get($zh);
		if (is_numeric(substr($tmp, -1, 1))) $tmp = substr($tmp, 0, -1);
		$wpy .= $tmp;
	}
	$freq = intval(log(intval($freq)) * 100);
	$buf = $word . "\t" . $wpy . "\t" . $freq . "\n";
	fputs($fw, $buf);
}
fclose($fd);
fclose($fw);
?>
