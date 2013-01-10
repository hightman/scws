<?php
// download page
// $Id$
//
$title = '下载中心';
include 'header.inc.php';
?>
<div class="block">
	<h2>下载：SCWS-<?php echo $version; ?><a name="scws">&nbsp;</a><span>scws 完整源代码套件</span></h2>
	<p>SCWS 全部源代码，包括 libscws 核心库，命令行工具程序，php 扩展代码，规则集及文档。使用 ANSI-C 语言开发，推荐在 Unix-Like OS 的 gcc 环境编译安装（也支持 cygwin 环境）。</p>
	<p>
	　[立即下载：<a href="down/scws-<?php echo $version; ?>.tar.bz2">scws-<?php echo $version; ?>.tar.bz2</a> (<?php printf("%dKB", filesize('down/scws-' . $version . '.tar.bz2') / 1024); ?>)]
	　[<a href="docs.php#instscws">详细安装说明</a>]
	　[<a href="docs.php#libscws">C-API 文档</a>]
	　[<a href="docs.php#phpscws">PHP扩展-API 文档</a>]
	</p>	
</div>
<div class="block">
	<h2>php_scws.dll (<?php echo $version; ?>)<a name="dll">&nbsp;</a></h2>
	<p>php_scws.dll 是由 <a href="http://www.yanbin.org" target="_blank">ben</a> 移植用于 Windows 平台下的 PHP 动态扩展库，请根据您使用的版本下载，均为 x86 环境。其他版本的 PHP 或环境请自行根据源码目录下的 phpext/win32 构建。（NTS 表示 Non-Thread-Safety)</p>
	<p>
	　[<a href="down/php-4.4.x/php_scws.dll">PHP-4.4.x</a> (44KB/VC6/ZTS)]
	　[<a href="down/php-5.2.x/php_scws.dll">PHP-5.2.x</a> (44KB/VC6/ZTS)]
	　[<a href="down/php-5.3.x/php_scws.dll">PHP-5.3.x</a> (40KB/<span class="notice">VC9</span>/ZTS)]<br />
	　[<a href="down/php-5.4.x/php_scws.dll">PHP-5.4.x</a> (40KB/<span class="notice">VC9</span>/ZTS)]
	　[<a href="down/php-5.3.x-nts/php_scws.dll">PHP-5.3.x</a> (40KB/<span class="notice">VC9</span>/NTS)]	 
	　[<a href="down/php-5.4.x-nts/php_scws.dll">PHP-5.4.x</a> (40KB/<span class="notice">VC9</span>/NTS)]	<br />
	　[<a href="down/php-5.3.x_vc6/php_scws.dll">PHP-5.3.x</a> (44KB/<span class="notice">VC6</span>/ZTS)]
	　[<a href="docs.php#instdll">详细安装说明</a>]
	　[<a href="docs.php#phpscws">PHP扩展-API 文档</a>]
	</p>	
</div>
<div class="block">
	<h2>XDB 词典文件<a name="dict">&nbsp;</a></h2>
	<p>XDB 格式的词典文件，可用于 SCWS-1.x.x 和 PSCWS4，不可用于 PSCWS23。此为通用词典文件，定制词典或其它服务请查看<a href="support.php">服务支持</a>页面。</p>
	<p>
	　[<a href="down/scws-dict-chs-gbk.tar.bz2">简体中文(GBK)</a> (3.84MB，28万词，<span class="notice"><?php echo date("Y/m/d", filemtime("down/scws-dict-chs-gbk.tar.bz2")); ?>更新</span>)]<br />
	　[<a href="down/scws-dict-chs-utf8.tar.bz2">简体中文(UTF-8)</a> (3.9MB，28万词，<span class="notice"><?php echo date("Y/m/d", filemtime("down/scws-dict-chs-utf8.tar.bz2")); ?>更新</span>)] <br />
	　[<a href="down/scws-dict-cht-utf8.tar.bz2">繁体中文(UTF-8)</a> (1.21MB，10万词)]
	</p>	
</div>
<div class="block">
	<h2>PSCWS4<a name="pscws4">&nbsp;</a></h2>
	<p>这是用纯 PHP 代码实现的 C 版 Libscws 的全部功能，即第四版的 PSCWS，速度较慢，不推荐使用。下载包不含词典，请从上面 XDB 词典中下载。</p>
	<p>　[立即下载：<a href="down/pscws4-20081221.tar.bz2">pscws4-20081221.tar.bz2</a> (18.1KB)]　[<a href="docs.php#pscws4">说明文档</a>]</p>	
</div>
<div class="block">
	<h2>PSCWS23<a name="pscws23">&nbsp;</a></h2>
	<p>纯 PHP 开发的 SCWS 第二版和第三版，仅支持 GBK 字符集，速度较快，推荐在全 PHP 环境中使用，已含专用 xdb 词典一部。</p>
	<p>　[立即下载：<a href="down/pscws23-20081221.tar.bz2">pscws23-20081221.tar.bz2</a> (2.79MB)]　[<a href="docs.php#pscws23">说明文档</a>]</p>
</div>
<div class="block">
	<h2>规则集文件<a name="rules">&nbsp;</a></h2>
	<p>SCWS 及 PSCWS4 通用的规则集文件，用于识别人名、地名、数字年代等。内含简体GBK、繁体UTF8、简体UTF8三个文件。不需要单独下载，随 scws 一起发布的源码包中已经包含这些文件。</p>
	<p>　[立即下载：<a href="down/rules.tgz">rules.tgz</a> (内含三个文件)] (2011.4.20更新)</p>
</div>
<div class="block">
	<h2>XDB导入导出工具<a name="xtools">&nbsp;</a></h2>
	<p>XDB文件是专为 SCWS 优化而开发的一个高效简易存储结构，不能直接编辑和查看。现特意用纯 PHP 脚本编写了2个小工具，可以直接将 xdb 文件导出成可视的纯文本文件，以及由这样的文本文件导入生成 xdb 文件。</p>
	<p>　[立即下载：<a href="down/phptool_for_scws_xdb.zip">phptool_for_scws_xdb.zip</a> (9KB)]</p>
</div>
<?php include 'footer.inc.php'; ?>
