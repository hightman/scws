<?php
// test query
$input = ($_SERVER['argv'][1] ? $_SERVER['argv'][1] : 'dict.hdb');
$word = $_SERVER['argv'][2] ? $_SERVER['argv'][2] : '²âÊÔ';

// open the dbm file
if (strrchr($input, '.') == '.hdb')
{
	require ('hdb.class.php');
	$db = new HashTreeDB;
}
else if (strrchr($input, '.') == '.xdb')
{
	require ('xdb.class.php');
	$db = new XTreeDB;
}
else
{
	require ('dba.class.php');
	$db = new DbaHandler;
}
if (!($ok = $db->Open($input, 'r')))
{
	echo "ERROR: cann't open the database($output).\n";
	exit(0);
}

// compare the value
function get_microtime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

$start = get_microtime();
$value = $db->get($word);
$tcost = get_microtime() - $start;

if ($value === false) $value = "<NULL>";
else
{
	$tmp = unpack('ftf/fidf/Cflag/a3attr', $value);
	$value  = "Array(tf => $tmp[tf], idf => $tmp[idf], attr => $tmp[attr], flag =>";
	$value .= ($tmp['flag'] & 0x01) ? " FULL" : "";
	$value .= ($tmp['flag'] & 0x02) ? " PART" : "";
	$value .= ")";

}
echo "The result of key `$word' is: (io=" . $db->_io_times . ", time=" . $tcost . ")\n";
echo "$value\n";
$db->close();
?>
