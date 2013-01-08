API of LIBSCWS
===============
$Id$


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
#include <stdio.h>
#include <scws/scws.h>
#define SCWS_PREFIX     "/usr/local"

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
gcc -o test -I/usr/local/include -L/usr/local/lib test.c -lscws
./test
```
