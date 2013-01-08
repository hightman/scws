# $Id$

LIBTOOL = @LIBTOOL@ $(QUIET:y=--quiet)

libscwsincludedir = @prefix@/include/scws

libscwsinclude_HEADERS = charset.h crc32.h pool.h scws.h xdict.h darray.h rule.h xdb.h xtree.h version.h

lib_LTLIBRARIES = libscws.la

libscws_la_SOURCES = charset.c crc32.c pool.c scws.c xdict.c darray.c rule.c lock.c xdb.c xtree.c

libscws_la_LDFLAGS = @LDFLAGS@ -no-undefined -version-info @SHARED_LIB_VERSION@

EXTRA_DIST = lock.h config_win32.h
