README of phpext on win32
===========================

$Id$

* 在 1.0.2 及以前，由 ben 移植并编译到 windows 中，下载地址：
  <http://www.yanbin.org/php-scws-windows-edtion/>

* 自 1.0.3 起，由 hightman 整合将 win32 的支持加入到代码分支中，并提供相应的工程文件，
  可在 vc 环境下可直接编译。

* 自 1.1.8 起，同时提供 vc9 环境为 php-5.3.x 编译的 php_scws.dll

* 自 1.2.0 起，同时提供 VC9 环境为 php-5.4.x 编译的 php_scws.dll；
  同时还提供了 Non-Thread-Safety 的 5.3/5.4 下的 php_scws.dll。

> 注意：未注明 nts 时，编译好的各版本 php_scws.dll 均为 Thread-Safety 版本。
