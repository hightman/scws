用法说明：

scws.dsw: VC6 工程文件
scws.sln：VC9(vs2008) 工程文件


编译前的准备工作：
在 scws-1.x.y/ 同级的目录下放置 php 源码目录，对应如下：

-- VC6 (Thread SafeTy) --

php-4.4.9/   同时请将 php4.4.x的winzip压缩包里的 php4ts.lib 也放入此目录
php-5.2.12/  同时请将 php5.2.x的winzip压缩包中 dev/ 目录下的 php5ts.lib 也放入此目录
php-5.3.1/   同时请将 php5.3.x的winzip压缩包中 dev/ 目录下的 php5ts.lib 也放入此目录

-- VC9 (Thread Safety) --
php-5.3.6/  同时请将 php5.3.x的winzip压缩包中 dev/ 目录下的 php5ts.lib 也放入此目录
php-5.4.0/  同时请将 php5.4.x的winzip压缩包中 dev/ 目录下的 php5ts.lib 也放入此目录

-- VC9 (NON-Thread safety)
php-5.3.6/  同时请将 php5.3.x的winzip压缩包中 dev/ 目录下的 php5.lib 也放入此目录
php-5.4.0/  同时请将 php5.4.x的winzip压缩包中 dev/ 目录下的 php5.lib 也放入此目录


准备就绪后打开相应的工程文件，进行编译即可（请选 Release），编译完的 php_scws.dll 位于
scws-1.x.y/Release/ 下


hightman.20110730
