/**
 * @file xtree.h
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifndef	_SCWS_XTREE_20070525_H_
#define	_SCWS_XTREE_20070525_H_

#ifdef __cplusplus
extern "C" {
#endif

/* pool required */
#include "pool.h"

/* data structure for Hash+Tree */
typedef struct tree_node node_st, *node_t;
struct tree_node
{
	char *key;
	void *value;
	int vlen;
	node_t left;
	node_t right;
};

typedef struct 
{	
	pool_t p;		/* pool for memory manager */
	int base;		/* base number for hasher (prime number recommend) */
	int prime;		/* good prime number for hasher */
	int count;		/* total nodes */
	node_t *trees;	/* trees [total=prime+1] */
}	xtree_st, *xtree_t;

/* xtree: api */
int xtree_hasher(xtree_t xt, const char *key, int len);
xtree_t xtree_new(int base, int prime);	/* create a new hasxtree */
void xtree_free(xtree_t xt);			/* delete & free xthe xtree */

void xtree_put(xtree_t xt, const char *value, const char *key);
void xtree_nput(xtree_t xt, void *value, int vlen, const char *key, int len);

void *xtree_get(xtree_t xt, const char *key, int *vlen);
void *xtree_nget(xtree_t xt, const char *key, int len, int *vlen);

/*
void xtree_del(xtree_t xt, const char *key);
void xtree_ndel(xtree_t xt, const char *key, int len);
*/

#ifdef DEBUG
void xtree_draw(xtree_t xt);
#endif

void xtree_optimize(xtree_t xt);
void xtree_to_xdb(xtree_t xt, const char *fpath);

#ifdef __cplusplus
}
#endif

#endif
