/**
 * @file xtree.c
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifdef HAVE_CONFIG_H
#	include "config.h"
#endif

#include "xtree.h"
#include "xdb.h"
#include <stdio.h>
#include <stdlib.h>
#ifndef WIN32
#	include <unistd.h>
#endif
#include <string.h>

/* private static functions */
static int _xtree_hasher(xtree_t xt, const char *s, int len)
{
	unsigned int h = xt->base;
	while (len--)
	{
		h += (h<<5);
		h ^= (unsigned char) s[len];
		h &= 0x7fffffff;
	}
	return (h % xt->prime);
}

static node_t _xtree_node_search(node_t head, node_t **pnode, const char *key, int len)
{
	int cmp;	
	
	cmp = memcmp(key, head->key, len);
	if (cmp == 0)
		cmp = len - strlen(head->key);
	
	if (cmp != 0)	
	{
		node_t *next;

		next = (cmp > 0 ? &head->right : &head->left);
		if (*next == NULL)
		{
			if (pnode != NULL)
				*pnode = next;
			return NULL;
		}
		return _xtree_node_search(*next, pnode, key, len);
	}
	return head;
}

static node_t _xtree_node_find(xtree_t xt, node_t **pnode, const char *key, int len)
{	
	int i;
	i = (xt->prime > 1 ? _xtree_hasher(xt, key, len) : 0);
	if (xt->trees[i] == NULL)
	{
		if (pnode != NULL) 
			*pnode = &xt->trees[i];
		return NULL;
	}
	return _xtree_node_search(xt->trees[i], pnode, key, len);
}

/* public functions */
xtree_t xtree_new(int base, int prime)
{
	xtree_t xnew;
	pool_t p;

	p = pool_new();
	xnew = pmalloc(p, sizeof(xtree_st));
	xnew->p = p;
	xnew->base = (base ? base : 0xf422f);
	xnew->prime = (prime ? prime :  31);
	xnew->count = 0;
	xnew->trees = (node_t *) pmalloc_z(p, sizeof(node_t) * xnew->prime);
	return xnew;
}

void xtree_free(xtree_t xt)
{
	if (xt)
		pool_free(xt->p);
}

void xtree_nput(xtree_t xt, void *value, int vlen, const char *key, int len)
{
	node_t node, *pnode;

	if (xt == NULL || key == NULL || len == 0)
		return;

	if ((node = _xtree_node_find(xt, &pnode, key, len)) != NULL)
	{
		node->value = value;
		node->vlen = vlen;
		return;
	}
	
	if (value != NULL)
	{	
		*pnode = node = (node_t) pmalloc(xt->p, sizeof(node_st));
		node->key = pstrndup(xt->p, key, len);
		node->value = value;
		node->vlen = vlen;
		node->left = NULL;
		node->right = NULL;
	}
}

void xtree_put(xtree_t xt, const char *value, const char *key)
{
	if (xt != NULL && key != NULL)
		xtree_nput(xt, (void *) value, value ? strlen(value) : 0, key, strlen(key));
}

void *xtree_nget(xtree_t xt, const char *key, int len, int *vlen)
{
	node_t node;

	if (xt == NULL || key == NULL || len == 0
		|| !(node = _xtree_node_find(xt, NULL, key, len)))
	{
		return NULL;
	}

	if (vlen != NULL)
		*vlen = node->vlen;
	return node->value;
}

void *xtree_get(xtree_t xt, const char *key, int *vlen)
{
	if (xt == NULL || key == NULL)
		return NULL;
	
	return xtree_nget(xt, key, strlen(key), vlen);
}

/*
void xtree_ndel(xtree_t xt, const char *key, int len)
{
	xtree_nput(xt, NULL, 0, key, len);
}

void xtree_del(xtree_t xt, const char *key)
{
	if (xt == NULL || key == NULL)
		return;
	
	xtree_ndel(xt, key, strlen(key));
}
*/

#ifdef DEBUG
/* draw the xtree to stdout */
struct draw_arg
{
	int depth;
	int count;
	int flag;
};

static void _xtree_draw_node(node_t node, struct draw_arg *arg, int depth, char *icon1)
{
	char *icon2;
	
	icon2 = malloc(strlen(icon1) + 4);
	strcpy(icon2, icon1);

	// output the flag & icon
	if (arg->flag == 'T')	
		printf("(Ｔ) ");	
	else
	{
		printf("%s", icon2);
		if (arg->flag  == 'L')
		{
			strcat(icon2, " ┃");
			printf(" ┟(Ｌ) ");
		}
		else
		{
			strcat(icon2, " 　");
			printf(" └(Ｒ) ");
		}
	}

	// draw the node data
	if (node == NULL)	
		printf("<NULL>\n");	
	else
	{
		printf("%s (value on 0x%x vlen=%d)\n", node->key, (unsigned int)node->value, node->vlen);
		
		arg->count++;
		depth++;
		if (depth > arg->depth) 
			arg->depth = depth;

		// draw the left & right
		arg->flag = 'L';
		_xtree_draw_node(node->left, arg, depth, icon2);

		arg->flag = 'R';
		_xtree_draw_node(node->right, arg, depth, icon2);
	}
	free(icon2);
}

void xtree_draw(xtree_t xt)
{
	int i;
	struct draw_arg arg;	

	if (!xt)
		return;	

	for (i = 0; i < xt->prime; i++)
	{		
		arg.depth = 0;
		arg.count = 0;
		arg.flag = 'T';
		_xtree_draw_node(xt->trees[i], &arg, 0, "");
		printf("-----------------------------------------\n");
		printf("Tree [%d] max_depth: %d nodes_num: %d\n", i, arg.depth, arg.count);
	}
}
#endif

/* optimize the tree */
static void _xtree_count_nodes(node_t node, int *count)
{
	if (node == NULL)
		return;

	*count += 1;
	_xtree_count_nodes(node->left, count);
	_xtree_count_nodes(node->right, count);
}

static void _xtree_load_nodes(node_t node, node_t *nodes, int *count)
{
	int i = *count;
	if (node == NULL)
		return;
	
	nodes[i] = node;
	*count = ++i;
	_xtree_load_nodes(node->left, nodes, count);
	_xtree_load_nodes(node->right, nodes, count);
}

static void _xtree_reset_nodes(node_t *nodes, int low, int high, node_t *curr)
{
	if (low <= high)
	{
		int mid = (low + high)>>1;

		*curr = nodes[mid];
		_xtree_reset_nodes(nodes, low, mid-1, &(*curr)->left);
		_xtree_reset_nodes(nodes, mid + 1, high, &(*curr)->right);
	}
	else
	{
		*curr = NULL;
	}
}

#ifdef WIN32
static int _xtree_node_cmp(node_t *a, node_t *b)
#else
static int _xtree_node_cmp(a, b)
	node_t *a, *b;
#endif
{
	return strcmp((*a)->key, (*b)->key);
}

void xtree_optimize(xtree_t xt)
{
	int i, cnt;
	node_t *nodes;

	if (!xt)
		return;	

	for (i = 0; i < xt->prime; i++)
	{
		cnt = 0;
		_xtree_count_nodes(xt->trees[i], &cnt);
		if (cnt > 2)			
		{
			nodes = (node_t *)malloc(sizeof(node_t) * cnt);
			cnt = 0;
			_xtree_load_nodes(xt->trees[i], nodes, &cnt);
			qsort(nodes, cnt, sizeof(node_t), _xtree_node_cmp);
			_xtree_reset_nodes(nodes, 0, cnt - 1, &xt->trees[i]);
			free(nodes);
		}
	}
}

/* convert xtree to xdb file */
static void _xtree_to_xdb_node(node_t node, xdb_t x)
{
	if (node == NULL)
		return;

	xdb_nput(x, node->value, node->vlen, node->key, strlen(node->key));
	_xtree_to_xdb_node(node->left, x);
	_xtree_to_xdb_node(node->right, x);
}

void xtree_to_xdb(xtree_t xt, const char *fpath)
{
	xdb_t x;
	int i;

	if (!xt || !(x = xdb_create(fpath, xt->base, xt->prime)))
		return;

	for (i = 0; i < xt->prime; i++)
	{
		_xtree_to_xdb_node(xt->trees[i], x);
	}

	xdb_close(x);
}

