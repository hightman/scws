/**
 * @file xlog.h
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifndef	_SCWS_XLOG_20070530_H_
#define	_SCWS_XLOG_20070530_H_

#ifdef HAVE_CONFIG_H
#	include "config.h"
#endif

/* required header */
#include <stdio.h>
#include <syslog.h>

/* constant defintions */
#define MAXLOG_LINE		1024
#define ZONE			__FILE__,__LINE__

/* if nodebug, basically compile it out */
#ifdef DEBUG
#  define xlog_debug	_xlog_debug
#else
#  define xlog_debug	if(0) _xlog_debug
#endif

/* data structure */
typedef enum 
{
	xlog_STDOUT,
	xlog_SYSLOG,
	xlog_FILE
}	xlog_type;

typedef struct
{
	char *facility;
	int level;
}	xlog_level;

typedef struct
{
	xlog_type type;
	int	mlevel;
	FILE *fp;
}	xlog_st, *xlog_t;

/* xlog: api */
xlog_t xlog_new(xlog_type type, char *ident, char *facility);
void xlog_write(xlog_t xl, int level, const char *fmt, ...);
void xlog_free(xlog_t xl);

/* debug logging to stderr */
void _xlog_debug(char *file, int line, const char *fmt, ...);

#endif
