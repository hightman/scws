/*
 * @file xlog.c
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * @notice this is modified from source of jabberd2.0s10
 * $Id  $
 */

#include "xlog.h"
#include <errno.h>
#include <string.h>
#include <stdarg.h>
#include <time.h>
#include <sys/time.h>

static const char *_xlog_levels[] =
{
	"emergency",
	"alert",
	"critical",
	"error",
	"warning",
	"notice",
	"info",
	"debug",
	NULL
};

static xlog_level _xlog_facilities[] = 
{
	{ "local0", LOG_LOCAL0 },
	{ "local1", LOG_LOCAL1 },
	{ "local2", LOG_LOCAL2 },
	{ "local3", LOG_LOCAL3 },
	{ "local4", LOG_LOCAL4 },
	{ "local5", LOG_LOCAL5 },
	{ "local6", LOG_LOCAL6 },
	{ "local7", LOG_LOCAL7 },
	{ NULL, -1 }
};

static int _xlog_facility(char *facility) 
{
	xlog_level *lf;

	if (facility == NULL)
		return -1;

	for (lf = _xlog_facilities; lf->facility; lf++)
	{
		if (!strcasecmp(lf->facility, facility))
			return lf->level;
	}
	return -1;
}

/* create new xlog */
xlog_t xlog_new(xlog_type type, char *ident, char *facility)
{
	xlog_t xl;
	int fnum = 0;

	xl = (xlog_t) malloc(sizeof(xlog_st));
	memset(xl, 0, sizeof(xlog_st));

	fnum = _xlog_facility(facility);
	xl->type = type;
	xl->mlevel = (fnum < 0 ? 4 : facility[5] - '0');

	/* syslog */
	if (type == xlog_SYSLOG)
	{
		if (fnum < 0)
			fnum = LOG_LOCAL4;

		openlog(ident, LOG_PID, fnum);
		return xl;
	}
	
	/* stdout */
	if (type == xlog_STDOUT)
	{
		xl->fp = stdout;
		return xl;
	}

	/* file log */
	if (!(xl->fp = fopen(ident, "a+")))
	{
		perror("Cann't open the logfile, logging will goto stdout");
		xl->type = xlog_STDOUT;
		xl->fp = stdout;
	}
	return xl;
}

/* write to log */
void xlog_write(xlog_t xl, int level, const char *fmt, ...)
{
	va_list ap;
	char *pos, message[MAXLOG_LINE];
	int sz;
	time_t t;

	if (level > xl->mlevel)
		return;

	if (xl->type == xlog_SYSLOG)
	{
		va_start(ap, fmt);

#ifdef HAVE_VSYSLOG
		vsyslog(level, fmt, ap);
#else
		vsnprintf(message, MAXLOG_LINE-1, fmt, ap);
		syslog(level, "%s", message);
#endif
		va_end(ap);

#ifndef DEBUG
		return;
#endif
	}

	t = time(NULL);
	pos = ctime(&t);
	sz = strlen(pos);

	/* chop off the \n */
	pos[sz-1] = ' ';

	/* insert the header */
	snprintf(message, MAXLOG_LINE-1, "%s[%s] ", pos, _xlog_levels[level]);

	/* find the end and attach the rest of the msg */
	sz = strlen(message);
	pos = message + sz;

	va_start(ap, fmt);
	vsnprintf(pos, MAXLOG_LINE - sz - 1, fmt, ap);
	va_end(ap);

	fprintf(xl->fp, "%s\n", message);

#ifdef DEBUG
	/* If we are in debug mode we want everything copied to the stdout */
	if (xl->type != xlog_STDOUT)	
		fprintf(stdout, "%s\n", message);	
#endif
}

/* free log pointer */
void xlog_free(xlog_t xl)
{
	if (xl->type == xlog_SYSLOG)
		closelog();

	if (xl->type == xlog_FILE)
		fclose(xl->fp);

	free(xl);
}

/* debug logging */
void _xlog_debug(char *file, int line, const char *fmt, ...)
{
	va_list ap;
	char *pos, message[MAXLOG_LINE];
	int sz;
	time_t t;

	/* timestamp */
	t = time(NULL);
	pos = ctime(&t);
	sz = strlen(pos);

	/* chop off the \n */
	pos[sz-1] = ' ';

	/* insert the header */
	snprintf(message, MAXLOG_LINE-1, "%s%s:%d ", pos, file, line);

	/* find the end and attach the rest of the msg */
	sz = strlen(message);
	pos = message + sz;
	va_start(ap, fmt);
	vsnprintf(pos, MAXLOG_LINE - sz - 1, fmt, ap);
	va_end(ap);

	fprintf(stderr,"%s\n", message);
}
