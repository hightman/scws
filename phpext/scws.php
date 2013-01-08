<?php
if(!extension_loaded('scws')) {
	dl('scws.' . PHP_SHLIB_SUFFIX);
}
$module = 'scws';
$functions = get_extension_funcs($module);
echo "Functions available in the $moduel extension:\n";
foreach($functions as $func) {
    echo $func . "\n";
}
echo "\n";
$function = $module . '_version';
if (extension_loaded($module)) {
	$str = $function($module);
} else {
	$str = "Module $module is not compiled into PHP";
}
echo "$str\n\n";

$text = <<<EOF
陈凯歌并不是《无极》的唯一著作权人，一部电影的整体版权归电影制片厂所有。

一部电影的作者包括导演、摄影、编剧等创作人员，这些创作人员对他们的创作是有版权的。不经过制片人授权，其他人不能对电影做拷贝、发行、反映，不能通过网络来传播，既不能把电影改编成小说、连环画等其他艺术形式发表，也不能把一部几个小时才能放完的电影改编成半个小时就能放完的短片。

著作权和版权在我国是同一个概念，是法律赋予作品创作者的专有权利。所谓专有权利就是没有经过权利人许可又不是法律规定的例外，要使用这个作品，就必须经过作者授权，没有授权就是侵权。

一九八零年春天
EOF;

$cws = scws_open();
scws_set_charset($cws, "utf8");
scws_set_dict($cws, ini_get('scws.default.fpath') . '/dict.utf8.xdb');
scws_set_rule($cws, ini_get('scws.default.fpath') . '/rules.utf8.ini');
//scws_set_ignore($cws, true);
//scws_set_multi($cws, true);
scws_send_text($cws, $text);

echo "\n";

// top words
printf("No. WordString               Attr  Weight(times)\n");
printf("-------------------------------------------------\n");
$list = scws_get_tops($cws, 10, "~v");
$cnt = 1;
foreach ($list as $tmp)
{
	printf("%02d. %-24.24s %-4.2s  %.2f(%d)\n",
		$cnt, $tmp['word'], $tmp['attr'], $tmp['weight'], $tmp['times']);
	$cnt++;
}

echo "\n\n-------------------------------------------------\n";
// segment
while ($res = scws_get_result($cws))
{
	foreach ($res as $tmp)
	{
		if ($tmp['len'] == 1 && $tmp['word'] == "\r")
			continue;
		if ($tmp['len'] == 1 && $tmp['word'] == "\n")
			echo $tmp['word'];
		else		
			printf("%s/%s ", $tmp['word'], $tmp['attr']);		
	}
}
echo "\n\n";

scws_close($cws);
?>
