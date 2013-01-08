README of SCWS
===============
$Id$

SCWS 简介
---------

[SCWS][1] 是 Simple Chinese Word Segmentation 的首字母缩写（即：简易中文分词系统）。
这是一套基于词频词典的机械式中文分词引擎，它能将一整段的中文文本基本正确地切分成词。词是
中文的最小语素单位，但在书写时并不像英语会在词之间用空格分开，所以如何准确并快速分词一直
是中文分词的攻关难点。

SCWS 采用纯 C 语言开发，不依赖任何外部库函数，可直接使用动态链接库嵌入应用程序，支持的
中文编码包括 `GBK`、`UTF-8` 等。此外还提供了 [PHP][2] 扩展模块，可在 PHP 中快速
而方便地使用分词功能。

分词算法上并无太多创新成分，采用的是自己采集的词频词典，并辅以一定的专有名称，人名，地名，
数字年代等规则识别来达到基本分词，经小范围测试准确率在 90% ~ 95% 之间，基本上能满足一些
小型搜索引擎、关键字提取等场合运用。首次雏形版本发布于 2005 年底。

SCWS 由 [hightman][8] 开发，并以 BSD 许可协议开源发布 ，参见 [COPYING][7]。


PHP 分词演示
------------

* _GBK_ <http://www.xunsearch.com/scws/demo/v4.php>
* _UTF-8_ <http://www.xunsearch.com/scws/demo/v48.php>
* _繁体中文_ <http://www.xunsearch.com/scws/demo/v48.cht.php>


安装
-----

目前 scws 只提供源码包，和大多数 GNU 软件的编译安装步骤一样。下面我们以 `Linux` 系统为例，
要求装有 `gcc` 编译器。`Windows` 上则建议用 [cygwin][3] 或 [mingw][4] 环境编译。

1. 取得 scws 源码并解压到当前目录
   ```
   wget -q -O - http://www.xunsearch.com/scws/down/scws-1.2.1.tar.bz2 | tar xjf -
   ```

2. 进入源码目录配置和编译
   ```
   cd scws-1.2.1 ; ./configure ; make install
   ```
   > 注意：这是以默认方式安装到 `/usr/local` 目录。详细配置参数执行 `./configure --help` 查看。


PHP 扩展
---------

在 UNIX 类的系统上，请直接从源码方式编译安装 PHP 扩展；对于 windows 系统，请直接下载我们编译好的
`php_scws.dll` 即可。

假定您已经将 scws 安装到 `$prefix` 目录，还要求系统环境装有 autoconf automake 及 phpize 工具。

1. 进入源码目录的 phpext/ 并执行 `phpize` (位于 php 安装目录下的 bin 目录里)

2. 执行 `configure` 进行配置和基础检测，假定您的 php 安装在 `$php_prefix` 目录
   ```
   ./configure --with-scws=/usr/local --with-php-config=$php_prefix/bin/php-config
   ```

3. 先编译然后以 `root` 身份安装
   ```
   make
   sudo make install
   ```

4. 在 php.ini 中加入以下内容
   ```
   [scws]
   ; 注意请检查 php.ini 中的 extension_dir 的设定值是否正确, 否则请将 extension_dir 设为空，
   ; 再把 extension = scws.so 或 php_scws.dll 指定绝对路径。
   extension = scws.so
   scws.default.charset = gbk
   scws.default.fpath = $prefix/etc
   ```

5. 命令行下执行 `php -m` 就能看到 scws 了，phpinfo() 也会包含关于 scws 的信息。

6. windows 下的 php 扩展是采用 dll 库文件。我们已预编译若干个版本，采用 zip 压缩位于
   `phpext/win32/php_scws-1.2.1-win32.zip`，请直接解压后选择相应版本的 `php_scws.dll`。
   将它复制到 PHP 的扩展目录中（通常是 extensions 或 ext 目录），然后参考上面的方式修改 `php.ini`。

7. 关于 PHP 扩展的示例用法及开发参考，请参见 [phpext/README.md][6]。


配套工具用法
------------

1. **$prefix/bin/scws** 这是分词的命令行工具，执行 scws -h 可以看到详细帮助说明。
   ```
   Usage: scws [options] [[-i] input] [[-o] output]
   ```
   * _-i string|file_ 要切分的字符串或文件，如不指定则程序自动读取标准输入，每输入一行执行一次分词
   * _-o file_ 切分结果输出保存的文件路径，若不指定直接输出到屏幕
   * _-c charset_ 指定分词的字符集，默认是 gbk，可选 utf8
   * _-r file_ 指定规则集文件（规则集用于数词、数字、专有名字、人名的识别）
   * _-d file[:file2[:...]]_ 指定词典文件路径（XDB格式，请在 -c 之后使用）
     ```
     自 1.1.0 起，支持多词典同时载入，也支持纯文本词典（必须是.txt结尾），多词典路径之间用冒号(:)隔开，
     排在越后面的词典优先级越高。
     
     文本词典的数据格式参见 scws-gen-dict 所用的格式，但更宽松一些，允许用不定量的空格分开，只有<词>是必备项目，
     其它数据可有可无，当词性标注为“!”（叹号）时表示该词作废，即使在较低优先级的词库中存在该词也将作废。
     ```
   * _-M level_ 复合分词的级别：1~15，按位异或的 1|2|4|8 依次表示 短词|二元|主要字|全部字，缺省不复合分词。
   * _-I_ 输出结果忽略跳过所有的标点符号
   * _-A_ 显示词性
   * _-E_ 将 xdb 词典读入内存 xtree 结构 (如果切分的文件很大才需要)
   * _-N_ 不显示切分时间和提示
   * _-D_ debug 模式 (很少用，需要编译时打开 --enable-debug)
   * _-U_ 将闲散单字自动调用二分法结合
   * _-t num_ 取得前 num 个高频词
   * _-a [~]attr1[,attr2[,...]]_ 只显示某些词性的词，加~表示过滤该词性的词，多个词性之间用逗号分隔
   * _-v_ 查看版本

2. **$prefix/bin/scws-gen-dict** 词典转换工具
   ```
   Usage: scws-gen-dict [options] [-i] dict.txt [-o] dict.xdb
   ```
   * _-c charset_ 指定字符集，默认为 gbk，可选 utf8
   * _-i file_ 文本文件(txt)，默认为 dict.txt
   * _-o file_ 输出 xdb 文件的路径，默认为 dict.xdb
   * _-p num_ 指定 XDB 结构 HASH 质数（通常不需要）
   * _-U_ 反向解压，将输入的 xdb 文件转换为 txt 格式输出 （TODO）

   > 文本词典格式为每行一个词，各行由 4 个字段组成，字段之间用若干个空格或制表符(\t)分隔。
   > 含义（其中只有 <词> 是必须提供的），`#` 开头的行视为注释忽略不计：
   > ```
   > #<词>  <词频(TF)>  <词重(IDF)>  <词性(北大标注)>
   > 新词条 12.0        2.2          n
   > ```

libscws API
-------------

这是整合 scws 到其它应和程序的接口说明，详见 [API][5]。


rules.ini 规则集
-----------------

（暂缺）


关于 XDB 词典
--------------

我们的词典使用的是自行开发的专用 XDB 格式，免费提供的词典是通用的互联网信息词汇集，
收录了大约 28 万个词。

如果您需要定制词典以作特殊用途，请与我们联系，可能会视情况进行收费。


性能指标
---------

在 FreeBSD 6.2 系统，单核单 CPU 至强 3.0G 的服务器上，测试长度为 80,535 的文本。
用附带的命令行工具耗时将约 0.17 秒，若改用 php 扩展方式调用，则耗时约为 0.65 秒。

分词精度 95.60%，召回率 90.51% (F-1: 0.93)


其它
-------

该文档由 hightman 于 2007/06/08 首次编写，同时在不断修订中！

项目主页：<http://www.xunsearch.com/scws> 我的邮箱：hightman2@yahoo.com.cn


[1]: http://www.xunsearch.com/scws
[2]: http://www.php.net
[3]: http://www.cygwin.com
[4]: http://www.mingw.org
[5]: https://github.com/hightman/scws/blob/master/API.md
[6]: https://github.com/hightman/scws/blob/master/phpext/README.md
[7]: https://github.com/hightman/scws/blob/master/COPYING
[8]: http://www.hightman.cn

