<?php
// ddemo page
// $Id: $
$title = '在线演示';
include 'header.inc.php';
?>
<div class="block">
	<h2>演示：SCWS-1.x.x <span>以 php 扩展方式运行</span></h2>
	<p>
		[<a href="demo/v4.php" target="_blank">简体中文(GBK)</a>]
		[<a href="demo/v48.php" target="_blank">简体中文(UTF-8)</a>]
		[<a href="demo/v48.cht.php" target="_blank">繁体中文(UTF-8)</a>]
	</p>	
</div>
<div class="block">
	<h2>演示：PSCWS23</h2>
	<p>纯 PHP 开发的 SCWS 第二版和第三版，仅支持 GBK 字符集，速度较快，推荐在全 PHP 环境中使用。</p>
	<p>[<a href="demo/pscws23/demo.php" target="_blank">进入在线演示</a>]</p>
</div>
<div class="block">
	<h2>其它相关演示</h2>
	<p>新词生词对应的TF/IDF计算器：[<a href="demo/get_tfidf.php">点击进入</a>]</p>
	<p>基于scws的自动分类建议系统：[<a href="demo/a.php">点击进入</a>]</p>
</div>
<?php include 'footer.inc.php'; ?>
