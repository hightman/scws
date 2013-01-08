<?php
// xxx
// convert dict.txt -> dict.xdb
//
define ("_WORD_ALONE_",		0x4000000);
define ("_WORD_PART_",		0x8000000);
define ("_WORD_MAXLEN_",	12);

$file = $_SERVER['argv'][1];
if (!isset($file)) 
	$file = "dict.xdb";

require 'xdb.class.php';
$db = new XTreeDB;
$db->Open($file, 'w');
if (!$db)
	die("fail to open dictionary file.\n");

$word_num = 0;
$record_num = 0;
$skip_num = 0;

// load to memory first
$rec = array();

$fd = fopen("dict.txt", "r");
echo "Loading data into memory ... \n";
while ($line = fgets($fd, 256))
{
	$line = trim($line);
	if (empty($line))
		continue;

	$w = preg_split("/[\s]+/", $line);
	$word = trim($w[0]);
	$freq = intval($w[1]);
	$len = strlen($word);

	if ($len < 4 || $len > _WORD_MAXLEN_ || substr($word, 0, 1) == '#') 
	{
		$skip_num++;
		continue;
	}

	// 处理词
	$r = $rec[$word];
	if (!$r || !($r & _WORD_ALONE_))
	{
		if ($r) 
		{
			$r &= _WORD_PART_;
		}
		else
		{
			$r = 0;
			$record_num++;
		}

		$r |= $freq;
		$r |= _WORD_ALONE_;

		$r = strval($r);
		$rec[$word ] = $r;
		$word_num++;
	}

	// 处理词段
	$len -= 2;
	while ($len > 2)
	{
		$len -= 2;
		$word = substr($word, 0, -2);
		$r = $rec[$word];
		if (!$r)
		{
			$record_num++;
			$r = _WORD_PART_;
		}
		else if ($r & _WORD_PART_)
		{
			continue;
		}
		else
		{
			$r |= _WORD_PART_;
		}
		$rec[$word] = $r;
	}

	if ($word_num % 10000 == 0)
	{
		echo "{$word_num} ... \n";
		flush();
	}
}
fclose($fd);

echo "Loading OK! words num: $word_num  records num: $record_num skip num: $skip_num \n";
echo "Try to insert into cdb records ... \n";

foreach ($rec as $key => $value)
{
	$db->Put($key, $value);
}

$db->Optimize();
$db->Close();
echo "\n";
?>
