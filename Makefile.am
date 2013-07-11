# Automake file on top directory
# $Id$

AUTOMAKE_OPTIONS = 1.5 subdir-objects

SUBDIRS = . libscws cli etc

DIST_SUBDIRS = libscws cli etc

EXTRA_DIST = API.md phpext win32

UNISON_OPT = -auto -batch -prefer newer -terse -times -logfile /dev/null
	
dict:
	cd etc && make dict

dist-dict:
	cd etc && make dist-dict

dist-hook:
#	-rm -f $(distdir)/libscws/version.h

sync-web:
	unison ./web ssh://xunsearch/web/scws $(UNISON_OPT)

dist-web: dist-bzip2
	mv -f $(distdir).tar.bz2 web/down
	cd etc && tar czf ../web/down/rules.tgz *.ini
	make sync-web

