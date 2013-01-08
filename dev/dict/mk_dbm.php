<?php
// convert dict.txt => dict.<dbm>
//
// php mk_dbm.php [input file] [output file]
// output file: .gdbm, .cdb, .hdb, .xdb
//
set_time_limit(0);
ini_set('memory_limit', '128M');

if (!isset($_SERVER['argv'][2]))
{
	echo "Usage: {$_SERVER['argv'][0]} <input file> <output file>\n";
	echo "       {$_SERVER['argv'][0]} dict.txt dict.hdb\n";
	echo "       {$_SERVER['argv'][0]} dict.txt dict.xdb\n";
	echo "       {$_SERVER['argv'][0]} dict.txt dict.cdb\n\n";
	exit(0);
}

// get the paramters
$input = $_SERVER['argv'][1];
$output = $_SERVER['argv'][2];

// create the dbm file
if (strrchr($output, '.') == '.hdb')
{
	require ('hdb.class.php');
	$db = new HashTreeDB(0, 0x3ffd);
	$ok = $db->Open($output, 'w');
}
if (strrchr($output, '.') == '.xdb')
{
	require ('xdb.class.php');
	$db = new XTreeDB(0, 0x3ffd);
	$ok = $db->Open($output, 'w');
}
else
{
	require ('dba.class.php');
	$db = new DbaHandler;
	$ok = $db->Open($output, 'n');
}
if (!$ok)
{
	echo "ERROR: cann't setup the database($output).\n";
	exit(0);
}

// check the input file
$fd = fopen($input, "r");
if (!$fd)
{
	$db->Close();
	echo "ERROR: cann't read the input file($input).\n";
	exit(0);
}

// load the data
$total = 0;
$rec = array();
echo "Loading text file data ... ";
while ($line = fgets($fd, 512))
{
	list($word, $tf, $idf, $attr) = explode("\t", $line);
	$k = (ord($word[0]) + ord($word[1])) & 0x3f;
	$attr = trim($attr);

	if (!isset($rec[$k])) $rec[$k] = array();
	if (!isset($rec[$k][$word]))
	{
		$total++;
		$rec[$k][$word] = array();
	}
	$rec[$k][$word]['tf'] = $tf;
	$rec[$k][$word]['idf'] = $idf;
	$rec[$k][$word]['attr'] = $attr;

	$len = strlen($word);
	while ($len > 4)
	{
		$len -= 2;
		$temp = substr($word, 0, $len);
		if (!isset($rec[$k][$temp]))
		{
			$total++;
			$rec[$k][$temp] = array();
		}
		$rec[$k][$temp]['part'] = 1;
	}
}
fclose($fd);

// load ok & try to save it to DBM
echo "OK, total=$total\n";
for ($k = 0; $k < 0x40; $k++)
{
	if (!isset($rec[$k])) continue;
	$cnt = 0;
	printf("Inserting [%02d/64] ... ", $k);
	foreach ($rec[$k] as $w => $v)
	{
		//$tf = (isset($v['tf']) ? ($v['tf'] * 100) : 0);
		//$idf = (isset($v['idf']) ? ($v['idf'] * 100) : 0);
		$flag = (isset($v['tf']) ? 0x01 : 0);
		if ($v['part']) $flag |= 0x02;
		//$data = pack('VVCa3', $tf, $idf, $flag, $v['attr']);
		$data = pack('ffCa3', $v['tf'], $v['idf'], $flag, $v['attr']);
		$db->Put($w, $data);
		$cnt++;
	}
	printf("%d Records saved.\n", $cnt);
}

$db->Close();
echo "Done!\n";
// end the code
?>
