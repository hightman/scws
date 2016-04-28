/**
 * @file pool.c
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#include "pool.h"
#include <stdio.h>
#include <stdlib.h>
#ifndef WIN32
#	include <unistd.h>
#endif
#include <string.h>

/** pool memory management */
static void _pool_append_clean(pool_t p, void *obj)
{
	struct pclean *c;

	p->size += sizeof(struct pclean);
	c = (struct pclean *) malloc(sizeof(struct pclean));
	c->obj = obj;
	c->nxt = p->clean;
	p->clean = c;
}

static void _pool_heap_new(pool_t p)
{
	if (p->heap != NULL)	
		p->dirty += (p->heap->size - p->heap->used);
	
	p->heap = (struct pheap *) malloc(POOL_BLK_SIZ);
	p->heap->size = POOL_BLK_SIZ - sizeof(struct pheap);
	p->heap->used = 0;
	p->size += POOL_BLK_SIZ;

	_pool_append_clean(p, (void *) p->heap);
}


pool_t pool_new()
{	
	pool_t p;

	p = (pool_t) malloc(sizeof(pool_st));
	p->size = sizeof(pool_st);
	p->dirty = 0;
	p->heap = NULL;
	p->clean = NULL;
	_pool_heap_new(p);
	return p;
}

void pool_free(pool_t p)
{
	struct pclean *cur, *nxt;
	
	cur = p->clean;
	while (cur != NULL)
	{
		free(cur->obj);
		nxt = cur->nxt;
		free(cur);
		cur = nxt;
	}
	free(p);
}

void *pmalloc(pool_t p, int size)
{
	void *block;

	/* big request */
	if (size > (p->heap->size / 4))
	{
		block = malloc(size);
		p->size += size;
		_pool_append_clean(p, block);
		return block;
	}

	/* memory align (>=4) */
	if (size & 0x04)
	{
		while (p->heap->used & 0x03)
		{
			p->dirty++;
			p->heap->used++;
		}		
	}

	/* not enough? */
	if (size > (p->heap->size - p->heap->used))	
		_pool_heap_new(p);
	
	block = (void *)((char *) p->heap->block + p->heap->used);
	p->heap->used += size;
	return block;
}

void *pmalloc_x(pool_t p, int size, char c)
{
	void *result = pmalloc(p, size);	
	memset(result, c, size);
	return result;
}  

void *pmalloc_z(pool_t p, int size)
{
	return pmalloc_x(p, size, 0);
}

char *pstrdup(pool_t p, const char *src)
{
	char *dst;
	int len;

	if (src == NULL) 
		return NULL;

	len = strlen(src) + 1;
	dst = (char *) pmalloc(p, len);
	memcpy(dst, src, len);
	return dst;
}

char *pstrndup(pool_t p, const char *src, int len)
{
	char *dst;

	if (src == NULL) 
		return NULL;

	dst = (char *) pmalloc(p, len + 1);
	memcpy(dst, src, len);
	dst[len] = '\0';

	return dst;
}

