/**
 * @file xdb.c (xtree use file storage)
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifdef HAVE_CONFIG_H
#	include "config.h"
#endif

#ifdef WIN32
#	include "config_win32.h"
#endif

#include "xdb.h"
#include "lock.h"
#include <stdio.h>
#include <stdlib.h>
#ifndef WIN32
#	include <unistd.h>
#endif
#include <string.h>
#include <fcntl.h>
#include <sys/stat.h>
#include <sys/types.h>

#ifdef HAVE_MMAP
#   include <sys/mman.h>
#endif

static int _xdb_hasher(xdb_t x, const char *s, int len)
{
	unsigned int h = x->base;
	while (len--)
	{
		h += (h<<5);
		h ^= (unsigned char) s[len];
		h &= 0x7fffffff;
	}
	return (h % x->prime);
}

static void _xdb_read_data(xdb_t x, void *buf, unsigned int off, int len)
{
	/* check off & x->fsize? */
	if (off > x->fsize)
		return;
	
	/* fixed the len boundary!! */
	if ((off + len) > x->fsize)	
		len = x->fsize - off;

	if (x->fd >= 0)
	{
		lseek(x->fd, off, SEEK_SET);
		read(x->fd, buf, len);
	}
	else
	{
		memcpy(buf, x->fmap + off, len);
	}
        /* hightman.101230: fixed overflow, thanks to hovea on bbs */
	//memset((void *)((char *)buf + len), 0, 1);
}

/* recursive to search the matched record */
static void _xdb_rec_get(xdb_t x, xrec_t rec, const char *key, int len)
{
	unsigned char buf[XDB_MAXKLEN + 2];	// greater than: 255
	int cmp;

	if (rec->me.len == 0)
		return;

	// [left][right] = 16\0
	_xdb_read_data(x, buf, rec->me.off + 16, len + 1);
	cmp = memcmp(key, buf+1, len);
	if (!cmp)
		cmp = len - buf[0];	
	if (cmp > 0)
	{
		// right
		rec->poff = rec->me.off + sizeof(xptr_st);
		_xdb_read_data(x, &rec->me, rec->me.off + sizeof(xptr_st), sizeof(xptr_st));
		_xdb_rec_get(x, rec, key, len);
	}
	else if (cmp < 0)
	{
		// left
		rec->poff = rec->me.off;
		_xdb_read_data(x, &rec->me, rec->me.off, sizeof(xptr_st));
		_xdb_rec_get(x, rec, key, len);
	}
	else
	{
		// found!
		rec->value.off = rec->me.off + 17 + len;
		rec->value.len = rec->me.len - 17 - len;
	}
}

static xrec_t _xdb_rec_find(xdb_t x, const char *key, int len, xrec_t rec)
{	
	int i;
	
	if (rec == NULL)
		rec = (xrec_t) malloc(sizeof(xrec_st));

	memset(rec, 0, sizeof(xrec_st));
	i = (x->prime > 1 ? _xdb_hasher(x, key, len) : 0);
	rec->poff = i * sizeof(xptr_st) + sizeof(struct xdb_header);

	_xdb_read_data(x, &rec->me, rec->poff, sizeof(xptr_st));
	_xdb_rec_get(x, rec, key, len);
	return rec;
}

/* mode = r(readonly) | w(write&read) */
xdb_t xdb_open(const char *fpath, int mode)
{
	xdb_t x;
	struct stat st;
	struct xdb_header xhdr;

	/* create the new memory */
	if (!(x = (xdb_t ) malloc(sizeof(xdb_st))))
		return NULL;

	/* try to open & check the file */
	if ((x->fd = open(fpath, mode == 'w' ? O_RDWR : O_RDONLY)) < 0)
	{
#ifdef DEBUG
		perror("Failed to open the XDB file");
#endif
		free(x);
		return NULL;
	}

	/* check the file */
	if (fstat(x->fd, &st) || !S_ISREG(st.st_mode) || (x->fsize = st.st_size) <= 0)
	{
#ifdef DEBUG
		perror("Invalid XDB file");
#endif
		close(x->fd);
		free(x);
		return NULL;
	}

	/* check the XDB header: XDB+version(1bytes)+base+prime+fsize+<dobule check> = 19bytes */
	lseek(x->fd, 0, SEEK_SET);
	if ((read(x->fd, &xhdr, sizeof(xhdr)) != sizeof(xhdr))
		|| memcmp(xhdr.tag, XDB_TAGNAME, 3) || (xhdr.fsize != x->fsize))
	{
#ifdef DEBUG
		perror("Invalid XDB file format");
#endif
		close(x->fd);
		free(x);
		return NULL;
	}
	x->prime = xhdr.prime;
	x->base = xhdr.base;
	x->version = (int) xhdr.ver;
	x->fmap = NULL;
	x->mode = mode;

	/* lock the file in write mode */
	if (mode == 'w')
		_xdb_flock(x->fd, LOCK_EX);
	/* try mmap if readonly */
#ifdef HAVE_MMAP
	else
	{
		x->fmap = (char *) mmap(NULL, x->fsize, PROT_READ, MAP_SHARED, x->fd, 0);
		close(x->fd);
		x->fd = -1;

		if (x->fmap == (char *) MAP_FAILED)
		{
#ifdef DEBUG
			perror("Mmap() failed");
#endif
			free(x);
			return NULL;
		}
	}
#endif
	return x;
}

xdb_t xdb_create(const char *fpath, int base, int prime)
{
	xdb_t x;
	struct xdb_header xhdr;

	/* create the new memory */
	if (!(x = (xdb_t ) malloc(sizeof(xdb_st))))
		return NULL;

	/* try to open & check the file */
	if ((x->fd = open(fpath, (O_CREAT|O_RDWR|O_TRUNC|O_EXCL), 0600)) < 0)
	{
#ifdef DEBUG
		perror("Failed to open & create the db file");
#endif
		free(x);
		return NULL;
	}

	/* write the header */
	_xdb_flock(x->fd, LOCK_EX);
	x->prime = prime ? prime : 2047;
	x->base = base ? base : 0xf422f;
	x->fsize = sizeof(xhdr) + x->prime * sizeof(xptr_st);
	x->fmap = NULL;
	x->mode = 'w';
	memset(&xhdr, 0, sizeof(xhdr));
	memcpy(&xhdr.tag, XDB_TAGNAME, 3);
	xhdr.ver = XDB_VERSION;
	xhdr.prime = x->prime;
	xhdr.base = x->base;
	xhdr.fsize = x->fsize;
	xhdr.check = (float)XDB_FLOAT_CHECK;

	/* check the XDB header: XDB+version(1bytes)+base+prime+fsize+<dobule check> = 19bytes */
	lseek(x->fd, 0, SEEK_SET);
	write(x->fd, &xhdr, sizeof(xhdr));
	return x;
}

void xdb_close(xdb_t x)
{
	if (x == NULL)
		return;

#ifdef HAVE_MMAP
	if (x->fmap != NULL)
	{		
		munmap(x->fmap, x->fsize);
		x->fmap = NULL;
	}
#endif

	if (x->fd >= 0)
	{
		if (x->mode == 'w')
		{		
			lseek(x->fd, 12, SEEK_SET);
			write(x->fd, &x->fsize, sizeof(x->fsize));
			_xdb_flock(x->fd, LOCK_UN);
		}
		close(x->fd);
		x->fd = -1;
	}
	free(x);
}

/* read mode (value require free by user) */
void *xdb_nget(xdb_t x, const char *key, int len, unsigned int *vlen)
{
	xrec_st rec;
	void *value = NULL;

	if (x == NULL || key == NULL || len > XDB_MAXKLEN)
		return NULL;

	/* not found, return the poff(for write) */
	_xdb_rec_find(x, key, len, &rec);
	if (rec.value.len > 0)
	{
		/* auto append one byte with '\0' */		
		value = malloc(rec.value.len + 1);
		if (vlen != NULL)		
			*vlen = rec.value.len;
		_xdb_read_data(x, value, rec.value.off, rec.value.len);
                *((char *)value + rec.value.len) = '\0';
	}	
	return value;
}

void *xdb_get(xdb_t x, const char *key, unsigned int *vlen)
{
	if (x == NULL || key == NULL)
		return NULL;
	return xdb_nget(x, key, strlen(key), vlen);
}

/* write mode */
void xdb_nput(xdb_t x, void *value, unsigned int vlen, const char *key, int len)
{
	xrec_st rec;

	if (x == NULL || x->fd < 0 || key == NULL || len > XDB_MAXKLEN)
		return;

	/* not found, return the poff(for write) */	
	_xdb_rec_find(x, key, len, &rec);
	if (rec.value.len > 0 && vlen <= rec.value.len)
	{
		/* just replace */
		if (vlen > 0)
		{		
			lseek(x->fd, rec.value.off, SEEK_SET);
			write(x->fd, value, vlen);
		}
		if (vlen < rec.value.len)
		{
			vlen += rec.me.len - rec.value.len;
			lseek(x->fd, rec.poff + 4, SEEK_SET);
			write(x->fd, &vlen, sizeof(vlen));
		}
	}
	else if (vlen > 0)
	{
		/* insert for new data */
		unsigned char buf[512];
		xptr_st pnew;

		pnew.off = x->fsize;		
		memset(buf, 0, sizeof(buf));
		pnew.len = rec.me.len - rec.value.len;
		if (pnew.len > 0)
		{
			_xdb_read_data(x, buf, rec.me.off, pnew.len);
		}
		else
		{
			buf[16] = len;	// key len
			strncpy(buf + 17, key, len);
			pnew.len = 17 + len;
		}
		lseek(x->fd, pnew.off, SEEK_SET);
		write(x->fd, buf, pnew.len);
		write(x->fd, value, vlen);
		pnew.len += vlen;
		x->fsize += pnew.len;

		/* update noff & vlen -> poff */
		lseek(x->fd, rec.poff, SEEK_SET);
		write(x->fd, &pnew, sizeof(pnew));
	}
}

void xdb_put(xdb_t x, const char *value, const char *key)
{
	if (x == NULL || key == NULL)
		return;	
	xdb_nput(x, (void *) value, value ? strlen(value) : 0, key, strlen(key));
}

#ifdef DEBUG
/* draw the xdb to stdout */
struct draw_arg
{
	int depth;
	int count;
	int flag;
};

static void _xdb_draw_node(xdb_t x, xptr_t ptr, struct draw_arg *arg, int depth, char *icon1)
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
	if (ptr->len == 0)
		printf("<NULL>\n");	
	else
	{
		unsigned char buf[XDB_MAXKLEN + 18];		// greater than 18 = sizeof(xptr_st)*2+1
		int vlen, voff;

		vlen = sizeof(buf) - 1;
		if (vlen > ptr->len)
			vlen = ptr->len;

		_xdb_read_data(x, buf, ptr->off, vlen);
		vlen = ptr->len - buf[16] - 17;
		voff = ptr->off + buf[16] + 17;

		printf("%.*s (vlen=%d, voff=%d)\n", buf[16], buf+17, vlen, voff);

		arg->count++;
		depth++;
		if (depth > arg->depth)
			arg->depth = depth;

		// draw the left & right;
		arg->flag = 'L';
		memcpy(ptr, buf, sizeof(xptr_st));
		_xdb_draw_node(x, ptr, arg, depth, icon2);

		arg->flag = 'R';
		memcpy(ptr, buf + sizeof(xptr_st), sizeof(xptr_st));
		_xdb_draw_node(x, ptr, arg, depth, icon2);
	}
	free(icon2);
}

void xdb_draw(xdb_t x)
{
	int i;
	struct draw_arg arg;
	xptr_st ptr;

	if (!x) return;

	xdb_version(x);
	for (i = 0; i < x->prime; i++)
	{		
		arg.depth = 0;
		arg.count = 0;
		arg.flag = 'T';

		_xdb_read_data(x, &ptr, i * sizeof(xptr_st) + sizeof(struct xdb_header), sizeof(xptr_st));
		_xdb_draw_node(x, &ptr, &arg, 0, "");

		printf("-----------------------------------------\n");
		printf("Tree(xdb) [%d] max_depth: %d nodes_num: %d\n", i, arg.depth, arg.count);
	}
}
#endif

/* optimize the xdb */
typedef struct xdb_cmper
{
	xptr_st ptr;
	char *key;
}	xcmper_st;

static void _xdb_count_nodes(xdb_t x, xptr_t ptr, int *count)
{
	int off;
	if (ptr->len == 0)
		return;

	*count += 1;
	off = ptr->off;

	/* left & right */
	_xdb_read_data(x, ptr, off, sizeof(xptr_st));
	_xdb_count_nodes(x, ptr, count);

	_xdb_read_data(x, ptr, off + sizeof(xptr_st), sizeof(xptr_st));
	_xdb_count_nodes(x, ptr, count);
}

#ifdef HAVE_STRNDUP
#define	_mem_ndup		strndup
#else
static inline void *_mem_ndup(const char *src, int len)
{
	char *dst;
	dst = malloc(len+1);
	memcpy(dst, src, len);
	dst[len] = '\0';
	return dst;
}
#endif

static void _xdb_load_nodes(xdb_t x, xptr_t ptr, xcmper_st *nodes, int *count)
{
	int i;
	unsigned char buf[XDB_MAXKLEN + 18];

	if (ptr->len == 0)
		return;

	i = sizeof(buf)-1;
	if (i > (int)ptr->len)
		i = ptr->len;

	_xdb_read_data(x, buf, ptr->off, i);

	i = *count;
	nodes[i].ptr.off = ptr->off;
	nodes[i].ptr.len = ptr->len;
	nodes[i].key = (char *) _mem_ndup(buf + 17, buf[16]);
	*count = i+1;

	/* left & right */
	memcpy(ptr, buf, sizeof(xptr_st));
	_xdb_load_nodes(x, ptr, nodes, count);

	memcpy(ptr, buf + sizeof(xptr_st), sizeof(xptr_st));
	_xdb_load_nodes(x, ptr, nodes, count);
}

static void _xdb_reset_nodes(xdb_t x, xcmper_st *nodes, int low, int high, unsigned int poff)
{
	xptr_st ptr = { 0, 0 };

	if (low <= high)
	{
		int mid = (low + high)>>1;

		memcpy(&ptr, &nodes[mid].ptr, sizeof(xptr_st));
		
		_xdb_reset_nodes(x, nodes, low, mid-1, ptr.off);
		_xdb_reset_nodes(x, nodes, mid+1, high, ptr.off + sizeof(xptr_st));
	}

	/* save it */
	lseek(x->fd, poff, SEEK_SET);
	write(x->fd, &ptr, sizeof(xptr_st));
}

static int _xdb_node_cmp(a, b)
	xcmper_st *a, *b;
{
	return strcmp(a->key, b->key);
}

void xdb_optimize(xdb_t x)
{
	int i, cnt, poff;
	xptr_st ptr, head;
	xcmper_st *nodes;

	if (x == NULL || x->fd < 0)
		return;	

	for (i = 0; i < x->prime; i++)
	{
		poff = i * sizeof(xptr_st) + sizeof(struct xdb_header);
		_xdb_read_data(x, &head, poff, sizeof(xptr_st));

		cnt = 0;
		ptr = head;
		_xdb_count_nodes(x, &ptr, &cnt);

		if (cnt > 2)
		{
			nodes = (xcmper_st *) malloc(sizeof(xcmper_st) * cnt);
			
			cnt = 0;
			ptr = head;
			_xdb_load_nodes(x, &ptr, nodes, &cnt);
			
			qsort(nodes, cnt, sizeof(xcmper_st), _xdb_node_cmp);
			_xdb_reset_nodes(x, nodes, 0, cnt - 1, poff);

			/* free the nodes & key pointer */
			while (cnt--)
				free(nodes[cnt].key);
			free(nodes);
		}
	}
}

void xdb_version(xdb_t x)
{
	printf("%s/%d.%d (base=%d, prime=%d)\n", XDB_TAGNAME,
		(x->version >> 5), (x->version & 0x1f), x->base, x->prime);
}

/* convert xdb file to xtree struct(memory) */
static void _xdb_to_xtree_node(xdb_t x, xtree_t xt, xptr_t ptr)
{
	unsigned char *buf;
	void *value;
	int voff;

	if (ptr->len == 0)
		return;

	buf = (unsigned char *) malloc(ptr->len + 1);
	_xdb_read_data(x, buf, ptr->off, ptr->len);

	/* save the key & value -> xtree */
	voff = buf[16] + 17;

	/* 2009-09-22, 11:29, Mistruster: posted on bbs */
	if (voff >= (int)ptr->len)
		return;
	value = pmalloc(xt->p, ptr->len - voff);
	memcpy(value, buf + voff, ptr->len - voff);
	xtree_nput(xt, value, ptr->len - voff, buf + 17, buf[16]);

	/* left & right */
	memcpy(ptr, buf, sizeof(xptr_st));
	_xdb_to_xtree_node(x, xt, ptr);

	memcpy(ptr, buf + sizeof(xptr_st), sizeof(xptr_st));
	_xdb_to_xtree_node(x, xt, ptr);

	free(buf);
}

xtree_t xdb_to_xtree(xdb_t x, xtree_t xt)
{
	int i = 0;
	xptr_st ptr;

	if (!x)
		return NULL;

	if (!xt && !(xt = xtree_new(x->base, x->prime)))
		return NULL;
	
    do
	{
		_xdb_read_data(x, &ptr, i * sizeof(xptr_st) + sizeof(struct xdb_header), sizeof(xptr_st));
		_xdb_to_xtree_node(x, xt, &ptr);
	}
    while (++i < x->prime);

	return xt;
}

