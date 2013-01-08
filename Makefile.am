# Automake file on top directory
# $Id$

AUTOMAKE_OPTIONS = 1.5 subdir-objects

SUBDIRS = . libscws cli etc

DIST_SUBDIRS = libscws cli etc

EXTRA_DIST = API.md README.md phpext win32
	
dict:
	cd etc && make dict

dist-dict:
	cd etc && make dist-dict

dist-hook:
	-rm -f $(distdir)/libscws/version.h

