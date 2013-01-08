1. baidu_getnum.php  - 从 baidu.com 取回关键词的数据频率

   Usage: php baidu_getnum.php w.txt > tmp.txt
   Usage: php baidu_getnum.php <word>

2. w.txt , z.txt
   具有初始词频的文件, w.txt 是词文件, z.txt 是字文件

3. bdb.class2.php  (binary db) 试验产品，效率差，不用了

4. calc_text.php
   将 w.txt 和 z.txt 转换成备用的词典文本文件
   php calc_text.php > dict.txt
   php calc_text.php w > dw.txt  (only w.txt)
   php calc_text.php z > dz.txt  (only z.text)

5. dba.class.php
   操作 dba 的类库, 以便和 xdb/hdb 接口完全一致

6. draw_hdb.php
   绘出 hdb/xdb 的节点图

   php draw_hdb.php <dict_file> <tree_index:default=-1>
   dict_file = *.hdb | *.xdb

7. hdb.class.php  (hdb核心类库)

8. leitu_get.php (从leitu.com 获取词性)
   php leitu_get.php <input txtfile>

9. mk_dbm.php 将calc_text.php 生成的 txt 文件生成直接使用的 dba或xdb/hdb格式

   php mk_dbm.php <input file> <output file>
   output格式自动识别：xdb, hdb, cdb, gdbm ...

10. sync_hdb.php 整理 xdb/hdb 的数据结构，将二叉数转为平衡二叉树

   php sync_hdb.php <dbm file> [tree_index]

11. test_query.php <dict query test>
   php test_quer.php <dictfile> <query word>

12. xdb.class.php  (xdb操作核心类库)

------------------------------------------------
xdb与hdb的区别？

hdb是跨平台跨机器的，和机器字节序无关，且每条记录比xdb节省4字节空间
xdb为兼容C版的xdb而编写，和机器字节序有关，其余均差不多.


将 w.txt/z.txt 转换成可用词典的必备步骤：

php calc_text.php > dict.txt
php mk_dbm.php dict.txt dict.xdb
php sync_xdb.php dict.xdb -1
...
生成的 xdb 可供C版分词直接使用



