<?php
// get_tfidf.php (by baidu);
if (!strcasecmp($_SERVER['QUERY_STRING'], 'source')) exit(highlight_file(__FILE__));
function get_tfidf($word, $count)
{
	if ($count < 1000) $count = 21000 - $count * 18;
	$tf = log($count);
	$tf = pow($tf, 5) * log(strlen($word));
	$tf = log($tf);
	$idf = log(5000000000/$count);
	//if ($tf > 13) $idf *= 1.4;
	return array($tf, $idf);
}

function get_count($word)
{
	$url  = "http://www.baidu.com/s?ie=gb2312&wd=" . urlencode($word);
	$data = @file_get_contents($url);
	if (!$data) return -1;
	$pos = -1;
	$pos1 = @strpos($data, "找到相关网页约", 2048) + 14;
	$pos0 = @strpos($data, "找到相关网页", 2048) + 12;
	$pos = ($pos1 > 14 ? $pos1 : $pos0);
	$total = 0;
	if ($pos > 12)
	{
		$pos2 = @strpos($data, "篇", $pos);
		$total = substr($data, $pos, $pos2 - $pos1);
		$total = (int) str_replace(",", "", $total);
	}
	return $total;
}

$res = array();
$warn_str = '';
$word = isset($_REQUEST['data']) ? $_REQUEST['data'] : '';
if ($word != '')
{
	if (get_magic_quotes_gpc()) $word = stripslashes($word);
	$word = trim(strip_tags($word));
	if (strlen($word) < 2) $warn_str = "请输入正确的词汇";
	else if (strlen($word) > 30) $warn_str = "输入的词语太长了";
	else if (strpos($word, ' ') !== false) $warn_str = "词汇不要包含空格";
	else if (preg_match('/[\x81-\xfe]/', $word) && preg_match('/[\x20-\x7f]{3}/', $word)) 
		$warn_str = "中英混合时字母最多只能出3个以下的连续字母";
	else
	{
		$count = get_count($word);
		if ($count < 0) $warn_str = "内部原因，计算失败！";
		else $res = get_tfidf($word, $count);
	}
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
<title>新词生词的TF/IDF计算器 - for scws</title>
<style>
body { font-size: 14px; }
pre { font-size: 12px; color: red; }
</style>
</head>
<body>
<h1>新词生词的TF/IDF计算器</h1>
<p>此计算器的依据是参照百度搜索结果数量加以计算，计算公式仅适用于 scws 分词，其它用途则只能用于参考。</p>
<form method="post">
<input type="text" size="30" name="data" value="<?php echo htmlspecialchars($word); ?>" />
<input type="submit">
</form>
<p>
<?php
if (!empty($word) && empty($warn_str)) 
	printf("计算结果：WORD=%s TF=%.2f IDF=%.2f<br />\n", $word, $res[0], $res[1]);
if (!empty($warn_str))
	printf("<font color=\"red\">错误：%s</font><br />\n", $warn_str);
?>
</p>
</body>
</html>
