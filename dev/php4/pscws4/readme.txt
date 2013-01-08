===== PSCWS4 - 说明文档 =====
$Id$

[ 关于 PSCWS4 ]

PSCWS4 是由 hightman 于 2007 年开发的纯 PHP 代码实现的简易中文分词系统第四版的简称。

PSCWS 是英文 PHP Simple Chinese Words Segmentation 的头字母缩写，它是 SCWS 项目的前身。
现 SCWS 已作为 FTPHP 项目的一个子项目继续发展，现于 2008-12 重新修订并整理发布。

SCWS 是一套开源并且免费的中文分词系统，提供优秀易用的 PHP 接口。
项目主页：http://www.ftphp.com/scws

PSCWS4 在算法、功能以及词典/规则文件格式上，完全兼容于 C 编写 libscws，类库的方法完全兼容
于 scws 的 php 扩展中的预定义类的方法。

第四版的算法采用N-核心词路径最优方案，采用强大的规则引擎识别人名地名数字等专有名词，C实现
的版本速度和效率均非常高，推荐使用。PSCWS4 是 SCWS(C) 的 PHP 实现，速度较慢。


[ 性能评估 ]

采用 test.php 的分词命令行调用, 操作系统 FreeBSD 6.2 , CPU 为单至强 3.0G

PSCWS4 - 长度为 80, 535 的文本,  耗时将近 30 秒
         分词精度 95.60%, 召回率 90.51% (F-1: 0.93)

附：同等长度文本在 scws-1.0 (PHP 扩展方式) 耗时仅需 0.65 秒(C调用则为 0.17秒).
    强烈建议有条件者改用 scws-1.0 (C版)

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
        按位与的 1 | 2 | 4 | 8 分别表示: 短词 | 二元 | 主要单字 | 所有单字
	
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

[ 关于词典 ]

PSCWS4 使用的是 XDB 格式词典，与 C 版的 libscws 完全兼容。

我们提供的默认词典是通用的互联网信息词汇集，约 28 万个词。如果您需要定制词典以作特殊用
途，请与我们联系，可能会视情况进行收费。

[ 注意事项 ]

PSCWS4 由纯 PHP 代码实现，不需要任何外部扩展支持，但效率一般，建议选用 C 版编写的扩展。

PSCWS4 可以良好的运行在各种版本的 PHP4 和 PHP5 上，支持 GBK，UTF-8 等宽型字符集，若您的

提供下载的词典是在 Intel 架构的平台上制作的，放到其它架构的机器中运行可能会存在问题导致
切词完全错误（典型的如：Sparc 架构的 Solaris/SunOS 服务器中），若您发现问题请及时与我们
联系寻求解决。

[ 联系我们 ]

SCWS 项目网站：http://www.ftphp.com/scws
我的个人 Email：hightman2@yahoo.com.cn   （一般问题请勿直接来信，谢谢）

--

2008.12.21 - hightman
