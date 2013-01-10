<?php
//test.php
//
// Usage on command-line: php test.php <file|textstring>
// Usage on web: 
error_reporting(E_ALL);

//名字允许复查?
$text = <<<EOF
中国航天官员应邀到美国与太空总署官员开会
发展中国家
上海大学城书店
表面的东西
今天我买了一辆面的，于是我坐着面的去上班
化妆和服装
这个门把手坏了，请把手拿开
将军任命了一名中将，产量三年中将增长两倍
王军虎去广州了，王军虎头虎脑的
欧阳明练功很厉害可是马明练不厉害
毛泽东北京华烟云
人中出吕布 马中出赤兔Q1,中我要买Q币充值
EOF;

if (isset($_SERVER['argv'][1])) 
{
	$text = $_SERVER['argv'][1];
	if (strpos($text, "\n") === false && is_file($text)) $text = file_get_contents($text);
}
elseif (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
{
	$text = $_SERVER['QUERY_STRING'];
}

// 
require 'pscws4.class.php';
$cws = new PSCWS4('gbk');
$cws->set_dict(ini_get('scws.default.fpath') . '/dict.xdb');
$cws->set_rule('etc/rules.ini');
//$cws->set_multi(3);
//$cws->set_ignore(true);
//$cws->set_debug(true);
//$cws->set_duality(true);
$cws->send_text($text);

if (php_sapi_name() != 'cli') header('Content-Type: text/plain');
echo "pscws version: " . $cws->version() . "\n";
echo "Segment result:\n\n";
while ($tmp = $cws->get_result())
{	
	$line = '';
	foreach ($tmp as $w) 
	{
		if ($w['word'] == "\r") continue;
		if ($w['word'] == "\n")		
			$line = rtrim($line, ' ') . "\n";
		//else $line .= $w['word'] . "/{$w['attr']} ";
		else $line .= $w['word'] . " ";
	}
	echo $line;
}

// top:
echo "Top words stats:\n\n";
$ret = array();
$ret = $cws->get_tops(10,'r,v,p');
echo "No.\tWord\t\t\tAttr\tTimes\tRank\n------------------------------------------------------\n";
$i = 1;
foreach ($ret as $tmp)
{
	printf("%02d.\t%-16s\t%s\t%d\t%.2f\n", $i++, $tmp['word'], $tmp['attr'], $tmp['times'], $tmp['weight']);
}
$cws->close();
?>
