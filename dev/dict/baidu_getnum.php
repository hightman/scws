<?php
// get the basic data
function baidu_get_num($kw)
{
	$url  = "http://www.baidu.com/s?lm=0&si=&cl=3&ie=gb2312&ct=0&wd=" . urlencode($kw);
	$url .= "&rn=$num&pn=$off";
	$data = @file_get_contents($url);
	$pos = -1;
	if (!$data) return -1;
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

// load from the data
$file = ($_SERVER['argv'][1] ? $_SERVER['argv'][1] : 'wordlist-gbk.txt');
$fd = @fopen($file, "r");
if (!$fd)
{
	$try = 0;
	$freq = -1;
	$word = $file;
	while ($freq < 0 && $try < 5)
	{
		$freq = baidu_get_num($word);
		$try++;
		if ($freq < 0) sleep(60);
	}
	echo "$word\t$freq\n";
	exit(0);
}

while ($line = fgets($fd, 256))
{
	list($word, $freq, $reversed) = explode("\t", $line);
	$freq = intval($freq);
	$reversed = trim($reversed);
	$try = 0;
	while ($freq < 0 && $try < 5)
	{
		$freq = baidu_get_num($word);
		$try++;
		if ($freq < 0) sleep(60);
	}
	echo "$word\t$freq\t$reversed\n";
}
fclose($fd);
?>
