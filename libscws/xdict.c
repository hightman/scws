/**
 * @file xdict.c (dictionary query)
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifdef HAVE_CONFIG_H
#    include "config.h"
#endif

#ifdef WIN32
#    include "config_win32.h"
#endif

#include "xdict.h"
#include "xtree.h"
#include "xdb.h"
#include "crc32.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#ifndef WIN32
#    include <sys/param.h>
#endif
#include <sys/types.h>
#include <sys/stat.h>

/* temp file format for TEXT xdb */
#if !defined(PATH_MAX) || (PATH_MAX < 1024)
#    define	XDICT_PATH_MAX	1024
#else
#    define	XDICT_PATH_MAX	PATH_MAX
#endif

#ifdef HAVE_STRTOK_R
#    define	_strtok_r	strtok_r
#else

static char *_strtok_r(char *s, char *delim, char **lasts)
{
	register char *spanp;
	register int c, sc;
	char *tok;

	if (s == NULL && (s = *lasts) == NULL)
		return NULL;

	/*
	 * Skip (span) leading delimiters (s += strspn(s, delim), sort of).
	 */
cont:
	c = *s++;
	for (spanp = (char *) delim; (sc = *spanp++) != 0;)
	{
		if (c == sc) goto cont;
	}

	if (c == 0)
	{ /* no non-delimiter characters */
		*lasts = NULL;
		return NULL;
	}
	tok = s - 1;

	/*
	 * Scan token (scan for delimiters: s += strcspn(s, delim), sort of).
	 * Note that delim must have one NUL; we stop if we see that, too.
	 */
	for (;;)
	{
		c = *s++;
		spanp = (char *) delim;
		do
		{
			if ((sc = *spanp++) == c)
			{
				if (c == 0) s = NULL;
				else s[-1] = '\0';
				*lasts = s;
				return tok;
			}
		}
		while (sc != 0);
	}
}
#endif

#ifdef WIN32
#    include <direct.h>

static void _realpath(const char *src, char *dst)
{
	int len = strlen(src);
	if (strchr(src, ':') != NULL)
		memcpy(dst, src, len + 1);
	else
	{
		char *ptr;
		getcwd(dst, XDICT_PATH_MAX - len - 2);
		ptr = dst + strlen(dst);
		*ptr++ = '/';
		memcpy(ptr, src, len + 1);
	}
}
#else
#    define	_realpath	realpath
#endif

/* open the text dict */
static xdict_t _xdict_open_txt(const char *fpath, int mode, unsigned char *ml)
{
	xdict_t xd;
	xtree_t xt;
	char buf[XDICT_PATH_MAX], tmpfile[XDICT_PATH_MAX];
	struct stat st1, st2;

	// check the input filepath
	_realpath(fpath, buf);
	if (stat(buf, &st1) < 0)
		return NULL;

	// check dest file & orginal file, compare there mtime
#ifdef WIN32
	{
		char *tmp_ptr;
		GetTempPath(sizeof(tmpfile) - 20, tmpfile);
		tmp_ptr = tmpfile + strlen(tmpfile);
		if (tmp_ptr[-1] == '\\') tmp_ptr--;
		sprintf(tmp_ptr, "\\scws-%08x.xdb", scws_crc32(buf));
	}
#else
	sprintf(tmpfile, "/tmp/scws-%08x.xdb", scws_crc32(buf));
#endif
	if (!stat(tmpfile, &st2) && st2.st_mtime > st1.st_mtime)
	{
		xdb_t x;
		if ((x = xdb_open(tmpfile, 'r')) != NULL)
		{
			xd = (xdict_t) malloc(sizeof(xdict_st));
			memset(xd, 0, sizeof(xdict_st));
			xd->ref = 1;

			if (mode & SCWS_XDICT_MEM)
			{
				/* convert the xdb(disk) -> xtree(memory) */
				if ((xt = xdb_to_xtree(x, NULL)) != NULL)
				{
					xdb_close(x);
					xd->xdict = (void *) xt;
					xd->xmode = SCWS_XDICT_MEM;
					return xd;
				}
			}
			xd->xmode = SCWS_XDICT_XDB;
			xd->xdict = (void *) x;
			return xd;
		}
	}

	// create xtree
	if ((xt = xtree_new(0, 0)) == NULL)
		return NULL;
	else
	{
		int cl, kl;
		FILE *fp;
		word_st word, *w;
		char *key, *part, *last, *delim = " \t\r\n";

		// re-build the xdb file from text file	
		if ((fp = fopen(buf, "r")) == NULL)
			return NULL;

		// parse every line
		word.attr[2] = '\0';
		while (fgets(buf, sizeof(buf) - 1, fp) != NULL)
		{
			// <word>[\t<tf>[\t<idf>[\t<attr>]]]		
			if (buf[0] == ';' || buf[0] == '#') continue;

			key = _strtok_r(buf, delim, &last);
			if (key == NULL) continue;
			kl = strlen(key);

			// init the word
			do
			{
				word.tf = word.idf = 1.0;
				word.flag = SCWS_WORD_FULL;
				word.attr[0] = '@';
				word.attr[1] = '\0';

				if (!(part = _strtok_r(NULL, delim, &last))) break;
				word.tf = (float) atof(part);

				if (!(part = _strtok_r(NULL, delim, &last))) break;
				word.idf = (float) atof(part);

				if (part = _strtok_r(NULL, delim, &last))
				{
					word.attr[0] = part[0];
					if (part[1]) word.attr[1] = part[1];
				}
			}
			while (0);

			// save into xtree
			if ((w = xtree_nget(xt, key, kl, NULL)) == NULL)
			{
				w = (word_st *) pmalloc(xt->p, sizeof(word_st));
				memcpy(w, &word, sizeof(word));
				xtree_nput(xt, w, sizeof(word), key, kl);
			}
			else
			{
				w->tf = word.tf;
				w->idf = word.idf;
				w->flag |= word.flag;
				strcpy(w->attr, word.attr);
			}

			// parse the part	
			cl = ml[(unsigned char) (key[0])];
			while (1)
			{
				cl += ml[(unsigned char) (key[cl])];
				if (cl >= kl) break;

				if ((w = xtree_nget(xt, key, cl, NULL)) != NULL)
					w->flag |= SCWS_WORD_PART;
				else
				{
					w = (word_st *) pmalloc_z(xt->p, sizeof(word_st));
					w->flag = SCWS_WORD_PART;
					xtree_nput(xt, w, sizeof(word), key, cl);
				}
			}
		}
		fclose(fp);

		// optimize the xtree & save to xdb
		xtree_optimize(xt);
		unlink(tmpfile);
		xtree_to_xdb(xt, tmpfile);
		chmod(tmpfile, 0777);

		// return xtree
		xd = (xdict_t) malloc(sizeof(xdict_st));
		memset(xd, 0, sizeof(xdict_st));
		xd->ref = 1;
		xd->xdict = (void *) xt;
		xd->xmode = SCWS_XDICT_MEM;
		return xd;
	}
}

/* setup & open the dict */
xdict_t xdict_open(const char *fpath, int mode)
{
	xdict_t xd;
	xdb_t x;

	if (!(x = xdb_open(fpath, 'r')))
		return NULL;

	xd = (xdict_t) malloc(sizeof(xdict_st));
	memset(xd, 0, sizeof(xdict_st));
	xd->ref = 1;
	if (mode & SCWS_XDICT_MEM)
	{
		xtree_t xt;

		/* convert the xdb(disk) -> xtree(memory) */
		if ((xt = xdb_to_xtree(x, NULL)) != NULL)
		{
			xdb_close(x);
			xd->xdict = (void *) xt;
			xd->xmode = SCWS_XDICT_MEM;
			return xd;
		}
	}

	xd->xmode = SCWS_XDICT_XDB;
	xd->xdict = (void *) x;
	return xd;
}

/* add a dict */
xdict_t xdict_add(xdict_t xd, const char *fpath, int mode, unsigned char *ml)
{
	xdict_t xx;

	xx = (mode & SCWS_XDICT_TXT ? _xdict_open_txt(fpath, mode, ml) : xdict_open(fpath, mode));
	if (xx != NULL)
	{
		xx->next = xd;
		return xx;
	}
	return xd;
}

/* fork the dict */
xdict_t xdict_fork(xdict_t xd)
{
	xdict_t xx;
	for (xx = xd; xx != NULL; xx = xx->next)
	{
		xx->ref++;
	}
	return xd;
}

/* close the dict */
void xdict_close(xdict_t xd)
{
	xdict_t xx;

	while ((xx = xd) != NULL)
	{
		xd = xx->next;
		xx->ref--;
		if (xx->ref == 0)
		{
			if (xx->xmode == SCWS_XDICT_MEM)
				xtree_free((xtree_t) xx->xdict);
			else
			{
				xdb_close((xdb_t) xx->xdict);
			}
			free(xx);
		}
	}
}

/* query the word */
#define	_FLAG_BOTH(x)	(((x)->flag & (SCWS_WORD_PART|SCWS_WORD_FULL)) == (SCWS_WORD_PART|SCWS_WORD_FULL))
#define	_FLAG_FULL(x)	((x)->flag & SCWS_WORD_FULL)
#define	_FLAG_PART(x)	((x)->flag & SCWS_WORD_PART)
#define	_FLAG_MALLOC(x)	((x)->flag & SCWS_WORD_MALLOCED)

word_t xdict_query(xdict_t xd, const char *key, int len)
{
	word_t value, value2;

	value = value2 = NULL;
	while (xd != NULL)
	{
		if (xd->xmode == SCWS_XDICT_MEM)
		{
			/* this is ThreadSafe, recommend. */
			value = (word_t) xtree_nget((xtree_t) xd->xdict, key, len, NULL);
		}
		else
		{
			/* the value malloced in lib-XDB. free required */
			value = (word_t) xdb_nget((xdb_t) xd->xdict, key, len, NULL);
			if (value != NULL) value->flag |= SCWS_WORD_MALLOCED;
		}
		xd = xd->next;

		// check value2
		if (value != NULL)
		{
			if (value2 == NULL)
			{
				if (_FLAG_BOTH(value))
					return value;
				value2 = value;
			}
			else
			{
				if (_FLAG_FULL(value2) && _FLAG_PART(value))
				{
					value2->flag |= SCWS_WORD_PART;
					if (_FLAG_MALLOC(value))
						free(value);
					return value2;
				}
				if (_FLAG_FULL(value) && _FLAG_PART(value2))
				{
					value->flag |= SCWS_WORD_PART;
					if (_FLAG_MALLOC(value2))
						free(value2);
					return value;
				}
				if (_FLAG_MALLOC(value))
					free(value);
			}
		}
	}
	return value2;
}
