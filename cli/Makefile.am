# $Id$
#

LIBTOOL = @LIBTOOL@ $(QUIET:y=--quiet)

libscwsdir = $(top_srcdir)/libscws

INCLUDES = -I$(top_srcdir) -I$(libscwsdir)

bin_PROGRAMS = scws scws-gen-dict

scws_SOURCES = scws_cmd.c
scws_LDADD = $(libscwsdir)/libscws.la

scws_gen_dict_SOURCES = gen_dict.c
scws_gen_dict_LDADD = $(libscwsdir)/libscws.la

