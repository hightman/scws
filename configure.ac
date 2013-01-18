#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.
# scws-1.x.x configure - modified by hightman

AC_PREREQ([2.68])
AC_INIT([scws], [1.2.1], [http://www.xunsearch.com/scws])

# init automake
AM_INIT_AUTOMAKE

# copy right
AC_COPYRIGHT([Copyright (C)2007-2013, hightman
This package maybe copied, distributed and modified under
the terms of my License; see COPYING for more details])

# sorce code tree
#AC_CONFIG_SUBDIRS([libscws cli])
AC_CONFIG_SRCDIR([cli/gen_dict.c])
AC_CONFIG_HEADERS([config.h])

# Checks for programs.
#AC_PROG_AWK
AC_PROG_CC
AC_PROG_SED
AC_PROG_INSTALL
AC_PROG_LN_S
AC_PROG_MAKE_SET
AC_DISABLE_STATIC
AC_PROG_LIBTOOL

# libtool
LIBTOOL="$LIBTOOL --preserve-dup-deps"
AC_SUBST(LIBTOOL)

# libtool compile
SHARED_LIB_VERSION=2:0:1
AC_SUBST(SHARED_LIB_VERSION)

# debug mode
AC_ARG_ENABLE(debug, 
	AC_HELP_STRING([--enable-debug], [Compile with more warnings and debugging symbols]),
	[
		enable_debug=yes
		AC_DEFINE(DEBUG,,[enable debug mode])
	], [ 
		enable_debug=no 
	]
)

if test "$enable_debug" = "no" ; then
  tmp_cflags=`echo $CFLAGS | sed 's/-O//g'`	
  if test "$tmp_cflags" = "$CFLAGS"; then  CFLAGS="$CFLAGS -O2"; fi
else
  tmp_cflags=`echo $CFLAGS | sed 's/-g//g'`	
  if test "$tmp_cflags" = "$CFLAGS"; then  CFLAGS="$CFLAGS -g"; fi
fi

# Checks for libraries.
AC_CHECK_LIB(m, logf, [], [ AC_MSG_ERROR([math lib not found]) ])

# Checks for header files.
AC_CHECK_HEADERS([fcntl.h netinet/in.h math.h stdlib.h string.h sys/file.h sys/param.h sys/time.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_C_CONST
AC_C_INLINE
AC_HEADER_TIME
AC_CHECK_SIZEOF(int, 4, [], [ AC_MSG_ERROR(sizeof(int) != 4) ])
#AC_CHECK_SIZEOF(char *, 4, [], [ AC_MSG_ERROR(sizeof(char *) != 4) ])
AC_CHECK_SIZEOF(float, 4, [], [ AC_MSG_ERROR(sizeof(float) != 4) ])

# check for struct_flock
AC_CACHE_CHECK(for struct flock, ac_cv_struct_flock,
	AC_TRY_COMPILE([
#include <unistd.h>
#include <fcntl.h>
	], [
		struct flock x; 
	], [
		AC_DEFINE(HAVE_STRUCT_FLOCK, 1, [whether you have struct flock])
	], [])
)

# Checks for library functions.
AC_FUNC_LSTAT
AC_FUNC_LSTAT_FOLLOWS_SLASHED_SYMLINK
AC_FUNC_MMAP
AC_FUNC_MEMCMP
AC_CHECK_FUNCS([flock gettimeofday malloc memset munmap pow realpath strcasecmp strchr strdup strrchr strndup strtok_r])

AC_CONFIG_FILES([Makefile cli/Makefile etc/Makefile libscws/Makefile libscws/version.h])
AC_OUTPUT
