<?php
// draw the tree by index
// default index: -1 show all tree
//
$file = $_SERVER['argv'][1] ? $_SERVER['argv'][1] : 'test.hdb';

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

$db->open($file, "r");
$db->draw(isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : -1);
$db->close();
?>
