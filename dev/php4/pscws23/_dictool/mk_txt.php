<?php
// xxx
// dump the dict.cdb to dict.txt
//
define ("_WORD_ALONE_",		0x4000000);
define ("_WORD_PART_",		0x8000000);

$db = dba_open("dict.cdb", "r", "cdb");
$total = 0;
if ($key = dba_firstkey($db))
{
	do {
		$value = (int) dba_fetch($key, $db);
		if (!($value & _WORD_ALONE_))
			continue;
		$value &= ~(_WORD_ALONE_ | _WORD_PART_);
		echo "{$key}\t\t{$value}\r\n";
		$total++;
	}
	while ($key = dba_nextkey($db));
}
dba_close($db);

echo "# total {$total}\n";
?>
