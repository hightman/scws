<?php
/* ----------------------------------------------------------------------- *\
   PHP-简易中文分词 (SCWS) ver 3.1/2.1 (实例演示)
   
   (v2) 基于词频词典逐点搜索最长词,
   (v3) 双向根据词频取较高之分法

   这两个版本的用法及 API 一致.
   $Id$

   -----------------------------------------------------------------------
   作者: 马明练(hightman) (MSN: MingL_Mar@msn.com) (php-QQ群: 17708754)
   网站: http://www.ftphp.com/scws
   时间: 2006/03/05
   修订: 2008/12/20
   目的: 学习研究交流用, 希望有好的建议及用途希望能进一步交流.
   -----------------------------------------------------------------------
   环境: PHP 4.1.0及更高版本含 PHP5 (编译建议 --enable-dba --with-[cdb|gdbm])
\* ----------------------------------------------------------------------- */

/**
 * 查看源码的参数 <*.php?source>
 */

$stag = 'source';
$slen = strlen($stag);
if (isset($_SERVER['QUERY_STRING']) 
	&& !strncmp($_SERVER['QUERY_STRING'], $stag, $slen))
{
	$qlen = strlen($_SERVER['QUERY_STRING']);	
	$files = array('pscws2', 'pscws3', 'dict', 'xdb_r');
	$file = ($qlen > $slen && $qlen < ($slen + count($files))) ? $files[$qlen-$slen] . '.class.php' : __FILE__;
	highlight_file($file);
	exit(0);
}

/**
 * 实例开始
 */

// 尝试计算实列运算时间
function get_microtime()
{
	list($usec, $sec) = explode(' ', microtime()); 
	return ((float)$usec + (float)$sec); 
}
$time_start = get_microtime();

// 分词结果之回调函数 (param: 分好的词组成的数组)
function words_cb($ar)
{
	foreach ($ar as $tmp)
	{
		if ($tmp == "\n")
		{
			echo $tmp;
			continue;
		}
		echo $tmp . ' ';
	}
	flush();
}

// 实例化前的参数指定与读取
$dict = 'dict/dict.xdb';	// 默认采用 xdb (不需其它任何依赖)
$mydata  = NULL;	// 待切数据
$version = 3;		// 采用版本
$autodis = false;	// 是否识别名字
$ignore  = false;	// 是否忽略标点
$debug   = false;	// 是否为除错模式
$stats	 = false;	// 是否查看统计结果
$is_cli  = (php_sapi_name() == 'cli');	// 是否为 cli 运行环境
$sample_text = <<<__EOF__
陈凯歌并不是《无极》的唯一著作权人，一部电影的整体版权归电影制片厂所有。

一部电影的作者包括导演、摄影、编剧等创作人员，这些创作人员对他们的创作是有版权的。不经过制片人授权，其他人不能对电影做拷贝、发行、反映，不能通过网络来传播，既不能把电影改编成小说、连环画等其他艺术形式发表，也不能把一部几个小时才能放完的电影改编成半个小时就能放完的短片。

著作权和版权在我国是同一个概念，是法律赋予作品创作者的专有权利。所谓专有权利就是没有经过权利人许可又不是法律规定的例外，要使用这个作品，就必须经过作者授权，没有授权就是侵权。
__EOF__;

// 根据不同版本的环境读取参数设置
if ($is_cli)
{
	$argc = $_SERVER['argc'];
	for ($i = 1; $i < $argc; $i++)
	{
		$optarg = $_SERVER['argv'][$i];
		if (!strncmp($optarg, "--", 2))
		{
			$cmp = substr($optarg, 2);
			if (!strcasecmp($cmp, "help"))
			{
				$mydata = NULL;
				break;
			}
			else if (!strcasecmp($cmp, "autodis"))
				$autodis = true;
			else if (!strcasecmp($cmp, "ignore"))
				$ignore = true;
			else if (!strcasecmp($cmp, "v2"))
				$version = 2;
			else if (!strcasecmp($cmp, "debug"))
				$debug = true;
			else if (!strcasecmp($cmp, "stats"))
				$stats = true;
			else if (!strcasecmp($cmp, "dict"))
			{
				$i++;
				$dict = $_SERVER['argv'][$i];
			}
		}
		else if (is_null($mydata))
		{
			if (is_file($optarg)) $mydata = @file_get_contents($optarg);
			else $mydata = trim($optarg);
		}
	}
}
else
{
	// 部分参数选项
	$checked_ignore = $checked_autodis = $checked_v2 = '';
	
	// 是否指定有第 2 版
	if (isset($_REQUEST['version']) && $_REQUEST['version'] == 2)
	{
		$version = 2;
		$checked_v2 = ' selected';
	}

	// 是否指定一个词典格式
	$selected_gdbm = $selected_text = $selected_sqlite = '';
	if (isset($_REQUEST['dict']))
	{
		if ($_REQUEST['dict'] == 'gdbm')
		{
			$dict = 'dict/dict.gdbm';
			$selected_gdbm = ' selected';
		}
		else if ($_REQUEST['dict'] == 'text')
		{
			$dict = 'dict/dict.txt';
			$selected_text = ' selected';
		}
		else if ($_REQUEST['dict'] == 'sqlite')
		{
			$dict = 'dict/dict.sqlite';
			$selected_sqlite = ' selected';
		}
		else if ($_REQUEST['dict'] == 'cdb')
		{
			$dict = 'dict/dict.cdb';
			$selected_cdb = ' selected';
		}
		else
		{
			$_REQUEST['dict'] = 'xdb';
		}
	}

	// 是否开启人名识别 (缺省关闭)
	if (isset($_REQUEST['autodis']) && !strcmp($_REQUEST['autodis'], 'yes'))
	{
		$autodis = true;
		$checked_autodis = ' checked';
	}

	// 是否清除标点符号
	if (isset($_REQUEST['ignore']) && !strcmp($_REQUEST['ignore'], 'yes'))
	{
		$ignore = true;
		$checked_ignore = ' checked';
	}

	// 是否开启debug
	if (isset($_REQUEST['debug']) && !strcmp($_REQUEST['debug'], 'yes'))
	{
		$debug = true;
		$checked_debug = ' checked';
	}

	// 是否看统计表
	if (isset($_REQUEST['stats']) && !strcmp($_REQUEST['stats'], 'yes'))
	{
		$stats = true;
		$checked_stats = ' checked';
	}

	// 切分数据
	if (!isset($_REQUEST['mydata']) || empty($_REQUEST['mydata']))
	{
		$mydata = $sample_text;
	}
	else
	{
		$mydata = & $_REQUEST['mydata'];
		if (get_magic_quotes_gpc())
			$mydata = stripslashes($mydata);
	}
}

// 清除最后的 \r\n\t
if (!is_null($mydata)) 
	$mydata = trim($mydata);

// 实例化分词对像(mydata非空)
$object = 'PSCWS' . $version;
require (strtolower($object) . '.class.php');
	
$cws = new $object($dict);
$cws->set_ignore_mark($ignore);
$cws->set_autodis($autodis);
$cws->set_debug($debug);
// hightman.060330: 强行开启统计
$cws->set_statistics($stats);

?>
<?php if (!$is_cli) { ?>
<html>
<head>
<title>PHP 简易中文分词(SCWS) 第<?php echo $version; ?>版在线演示 (by hightman)</title>
<meta http-equiv="Content-type" content="text/html; charset=gbk">
<style type="text/css">
<!--
td, body	{ background-color: #efefef; font-family: tahoma; font-size: 14px; }
.demotx		{ font-size: 12px; width: 100%; height: 100px; }
small		{ font-size: 12px; }
//-->
</style>
</head>
<body>
<h3>
  <font color=red>PHP 简易中文分词(SCWS)</font>
  <font color=blue>第<?php echo $version; ?>版</font> - 在线演示 (by hightman)
</h3>  
基本功能: (v2)根据词频词典进行正向机械分词, (v3)双向根据词频取较高之分法, 中外人名智能辨认 (词典格式: xdb/gdbm/cdb/sqlite/text等)
<hr />

<table width=100% border=0>
  <tr>
    <form method=post>
	<td width=100%>
	  <strong>请输入文字点击提交尝试分词: </strong> <br />
	  <textarea name=mydata cols=60 rows=8 class=demotx><?php echo $mydata; ?></textarea>
	  <small>
	    <input type=checkbox name=autodis value="yes"<?php echo $checked_autodis;?>> 智能识别人名
		&nbsp;
		<input type=checkbox name=ignore value="yes"<?php echo $checked_ignore;?>> 清除标点符号
		&nbsp;
		<input type=checkbox name=stats value="yes"<?php echo $checked_stats;?>> <font color=red>统计结果</font>
		&nbsp;
		<input type=checkbox name=debug value="yes"<?php echo $checked_debug;?>> debug
		&nbsp;
		<br />
		词典格式: 
		<select name=dict size=1>		
		<option value=xdb>XDB</option>
		<option value=cdb<?php echo $selected_cdb;?>>CDB</option>
		<option value=gdbm<?php echo $selected_gdbm; ?>>GDBM</option>
		<option value=text<?php echo $selected_text; ?>>Text</option>
		<option value=sqlite<?php echo $selected_sqlite; ?>>SQLite2.x</option>
		</select>
		&nbsp;
		尝试采用第
		<select name=version size=1 style="color: red; font-weight: bold;">
		  <option value=3>3</option>
		  <option value=2<?php echo $checked_v2;?>>2</option>
		</select>
		版分词算法
		&nbsp;&nbsp;
	  </small>
	  <input type=submit>
	  </td>
	  </form>
	</tr>
	<tr>
	  <td><hr /></td>
	</tr>
	<tr>
	  <td width=100%>
	    <strong>分词结果(原文总长度 <?php echo strlen($mydata); ?> 字符) </strong>
		<br />
		<textarea cols=60 rows=8 class=demotx readonly style="color:#888888;">
<?php } else { ?>
_____________________________________________________________________

PHP简易中文分词程序(SCWS) - 第<?php echo $version; ?>版 - by hightman
_____________________________________________________________________
1.基于词频词典逐点搜索最长词(v2), 双向根据词频取较高之分法(v3)
2.用法: <?php echo $_SERVER['argv'][0]; ?> [选项] <string|file>
3.选项: --autodis    打开人名识别
        --ignore     清除结果中的标点符号
        --v2         使用第2版分词算法(缺省第3版)
        --stats      分析统计分词结果 
        --dict <file>直接指定词典文件, 后缀(.xdb|.cdb|.gdbm|.txt|.sqlite)
        --help       显示本页帮助文件
4.结果: 直接输出分词结果, 词之间以空格分隔
_____________________________________________________________________

<?php } ?>
<?php
// 执行切分, 分词结果数组执行 words_cb()
$cws->segment($mydata, 'words_cb');

// 切分时间统计
$time_end = get_microtime();
$time = $time_end - $time_start;

// hightman.060330: 分词结果报表
$stat_string  = '';
if ($stats)
{
	$stat_string .= sprintf("%-16s  %-8s  %s\n", '字或词', '次数', '出现位置');
	$stat_string .= str_repeat('-', 70) . "\n";
	
	$list = &$cws->get_statistics();
	$word_num = 0;
	foreach ($list as $k => $v)
	{
		$stat_string .= sprintf("%-16s  %-8d  %s\n", $k, $v['times'], implode(', ',$v['poses']));
		$word_num += $v['times'];
	}

	$stat_string .= str_repeat('-', 70) . "\n";
	$stat_string .= sprintf("合计:%4d 词      %-8d\n", count($list), $word_num);
}

// 以下显示结果
?>
<?php if (!$is_cli) { ?>
	  </textarea>		
	  <small>
	    分词耗时: <?php echo $time; ?>秒, 
		词典: <?php echo $dict; ?>, 查询次数: <?php echo $cws->_dict->query_times; ?>次
	  </small>
	</td>
  </tr>
</table>
<hr />
<pre>
<?php echo $stat_string; ?>
</pre>
<hr />
<small>
  注: 本程序代码及相关词典免费开放下载, 供研究学习交流.
  参见网页 <a href=http://www.ftphp.com/scws target=_blank>http://www.ftphp.com/scws</a>
  或直接 <a href="?source" target="_blank">查看源码</a>
</small>
</body>
</html>
<?php } else if (!empty($mydata)) { ?>

_____________________________________________________________________
总长: <?php echo strlen($mydata); ?>字符, 耗时: <?php echo $time; ?>秒, 查词次数: <?php echo $cws->_dict->query_times; ?>次, 词典: <?php echo $dict; ?>

<?php echo $stat_string; } ?>
