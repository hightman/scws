/**
 * @file xdb.h (read only)
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifndef	_SCWS_XDB_20070525_H_
#define	_SCWS_XDB_20070525_H_

#ifdef __cplusplus
extern "C" {
#endif

/* constant var define */
#define	XDB_FLOAT_CHECK		(3.14)
#define	XDB_TAGNAME			"XDB"
#define	XDB_MAXKLEN			0xf0
#define	XDB_VERSION			34			/* version: 3bit+5bit */

#include "xtree.h"

/* data structure for [Record] */
typedef struct xdb_pointer
{
	unsigned int off;
	unsigned int len;
}	xptr_st, *xptr_t;

typedef struct xdb_record
{
	unsigned int poff;
	xptr_st me;
	xptr_st value;
}	xrec_st, *xrec_t;

/* header struct */
struct xdb_header
{
	char tag[3];
	unsigned char ver;
	int base;
	int prime;
	unsigned int fsize;
	float check;
	char unused[12];
};

typedef struct
{
	int fd;					/* file descriptoin */
	int base;				/* basenum for hash count */
	int prime;				/* base prime for hash mod */
	unsigned int fsize;		/* total filesize */
	int version;			/* version: low 4bytes */
	char *fmap;				/* file content image by mmap (read only) */
	int mode;				/* xdb_open for write or read-only */
}	xdb_st, *xdb_t;

/* xdb: open the db, mode = r|w|n */
xdb_t xdb_open(const char *fpath, int mode);
xdb_t xdb_create(const char *fpath, int base, int prime);

/* read mode */
void *xdb_nget(xdb_t x, const char *key, int len, unsigned int *vlen);
void *xdb_get(xdb_t x, const char *key, unsigned int *vlen);

#ifdef DEBUG
void xdb_draw(xdb_t x);
#endif

/* return the xtree pointer */
xtree_t xdb_to_xtree(xdb_t x, xtree_t xt);

/* write mode */
void xdb_nput(xdb_t x, void *value, unsigned int vlen, const char *key, int len);
void xdb_put(xdb_t x, const char *value, const char *key);
void xdb_optimize(xdb_t x);

/* xdb: close the db */
void xdb_close(xdb_t x);
void xdb_version(xdb_t x);

#ifdef __cplusplus
}
#endif

#endif
