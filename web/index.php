<?php include 'header.inc.php'; ?>
<div class="block">
	<h2>简介</h2>
	<p>SCWS 是 Simple Chinese Word Segmentation 的首字母缩写（即：简易中文分词系统）。</p>
	<p>
		这是一套基于词频词典的机械式中文分词引擎，它能将一整段的中文文本基本正确地切分成词。
		词是中文的最小语素单位，但在书写时并不像英语会在词之间用空格分开，
		所以如何准确并快速分词一直是中文分词的攻关难点。
	</p>
	<p>
		SCWS 采用纯 C 语言开发，不依赖任何外部库函数，可直接使用动态链接库嵌入应用程序，
		支持的中文编码包括 GBK、UTF-8 等。此外还提供了 PHP 扩展模块，
		可在 PHP 中快速而方便地使用分词功能。
	</p>
	<p>
		分词算法上并无太多创新成分，采用的是自己采集的词频词典，并辅以一定的专有名称，人名，地名，
		数字年代等规则识别来达到基本分词，经小范围测试准确率在 90% ~ 95% 之间，
		基本上能满足一些小型搜索引擎、关键字提取等场合运用。首次雏形版本发布于 2005 年底。
	</p>
	<p>
		SCWS 由 <a href="http://www.hightman.cn" target="_blank">hightman</a> 开发，
		并以 BSD 许可协议开源发布，源码托管在 <a href="https://github.com/hightman/scws" target="_blank">github</a>。
	</p>
</div>
<div class="block">
	<h2>动态</h2>
	<ul>
		<li class="sticky">
			<strong>推荐！！</strong>基于 scws + Xapian 的开源全文搜索引擎 
			<a href="http://www.xunsearch.com" target="_blank">xunsearch（迅搜）</a>发布，是非常好用的 php 全文解决方案！
		</li>
		<li class="important">
		<dt>2013-1-15: SCWS-1.2.1 Released.</dt>
		<dd>1) 将源码迁移并托管到 <a href="https://github.com/hightman/scws" target="_blank">github</a></dd>
		<dd>2) 改进 C API 中 scws_fork() 的算法，使之更为合理</dd>		 
		<dd>3) 迁移并修改新版官方主页：<a href="http://www.xunsearch.com/scws/">http://www.xunsearch.com/scws</a></dd>
		</li>
		<li>
		<dt>2012-3-29: SCWS-1.2.0 Released.</dt>
		<dd>1) 修改 php 扩展代码以兼容支持 php 5.4.x </dd>
		<dd>2) 修正 php 扩展中 scws_get_tops 的 limit 参数不允许少于 10 的问题</dd>		 
		<dd>3) libscws 增加 scws_fork() 从既有的 scws 实例产生分支并共享词典/规则集，主要用于多线程开发。</dd>
		<dd>4) 新增部分版本的 win32 的 dll 扩展，详见<a href="download.php#dll">下载页面</a></dd>			
		</li>
		<li>
		<dt>2011-12-26: SCWS-1.1.9 Released.</dt>
		<dd>1) 明确使用开源协议 New BSD License 发布新版本</dd>
		<dd>2) 深度优化复合分词中的 SCWS_MULTISHORT 选项，更为合理有效，符合全文检索的需求</dd>
		<dd>3) 测试脚本自动加载当前目录下的 dict_user.txt 文本词典</dd>
		<dd>4) 修正 scws.c 中 __PARSE_XATTR__ 宏的 BUG 导致 scws_get_tops 和 scws_get_words 的 xattr 参数工作不正常的问题</dd>
		<dd>5) 移除 scws.c 中关于 jabberd2s10 的注释，已不包含它的代码</dd>
		<dd>6) 为独立使用的 .h 文件添加 C++ 的 extern "C" 标记以便直接使用：xdb.h，xdict.h，xtree.h，pool.h，darray.h</dd>
		</li>		
		<li>
		<dt>2011-07-30: SCWS-1.1.8 Released.</dt>
		<dd>1) win32/目录新增 vc9 工程文件, 默认为 php-5.3.x 提供的 php_scws.dll 采用 VC9(thread-safety) 编译</dd>
		<dd>2) 修改英语专有名词的识别方式, 原先 X.Y.Z 必须字母全大写，现也允许小写</dd>
		<dd>3) 修改 congiure.in 在 ---enable-developer 选项的处理方式，不覆盖预设的 CFLAGS</dd>
		<dd>4) 改变数字字母单独成词时的规则，当其中同时包含2个连续字母以及2个连续数字时强制拆分。例：原先单独的  iso9001 是整词，新规则切为 iso+9001 而 i9001 则保持不变仍为。这样做更有利于全文检索。</dd>
		</li>			
		<li>
		<dt>2011-05-21: SCWS-1.1.7 Released.</dt>
		<dd>1) 删除 __PARSE_XATTR__ 宏中企图修改 xattr 的内容的作法, 当 xattr 为常量字符串时会出错.</dd>
		<dd>2) 调整 config.h 的包含方式移入 .c 文件而非 .h 文件</dd>
		<dd>3) 增加一些PHP测试脚本, 位于phpext/scws_test.php, 精选了一些岐义较多的语句进行测试。</dd>
		<dd>4) 修正 scws_has_word() 的一处内存泄露 (感谢lauxinz)</dd>
		<dd>5) 修改调试模式的编译选项，去除-O2避免源码和代码无法对应。 (感谢lauxinz)</dd>
		</li>	
		<li>
		<dt>2011-04-20: SCWS-1.1.6 Released.</dt>
		<dd>1) 修正夹杂在汉字中间的1-2个英文字符的词性为 en 而不是原来的 un 导致清除符号时消失.</dd>
		<dd>2) 调整将数字后面的独立 % 纳入整词作为百分比，如 33.3% 作为整词而不再是 33.3 和 %</dd>
		<dd>3) 修改连字符(-)和下划线(_)的规则，当出现在字母单词之间时视为同一词而不再强行切开，此时如果激活复合分词的 DUALITY 选项，则仍能将符号切开作为复合词。</dd>
		<dd>4) 修正浮点数的识别规则，避免将IPv4地址识别为2个小数的尴尬，比如 192.168.1.1 以前会被切成 192.168 和1.1 2个数字，现在不会了。</dd>
		<dd>5) libscws 安装后将所有的头文件(*.h)按装到 $prefix/include/scws 而不是以前的 $prefix/include，故采用C  API开发时头部建议写 #include &lt;scws/scws.h&gt;
			</li>	    
		<li>
		<dt>2010-12-31: SCWS-1.1.5 Released.</dt>
		<dd>1) 修正 xdb.c 中存在的一处缓冲区溢出, 感谢论坛网友 hovea.</dd>
		<dd>2) 修正 phpext/ 中 scws_get_result() 参数解析里多了一个z 的问题，感谢网友（阿男）告知 </dd>
		<dd>3) 修正 scws.c 中某些字符在ignore symbol设置下无效的问题</dd>
		<dd class="notice">4) 修正 1.1.4 的 xdb.c 270行处由于书写错误导致的严重错误, 1.1.4版作废应及时升为 1.1.5</dd>
		</li>
		<li>2010-12-02: 新增基于HTTP/post的<a href="api.php">SCWS在线分词API</a>，供一些云平台的应用程序简易轻型调用。</li>	    
		<li>
		<dt>2010-09-15: SCWS-1.1.3 Released.</dt>
		<dd>1) 将 cli/ 下的工具程序命名下划线改成连接线(减号), gen_scws_dict 改为 gen-scws-dict</dd>
		<dd>2) 消除 php5.3 的警告信息, 重写 phpext/ 中的部分zend API, 统一采用 zend_parse_parameters() </dd>
		</li>	    
		<li>
		<dt>2010-05-09: SCWS-1.1.2 Released.</dt>
		<dd>1) 这是一个bug fixed的发布, 修正非内存模式的词典返回的 malloced 标识与 zflag_symbol 冲突导致姓名识别失败.</dd>
		<dd>2) 附带修正 <a href="download.php#xtools">phptool_for_scws_xdb.zip</a> 导出词典时最后出现负偏移的 bug </dd>
		<dd>3) 新增支持 php-5.3.x 的 php_scws.dll，编译环境为 VC6, x86, ThreadSafe </dd>
		<dd>4) 关于 1.1.x 的新功能的详细用法及介绍请<a href="http://bbs.xunsearch.com/showthread.php?tid=1303" target="_blank">点此进入BLOG查看</a>；<a href="demo/a.php" target="_blank">文本自动分类</a>、<a href="demo/get_tfidf.php" target="_blank">新词TF/IDF计算器</a>。</dd>
		</li>
		<li class="notice">2010-03-04: SCWS-1.1.1 Released, 修正在 xdict 中针对 SCWS_WORD_MALLOCED 定义过大(应为0x80)导致内存泄露.</li>
		<li>2010-03-19: 简体中文 xdb 词典更新, 修正部分生冷汉字被误当符号清除的 Bug(感谢 iSS的反馈), 点击这里<a href="download.php#dict">重新下载XDB词典</a>.</li>
		<li>
		<dt>2010-01-28: SCWS-1.1.0 Released.</dt>
		<dd>1) 新增功能: 支持载入纯文本词典(TXT), 一次分词可使用多个词典, 以实现不改变核心词库的原则下快速增减词。</dd>
		<dd>2) 新增功能：判断文本中是否包含指定词性的词汇及获取指定词性的词汇列表（词性参数和scws_get_tops相同）</dd>
		<dd>3) 该版本同步编译支持 Win32 的 php_scws.dll，支持 5.2.x 及 4.4.x 系列的 PHP</dd>
		<dd>4) scws_gen_dict 所有的文本词典格式更为宽松与add_dict兼容，允许多个空格或制表符分割，可省略除词外的选项</dd>
		</li>
		<li>2009-7-31 SCWS 发布 1.0.4, 修正紧贴在中文后结尾的1~2个英文字母返回长度多1的bug。</li>
		<li>2009-7-16 SCWS 中的 php 扩展实现略作修改以正确支持 PHP5.3+, 版本号没有改变, 但即日起的下载包已作更新。</li>	
		<li>2009-7-1 发布一套用纯 php 开发的 xdb 词典导入与导出工具，有需要的请下载参考使用(<a href="download.php#xtools">phptool_for_scws_xdb.zip</a>)。</li>	
		<li>2009-5-26 SCWS 发布更新 1.0.3 版，整合yanbin提供的win32编译工程文件及少数地方的兼容，但需要用户自己编译，因为我也没有编译环境，只是将代码调整到兼容win32环境。</li>	
		<li>2009-5-15 SCWS 发布更新 1.0.2 版，加入词性规则消岐，很好的处理了大部分短词岐义分词。</li>	
		<li>2008-12-21 SCWS 划入 FTPHP 项目，作为子项目重建本网站。</li>	
		<li>2006 - 2007 陆续开发纯 PHP 实现的 PSCWS 第二版与第三版，2007-06-09 发布 scws-0.0.1 pre 版，功能基本完整，2008-03-08 发布 scws-1.0.0 正式版。</li>		
	</ul>
</div>
<div class="block">
	<h2>版本列表</h2>
	<table border="1" width="100%">
		<tr>
			<th>版本</th>
			<th>类型</th>
			<th>平台</th>
			<th>性能</th>
			<th>其它</th>
		</tr>
		<tr>
			<td>SCWS-1.1.x</td>
			<td align="center">C 代码</td>
			<td align="center">*Unix*/*PHP*</td>
			<td>准确: 95%, 召回: 91%, 速度: 1.2MB/sec <br>PHP扩展分词速度: 250KB/sec</td>
			<td align="center"><a href="download.php#scws">[下载]</a> <a href="docs.php#phpscws">[文档]</a> <a href="docs.php#instscws">[安装说明]</a></td>
		</tr>
		<tr>
			<td>php_scws.dll(1)</td>
			<td align="center">PHP扩展库</td>
			<td align="center">Windows/PHP 4.4.x</td>
			<td>准确: 95%, 召回: 91%, 速度: 40KB/sec</td>
			<td align="center"><a href="download.php#dll">[下载]</a> <a href="docs.php#phpscws">[文档]</a> <a href="docs.php#instdll">[安装说明]</a></td>
		</tr>
		<tr>
			<td>php_scws.dll(2)</td>
			<td align="center">PHP扩展库</td>
			<td align="center">Windows/PHP 5.2.x</td>
			<td>准确: 95%, 召回: 91%, 速度: 40KB/sec</td>
			<td align="center"><a href="download.php#dll">[下载]</a> <a href="docs.php#phpscws">[文档]</a> <a href="docs.php#instdll">[安装说明]</a></td>
		</tr>
		<tr>
			<td>php_scws.dll(3)</td>
			<td align="center">PHP扩展库</td>
			<td align="center">Windows/PHP 5.3.x</td>
			<td>准确: 95%, 召回: 91%, 速度: 40KB/sec</td>
			<td align="center"><a href="download.php#dll">[下载]</a> <a href="docs.php#phpscws">[文档]</a> <a href="docs.php#instdll">[安装说明]</a></td>
		</tr>
		<tr>
			<td>php_scws.dll(4)</td>
			<td align="center">PHP扩展库</td>
			<td align="center">Windows/PHP 5.4.x</td>
			<td>准确: 95%, 召回: 91%, 速度: 40KB/sec</td>
			<td align="center"><a href="download.php#dll">[下载]</a> <a href="docs.php#phpscws">[文档]</a> <a href="docs.php#instdll">[安装说明]</a></td>
		</tr>		
		<tr>
			<td>PSCWS23</td>
			<td align="center">PHP源代码</td>
			<td align="center"><i>不限</i> (不支持UTF-8)</td>
			<td>准确: 93%, 召回: 89%, 速度: 960KB/min</td>
			<td align="center"><a href="download.php#pscws23">[下载]</a> <a href="docs.php#pscws23">[文档]</a></td>
		</tr>
		<tr>
			<td>PSCWS4</td>
			<td align="center">PHP源代码</td>
			<td align="center"><i>不限</i></td>
			<td>准确: 95%, 召回: 91%, 速度: 160KB/min</td>
			<td align="center"><a href="download.php#pscws4">[下载]</a> <a href="docs.php#pscws4">[文档]</a></td>
		</tr>		
	</table>
</div>
<div class="block">
	<h2>友情链接</h2>
	<p>
		<a href="http://www.czxiu.com/" target="_blank">QQ表情</a>
		<a href="http://id.czxiu.com/" target="_blank">搞笑证件</a>
		<a href="http://www.xunsearch.com" target="_blank">Xunsearch</a>
	</p>
</div>
<?php include 'footer.inc.php'; ?>
