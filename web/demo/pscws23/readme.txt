===== PSCWS23 - 说明文档 =====
$Id: readme.txt,v 1.3 2008/12/21 04:37:59 hightman Exp $

[ 关于 PSCWS23 ]

PSCWS23 是由 hightman 于 2006 年开发的纯 PHP 代码实现的简易中文分词系统第二和第三版的简称。

PSCWS 是英文 PHP Simple Chinese Words Segmentation 的头字母缩写，它是 SCWS 项目的前身。
现 SCWS 已作为 FTPHP 项目的一个子项目继续发展，现于 2008-12 重新修订并整理发布。

SCWS 是一套开源并且免费的中文分词系统，提供优秀易用的 PHP 接口。
项目主页：http://www.ftphp.com/scws

PSCWS 的第二版和第三版调用接口完全一致，词典也通用，仅仅是内部分词算法不一样。其中第二版
采用的是正向最大匹配结合N(默认为2)层消岐方案；第三版则采用双向匹配比较相邻词汇的频率取优。

使用速度上第二版略快一些，但差别不大，准确率也相差不多各有特色。


[ 性能评估 ]

采用 demo.php 的分词调用, 操作系统 FreeBSD 6.2 , CPU 为双至强 3.0G

PSCWS2 - 长度为 80, 535 的文本,  耗时 4.9 秒, 查词 44688 次
         分词精度 93.67%, 召回率 88.54% (F-1: 0.91)

PSCWS3 - 长度为 80, 535 的文本,  耗时 6.8 秒, 查词 48181 次
         分词精度 92.99%, 召回率 87.91% (F-1: 0.90)

附：同等长度文本在 scws-1.0 (PHP 扩展方式) 耗时仅需 0.65 秒(C调用则为 0.17秒).
    分词精度 95.60%, 召回率 90.51% (F-1: 0.93), 强烈建议有条件者改用 scws-1.0 (C版)

注：多次评测后发现在单 CPU 的机器上性能也大致差不多。

[ 文件结构 ]

  文件                   描述                      使用必需?
  --------------------------------------------------------------
  dict/dict.xdb        - XDB 格式词典              (必要文件)
  pscws2.class.php     - PSCWS 第二版核心类库代码  (必要文件)
  pscws3.class.php     - PSCWS 第三版核心类库代码  (必要文件)   
  dict.class.php       - 词典操作类库              (必要文件)
  xdb_r.class.php      - XDB 格式读取类            (必要文件)

  demo.php             - 演示文件, 支持 web/命令行 (可选)
  readme.txt           - 说明文件                  (可选)

[ 使用说明 ]

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

[ 关于词典 ]

PSCWS23 支持的词典格式包括：XDB，SQLite，CDB/GDBM 及 Txt 文本格式，依据所设词典的后缀名
而自动识别（后缀名为小写，如：dict.xdb, dict.sqlite ...）

目前推荐和默认采用 XDB 格式，这是专为 SCWS 开发而且采用纯 PHP 代码实现的 XTreeDB，效率
非常不错，比 CDB 还略快。

其它格式仅作简介，一般也不再推荐使用，其中 CDB/GDBM 需要 PHP 的 dba 扩展及相关库函数。
（编译选项 --enable-dba --with-cdb --with-gdbm）

我们提供的默认词典是通用的互联网信息词汇集，约 26 万个词。如果您需要定制词典以作特殊用
途，请与我们联系，可能会视情况进行收费。

[ 注意事项 ]

PSCWS23 由纯 PHP 代码实现，不同的词典格式可能需要适当的 PHP 扩展支持，默认推荐的词典格式
现在已经改为 XDB （原先是CDB），不再需要外部扩展支持。

PSCWS23 可以良好的运行在各种版本的 PHP4 和 PHP5 上，但仅支持 GBK 字符集，若您的系统采用
的是 UTF-8 字符集，则不适合用本系统，请参见项目主页上的 scws-1.0.0 ，这套工具完美支持 GBK
和 UTF-8 字符集同时支持词性标注等。（注：BIG5 字符集可以按 GBK 字符集处理）

提供下载的词典是在 Intel 架构的平台上制作的，放到其它架构的机器中运行可能会存在问题导致
切词完全错误（典型的如：Sparc 架构的 Solaris/SunOS 服务器中），若您发现问题请及时与我们
联系寻求解决。

[ 联系我们 ]

SCWS 项目网站：http://www.ftphp.com/scws
我的个人 Email：hightman2@yahoo.com.cn   （一般问题请勿直接来信，谢谢）


--

2008.12.20 - hightman
