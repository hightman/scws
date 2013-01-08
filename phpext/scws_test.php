<?php
/**
 * SCWS auto test
 *
 * $Id: scws_test.php,v 1.5 2011/12/23 07:05:26 hightman Exp $
 */
//1. setup env & check extension
if (!extension_loaded('scws')) dl('scws.' . PHP_SHLIB_SUFFIX);

//2. check again
if (!extension_loaded('scws'))
{
	echo "ERROR: scws extension was not found.\n";
	exit(0);
}

//3. test data
define ('SCWS_TEST_MULTI_SHORT',	0x01);
define ('SCWS_TEST_MULTI_DUALITY',	0x02);
define ('SCWS_TEST_MULTI_ZMAIN',	0x04);
define ('SCWS_TEST_MULTI_ZALL',		0x08);
define ('SCWS_TEST_MULTI_MASK',		0x0f);
define ('SCWS_TEST_IGNORE_SYMBOL',	0x10);
define ('SCWS_TEST_DUALITY',		0x20);
//options: multi(default=0), ignore(symbol, default: false), duality(default:false)

$TEST_DATA = 
array(
	'大家好，我是马明练' => array('result' => '大家 好 ， 我 是 马明练', 'option' => 0),
	'结合成分子时' => array('result' => '结合 成 分子 时', 'option' => 0),
	'提高人民生活水平' => array('result' => '提高 人民 生活 水平', 'option' => 0),
	'奥巴马上台后中美关系如何变革' => array('result' => '奥巴马 上台 后 中美关系 如何 变革', 'option' => 0),
	'一九四九年，新中国成立了' => array('result' => '一九四九年 ， 新中国 成立 了', 'option' => 0),	
	'哪个人生下来就会算算术呢' => array('result' => '哪个 人 生下 来 就 会 算 算术 呢', 'option' => 0),
	//'俄罗斯民调显示梅德韦杰夫人气急升' => array('result' => '俄罗斯 民调 显示 梅德韦杰夫 人气 急 升 ', 'option' => 0),
	'2008年中国网络游戏的实际销售收入达183.8亿元人民币，比2007年增长了76.6%' => array('result' => '2008 年 中国 网络游戏 的 实际 销售 收入 达 183.8 亿 元 人民币 ， 比 2007 年 增长 了 76.6%', 'option' => 0),
	'你说的确实在理' => array('result' => '你 说 的 确实 在理', 'option' => 0),	
	'圆周率的近似值为3.14！' => array(
		'result' => '圆周率 的 近似值 为 3.14',
		'option' => SCWS_TEST_IGNORE_SYMBOL,
	),
	'中国的全称是中华人民共和国' => array(
		'result' => '中国 国 的 全称 称 是 中华人民共和国 中华 人民 共和国 华 人 民 国',
		'option' => SCWS_TEST_MULTI_SHORT | SCWS_TEST_MULTI_ZMAIN,
	),
	'读到第三章，我也不知该说什么好了' => array(		
		'result' => '读到 到 第三章 我也 也 不知 该 该说 说 什么 好 好了',
		'option' => SCWS_TEST_DUALITY|SCWS_TEST_IGNORE_SYMBOL,
	),
	'我家的IP是192.168.1.100，4年前就用了，型号是386AC90F' => array(
		'result' => '我家 的 IP 是 192 . 168 . 1 . 100 ， 4 年前 就 用 了 ， 型号 是 386 AC 90 90F',
		'option' => SCWS_TEST_MULTI_DUALITY,
	),
	'管理制度，越南民主共和国' => array(
		'result' => '管理制度 管理 制度 ， 越南民主共和国 越南 民主 共和国',
		'option' => SCWS_TEST_MULTI_SHORT | SCWS_TEST_MULTI_DUALITY,
	),
	'李姚明' => array(
		'result' => '李姚明 李姚 姚明',
		'option' => SCWS_TEST_MULTI_SHORT | SCWS_TEST_MULTI_DUALITY,
	),
	'李姚明' => array(
		'result' => '李姚明',
		'option' => SCWS_TEST_MULTI_SHORT,
	),
	'中华人民共和国' => array(
		'result' => '中华人民共和国 中华 人民 共和国',
		'option' => SCWS_TEST_MULTI_SHORT | SCWS_TEST_MULTI_DUALITY,
	),
);

//3. build scws handler
$scws = scws_new();
$scws->set_charset('utf8');
$scws->set_dict(ini_get('scws.default.fpath') . '/dict.utf8.xdb');
$scws->set_rule(ini_get('scws.default.fpath') . '/rules.utf8.ini');
if (file_exists('dict_user.txt')) $scws->add_dict('dict_user.txt', SCWS_XDICT_TXT);

//4. do the test & record result
$success = $failure = 0;
$start = 1;
foreach ($TEST_DATA as $text => $data)
{
	echo "Test [$start] ... ";
	$scws->set_multi($data['option'] & SCWS_TEST_MULTI_MASK);
	$scws->set_ignore(($data['option'] & SCWS_TEST_IGNORE_SYMBOL) ? true : false);
	$scws->set_duality(($data['option'] & SCWS_TEST_DUALITY) ? true : false);
	$scws->send_text($text);
	$words = array();
	while ($result = $scws->get_result())
	{
		foreach ($result as $tmp)
			$words[] = $tmp['word'];
	}
	$result2 = implode(' ', $words);
	if ($result2 === $data['result'])
	{
		$success++;
		echo "PASS!\n";
	}
	else
	{
		$failure++;
		echo "FAILURE!\n";
		echo "----------------------------------------\n";
		echo "ORGINAL TEXT: $text\n";
		echo "EXPECTED RESULT: {$data['result']}\n";
		echo "ACTUAL RESULT: $result2\n";
		echo "========================================\n";
	}
	$start++;
}
$scws->close();

//5. show result report
$start -= 1;
echo "// -------------------------------------\n";
echo "// TEST result report\n";
echo "// " . $scws->version() . "\n";
echo "// -------------------------------------\n";
echo "// Total test: $start\n";
printf("// Passed Num: %d (%.2f%%)\n", $success, 100 * $success / $start);
printf("// Failed Num: %d (%.2f%%)\n", $failure, 100 * $failure / $start);
echo "// -------------------------------------\n";
