# $Id$
#

EXTRA_DIST = rules.ini rules.utf8.ini rules_cht.utf8.ini

sysconf_DATA = rules.ini rules.utf8.ini rules_cht.utf8.ini

# dictionary
dict_FILES = dict.xdb dict.utf8.xdb

dict.txt:
	@echo "Generate dict.txt by php ..."
	php ../dev/dict/calc_text.php > $@

dict.utf8.txt: dict.txt
	@echo "Convert gbk text dict to utf8 ..."
	@php -r 'echo mb_convert_encoding(file_get_contents("dict.txt"), "utf-8", "gbk");' > $@
	
dict.xdb: dict.txt
	-rm -f $@
	../cli/scws-gen-dict -c gbk -i $< -o $@

dict.utf8.xdb: dict.utf8.txt
	-rm -f $@
	../cli/scws-gen-dict -c utf8 -i $< -o $@

dict: $(dict_FILES)
	-rm -f dict.txt dict.utf8.txt

dist-dict: dict
	tar -cjf ../web/down/scws-dict-chs-gbk.tar.bz2 dict.xdb
	tar -cjf ../web/down/scws-dict-chs-utf8.tar.bz2 dict.utf8.xdb
