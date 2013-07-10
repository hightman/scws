<?php
// hightman, SCWS v4 (built as php_extension)
// 2007/06/02
//
// view the source code
if (isset($_SERVER['QUERY_STRING']) 
	&& !strncasecmp($_SERVER['QUERY_STRING'], 'source', 6))
{
	highlight_file(__FILE__);
	exit(0);
}

// try to count the time
function get_microtime()
{
	list($usec, $sec) = explode(' ', microtime()); 
	return ((float)$usec + (float)$sec); 
}
$time_start = get_microtime();

// demo data
if (!isset($_REQUEST['mydata']) || empty($_REQUEST['mydata']))
	$mydata = file_get_contents('sample.utf8.txt');	
else
{
	$mydata = & $_REQUEST['mydata'];
	if (get_magic_quotes_gpc())
		$mydata = stripslashes($mydata);
}

// other options
$ignore = $showa = $stats = $duality = false;
$checked_ignore = $checked_showa = $checked_stats = $checked_duality = '';

// 是否清除标点符号
if (isset($_REQUEST['ignore']) && !strcmp($_REQUEST['ignore'], 'yes'))
{
	$ignore = true;
	$checked_ignore = ' checked';
}

// 是否散字自动二元
if (isset($_REQUEST['duality']) && !strcmp($_REQUEST['duality'], 'yes'))
{
	$duality = true;
	$checked_duality = ' checked';
}

// 是否标注词性
if (isset($_REQUEST['showa']) && !strcmp($_REQUEST['showa'], 'yes'))
{
	$showa = true;
	$checked_showa = ' checked';
}

// 是转看统计表
if (isset($_REQUEST['stats']) && !strcmp($_REQUEST['stats'], 'yes'))
{
	$stats = true;
	$checked_stats = ' checked';
}

// 是否复合分词?
$multi = 0;
if (isset($_REQUEST['multi']) && is_array($_REQUEST['multi'])){
	foreach ($_REQUEST['multi'] as $mval) $multi |= intval($mval);
}
$mtags = array('最短词' => 1, '二元' => 2, '重要单字' => 4, '全部单字' => 8);

$xattr = &$_REQUEST['xattr'];
if (!isset($xattr)) $xattr = '~v';
$limit = &$_REQUEST['limit'];
if (!isset($limit)) $limit = 10;

// do the segment
$cws = scws_new();
$cws->set_charset('utf8');

//
// use default dictionary & rules
//

$cws->set_duality($duality);
$cws->set_ignore($ignore);
$cws->set_multi($multi);
$cws->send_text($mydata);
?>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<title>PHP简易中文分词(SCWS) 第4版在线演示 (by hightman)</title>
<style type="text/css">
<!--
td, body	{ background-color: #efefef; font-family: tahoma; font-size: 14px; }
.demotx		{ font-size: 12px; width: 100%; height: 140px; word-break: break-all; }
small		{ font-size: 12px; }
//-->
</style>
</head>
<body>
<h3>
  <font color=red>PHP简易中文分词(SCWS)</font>
  <font color=blue>第4版(UTF8)</font> - 在线演示 (by hightman)
</h3>  
基本功能: 根据词频词典较为智能的中文分词，支持规则识别人名、地区等。（<a href="v4.php">GBK点这里</a> <a href="v48.cht.php">繁体版</a>）<br />
<a href="http://www.xunsearch.com" target="_blank">推荐看看，结合 scws + xapian 构建的开源全文搜索引擎 xunsearch ！！</a> <hr />

<table width=100% border=0>
  <tr>
    <form method=post>
	<td width=100%>
	  <strong>请输入文字点击提交尝试分词: </strong> <br />
	  <textarea name=mydata cols=60 rows=14 class=demotx><?php echo $mydata; ?></textarea>
	  <small>
	    <span style="color:#666666;">		
	    <strong>[复合分词选项]</strong>
<?php foreach ($mtags as $mtag => $mval) { ?>
		<input type=checkbox name="multi[]" value=<?php echo $mval . " " . (($multi & $mval) ? " checked" : "");?>><?php echo $mtag;?>&nbsp;
<?php } ?>
		</span>
	    <br />
		<input type=checkbox name=ignore value="yes"<?php echo $checked_ignore;?>> 清除标点符号
		&nbsp;
		<input type=checkbox name=duality value="yes"<?php echo $checked_duality;?>> 散字二元
		&nbsp;
		<input type=checkbox name=showa value="yes"<?php echo $checked_showa;?>> <font color=green>标注词性</font>
		&nbsp;
		<input type=checkbox name=stats value="yes"<?php echo $checked_stats;?>> <font color=red>只看统计</font>
		<input type=text name=limit size=2 value="<?php echo intval($limit);?>">个
		&nbsp;
		统计词性: 
		<input type=text name=xattr size=8 value="<?php echo htmlspecialchars($xattr);?>">(多个用,分开 以~开头表示不包含)
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
		(<a href="http://bbs.xunsearch.com/forumdisplay.php?fid=8" target="_blank">这次分词结果不对，点击汇报</a>)
		<br />
		<textarea cols=60 rows=14 class=demotx readonly style="color:#888;">
<?php
if ($stats == true)
{
	// stats
	printf("No. WordString               Attr  Weight(times)\n");
	printf("-------------------------------------------------\n");
	$list = $cws->get_tops($limit, $xattr);
	$cnt = 1;
	settype($list, 'array');
	foreach ($list as $tmp)
	{
		printf("%02d. %-24.24s %-4.2s  %.2f(%d)\n",
			$cnt, $tmp['word'], $tmp['attr'], $tmp['weight'], $tmp['times']);
		$cnt++;
	}
}
else
{
	// segment
	while ($res = $cws->get_result())
	{
		foreach ($res as $tmp)
		{
			if ($tmp['len'] == 1 && $tmp['word'] == "\r")
				continue;
			if ($tmp['len'] == 1 && $tmp['word'] == "\n")
				echo $tmp['word'];
			else if ($showa)
				printf("%s/%s ", $tmp['word'], $tmp['attr']);
			else
				printf("%s ", $tmp['word']);
		}
		flush();
	}
}

$cws->close();
$time_end = get_microtime();
$time = $time_end - $time_start;
?>
		</textarea>		
		<small>
		  分词耗时: <?php echo $time; ?>秒
		  <a href="../">返回scws主页</a>或直接<a href="?source" target="_blank">查看源码</a> Powered by <?php echo scws_version();?>
		</small>
	</td>
  </tr>
</table>
</body>
</html>
