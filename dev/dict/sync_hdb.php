<?php
// draw the tree by index
// default index: -1 show all tree
//

$file = $_SERVER['argv'][1] ? $_SERVER['argv'][1] : 'test.hdb';
$index = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : 1;

if (strrchr($file, '.') == '.xdb')
{
	require ("xdb.class.php");
	$db = new XTreeDB;
}
else
{
	require ("hdb.class.php");
	$db = new HashTreeDB;
}

$db->open($file, "w");
if ($index >= 0)
{
	echo "Before optimize tree data:\n";
	echo "-------------------------------------------------\n";
	$db->draw($index);
}

// optimize table
$db->optimize($index);
if ($index >= 0)
{
	echo "After optimize tree data:\n";
	echo "-------------------------------------------------\n";
	$db->draw($index);
}
$db->close();
?>
