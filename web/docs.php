<?php
// document page
// $Id$
//
$title = '文档';
include 'header.inc.php';
?>
<div class="block">
	<h2>文档目录<a name="top">&nbsp;</a></h2>
	<ul>
        <li><a href="#instscws">SCWS-1.x.x 安装说明</a></li>
        <li><a href="#libscws">Libscws C-API 文档</a></li>
        <li><a href="#utilscws">SCWS-1.x.x 命令行说明</a></li>
        <li><a href="#phpscws">SCWS 之 PHP 扩展文档</a></li>
        <li><a href="#instdll">php_scws.dll/Win32 安装说明</a></li>
        <li><a href="#pscws23">PSCWS23 文档</a></li>
        <li><a href="#pscws4">PSCWS4 文档</a></li>
		<li><a href="#attr">词典词性标注详解</a></li>
	</ul>	
</div>
<div class="block">
	<h2>SCWS-<?php echo $version; ?> 安装说明<a name="instscws">&nbsp;</a></h2>
	<pre>
以 Linux(FreeBSD) 操作系统为例

1. 取得 scws-<?php echo $version; ?> 的代码
wget http://www.xunsearch.com/scws/down/scws-<?php echo $version; ?>.tar.bz2

2. 解开压缩包
[hightman@d1 ~]$ tar xvjf scws-<?php echo $version; ?>.tar.bz2

3. 进入目录执行配置脚本和编译
[hightman@d1 ~]$ cd scws-<?php echo $version; ?>
[hightman@d1 ~/scws-<?php echo $version; ?>]$ ./configure --prefix=/usr/local/scws ; make ; make install

注：这里和通用的 GNU 软件安装方式一样，具体选项参数执行 ./configure --help 查看。
常用选项为：--prefix=&lt;scws的安装目录&gt;

4. 顺利的话已经编译并安装成功到 /usr/local/scws 中了，执行下面命令看看文件是否存在
[hightman@d1 ~/scws-<?php echo $version; ?>]$ ls -al /usr/local/scws/lib/libscws.la

5. 试试执行 scws-cli 文件
[hightman@d1 ~/scws-<?php echo $version; ?>]$ /usr/local/scws/bin/scws -h
scws (scws-cli/<?php echo $version; ?>)
Simple Chinese Word Segmentation - Command line usage.
Copyright (C)2007 by hightman.
...

6 用 wget 下载并解压词典，或从主页下载然后自行解压再将 *.xdb 放入 /usr/local/scws/etc 目录中
[hightman@d1 ~/scws-<?php echo $version; ?>]$ cd /usr/local/scws/etc
[hightman@d1 /usr/local/scws/etc]$ wget http://www.xunsearch.com/scws/down/scws-dict-chs-gbk.tar.bz2
[hightman@d1 /usr/local/scws/etc]$ wget http://www.xunsearch.com/scws/down/scws-dict-chs-utf8.tar.bz2
[hightman@d1 /usr/local/scws/etc]$ tar xvjf scws-dict-chs-gbk.tar.bz2
[hightman@d1 /usr/local/scws/etc]$ tar xvjf scws-dict-chs-utf8.tar.bz2

7. 写个小程序测试一下
[hightman@d1 ~]$ cat &gt; test.c
#include &lt;scws.h&gt;
#include &lt;stdio.h&gt;
main()
{
  scws_t s;
  s = scws_new();
  scws_free(s);
  printf("test ok!\n");
}

8. 编译测试程序
gcc -o test -I/usr/local/scws/include -L/usr/local/scws/lib test.c -lscws -Wl,--rpath -Wl,/usr/local/scws/lib
./test

9. 这样就好顺利安装完毕可以使用 libscws 这套 C-API 了

10. 如果您需要在 php 中调用分词，建议继续阅读本文安装 php 扩展，否则可跳过不看。

    假设您已经将 scws 按上述步骤安装到 /usr/local/scws 中。
    安装此扩展要求您的 php 和系统环境安装了相应的 autoconf automake 工具及 phpize 。

    1) 进入源码目录的 phpext/ 目录 ( cd ~/scws-<?php echo $version; ?> )
    2) 执行 phpize （在PHP安装目录的bin/目录下）
    3) 执行 ./configure --with-scws=/usr/local/scws 
       若 php 安装在特殊目录 $php_prefix, 则请在 configure 后加上 --with-php-config=$php_prefix/bin/php-config
    4) 执行 make 然后用 root 身份执行 make install     
    5) 在 php.ini 中加入以下几行

[scws]
;
; 注意请检查 php.ini 中的 extension_dir 的设定值是否正确, 否则请将 extension_dir 设为空，
; 再把 extension = scws.so 指定绝对路径。
;
extension = scws.so
scws.default.charset = gbk
scws.default.fpath = /usr/local/scws/etc

    6) 命令行下执行 php -m 就能看到 scws 了或者在 phpinfo() 中看看关于 scws 的部分，记得要重启 web 服务器
       才能使新的 php.ini 生效。
    7) 这样就算安装完成了，余下的工作只是PHP代码编写问题了。
       关于 PHP 扩展的使用说明请参看代码中 phpext/README.md 文件或其它文档章节。
				
	</pre>
	<p><a href="#top">[返回目录]</a></p>
</div>
<div class="block">
	<h2>Libscws - C API文档<a name="libscws">&nbsp;</a></h2>
	<pre>
概述
-----

libscws 是 SCWS 中使用 C 语言编写的函数库，没有任何外部库依赖，代码力争简洁高效，
针对分词词典上做了一些优化。除分词外，也可以用于自行设计的 XDB 文件和 XTree 存取。
所有的操作必须先包含以下头文件：

```c
#include <scws/scws.h>
```


数据类型
---------

1. **scws_t** scws 操作句柄（指针），大多数 API 的第一参数类型，通过 `scws_new()` 返回，
   不要尝试拷贝 `struct scws_st` 数据，拷贝结果不保证可以正确工作。

   ```c
   typedef struct scws_st {
     struct scws_st *p;
     xdict_t d; // 词典指针，可检测是否为 NULL 来判断是否加载成功
     rule_t r; // 规则集指针，可检测是否为 NULL 来判断是否加载成功
     unsigned char *mblen;
     unsigned int mode;
     unsigned char *txt;
     int len;
     int off;
     int wend;
     scws_res_t res0; // scws_res_t 解释见后面
     scws_res_t res1;
     word_t **wmap;
     struct scws_zchar *zmap;
   } scws_st, *scws_t;
   ```

2. **scws_res_t** scws 分词结果集，单链表结构，通过 `scws_get_result()` 返回，
   每次分词返回的结果集次数是不定的，须循环调用直到返回 `NULL`。

   ```c
   typedef struct scws_result *scws_res_t;
   struct scws_result {
     int off;  // 该词在原文本中的偏移
     float idf; // 该词的 idf 值
     unsigned char len; // 该词的长度
     char attr[3]; // 词性
     scws_res_t next; // 下一个词
   };
   ```

3. **scws_top_t** 高频关键词统计集，简称“词表集”，这是 scws 中统计调用时返回的结构，也是一个单链表结构。

   ```c
   typedef struct scws_topword *scws_top_t;
   struct scws_topword
   {
     char *word; // 词的字符串指针
     float weight; // 统计权重
     short times; // 出现次数
     char attr[2]; // 词性，注意只有2字节，不保证 ’\0‘ 结尾
     scws_top_t next;　// 下一个
   };
   ```

函数详解
---------

1. `scws_t scws_new()` 分配或初始化与 scws 系列操作的 `scws_st` 对象。该函数将自动分配、初始化、并返回新对象的指针。
   只能通过调用 `scws_free()` 来释放该对象。

   > **返回值** 初始化的 scws_st * （即 scws_t） 句柄。  
   > **错误** 在内存不足的情况下，返回NULL。

2. `scws_t scws_fork(scws_t p)` 在已有 scws 对象上产生一个分支，可以独立用于某个线程分词，但它继承并共享父对象词典、
   规则集资源。同样需要调用 `scws_free()` 来释放对象。在该分支对象上重设词典、规则集不会影响父对象及其它分支。
 
   > **参数 p** 现有的 scws 对象（也可以是分支）  
   > **返回值** 克隆出来的分支 scws_st * (scws_t) 句柄。  
   > **错误** 在内存不足的情况下，返回NULL。  
   > **注意** 主要用于多线程环境，以便共享内存词典、规则集。在 v1.2.0 及以前，分支对象设置词典规则集会影响到原对象及其它兄弟分支。

3. `void scws_free(scws_t s)` 释放 scws 操作句柄及对象内容，同时也会释放已经加载的词典和规则。

4. `void scws_set_charset(scws_t s, const char *cs)` 设定当前 scws 所使用的字符集。

   > **参数 cs** 新指定的字符集。若无此调用则系统缺省使用 gbk，还支持 utf8，指定字符集时参数的大小写不敏感。  
   > **错误** 若指定的字符集不存在，则会自动使用 gbk 字符集替代。

5. `int scws_add_dict(scws_t s, const char *fpath, int mode)` 添加词典文件到当前 scws 对象。

   > **参数 fpath** 词典的文件路径，词典格式是 XDB或TXT 格式。  
   > **参数 mode** 有3种值，分别为预定义的：
   >
   >   - SCWS_XDICT_TXT  表示要读取的词典文件是文本格式，可以和后2项结合用
   >   - SCWS_XDICT_XDB  表示直接读取 xdb 文件
   >   - SCWS_XDICT_MEM  表示将 xdb 文件全部加载到内存中，以 XTree 结构存放，可用异或结合另外2个使用。
   >
   >   具体用哪种方式需要根据自己的实际应用来决定。当使用本库做为守护进程时推荐使用 mem 方式，
   >   当只是嵌入调用时应该使用 xdb 方式，将 xdb 文件加载进内存不仅占用了比较多的内存，
   >   而且也需要一定的时间（35万条数据约需要0.3~0.5秒左右）。
   >
   > **返回值** 成功返回 0，失败返回 -1。  
   > **注意** 若此前 scws 句柄已经加载过词典，则新加入的词典具有更高的优先权。

6. `int scws_set_dict(scws_t s, const char *fpath, int mode)` 清除并设定当前 scws 操作所有的词典文件。

   > **参数 fpath** 词典的文件路径，词典格式是 XDB或TXT 格式。  
   > **参数 mode** 有3种值，参见 `scws_add_dict`。  
   > **返回值** 成功返回 0，失败返回 -1。  
   > **注意** 若此前 scws 句柄已经加载过词典，则此调用会先释放已经加载的全部词典。和 `scws_add_dict` 的区别在于会覆盖已有词典。

7. `void scws_set_rule(scws_t s, const char *fpath)` 设定规则集文件。

   > **参数 fpath** 规则集文件的路径。若此前 scws 句柄已经加载过规则集，则此调用会先释放已经加载的规则集。  
   > **错误** 加载失败，scws_t 结构中的 r 元素为 NULL，即通过 s->r == NULL 与否来判断加载的失败与成功。  
   > **注意** 规则集定义了一些新词自动识别规则，包括常见的人名、地区、数字年代等。规则编写方法另行参考其它部分。  

8. `void scws_set_ignore(scws_t s, int yes)` 设定分词结果是否忽略所有的标点等特殊符号（不会忽略\r和\n）。

   > **参数 yes** 1 表示忽略，0 表示不忽略，缺省情况为不忽略。

9. `void scws_set_multi(scws_t s, int mode)` 设定分词执行时是否执行针对长词复合切分。（例：“中国人”分为“中国”、“人”、“中国人”）。

   > **参数 mode** 复合分词法的级别，缺省不复合分词。取值由下面几个常量异或组合：
   >
   >   - SCWS_MULTI_SHORT   短词
   >   - SCWS_MULTI_DUALITY 二元（将相邻的2个单字组合成一个词）
   >   - SCWS_MULTI_ZMAIN   重要单字
   >   - SCWS_MULTI_ZALL    全部单字

10. `void scws_set_duality(scws_t s, int yes)` 设定是否将闲散文字自动以二字分词法聚合。

   > **参数 yes** 如果为 1 表示执行二分聚合，0 表示不处理，缺省为 0。  

11. `void scws_set_debug(scws_t s, int yes)` 设定分词时对于疑难多路径综合分词时，是否打印出各条路径的情况。

   > **注意** 打印使用的是 `fprintf(stderr, ...)` 故不要随便用，并且只有编译时加入 --enable-debug 选项才有效。

12. `void scws_send_text(scws_t s, const char *text, int len)` 设定要切分的文本数据。

   > **参数 text** 文本字符串指针。  
   > **参数 len** 文本的长度。  
   > **注意** 该函数可安全用于二进制数据，不会因为字符串中包括 \0 而停止切分。
   > 这个函数应在 `scws_get_result()` 和 `scws_get_tops()` 之前调用。
   >
   > scws 结构内部维护着该字符串的指针和相应的偏移及长度，连续调用后会覆盖之前的设定；故不应在多次的 scws_get_result 
   > 循环中再调用 scws_send_text() 以免出错。

13. `scws_res_t scws_get_result(scws_t s)` 取回一系列分词结果集。

   > **返回值** 结果集链表的头部指针，该函数必须循环调用，当返回值为 NULL 时才表示分词结束。  
   > **注意** 该分词结果必须调用 `scws_free_result()` 释放，参数为返回的链表头指针。

14. `void scws_free_result(scws_res_t result)` 根据结果集的链表头释放结果集。

15. `scws_top_t scws_get_tops(scws_t s, int limit, char *xattr)` 返回指定的关键词表统计集，系统会自动根据词语出现的次数及其 idf 值计算排名。

   > **参数 limit** 指定取回数据的最大条数，若传入值为0或负数，则自动重设为10。  
   > **参数 xattr** 用来描述要排除或参与的统计词汇词性，多个词性之间用逗号隔开。
   > 当以~开头时表示统计结果中不包含这些词性，否则表示必须包含，传入 NULL 表示统计全部词性。  
   > **返回值** 词表集链表的头指针，该词表集必须调用 `scws_free_tops()` 释放。

16. `void scws_free_tops(scws_top_t tops)` 根据词表集的链表头释放词表集。


17. `int scws_has_word(scws_t s, char *xattr)` 判断text中是包括指定的词性的词汇。

   > **参数 xattr** 用来描述要排除或参与的统计词汇词性，多个词性之间用逗号隔开。
   > 当以~开头时表示统计结果中不包含这些词性，否则表示必须包含，传入 NULL 表示统计全部词性。  
   > **返回值** 如果有返回 1 没有则返回 0。

18. `scws_top_t scws_get_words(scws_t s, char *xattr)` 返回指定词性的关键词表，系统会根据词语出现的先后插入列表。

   > **参数 xattr** 用来描述要排除或参与的统计词汇词性，多个词性之间用逗号隔开。
   > 当以~开头时表示统计结果中不包含这些词性，否则表示必须包含，传入 NULL 表示统计全部词性。  
   > **返回值** 返回词表集链表的头指针，该词表集必须调用 `scws_free_tops()` 释放。



实例代码
----------

下面是一个简单的分词实例代码，假设您的 scws 已安装至 `/usr/local` 目录，下面是源码：

```c
#include &lt;stdio.h>
#include &lt;scws/scws.h>
#define SCWS_PREFIX     "/usr/local/scws"

main()
{
  scws_t s;
  scws_res_t res, cur;
  char *text = "Hello, 我名字叫李那曲是一个中国人, 我有时买Q币来玩, 我还听说过C#语言";

  if (!(s = scws_new())) {
    printf("ERROR: cann't init the scws!\n");
    exit(-1);
  }
  scws_set_charset(s, "utf8");
  scws_set_dict(s, "/usr/local/scws/etc/dict.utf8.xdb", SCWS_XDICT_XDB);
  scws_set_rule(s, "/usr/local/scws/etc/rules.utf8.ini");

  scws_send_text(s, text, strlen(text));
  while (res = cur = scws_get_result(s))
  {
    while (cur != NULL)
    {
      printf("WORD: %.*s/%s (IDF = %4.2f)\n", cur->len, text+cur->off, cur->attr, cur->idf);
      cur = cur->next;
    }
    scws_free_result(res);
  }
  scws_free(s);
}
```

将以上代码复制保存为 test.c 然后执行下面指令编译并测试运行：

```
gcc -o test -I/usr/local/scws/include -L/usr/local/scws/lib test.c -lscws
./test
```
	</pre>
	<p><a href="#top">[返回目录]</a></p>
</div>
<div class="block">
	<h2>SCWS-1.x.x 命令行工具<a name="utilscws">&nbsp;</a></h2>
	<pre>
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
	</pre>
	<p><a href="#top">[返回目录]</a></p>
</div>
<div class="block">
	<h2>SCWS - PHP 扩展之文档<a name="phpscws">&nbsp;</a></h2>
	<pre>
简介
-----

[SCWS][1] 是一个简易的中文分词引擎，它可以将输入的文本字符串根据设定好的选项切割后以数组形式返回每一个词汇。
它为中文而编写，支持 gbk 和 utf8 字符集，适当的修改词典后也可以支持非中文的多字节语言切词（如日文、韩文等）。
除分词外，还提供一个简单的关键词汇统计功能，它内置了一个简单的算法来排序。

更多相关情况请访问 scws 主页：<http://www.xunsearch.com/scws>


需求
-----

本扩展需要 scws-1.x.x 的支持。


安装
-----

这是一个 php 扩展，除 windows 上的 php_scws.dll 外只提供源代码，需要自行下载并编译，具体参见[这里][2]。


运行时配置
----------

`scws.default.charset`  default = gbk, Changeable = PHP_INI_ALL  
`scws.default.fpath` default = NULL, Changeable = PHP_INI_ALL


> 有关 PHP_INI_* 常量进一步的细节与定义参见 PHP 手册。


资源类型
----------

本扩展定义了一种资源类型：scws 指针，指向正在被操作的 scws 对象。


预定义常量
-----------

* `SCWS_XDICT_XDB`  词典文件为 XDB
* `SCWS_XDICT_MEM`  将词典全部加载到内存里
* `SCWS_XDICT_TXT`  词典文件为 TXT（纯文本）

* `SCWS_MULTI_NONE`     不进行复合分词
* `SCWS_MULTI_SHORT`	短词复合  
* `SCWS_MULTI_DUALITY`   散字二元复合
* `SCWS_MULTI_ZMAIN`	重要单字
* `SCWS_MULTI_ZALL`     全部单字


预定义类
---------

这是一个类似 `Directory` 的内置式伪类操作，类方法建立请使用 `scws_new()` 函数，而不能直接用 `new SimpleCWS`。
否则不会包含有 handle 指针，将无法正确操作。它包含的方法有：

```php
class SimpleCWS  {
  resource handle;
  bool close(void);
  bool set_charset(string charset)
  bool add_dict(string dict_path[, int mode = SCWS_XDICT_XDB])
  bool set_dict(string dict_path[, int mode = SCWS_XDICT_XDB])
  bool set_rule(string rule_path)
  bool set_ignore(bool yes)
  bool set_multi(int mode)
  bool set_duality(bool yes)
  bool send_text(string text)
  mixed get_result(void)
  mixed get_tops([int limit [, string xattr]])
  bool has_word(string xattr)
  mixed get_words(string xattr)
  string version(void)
};
```

> **注意** 类方法的用与支 scws_xxx_xxx 系列函数用法一致，只不过免去第一参数，
> 故不另外编写说明，请参见函数列表即可。

**例子1** 使用类方法分词

```php
&lt;?php
$so = scws_new();
$so->set_charset('gbk');
// 这里没有调用 set_dict 和 set_rule 系统会自动试调用 ini 中指定路径下的词典和规则文件
$so->send_text("我是一个中国人,我会C++语言,我也有很多T恤衣服");
while ($tmp = $so->get_result())
{
  print_r($tmp);
}
$so->close();
?&gt;
```

**例子2** 使用函数提取高频词

```php
&lt;?php
$sh = scws_open();
scws_set_charset($sh, 'gbk');
scws_set_dict($sh, '/path/to/dict.xdb');
scws_set_rule($sh, '/path/to/rules.ini');
$text = "我是一个中国人，我会C++语言，我也有很多T恤衣服";
scws_send_text($sh, $text);
$top = scws_get_tops($sh, 5);
print_r($top);
?&gt;
```

> **注意** 为方便使用，当 `SimpleCWS::send_text` 方法或 `scws_send_text()` 函数被调用前并且没有
> 加载任何词典和规则集时，系统会自动在 `scws.default.fpath` (ini配置)目录中查找相应的字符集词典。
> 词典和规则文件的命名方式为 dict[.字符集].xdb 和 rules[.字符集].ini ，当字符集是 gbk 时中括号里面的
> 部分则不需要，直接使用 dict.xdb 和 rules.ini 而不是 dict.gbk.xdb 。
> 
> 此外，输入的文字，词典、规则文件这三者的字符集必须统一，如果不是默认的 gbk 字符集请调用 
> `SimpleCWS::set_charset` 或 `scws_set_charset` 来设定，否则可能出现意外错误。


函数详解
--------

1. `mixed scws_new(void)` 创建并返回一个 `SimpleCWS` 类操作对象。

   > **返回值** 成功返回类操作句柄，失败返回 false。

2. `mixed scws_open(void)` 创建并返回一个分词操作句柄。

   > **返回值** 成功返回 scws 操作句柄，失败返回 false。

3. `bool scws_close(resource scws_handle)`  
   `SimpleCWS::close(void)` 关闭一个已打开的 scws 分词操作句柄。

   > **参数 scws_handle** 即之前由 scws_open 打开的返回值。  
   > **返回值** 始终为 true  
   > **注意** 后面的 API 中省去介绍 scws_handle 参数，含义和本函数相同。

4. `bool scws_set_charset(resource scws_handle, string charset)`  
   `bool SimpleCWS::set_charset(string charset)` 设定分词词典、规则集、欲分文本字符串的字符集。

   > **参数 charset** 要新设定的字符集，目前只支持 utf8 和 gbk。（注：默认为 gbk，utf8不要写成utf-8）  
   > **返回值** 始终为 true

5. `bool scws_add_dict(resource scws_handle, string dict_path [, int mode])`
   `bool SimpleCWS::add_dict(string dict_path [, int mode])` 添加分词所用的词典，新加入的优先查找。

   > **参数 dict_path** 词典的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）  
   > **参数 mode** 可选，表示加载的方式。其值有：
   >
   >   - SCWS_XDICT_TXT  表示要读取的词典文件是文本格式，可以和后2项结合用
   >   - SCWS_XDICT_XDB  表示直接读取 xdb 文件（此为默认值）
   >   - SCWS_XDICT_MEM  表示将 xdb 文件全部加载到内存中，以 XTree 结构存放，可用异或结合另外2个使用。
   >
   > **返回值** 成功返回 true 失败返回 false

6. `bool scws_set_dict(resource scws_handle, string dict_path [, int mode])`  
   `bool SimpleCWS::set_dict(string dict_path [, int mode])` 设定分词所用的词典并清除已存在的词典列表。

   > **参数 dict_path** 词典的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）  
   > **参数 mode** 可选，表示加载的方式。参见 `scws_add_dict`  
   > **返回值** 成功返回 true 失败返回 false

7. `bool scws_set_rule(resource scws_handle, string rule_path)`  
   `bool SimpleCWS::set_rule(string rule_path)` 设定分词所用的新词识别规则集（用于人名、地名、数字时间年代等识别）。

   > **参数 rule_path** 规则集的路径，可以是相对路径或完全路径。（遵循安全模式下的 open_basedir）  
   > **参数 mode** 可选，表示加载的方式。参见 `scws_add_dict`  
   > **返回值** 成功返回 true 失败返回 false

8. `bool scws_set_ignore(resource scws_handle, bool yes)`  
   `bool SimpleCWS::set_ignore(bool yes)` 设定分词返回结果时是否去除一些特殊的标点符号之类。

   > **参数 yes** 设定值，如果为 true 则结果中不返回标点符号，如果为 false 则会返回，缺省为 false。  
   > **返回值** 始终为 true

9. `bool scws_set_multi(resource scws_handle, int mode)`  
   `bool SimpleCWS::set_multi(bool yes)` 设定分词返回结果时是否复式分割，如“中国人”返回“中国＋人＋中国人”三个词。

   > **参数 mode** 复合分词法的级别，缺省不复合分词。取值由下面几个常量异或组合（也可用 1-15 来表示）：
   >
   >   - SCWS_MULTI_SHORT   (1)短词
   >   - SCWS_MULTI_DUALITY (2)二元（将相邻的2个单字组合成一个词）
   >   - SCWS_MULTI_ZMAIN   (4)重要单字
   >   - SCWS_MULTI_ZALL    (8)全部单字
   >
   > **返回值** 始终为 true

10. `bool scws_set_duality(resource scws_handle, bool yes)`  
    `bool SimpleCWS::set_duality(bool yes)` 设定是否将闲散文字自动以二字分词法聚合

   > **参数 yes** 设定值，如果为 true 则结果中多个单字会自动按二分法聚分，如果为 false 则不处理，缺省为 false。  
   > **返回值** 始终为 true

11. `bool scws_send_text(resource scws_handle, string text)`  
    `bool SimpleCWS::send_text(string text)` 发送设定分词所要切割的文本。

   > **参数 text** 要切分的文本的内容。  
   > **返回值** 成功返回 true 失败返回 false  
   > **注意** 系统底层处理方式为对该文本增加一个引用，故不论多长的文本并不会造成内存浪费；
   > 执行本函数时，若未加载任何词典和规则集，则会自动试图在 ini 指定的缺省目录下查找缺省字符集的词典和规则集。

12. `mixed scws_get_result(resource scws_handle)`  
    `mixed SimpleCWS::get_result()` 根据 send_text 设定的文本内容，返回一系列切好的词汇。

   > **返回值** 成功返回切好的词汇组成的数组，若无更多词汇，返回 false。返回的词汇包含的键值如下：
   >
   >   - word _string_ 词本身
   >   - idf _float_ 逆文本词频
   >   - off _int_ 该词在原文本路的位置
   >   - attr _string_ 词性
   >
   > **注意** 每次切词后本函数应该循环调用，直到返回 false 为止，因为程序每次返回的词数是不确定的。

13. `mixed scws_get_tops(resource scws_handle [, int limit [, string attr]])`  
    `mixed SimpleCWS::get_tops([int limit [, string attr]])` 根据 send_text 设定的文本内容，返回系统计算出来的最关键词汇列表。

   > **参数 limit** 可选参数，返回的词的最大数量，缺省是 10  
   > **参数 attr** 可选参数，是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
   > 这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，缺省为NULL，返回全部词性，不过滤。  
   > **返回值** 成功返回统计好的的词汇组成的数组，返回 false。返回的词汇包含的键值如下：
   >
   >   - word _string_ 词本身
   >   - times _int_ 词在文本中出现的次数
   >   - weight _float_ 该词计算后的权重
   >   - attr _string_ 词性

14. `mixed scws_get_words(resource scws_handle, string attr)`  
    `mixed SimpleCWS::get_words(string attr)` 根据 send_text 设定的文本内容，返回系统中词性符合要求的关键词汇。

   > **参数 attr** 是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
   > 这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，若为空则返回全部词。  
   > **返回值** 成功返回符合要求词汇组成的数组，返回 false。返回的词汇包含的键值参见 `scws_get_result`

15. `bool scws_has_words(resource scws_handle, string attr)`  
    `mixed SimpleCWS::has_words(string attr)` 根据 send_text 设定的文本内容，返回系统中是否包括符合词性要求的关键词。

   > **参数 attr** 是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
   > 这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，若为空则返回全部词。  
   > **返回值** 如果有则返回 true，没有就返回 false。

16. `string scws_version(void)`  
    `string SimpleCWS::version(void)` 返回 scws 版本号名称信息（字符串）。


其它
------

本说明由 hightman 首次编写于 2007/06/07，最近于 2013/01/07 更新。


[1]: http://www.xunsearch.com/scws/
[2]: https://github.com/hightman/scws/blob/master/README.md
	</pre>
	<p><a href="#top">[返回目录]</a></p>
</div>
<div class="block">
	<h2>php_scws.dll/Win32 安装说明<a name="instdll">&nbsp;</a></h2>
	<pre>
1. 根据您当前用的 PHP 版本，下载相应已编译好的 php_scws.dll 扩展库。
   目前支持 PHP-4.4.x 和 PHP-5.2.x 系列，下载地址分别为：

   php-4.4.x: http://www.xunsearch.com/scws/down/php-4.4.x/php_scws.dll
   php-5.2.x: http://www.xunsearch.com/scws/down/php-5.2.x/php_scws.dll
   php-5.3.x: http://www.xunsearch.com/scws/down/php-5.3.x/php_scws.dll

2. 将下载后的  php_scws.dll 放到 php 安装目录的
   extensions/ 目录中去（通常为：X:/php/extensions/或 X:/php/ext/）。

3. 建立一个本地目录放规则集文件和词典文件，建议使用：C:/program files/scws/etc

4. 从 scws 主页上下载词典文件，解压后将 *.xdb 放到上述目录中
   词典系列：http://www.xunsearch.com/scws/down/scws-dict-chs-gbk.tar.bz2
           http://www.xunsearch.com/scws/down/scws-dict-chs-utf8.tar.bz2
           http://www.xunsearch.com/scws/down/scws-dict-cht-utf8.tar.bz2

5. 从 scws 主页上下载规则集文件，解压后将 *.ini 放到第 3 步建立的目录
   规则集文件压缩包：http://www.xunsearch.com/scws/down/rules.tgz
   解压后有三个文件分别为 rules.ini  rules.utf8.ini rules_cht.utf8.ini
   将三件文件拷到第 3 步所述的目录中

6. 修改 php.ini 通常位于 C:/windows/php.ini 或 C:/winnt/php.ini 之类的目录，
   在 php.ini 的末尾加入以下几行：

[scws]
;
; 注意请检查 php.ini 中的 extension_dir 的设定值是否正确, 否则请将 extension_dir 设为空，
; 再把 php_scws.dll 指定为绝对路径。
;
extension = php_scws.dll
scws.default.charset = gbk
scws.default.fpath = "c:/program files/scws/etc"

5. 重开 web 服务器即可完成。
	</pre>
	<p><a href="#top">[返回目录]</a></p>
</div>
<div class="block">
	<h2>PSCWS23 使用文档<a name="pscws23">&nbsp;</a></h2>
	<pre>
PSCWS2 和 PSCWS3 这两个类对应的文件分别为 pscws2.class.php 和 pscws3.class.php ，分别为
第二版及第三版。在 PHP 代码中的调用方法如下：

// 加入头文件, 若用第3版则文件名应为 pscws3.class.php
require '/path/to/pscws2.class.php';

// 建立分词类对像, 参数为词典路径
$pscws = new PSCWS2('/path/to/dict/dict.xdb');

//
// 接下来, 设定一些分词参数或选项
// 包括: set_dict, set_ignore_mark, set_autodis, set_debug ... 等方法
// 

// 调用 segment 方法执行词汇切割, segment 的第二参数为回调函数, 这将使系统自动将切好的词
// 组成的数组作为参数传递给该回调函数去执行，若为空则将词组成的数组返回。

$res = $pscws->segment($string);
print_r($res);

或 （特别地，回调函数视情况会多次调用）

function seg_cb($res) { print_r($res); }
$pscws->segment($string, 'seg_cb');

--- 类方法完全手册 ---
(注: 构造函数可传入词典路径作为参数, 这与另外调用 set_dict 效果是一样的)

class PSCWS2 { | class PSCWS3 {
  
  void set_dict(string dict_fpath);
  说明：设置分词引擎所采用的词典文件。
  参数：dict_fpath 为词典路径，内部会根据词典路径的后缀名采用相应的处理方式。
  返回值：无。
  错误：若有错误会给出 WARNING 级的错误提示。

  void set_ignore_mark(bool set);
  说明：设置分词结果是否忽略标点符号。
  参数：set 必须为布尔型的 true 或 false，分别表示要忽略和不忽略。
  返回值：无。

  void set_autodis(bool set);
  说明：设置分词算法是否启用自动识别人名。
  参数：set 必须为布尔型的 true 或 false，分别表示要识别和不识别。
  返回值：无。

  void set_debug(bool set);
  说明：设置分词过程是否输出分词过程的调试信息。
  参数：set 必须为布尔型的 true 或 false，分别表示要输出和不输出。
  返回值：无。

  void set_statistics(bool set);
  说明：设置分词过程是否记录各词汇出现的次数及位置。
  参数：set 必须为布尔型的 true 或 false，分别表示要记录和不记录。
  返回值：无。
  其它：在 segment() 方法执行结束后调用 get_statistics() 方法获取统计信息。

  Array &get_statistics(void);
  说明：返回上次 segment() 调用的分词结果的各词汇出现的次数及位置信息(引用返回)。
  参数：无。
  返回值：以词汇为键名，其值由次数(times)和(poses)位置列表数组组成。
  其它：该方法应该在 segment() 方法后调用，每次 segment() 调用前统计信息自动清零。

  mixed &segment(string text [, string cb]);
  说明：对字符串 text 执行分词。
  参数：text 为要执行分词的字符串；
        cb 是处理分词结果的回调函数名称，它接受由切好的词语组成的数组这一参数。
  返回值：当 cb 参数没有传入时，返回切好的词语组成的数组成(可以以引用方式返回)，
          若采用回调函数处理分词结果，则直接返回 true。
  其它：cb 函数在一次 segment() 过程中可能是多次调用的。
        若没有传入 cb 参数，segment() 将会在 text 分词结果后再将结果一次返回，
    当 text 很长时速度较慢，建议将 text 按明显的换行标记切分后再依次调用
    segment() 方法进行切词以提高效率！
};
	</pre>
	<p><a href="#top">[返回目录]</a></p>
</div>
<div class="block">
	<h2>PSCWS4 使用文档<a name="pscws4">&nbsp;</a></h2>
	<pre>
PSCWS4 类对应的文件为 pscws4.class.php。在 PHP 代码中的调用方法如下：

// 加入头文件
require '/path/to/pscws4.class.php';

// 建立分词类对像, 参数为字符集, 默认为 gbk, 可在后面调用 set_charset 改变
$pscws = new PSCWS4('utf8');

//
// 接下来, 设定一些分词参数或选项, set_dict 是必须的, 若想智能识别人名等需要 set_rule 
//
// 包括: set_charset, set_dict, set_rule, set_ignore, set_multi, set_debug, set_duality ... 等方法
// 
$pscws->set_dict('/path/to/etc/dict.xdb');
$pscws->set_rule('/path/to/etc/rules.ini');

// 分词调用 send_text() 将待分词的字符串传入, 紧接着循环调用 get_result() 方法取回一系列分好的词
// 直到 get_result() 返回 false 为止
// 返回的词是一个关联数组, 包含: word 词本身, idf 逆词率(重), off 在text中的偏移, len 长度, attr 词性
//

$pscws->send_text($text);
while ($some = $pscws->get_result())
{
   foreach ($some as $word)
   {
      print_r($word);
   }
}

// 在 send_text 之后可以调用 get_tops() 返回分词结果的词语按权重统计的前 N 个词
// 常用于提取关键词, 参数用法参见下面的详细介绍.
// 返回的数组元素是一个词, 它又包含: word 词本身, weight 词重, times 次数, attr 词性
$tops = $pscws->get_tops(10, 'n,v');
print_r($tops);

--- 类方法完全手册 ---
(注: 构造函数可传入字符集作为参数, 这与另外调用 set_charset 效果是一样的)

class PSCWS4 {

  void set_charset(string charset);
  说明：设定分词词典、规则集、欲分文本字符串的字符集，系统缺省是 gbk 字集。
  返回：无。
  参数：charset 是设定的字符集，目前只支持 utf8 和 gbk。（注：big5 也可作 gbk 处理）
  注意：输入要切分的文本，词典，规则文件这三者的字符集必须统一为该 charset 值。
  
  bool set_dict(string dict_fpath);
  说明：设置分词引擎所采用的词典文件。
  参数：dict_path 是词典的路径，可以是相对路径或完全路径。
  返回：成功返回 true 失败返回 false。
  错误：若有错误会给出 WARNING 级的错误提示。
  
  void set_rule(string rule_path);
  说明：设定分词所用的新词识别规则集（用于人名、地名、数字时间年代等识别）。
  返回：无。
  参数：rule_path 是规则集的路径，可以是相对路径或完全路径。
  
  void set_ignore(bool yes)
  说明：设定分词返回结果时是否去除一些特殊的标点符号之类。
  返回：无。
  参数：yes 设定值，如果为 true 则结果中不返回标点符号，如果为 false 则会返回，缺省为 false。
  
  void set_multi(int mode);
  说明：设定分词返回结果时是否复合分割，如“中国人”返回“中国＋人＋中国人”三个词。
  返回：无。
  参数：mode 设定值，1 ~ 15。
        按位异或的 1 | 2 | 4 | 8 分别表示: 短词 | 二元 | 主要单字 | 所有单字
    
  void set_duality(bool yes);
  说明：设定是否将闲散文字自动以二字分词法聚合。
  返回：无。
  参数：yes 设定值，如果为 true 则结果中多个单字会自动按二分法聚分，如果为 false 则不处理，缺省为 false。

  void set_debug(bool yes);
  说明：设置分词过程是否输出N-Path分词过程的调试信息。
  参数：yes 设定值，如果为 true 则分词过程中对于多路径分法分给出提示信息。
  返回：无。
  
  void send_text(string text)
  说明：发送设定分词所要切割的文本。
  返回：无。
  参数：text 是文本的内容。
  注意：执行本函数时，请先加载词典和规则集文件并设好相关选项。
  
  mixed get_result(void)
  说明：根据 send_text 设定的文本内容，返回一系列切好的词汇。
  返回：成功返回切好的词汇组成的数组， 若无更多词汇，返回 false。
  参数：无。
  注意：每次切割后本函数应该循环调用，直到返回 false 为止，因为程序每次返回的词数是不确定的。
        返回的词汇包含的键值有：word (string, 词本身) idf (folat, 逆文本词频) off (int, 在文本中的位置) attr(string, 词性)
    
  mixed get_tops( [int limit [, string attr]] )
  说明：根据 send_text 设定的文本内容，返回系统计算出来的最关键词汇列表。
  返回：成功返回切好的词汇组成的数组， 若无更多词汇，返回 false。
  参数：limit 可选参数，返回的词的最大数量，缺省是 10；
        attr 可选参数，是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
             这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，
         缺省为空，返回全部词性，不过滤。
         
  string version(void);
  说明：返回本版号。
  返回：版本号（字符串）。
  参数：无。
  
  void close(void);
  说明：关闭释放资源，使用结束后可以手工调用该函数或等系统自动回收。
  返回：无。
  参数：无。
};
	</pre>
	<p><a href="#top">[返回目录]</a></p>
</div>
<div class="block">
	<h2>词典词性标注详解<a name="attr">&nbsp;</a></h2>
	<pre>
由于词典条目多达26万条之巨，在整理的时候已经把很多明显不对的标注或词条清理了，
但仍然肯定有很多错误的条目。

主要表现在不是词的列在词里，还有词性标注错误的。本词典中的标注使用的是北大
版本的标注集（见附录），在使用中发现错误的请大家协助跟踪汇报。这是一个长期
艰巨的任务，希望本着有一纠一的原则。如有汇报，请遵守格式为：

词 原attr 正确attr
--------------------------
XXX - - （表示错误或不需要的词，应删除）不需要的词指能自动识别了的。
XXX n c （原来标注为n 实际应该为 c）

---- 附北大词性标注版本 ----
Ag 
形语素 
形容词性语素。形容词代码为a，语素代码ｇ前面置以A。 

a 
形容词 
取英语形容词adjective的第1个字母。 

ad 
副形词 
直接作状语的形容词。形容词代码a和副词代码d并在一起。 

an 
名形词 
具有名词功能的形容词。形容词代码a和名词代码n并在一起。 

b 
区别词 
取汉字“别”的声母。 

c 
连词 
取英语连词conjunction的第1个字母。 

Dg 
副语素 
副词性语素。副词代码为d，语素代码ｇ前面置以D。 

d 
副词 
取adverb的第2个字母，因其第1个字母已用于形容词。 

e 
叹词 
取英语叹词exclamation的第1个字母。 

f 
方位词 
取汉字“方” 

g 
语素 
绝大多数语素都能作为合成词的“词根”，取汉字“根”的声母。 

h 
前接成分 
取英语head的第1个字母。 

i 
成语 
取英语成语idiom的第1个字母。 

j 
简称略语 
取汉字“简”的声母。 

k 
后接成分 
　 
l 
习用语 
习用语尚未成为成语，有点“临时性”，取“临”的声母。 

m 
数词 
取英语numeral的第3个字母，n，u已有他用。 

Ng 
名语素 
名词性语素。名词代码为n，语素代码ｇ前面置以N。 

n 
名词 
取英语名词noun的第1个字母。 

nr 
人名 
名词代码n和“人(ren)”的声母并在一起。 

ns 
地名 
名词代码n和处所词代码s并在一起。 

nt 
机构团体 
“团”的声母为t，名词代码n和t并在一起。 

nz 
其他专名 
“专”的声母的第1个字母为z，名词代码n和z并在一起。 

o 
拟声词 
取英语拟声词onomatopoeia的第1个字母。 

ba 介词 把、将 　 
bei 介词 被 　 
p 
介词 
取英语介词prepositional的第1个字母。 

q 
量词 
取英语quantity的第1个字母。 

r 
代词 
取英语代词pronoun的第2个字母,因p已用于介词。 

s 
处所词 
取英语space的第1个字母。 

Tg 
时语素 
时间词性语素。时间词代码为t,在语素的代码g前面置以T。 

t 
时间词 
取英语time的第1个字母。 

dec 助词 的、之 　 
deg 助词 得 　 
di 助词 地 　 
etc 助词 等、等等 　 
as 助词 了、着、过 　 
msp 助词 所 　 
u 
其他助词 
取英语助词auxiliary 

Vg 
动语素 
动词性语素。动词代码为v。在语素的代码g前面置以V。 

v 
动词 
取英语动词verb的第一个字母。 

vd 
副动词 
直接作状语的动词。动词和副词的代码并在一起。 

vn 
名动词 
指具有名词功能的动词。动词和名词的代码并在一起。 

w 
其他标点符号 
　 
x 
非语素字 
非语素字只是一个符号，字母x通常用于代表未知数、符号。 

y 
语气词 
取汉字“语”的声母。 

z 
状态词 
取汉字“状”的声母的前一个字母。
	</pre>
	<p><a href="#top">[返回目录]</a></p>
</div>
<?php include 'footer.inc.php'; ?>
