<?php
// PHP简易中文分词(SCWS) 第4版 API
// 2010/12/01
// $Id$
//
set_time_limit(0);
error_reporting(0);
ini_set('display_errors', '0');

// show source
if (isset($_SERVER['QUERY_STRING']) 
    && !strncasecmp($_SERVER['QUERY_STRING'], 'source', 6))
{
	header("Content-Type: text/html; charset=utf-8");
    highlight_file(__FILE__);
    exit(0);
}

// get data
if (!isset($_POST['data']))
{
	$charset = 'utf-8';
	$respond = 'plain';
	$apiurl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
	$version2 = scws_version();
	include 'header.inc.php';
	echo <<<__EOF__
<div class="block">
<h2>SCWS(简易中文分词) 基于HTTP/POST的分词 API 使用说明</h2>
<ol>
<li>API 地址：<a href="$apiurl">$apiurl</a></li>
<li>
	请求方式：仅支持 POST，推荐采用纯 PHP 实现的 <a href="http://www.hightman.cn/bbs/showthread.php?tid=838" target="_blank">HTTP client 库</a>
</li>
<li>请求的参数变量及含义：
	<ul>
		<li><em>data</em> 需要分词的字符串(*必须*)</li>
		<li><em>respond</em> 响应结果格式(其值为: php/json/xml, 默认为 php，其中 php是指用php序列化后的结果)</li>
		<li><em>charset</em> 待分词的字符串编码(gbk/utf8，默认是utf8)</li>
		<li><em>ignore</em> 是否忽略标点符号(yes/no，默认为 no)</li>
		<li><em>duality</em> 是否散字自动二元(yes/no，默认为 no)</li>
		<li><em>traditional</em> 是否采用繁体字库(yes/no，默认为 no，仅当 charset 为 utf8 时有效)</li>
		<li><em>multi</em> 复合分词的级别(整数值 1~15：0x01-最短词；0x02-二元；0x04-重要单字；0x08-全部单字)
   			默认为0，如有需要建议设置为 3</li>
	</ul>
</li>
<li>响应的数据：
	<ol type="i">
		<li>如果出错则其中的 status 属性/键的值为 error，而 message 为错误信息</li>
		<li>成功则 status 值为 ok，words 值是分好的词的列表(数组)</li>
		<li>每个分好的词包括以下属性/键值：
			<ul>
				<li><em>word</em> 词的内容</li>
				<li><em>off</em> 该词在未分词文本中的偏移位置</li>
				<li><em>idf</em> 该词的 IDF 值</li>
				<li><em>attr</em> 词性 (北大标注格式) <a href="http://bbs.xunsearch.com/showthread.php?tid=1235" target="_blank">参见这里</a>。</li>
			</ul>
		</li>
	</ol>
</li>
<li>该 API 自 2010/12/2 起可用, 感谢用户 keen-lee 的建议并编写了API调用的初始版，<a href="{$apiurl}?source">查看 API 源码</a></li>   
<li>当前版本：{$version2}</li>
<li>在线测试：
	<script language="javascript">
	function set_multi(c, n) {
		c.form.multi.value = parseInt(c.form.multi.value) + (c.checked ? n : (0 - n));
	}
	</script>
	<form method="post" action="$apiurl">
		<textarea cols="80" rows="4" name="data"></textarea><br />
		复合分词：<input type="hidden" name="multi" value="0" />
		<input type="checkbox" onclick="set_multi(this,1)" />最短词
		<input type="checkbox" onclick="set_multi(this,2)" />散字二元
		<input type="checkbox" onclick="set_multi(this,4)" />重要单字
		<input type="checkbox" onclick="set_multi(this,8)" />全部单字
		<br />
		<input type="checkbox" name="ignore" value="yes" />忽略标点？
		<input type="checkbox" name="duality" value="yes" />散字二元？
		<input type="checkbox" name="traditional" value="yes" />繁体词库？
		输出格式：<input type="radio" name="respond" value="php" />php
		<input type="radio" name="respond" value="json" />json
		<input type="radio" name="respond" value="xml" checked />xml
		<input type="submit" value="提交分词" />
	</form>
</li>
</ol>
</div>
__EOF__;
	include 'footer.inc.php';
	exit(0);
}
$data = trim($_POST['data']);

// get respond
$respond = isset($_POST['respond']) ? strtolower($_POST['respond']) : 'php';
if ($respond === 'json' && !function_exists('json_encode'))
{
	$respond = 'plain';
	$result = '{"status":"error","message":"JSON data is unavailable"}';
	output_result($result);
}
if ($respond !== 'php' && $respond !== 'json' && $respond !== 'xml')
{
	$respond = 'php';
	output_result(set_simple_result('Invalid parameter: respond'));
}

// get charset
$charset = (isset($_POST['charset']) ? strtolower($_POST['charset']) : 'utf8');
if ($charset === 'utf-8') $charset = 'utf8';
else if ($charset === 'gb2312' || $charset === 'gb18030') $charset = 'gbk';
if ($charset !== 'gbk' && $charset !== 'utf8')
	output_result(set_simple_result('Invalid parameter: charset'));
if ($charset !== 'utf8' && $respond == 'json')
	output_result(set_simple_result('JSON respond data only work with utf8 charset'));

// get other parameters
$ignore = (isset($_POST['ignore']) && !strcasecmp($_POST['ignore'], 'yes')) ? true : false;
$duality = (isset($_POST['duality']) && !strcasecmp($_POST['duality'], 'yes')) ? true : false;
$traditional = (isset($_POST['traditional']) && !strcasecmp($_POST['traditional'], 'yes')) ? true : false;
$multi = isset($_POST['multi']) ? intval($_POST['multi']) : 0;
if ($multi < 0 || $multi > 15)
	output_result(set_simple_result('Invalid parameter: multi'));

// do segmentation
$scws = scws_new();
$scws->set_charset($charset);
if ($charset === 'utf8' && $traditional === true)
{
	$scws->set_rule(ini_get('scws.default.fpath') . '/rules_cht.utf8.ini');
	$scws->set_dict(ini_get('scws.default.fpath') . '/dict_cht.utf8.xdb');
}

// apply other settings & send the text content
$scws->set_duality($duality);
$scws->set_ignore($ignore);
$scws->set_multi($multi);
$scws->send_text($data);

// fetch the result
$words = array();
while ($res = $scws->get_result()) $words = array_merge($words, $res);

// output the result
$result = array('status' => 'ok', 'words' => $words);
output_result($result);

// -----------------------------------------------------------------
// internal functions
// -----------------------------------------------------------------
// output real result
function output_result($result)
{
	global $respond, $scws, $charset;
	if ($scws) $scws->close();

	// get oe (output encoding)
	if ($charset === 'utf8') $charset = 'utf-8';	
	// header
	if ($respond === 'xml')
	{
		header('Content-Type: text/xml; charset=' . $charset);
		echo '<?xml version="1.0" encoding="' . $charset . '"?>' . "\n";
		echo array_to_xml($result, '', 'respond');
	}
	else
	{
		header('Content-Type: text/plain; charset=' . $charset);
		if ($respond === 'json')
			echo json_encode($result);
		else if ($respond === 'php')
			echo serialize($result);
		else
			echo $result;
	}
	exit(0);
}

// set error
function set_simple_result($msg = 'Unknown reason', $type = 'error')
{
	return array('status' => $type, 'message' => $msg);
}

// convert php array to xml
function array_to_xml($var, $tag = '', $type = 'respond')
{
	if (!is_int($type))
	{
		if ($tag) return array_to_xml(array($tag => $var), 'scws:' . $type, 0);
		else
		{
			$tag = 'scws:' . $type;
			$type = 0;
		}
	}	
	$level = $type;
	$indent = str_repeat("\t", $level);
	if (!is_array($var))
	{
		$ret .= $indent . '<' . $tag;
		$var = strval($var);
		if ($var == '') $ret .= ' />';
		else if (!preg_match('/[^0-9a-zA-Z@\._:\/-]/', $var)) $ret .= '>' . $var . '</' . $tag . '>';
		else if (strpos($var, "\n") === false) $ret .= '><![CDATA[' . $var . ']]></' . $tag . '>';
		else $ret .= ">\n{$indent}\t<![CDATA[{$var}]]>\n{$indent}</{$tag}>";
		$ret .= "\n";
	}
	else if (_is_simple_array($var))
	{			
		foreach ($var as $tmp) $ret .= array_to_xml($tmp, $tag, $level);
	}
	else
	{
		$ret .= $indent . '<' . $tag;
		if ($level == 0) $ret .= ' xmlns:scws="http://www.xunsearch.com/scws"';
		$ret .= ">\n";
		foreach ($var as $key => $val)
		{
			$ret .= array_to_xml($val, $key, $level + 1);
		}
		$ret .= "{$indent}</{$tag}>\n";
	}
	return $ret;
}

// check an array is hash-related array or not
function _is_simple_array($arr)
{
	$i = 0;
	foreach ($arr as $k => $v) { if ($k !== $i++) return false; }
	return true;
}
