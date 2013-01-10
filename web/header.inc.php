<?php
// forced to with / trail end
if (substr($_SERVER['REQUEST_URI'], -4) === 'scws')
{
	header('HTTP/1.1 301 Moved Permanently');
	header("Location: " . $_SERVER['REQUEST_URI'] . '/');
	exit(0);
}
//
// load version from down files automatically
$version = false;
$files = glob(dirname(__FILE__) . "/down/scws-*.tar.bz2");
foreach ($files as $file)
{
	$pos1 = strpos($file, 'scws-') + 5;
	$pos2 = strpos($file, '.tar');
	$ver = substr($file, $pos1, $pos2 - $pos1);
	if (!$version || version_compare($ver, $version) > 0)
		$version = $ver;
}

// nav menu items
$menu = array(
	array('index.php', '首页'),
	array('download.php', '下载', '下载 scws 的各个版本'),
	array('demo.php', '演示', 'SCWS 分词在线演示'),
	array('docs.php', '文档', 'SCWS 说明文档'),
	array('about.php', '关于'),
	array('support.php', '服务&支持', '关于 SCWS 的服务与支持'),
	array('api.php', 'API/HTTP')
);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo isset($title) ? $title : 'SCWS'; ?>|中文分词|PHP中文分词 - 开源免费的中文分词系统</title>
<meta name="Keywords" content="PHP中文分词 PHP分词 分词组件 开源免费 分词系统 中文分词 全文索引 搜索引擎 PHP 分词词典" />
<meta name="Description" content="SCWS - 开源免费的简易中文分词系统，包括纯 C 和 PHP 的各种代码实现，支持 PHP 的扩展方式调用！" />
<style type="text/css">
body { width: 960px; margin: 5px auto; padding: 10px; font-size: 14px; font-family: Helvetica, sans-serif;}
a { color: #24a ; text-decoration: none; }
a:hover { text-decoration: underline; }
#header { overflow: hidden; zoom: 1; }
#header h1 { font-size: 24px; font-weight: bold; color: #c30; float: left; margin: 0; }
#header sup { font-size: 11px; color: #cbd; margin-left: 5px; }
#header span { margin-left: 20px; font-size: 12px; }
#nav { margin-top: 20px; padding-bottom: 10px; border-bottom: 2px solid #978; }
#nav a { margin-right: 10px; }
#nav a.active { text-decoration: underline; color: #48c; }

#footer { margin-top: 20px; padding-top: 10px; font-size: 12px; text-align: center; border-top: 1px solid #666; color: #666; }
#footer a { color: #666; }

.block li.sticky { font-size: 14px; }
.block li.sticky strong { color: #c00; }
.block li.important { list-style: none; border: 1px solid red; background: #eef; padding: 5px 10px; margin: 10px 10px 10px -14px; }
.block li.important dt { font-weight: bold; color: darkblue; font-size: 18px; }
.block li.important dd { font-size: 12px; line-height: 160%; }

.block .notice { color: #c00; }
.block h2 { font-size: 18px; font-weight: bold; color: #24a; }
.block h2 span { font-size: 12px; color: #666; font-weight: normal; margin-left: 10px; }
.block ul, .block ol { margin: 0 0 0 20px; padding: 0; }
.block li { margin-bottom: 10px; font-size: 12px; }
.block dt { font-family: Courier; font-size: 14px; padding: 2px 0; }
.block dd { margin: 0; padding: 2px 0; _text-indent: -40px; *text-indent: -40px; }
.block th { font-size: 14px; padding: 3px; }
.block td { font-size: 12px; padding: 3px; }
.block li ul, .block li ol { margin-top: 10px; }
.block ol li { font-size: 14px; }
.block ol em { display: inline-block; width: 70px; }
.block pre { padding: 20px; font-family: Helvetica, sans-serif; font-size: 12px; border: 1px dotted #bbc; }

/*


td, body { font: 76% Verdana, Arial, Helvetica, sans-serif; font-size: 14px; color: #333; word-break: break-all; }

small, .small { font-size: 12px; }
.small td { font-size: 12px; }
big, .big { font-size: 16px; }
.mid-title { font-size: 18px; font-weight: bold; }
.big-title { font-size: 24px; font-weight: bold; color: #c30; }
.ver-title { font-size: 11px; color: #cbd; }
.outer { width: 960px; }
li.first { list-style: none; border: 1px solid red; background: #e0e0f0; padding: 6px; margin: 12px; margin-left: -12px; }
li.first dt { font-weight: bold; color: darkblue; font-size: 18px; }
li.first dd { font-size: 12px; line-height: 200%; }
*/
</style>
</head>
<body>
<!-- title bar -->
<div id="header">
	<h1>SCWS 中文分词</h1>
	<sup>v<?php echo $version; ?></sup>
	<span>开源免费的中文分词系统，PHP分词的上乘之选！</span>
</div>
<div id="nav">
	<?php foreach ($menu as $item): ?>
	<a href="<?php echo $item[0]; ?>"<?php echo strstr($_SERVER['SCRIPT_NAME'], $item[0]) ? ' class="active"' : ''; ?><?php echo isset($item[2]) ? ' title="' . $item[2] . '"' : ''; ?>><?php echo $item[1]; ?></a>
	<?php endforeach; ?>
	<a href="http://bbs.xunsearch.com/forumdisplay.php?fid=8" target="_blank">论坛</a>
	<a href="https://github.com/hightman/scws/" title="scws 代码已全部托管在 github！" class="last">源码@github</a>	
</div>
