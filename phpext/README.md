SCWS 之 PHP 扩展
================
$Id$


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
<?php
$so = scws_new();
$so->set_charset('gbk');
// 这里没有调用 set_dict 和 set_rule 系统会自动试调用 ini 中指定路径下的词典和规则文件
$so->send_text("我是一个中国人,我会C++语言,我也有很多T恤衣服");
while ($tmp = $so->get_result())
{
  print_r($tmp);
}
$so->close();
?>
```

**例子2** 使用函数提取高频词

```php
<?php
$sh = scws_open();
scws_set_charset($sh, 'gbk');
scws_set_dict($sh, '/path/to/dict.xdb');
scws_set_rule($sh, '/path/to/rules.ini');
$text = "我是一个中国人，我会C++语言，我也有很多T恤衣服";
scws_send_text($sh, $text);
$top = scws_get_tops($sh, 5);
print_r($top);
?>
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
