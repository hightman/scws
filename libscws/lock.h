/*
   +----------------------------------------------------------------------+
   | PHP Version 5                                                        |
   +----------------------------------------------------------------------+
   | Copyright (c) 1997-2007 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 3.01 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available through the world-wide-web at the following url:           |
   | http://www.php.net/license/3_01.txt                                  |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Sascha Schumann <sascha@schumann.cx>                         |
   +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifndef LOCK_H
#define LOCK_H

/* php_flock internally uses fcntl whether or not flock is available
* This way our php_flock even works on NFS files.
* More info: /usr/src/linux/Documentation
*/
int _xdb_flock(int fd, int operation);

#ifndef HAVE_FLOCK
#	define LOCK_SH 1
#	define LOCK_EX 2
#	define LOCK_NB 4
#	define LOCK_UN 8
#endif

#ifdef WIN32
#   define EWOULDBLOCK WSAEWOULDBLOCK
#	define fsync _commit
#	define ftruncate(a, b) chsize(a, b)
#	include <windows.h>
#endif /* defined(WIN32) */

#endif	/* LOCK_H */
