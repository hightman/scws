/*
 * @file scws.c (core segment functions)
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id  $
 */

#ifdef HAVE_CONFIG_H
#	include "config.h"
#endif

#ifdef WIN32
#	include "config_win32.h"
#endif
 
#include "scws.h"
#include "xdict.h"
#include "rule.h"
#include "charset.h"
#include "darray.h"
#include "xtree.h"
#include <stdio.h>
#include <math.h>
#include <stdlib.h>
#include <string.h>

/* quick macro define for frequency usage */
#define	SCWS_IS_SPECIAL(x,l)	scws_rule_checkbit(s->r,x,l,SCWS_RULE_SPECIAL)
#define	SCWS_IS_NOSTATS(x,l)	scws_rule_checkbit(s->r,x,l,SCWS_RULE_NOSTATS)
#define	SCWS_CHARLEN(x)			s->mblen[(x)]
#define	SCWS_IS_ALNUM(x)		(((x)>=48&&(x)<=57)||((x)>=65&&(x)<=90)||((x)>=97&&(x)<=122))
#define	SCWS_IS_ALPHA(x)		(((x)>=65&&(x)<=90)||((x)>=97&&(x)<=122))
#define	SCWS_IS_UALPHA(x)		((x)>=65&&(x)<=90)
#define	SCWS_IS_DIGIT(x)		((x)>=48&&(x)<=57)
#define	SCWS_IS_WHEAD(x)		((x) & SCWS_ZFLAG_WHEAD)
#define	SCWS_IS_ECHAR(x)		((x) & SCWS_ZFLAG_ENGLISH)
#define	SCWS_NO_RULE1(x)		(((x) & (SCWS_ZFLAG_SYMBOL|SCWS_ZFLAG_ENGLISH))||(((x) & (SCWS_ZFLAG_WHEAD|SCWS_ZFLAG_NR2)) == SCWS_ZFLAG_WHEAD))
///#define	SCWS_NO_RULE2(x)		(((x) & SCWS_ZFLAG_ENGLISH)||(((x) & (SCWS_ZFLAG_WHEAD|SCWS_ZFLAG_N2)) == SCWS_ZFLAG_WHEAD))
#define	SCWS_NO_RULE2			SCWS_NO_RULE1
#define	SCWS_MAX_EWLEN			33
///hightman.070706: char token
#define	SCWS_CHAR_TOKEN(x)		((x)=='('||(x)==')'||(x)=='['||(x)==']'||(x)=='{'||(x)=='}'||(x)==':'||(x)=='"')	
///hightman.070814: max zlen = ?? (4 * zlen * zlen = ??)
#define	SCWS_MAX_ZLEN			128
#define	SCWS_EN_IDF(x)			(float)(2.5*logf(x))

static const char *attr_en = "en";
static const char *attr_un = "un";
static const char *attr_nr = "nr";
static const char *attr_na = "!";

/* create scws engine */
scws_t scws_new()
{
	scws_t s;
	s = (scws_t) malloc(sizeof(scws_st));
    if (s == NULL)
        return s;
	memset(s, 0, sizeof(scws_st));
	s->mblen = charset_table_get(NULL);
	s->off = s->len = 0;
	s->wend = -1;

	return s;
}

/* hightman.110320: fork scws */
scws_t scws_fork(scws_t p)
{
	scws_t s = scws_new();

	if (p != NULL && s != NULL)
	{
		s->mblen = p->mblen;
		s->mode = p->mode;
		// fork dict/rules
		s->r = scws_rule_fork(p->r);
		s->d = xdict_fork(p->d);
	}

	return s;
}

/* close & free the engine */
void scws_free(scws_t s)
{
	if (s->d)
	{
		xdict_close(s->d);
		s->d = NULL;
	}
	if (s->r)
	{
		scws_rule_free(s->r);
		s->r = NULL;
	}
	free(s);
}

/* add a dict into scws */
int scws_add_dict(scws_t s, const char *fpath, int mode)
{
	xdict_t xx;
	if (mode & SCWS_XDICT_SET)
	{
		xdict_close(s->d);
		mode ^= SCWS_XDICT_SET;
		s->d = NULL;
	}
	xx = s->d;
	s->d = xdict_add(s->d, fpath, mode, s->mblen);
	return (xx == s->d ? -1 : 0);
}

/* set the dict & open it */
int scws_set_dict(scws_t s, const char *fpath, int mode)
{
	return scws_add_dict(s, fpath, mode | SCWS_XDICT_SET);
}

void scws_set_charset(scws_t s, const char *cs)
{
	s->mblen = charset_table_get(cs);
}

void scws_set_rule(scws_t s, const char *fpath)
{
	if (s->r != NULL)
		scws_rule_free(s->r);

	s->r = scws_rule_new(fpath, s->mblen);	
}

/* set ignore symbol or multi segments */
void scws_set_ignore(scws_t s, int yes)
{
	if (yes == SCWS_YEA)
		s->mode |= SCWS_IGN_SYMBOL;

	if (yes == SCWS_NA)
		s->mode &= ~SCWS_IGN_SYMBOL;
}

void scws_set_multi(scws_t s, int mode)
{
	s->mode &= ~SCWS_MULTI_MASK;

	if (mode & SCWS_MULTI_MASK)	
		s->mode |= mode;
}

void scws_set_debug(scws_t s, int yes)
{
	if (yes == SCWS_YEA)
		s->mode |= SCWS_DEBUG;

	if (yes == SCWS_NA)
		s->mode &= ~SCWS_DEBUG;
}

void scws_set_duality(scws_t s, int yes)
{
	if (yes == SCWS_YEA)
		s->mode |= SCWS_DUALITY;

	if (yes == SCWS_NA)
		s->mode &= ~SCWS_DUALITY;
}

/* send the text buffer & init some others */
void scws_send_text(scws_t s, const char *text, int len)
{
	s->txt = (unsigned char *) text;
	s->len = len;
	s->off = 0;
}

/* get some words, if these is not words, return NULL */
#define	SCWS_PUT_RES(o,i,l,a)									\
do {															\
	scws_res_t res;												\
	res = (scws_res_t) malloc(sizeof(struct scws_result));		\
	res->off = o;												\
	res->idf = i;												\
	res->len = l;												\
	strncpy(res->attr, a, 2);									\
	res->attr[2] = '\0';										\
	res->next = NULL;											\
	if (s->res1 == NULL)										\
		s->res1 = s->res0 = res;								\
	else														\
	{															\
		s->res1->next = res;									\
		s->res1 = res;											\
	}															\
} while(0)

/* single bytes segment (纯单字节字符) */
#define	PFLAG_WITH_MB		0x01
#define	PFLAG_ALNUM			0x02
#define	PFLAG_VALID			0x04
#define	PFLAG_DIGIT			0x08
#define	PFLAG_ADDSYM		0x10
#define	PFLAG_ALPHA			0x20
#define	PFLAG_LONGDIGIT		0x40
#define	PFLAG_LONGALPHA		0x80

static void _str_toupper(char *src, char *dst)
{
	while (*src)
	{
		*dst++ = *src++;
		if (dst[-1] >= 'a' && dst[-1] <= 'z')
			dst[-1] ^= 0x20;
	}
}

static void _str_tolower(char *src, char *dst)
{
	while (*src)
	{
		*dst++ = *src++;
		if (dst[-1] >= 'A' && dst[-1] <= 'Z')
			dst[-1] ^= 0x20;
	}
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

static void _scws_alnum_multi(scws_t s, int start, int wlen)
{
	char chunk[SCWS_MAX_EWLEN];
	int i, j, k, ch, pflag;
	unsigned char *txt;
	float idf;

	txt = s->txt;
	pflag = 0;
	for (i = j = k = 0; i < wlen; i++)
	{
		ch = txt[start + i];
		if (SCWS_IS_DIGIT(ch))
		{
			if (pflag & PFLAG_DIGIT)
				continue;
			if (pflag != 0)
			{
				chunk[j++] = (char) (i-k);
				k = i;
			}
			pflag = PFLAG_DIGIT;
		}
		else if (SCWS_IS_ALPHA(ch))
		{
			if (pflag & PFLAG_ALPHA)
				continue;
			if (pflag != 0)
			{
				chunk[j++] = (char) (i-k);
				k = i;
			}
			pflag = PFLAG_ALPHA;
		}
		else
		{
			if (pflag & PFLAG_ADDSYM)
				continue;
			if (pflag != 0)
			{
				chunk[j++] = (char) (i-k);
				k = i;
			}
			pflag = PFLAG_ADDSYM;
		}
	}

	if (j > 0)
	{	
		chunk[j] = (char) (i-k);
		ch = start;
		for (i = 0; i <= j; i++)
		{
			if (!SCWS_IS_ALNUM(txt[ch]))
			{
				// just skip
			}
			else if (chunk[i] == 1)
			{
				if (i > 0 && chunk[i-1] > 1 && (i != 1 || i != j))
				{
					if (!SCWS_IS_ALNUM(txt[ch-1]))
					{
						idf = SCWS_EN_IDF(chunk[i]);
						SCWS_PUT_RES(ch, idf, chunk[i], attr_en);
					}
					else
					{
						idf = SCWS_EN_IDF(chunk[i-1]+1);
						SCWS_PUT_RES(ch - chunk[i-1], idf, chunk[i-1]+1, attr_en);
					}
				}
				if (i < j && (i != 0 || j != 1))
				{
					if (!SCWS_IS_ALNUM(txt[ch+1]))
					{
						idf = SCWS_EN_IDF(chunk[i]);
						SCWS_PUT_RES(ch, idf, chunk[i], attr_en);
					}
					else
					{
						idf = SCWS_EN_IDF(chunk[i+1]+1);
						SCWS_PUT_RES(ch, idf, chunk[i+1]+1, attr_en);
					}
				}
			}
			else
			{
				idf = SCWS_EN_IDF(chunk[i]);
				SCWS_PUT_RES(ch, idf, chunk[i], attr_en);
			}
			ch += chunk[i];
		}
	}
}

static void _scws_ssegment(scws_t s, int end)
{
	int start, wlen, ch, pflag, ipflag = 0;
	unsigned char *txt;
	float idf;

	start = s->off;
	wlen = end - start;

	/* check special words (need strtoupper) */
	if (wlen > 1)
	{	
		txt = (char *) _mem_ndup(s->txt + start, wlen);	
		_str_toupper(txt, txt);
		if (SCWS_IS_SPECIAL(txt, wlen))
		{
			SCWS_PUT_RES(start, 9.5, wlen, "nz");
			free(txt);
			return;
		}
		free(txt);
	}

	txt = s->txt;	
	/* check brief words such as S.H.E M.R. */	
	if (SCWS_IS_ALPHA(txt[start]) && txt[start+1] == '.')
	{
		for (ch = start + 2; ch < end; ch++)
		{
			if (!SCWS_IS_ALPHA(txt[ch])) break;
			ch++;
			if (ch == end || txt[ch] != '.') break;
		}
		if (ch == end)
		{
			SCWS_PUT_RES(start, 7.5, wlen, "nz");
			return;
		}
	}

	/* 取出单词及标点. 数字允许一个点且下一个为数字,不连续的. 字母允许一个不连续的' */
	while (start < end)
	{
		ch = txt[start++];
		if (ipflag && ch != 0x2e && !SCWS_IS_DIGIT(ch))
			ipflag = 0;
		if (SCWS_IS_ALNUM(ch))
		{
			pflag = SCWS_IS_DIGIT(ch) ? PFLAG_DIGIT : 0;
			wlen = 1;
			while (start < end)
			{
				ch = txt[start];
				if (pflag & PFLAG_DIGIT)
				{
					if (!SCWS_IS_DIGIT(ch))
					{
						// check percent % = 0x25
						if (ch == 0x25 && !SCWS_IS_DIGIT(txt[start+1]))
						{
							start++;
							wlen++;
							break;
						}
						if (ipflag)
							break;
						// special for IP address or version number? (find out all digit + dot)
						if (ch == 0x2e && (pflag & PFLAG_ADDSYM))
						{
							ipflag = 1;
							while(--wlen && txt[--start] != 0x2e);
							pflag = 0;
							break;
						}
						// strict must add: !$this->_is_digit(ord($this->txt[$start+1])))
						if ((pflag & PFLAG_ADDSYM) || ch != 0x2e || !SCWS_IS_DIGIT(txt[start+1]))
							break;
						pflag |= PFLAG_ADDSYM;												
					}
				}
				else
				{
					/* hightman.110419: - 出现在字母中间允许连接(0x2d), _ 允许连接(0x5f) */
					if ((ch == 0x2d || ch == 0x5f) && SCWS_IS_ALPHA(txt[start+1]))
						pflag |= PFLAG_ADDSYM;
					else if (!SCWS_IS_ALPHA(ch))
					{
						if ((pflag & PFLAG_ADDSYM) || ch != 0x27 || !SCWS_IS_ALPHA(txt[start+1]))
							break;
						pflag |= PFLAG_ADDSYM;
					}
				}
				start++;
				wlen++;
				if (wlen >= SCWS_MAX_EWLEN)
					break;
			}
			idf = SCWS_EN_IDF(wlen);
			SCWS_PUT_RES(start-wlen, idf, wlen, attr_en);
			if ((s->mode & SCWS_MULTI_DUALITY) && (pflag & PFLAG_ADDSYM))
				_scws_alnum_multi(s, start-wlen, wlen);
		}
		else if (!(s->mode & SCWS_IGN_SYMBOL))
		{
			SCWS_PUT_RES(start-1, 0.0, 1, attr_un);
		}
	}
}

/* multibyte segment */
static int _scws_mget_word(scws_t s, int i, int j)
{
	int r, k;
	word_t item;

	if (!(s->wmap[i][i]->flag & SCWS_ZFLAG_WHEAD))
		return i;

	for (r=i, k=i+1; k <= j; k++)
	{
		item = s->wmap[i][k];
		if (item && (item->flag & SCWS_WORD_FULL))
		{
			r = k;
			if (!(item->flag & SCWS_WORD_PART))
				break;					
		}
	}
	return r;
}

static void _scws_mset_word(scws_t s, int i, int j)
{
	word_t item;

	item = s->wmap[i][j];
	/* hightman.070705: 加入 item == null 判断, 防止超长词(255字以上)unsigned char溢出 */
	if ((item == NULL) || ((s->mode & SCWS_IGN_SYMBOL) 
      && !SCWS_IS_ECHAR(item->flag) && !memcmp(item->attr, attr_un, 2)))
		return;

	/* hightman.070701: 散字自动二元聚合 */
	if (s->mode & SCWS_DUALITY)
	{
		int k = s->zis;

		if (i == j && !SCWS_IS_ECHAR(item->flag) && memcmp(item->attr, attr_un, 2))
		{
			s->zis = i;
			if (k < 0)
				return;
			
			i = (k & ~SCWS_ZIS_USED);
			if ((i != (j-1)) || (!(k & SCWS_ZIS_USED) && s->wend == i))
			{
				SCWS_PUT_RES(s->zmap[i].start, s->wmap[i][i]->idf, (s->zmap[i].end - s->zmap[i].start), s->wmap[i][i]->attr);
				if (i != (j-1))
					return;
			}
			s->zis |= SCWS_ZIS_USED;
		}
		else
		{
			if ((k >= 0) && (!(k & SCWS_ZIS_USED) || (j > i)))
			{
				k &= ~SCWS_ZIS_USED;
				SCWS_PUT_RES(s->zmap[k].start, s->wmap[k][k]->idf, (s->zmap[k].end - s->zmap[k].start), s->wmap[k][k]->attr);
			}
			if (j > i)
				s->wend = j + 1;
			s->zis = -1;
		}
	}
		
	SCWS_PUT_RES(s->zmap[i].start, item->idf, (s->zmap[j].end - s->zmap[i].start), item->attr);

	// hightman.070902: multi segment
	// step1: split to short words
	if ((j-i) > 1)
	{
		int n, k, m = i;
		if (s->mode & SCWS_MULTI_SHORT)
		{
			while (m < j)
			{
				k = m;
				// hightman.111223: multi short enhanced
				for (n = m + 1; n <= j; n++)
				{
					// 3 chars at most
					if ((n == j && m == i) || (n - m) > 2) break;
					item = s->wmap[m][n];	
					if (!item) continue;
					// first shortest or last longest word
					if ((item->flag & SCWS_WORD_FULL) && (k == m || n == j))
						k = n;
					if (!(item->flag & SCWS_WORD_PART)) break;
				}
				// short word not found, stop to find, passed to next loop
				if (k == m)
					break;
				
				// save the short word
				item = s->wmap[m][k];
				SCWS_PUT_RES(s->zmap[m].start, item->idf, (s->zmap[k].end - s->zmap[m].start), item->attr);
				// find the next word or go to prev for duality last word
				if ((m = k + 1) == j)
				{
					m--;
					break;
				}
			}
		}

		if (s->mode & SCWS_MULTI_DUALITY)
		{
			while (m < j)
			{
				SCWS_PUT_RES(s->zmap[m].start, s->wmap[m][m]->idf, (s->zmap[m+1].end - s->zmap[m].start), s->wmap[m][m]->attr);
				m++;
			}
		}
	}

	// step2, split to single char
	if ((j > i) && (s->mode & (SCWS_MULTI_ZMAIN|SCWS_MULTI_ZALL)))
	{
		if ((j - i) == 1 && !s->wmap[i][j])
		{
			if (s->wmap[i][i]->flag & SCWS_ZFLAG_PUT) i++;
			else s->wmap[i][i]->flag |= SCWS_ZFLAG_PUT;
			s->wmap[j][j]->flag |= SCWS_ZFLAG_PUT;
		}
		do
		{
			if (s->wmap[i][i]->flag & SCWS_ZFLAG_PUT)
				continue;
			if (!(s->mode & SCWS_MULTI_ZALL) && !strchr("jnv", s->wmap[i][i]->attr[0]))
				continue;
			SCWS_PUT_RES(s->zmap[i].start, s->wmap[i][i]->idf, (s->zmap[i].end - s->zmap[i].start), s->wmap[i][i]->attr);
		}
		while (++i <= j);
	}
}

static void _scws_mseg_zone(scws_t s, int f, int t)
{
	unsigned char *mpath, *npath;
	word_t **wmap;
	int x,i,j,m,n,j2,sz;
	double weight, nweight;
	char attr1[3];

	mpath = npath = NULL;
	weight = nweight = (double) 0.0;

	wmap = s->wmap;
	j2 = 0;
	for (x = i = f; i <= t; i++)
	{
		j = _scws_mget_word(s, i, (x > i ? x - 1 : t));
		if (j == i) continue;
		// skip NR in NR
		if (j < j2 && wmap[i][j]->attr[0] == 'n' && wmap[i][j]->attr[1] == 'r') continue;				
		if (i > j2 && (wmap[i][j]->flag & SCWS_WORD_USED)) continue;

		/* one word only */
		if (i == f && j == t)
		{
			mpath = (unsigned char *) malloc(2);
			mpath[0] = j - i;
			mpath[1] = 0xff;
			break;
		}
		
		if (i != f && (wmap[i][j]->flag & SCWS_WORD_RULE))
			continue;

		/* create the new path */
		wmap[i][j]->flag |= SCWS_WORD_USED;
		nweight = (double) wmap[i][j]->tf * pow(j-i,4);

		if (npath == NULL)
		{
			npath = (unsigned char *) malloc(t-f+2);
			memset(npath, 0xff, t-f+2);
		}

		/* lookfor backward */
		x = sz = 0;
		memset(attr1, 0, sizeof(attr1));
		for (m = f; m < i; m = n+1)
		{
			n = _scws_mget_word(s, m, i-1);
			nweight *= wmap[m][n]->tf;
			npath[x++] = n - m;
			if (n > m)
			{
				nweight *= pow(n-m,4);
				wmap[m][n]->flag |= SCWS_WORD_USED;	
			}
			else sz++;

			if (attr1[0] != '\0')
				nweight *= scws_rule_attr_ratio(s->r, attr1, wmap[m][n]->attr, &npath[x-2]);
			memcpy(attr1, wmap[m][n]->attr, 2);
		}

		/* my self */
		npath[x++] = j - i;
		
		if (attr1[0] != '\0')
			nweight *= scws_rule_attr_ratio(s->r, attr1, wmap[i][j]->attr, &npath[x-2]);
		memcpy(attr1, wmap[i][j]->attr, 2);

		/* lookfor forward */
		for (m = j+1; m <= t; m = n+1)
		{
			n = _scws_mget_word(s, m, t);
			nweight *= wmap[m][n]->tf;
			npath[x++] = n - m;
			if (n > m)
			{
				nweight *= pow(n-m,4);
				wmap[m][n]->flag |= SCWS_WORD_USED;	
			}
			else sz++;

			nweight *= scws_rule_attr_ratio(s->r, attr1, wmap[m][n]->attr, &npath[x-2]);
			memcpy(attr1, wmap[m][n]->attr, 2);
		}
		
		npath[x] = 0xff;
		nweight /= pow(x+sz-1,5);

		/* draw the path for debug */
#ifdef DEBUG
		if (s->mode & SCWS_DEBUG)
		{		
			fprintf(stderr, "PATH by keyword = %.*s, (weight=%.4f):\n",
				s->zmap[j].end - s->zmap[i].start, s->txt + s->zmap[i].start, nweight);	
			for (x = 0, m = f; (n = npath[x]) != 0xff; x++)
			{
				n += m;
				fprintf(stderr, "%.*s ", s->zmap[n].end - s->zmap[m].start, s->txt + s->zmap[m].start);
				m = n + 1;
			}
			fprintf(stderr, "\n--\n");
		}		
#endif

		j2 = x = j;
		if ((x - i) > 1) i--;
		/* check better path */
		if (nweight > weight)
		{
			unsigned char *swap;

			weight = nweight;
			swap = mpath;
			mpath = npath;
			npath = swap;			
		}
	}

	/* set the result, mpath != NULL */
	if (mpath == NULL)
		return;
	
	for (x = 0, m = f; (n = mpath[x]) != 0xff; x++)
	{
		n += m;
		_scws_mset_word(s, m, n);
		m = n + 1;
	}

	/* 一口.070808: memory leak fixed. */
	if (mpath) free(mpath);
	if (npath) free(npath);
}

/* quick define for zrule_checker in loop */
#define	___ZRULE_CHECKER1___														\
if (j >= zlen || SCWS_NO_RULE2(wmap[j][j]->flag))									\
	break;

#define	___ZRULE_CHECKER2___														\
if (j < 0 || SCWS_NO_RULE2(wmap[j][j]->flag))										\
	break;

#define	___ZRULE_CHECKER3___														\
if (!scws_rule_check(s->r, r1, txt + zmap[j].start, zmap[j].end - zmap[j].start))	\
	break;

static void _scws_msegment(scws_t s, int end, int zlen)
{
	word_t **wmap, query;
	struct scws_zchar *zmap;
	unsigned char *txt;
	rule_item_t r1;
	int i, j, k, ch, clen, start;
	pool_t p;

	/* pool used to management some dynamic memory */
	p = pool_new();

	/* create wmap & zmap */
	wmap = s->wmap = (word_t **) darray_new(zlen, zlen, sizeof(word_t));
	zmap = s->zmap = (struct scws_zchar *) pmalloc(p, zlen * sizeof(struct scws_zchar));
	txt = s->txt;
	start = s->off;
	s->zis = -1;

	for (i = 0; start < end; i++)
	{
		ch = txt[start];
		clen = SCWS_CHARLEN(ch);
		if (clen == 1)
		{
			while (start++ < end)
			{
				ch = txt[start];
				if (start == end || SCWS_CHARLEN(txt[start]) > 1)
					break;
				clen++;
			}
			wmap[i][i] = (word_t) pmalloc_z(p, sizeof(word_st));
			wmap[i][i]->tf = 0.5;
			wmap[i][i]->flag |= SCWS_ZFLAG_ENGLISH;
			strcpy(wmap[i][i]->attr, SCWS_IS_ALPHA(txt[start-1]) ? attr_en : attr_un);
		}
		else
		{
			query = xdict_query(s->d, txt + start, clen);
			wmap[i][i] = (word_t) pmalloc(p, sizeof(word_st));
			if (query == NULL)
			{
				wmap[i][i]->tf = 0.5;
				wmap[i][i]->idf = 0.0;
				wmap[i][i]->flag = 0;
				strcpy(wmap[i][i]->attr, attr_un);
			}
			else
			{
				ch = query->flag;
				query->flag = SCWS_WORD_FULL;
				memcpy(wmap[i][i], query, sizeof(word_st));
				if (query->attr[0] == '#')
					wmap[i][i]->flag |= SCWS_ZFLAG_SYMBOL;

				if (ch & SCWS_WORD_MALLOCED)
					free(query);							
			}
			start += clen;
		}
		
		zmap[i].start = start - clen;
		zmap[i].end = start;
	}

	/* fixed real zlength */
	zlen = i;

	/* create word query table */
	for (i = 0; i < zlen; i++)
	{
		k = 0;
		for (j = i+1; j < zlen; j++)
		{
			query = xdict_query(s->d, txt + zmap[i].start, zmap[j].end - zmap[i].start);
			if (query == NULL)
				break;
			ch = query->flag;
			if ((ch & SCWS_WORD_FULL) && memcmp(query->attr, attr_na, 2))
			{
				wmap[i][j] = (word_t) pmalloc(p, sizeof(word_st));
				memcpy(wmap[i][j], query, sizeof(word_st));

				wmap[i][i]->flag |= SCWS_ZFLAG_WHEAD;

				for (k = i+1; k <= j; k++)
					wmap[k][k]->flag |= SCWS_ZFLAG_WPART;
			}

			if (ch & SCWS_WORD_MALLOCED)
				free(query);

			if (!(ch & SCWS_WORD_PART))
				break;		
		}
		
		if (k--)
		{
			/* set nr2 to some short name */
			if ((k == (i+1)))
			{
				if (!memcmp(wmap[i][k]->attr, attr_nr, 2))
					wmap[i][i]->flag |= SCWS_ZFLAG_NR2;
				//if (wmap[i][k]->attr[0] == 'n')
					//wmap[i][i]->flag |= SCWS_ZFLAG_N2;
			}				

			/* clean the PART flag for the last word */
			if (k < j)
				wmap[i][k]->flag ^= SCWS_WORD_PART;
		}
	}

	if (s->r == NULL)
		goto do_segment;
	
	/* auto rule set for name & zone & chinese numeric */

	/* one word auto rule check */
	for (i = 0; i < zlen; i++)
	{
		if (SCWS_NO_RULE1(wmap[i][i]->flag))
			continue;

		r1 = scws_rule_get(s->r, txt + zmap[i].start, zmap[i].end - zmap[i].start);
		if (r1 == NULL)
			continue;

		clen = r1->zmin > 0 ? r1->zmin : 1;
		if ((r1->flag & SCWS_ZRULE_PREFIX) && (i < (zlen - clen)))
		{			
			/* prefix, check after (zmin~zmax) */
			// 先检查 zmin 字内是否全部符合要求
			// 再在 zmax 范围内取得符合要求的字
			// int i, j, k, ch, clen, start;
			for (ch = 1; ch <= clen; ch++)
			{
				j = i + ch;
				___ZRULE_CHECKER1___
				___ZRULE_CHECKER3___
			}

			if (ch <= clen)
				continue;

			/* no limit znum or limit to a range */
			j = i + ch;
			while (1)
			{
				if ((!r1->zmax && r1->zmin) || (r1->zmax && (clen >= r1->zmax)))
					break;
				___ZRULE_CHECKER1___
				___ZRULE_CHECKER3___
				clen++;
				j++;
			}

			// 注意原来2字人名,识别后仍为2字的情况
			if (wmap[i][i]->flag & SCWS_ZFLAG_NR2)
			{
				if (clen == 1)
					continue;
				wmap[i][i+1]->flag |= SCWS_WORD_PART;
			}
			
			/* ok, got: i & clen */
			k = i + clen;
			wmap[i][k] = (word_t) pmalloc(p, sizeof(word_st));
			wmap[i][k]->tf = r1->tf;
			wmap[i][k]->idf = r1->idf;
			wmap[i][k]->flag = (SCWS_WORD_RULE|SCWS_WORD_FULL);
			strncpy(wmap[i][k]->attr, r1->attr, 2);

			wmap[i][i]->flag |= SCWS_ZFLAG_WHEAD;
			for (j = i+1; j <= k; j++)			
				wmap[j][j]->flag |= SCWS_ZFLAG_WPART;

			if (!(wmap[i][i]->flag & SCWS_ZFLAG_WPART))
				i = k;

			continue;
		}
		
		if ((r1->flag & SCWS_ZRULE_SUFFIX) && (i >= clen))
		{
			/* suffix, check before */
			for (ch = 1; ch <= clen; ch++)
			{
				j = i - ch;
				___ZRULE_CHECKER2___
				___ZRULE_CHECKER3___
			}
			
			if (ch <= clen)
				continue;

			/* no limit znum or limit to a range */
			j = i - ch;
			while (1)
			{
				if ((!r1->zmax && r1->zmin) || (r1->zmax && (clen >= r1->zmax)))
					break;
				___ZRULE_CHECKER2___
				___ZRULE_CHECKER3___
				clen++;
				j--;
			}

			/* ok, got: i & clen (maybe clen=1 & [k][i] isset) */
			k = i - clen;
			if (wmap[k][i] != NULL)
				continue;

			wmap[k][i] = (word_t) pmalloc(p, sizeof(word_st));
			wmap[k][i]->tf = r1->tf;
			wmap[k][i]->idf = r1->idf;
			wmap[k][i]->flag = SCWS_WORD_FULL;
			strncpy(wmap[k][i]->attr, r1->attr, 2);

			wmap[k][k]->flag |= SCWS_ZFLAG_WHEAD;
			for (j = k+1; j <= i; j++)
			{
				wmap[j][j]->flag |= SCWS_ZFLAG_WPART;
				if ((j != i) && (wmap[k][j] != NULL))
					wmap[k][j]->flag |= SCWS_WORD_PART;
			}
			continue;
		}
	}

	/* two words auto rule check (欧阳** , **西路) */
	for (i = zlen - 2; i >= 0; i--)
	{
		/* with value ==> must be have SCWS_WORD_FULL, so needn't check it ag. */
		if ((wmap[i][i+1] == NULL) || (wmap[i][i+1]->flag & SCWS_WORD_PART))
			continue;

		k = i+1;
		r1 = scws_rule_get(s->r, txt + zmap[i].start, zmap[k].end - zmap[i].start);
		if (r1 == NULL)
			continue;		

		clen = r1->zmin > 0 ? r1->zmin : 1;
		if ((r1->flag & SCWS_ZRULE_PREFIX) && (k < (zlen - clen)))
		{
			for (ch = 1; ch <= clen; ch++)
			{
				j = k + ch;
				___ZRULE_CHECKER1___
				___ZRULE_CHECKER3___
			}

			if (ch <= clen)
				continue;

			/* no limit znum or limit to a range */
			j = k + ch;
			while (1)
			{
				if ((!r1->zmax && r1->zmin) || (r1->zmax && (clen >= r1->zmax)))
					break;
				___ZRULE_CHECKER1___
				___ZRULE_CHECKER3___
				clen++;
				j++;
			}

			/* ok, got: i & clen */
			k = k + clen;
			wmap[i][k] = (word_t) pmalloc(p, sizeof(word_st));
			wmap[i][k]->tf = r1->tf;
			wmap[i][k]->idf = r1->idf;
			wmap[i][k]->flag = SCWS_WORD_FULL;
			strncpy(wmap[i][k]->attr, r1->attr, 2);

			wmap[i][i+1]->flag |= SCWS_WORD_PART;
			for (j = i+2; j <= k; j++)			
				wmap[j][j]->flag |= SCWS_ZFLAG_WPART;

			i--;
			continue;
		}

		if ((r1->flag & SCWS_ZRULE_SUFFIX) && (i >= clen))
		{
			/* suffix, check before */
			for (ch = 1; ch <= clen; ch++)
			{
				j = i - ch;
				___ZRULE_CHECKER2___
				___ZRULE_CHECKER3___
			}
			
			if (ch <= clen)
				continue;

			/* no limit znum or limit to a range */
			j = i - ch;
			while (1)
			{
				if ((!r1->zmax && r1->zmin) || (r1->zmax && (clen >= r1->zmax)))
					break;
				___ZRULE_CHECKER2___
				___ZRULE_CHECKER3___
				clen++;
				j--;
			}

			/* ok, got: i & clen (maybe clen=1 & [k][i] isset) */
			k = i - clen;
			i = i + 1;
			wmap[k][i] = (word_t) pmalloc(p, sizeof(word_st));
			wmap[k][i]->tf = r1->tf;
			wmap[k][i]->idf = r1->idf;
			wmap[k][i]->flag = SCWS_WORD_FULL;
			strncpy(wmap[k][i]->attr, r1->attr, 2);

			wmap[k][k]->flag |= SCWS_ZFLAG_WHEAD;
			for (j = k+1; j <= i; j++)
			{
				wmap[j][j]->flag |= SCWS_ZFLAG_WPART;
				if (wmap[k][j] != NULL)
					wmap[k][j]->flag |= SCWS_WORD_PART;
			}

			i -= (clen+1);
			continue;
		}
	}

	/* real do the segment */
do_segment:

	/* find the easy break point */
	for (i = 0, j = 0; i < zlen; i++)
	{
		if (wmap[i][i]->flag & SCWS_ZFLAG_WPART)
			continue;

		if (i > j)
			_scws_mseg_zone(s, j, i-1);

		j = i;
		if (!(wmap[i][i]->flag & SCWS_ZFLAG_WHEAD))
		{
			_scws_mset_word(s, i, i);
			j++;
		}
	}

	/* the lastest zone */
	if (i > j)
		_scws_mseg_zone(s, j, i-1);

	/* the last single for duality */
	if ((s->mode & SCWS_DUALITY) && (s->zis >= 0) && !(s->zis & SCWS_ZIS_USED))	
	{
		i = s->zis;
		SCWS_PUT_RES(s->zmap[i].start, s->wmap[i][i]->idf, (s->zmap[i].end - s->zmap[i].start), s->wmap[i][i]->attr);
	}

	/* free the wmap & zmap */
	pool_free(p);
	darray_free((void **) wmap);
}

scws_res_t scws_get_result(scws_t s)
{
	int off, len, ch, clen, zlen, pflag;
	unsigned char *txt;

	off = s->off;
	len = s->len;
	txt = s->txt;
	s->res0 = s->res1 = NULL;
	while ((off < len) && (txt[off] <= 0x20))
	{
		if (txt[off] == 0x0a || txt[off] == 0x0d)
		{
			s->off = off + 1;
			SCWS_PUT_RES(off, 0.0, 1, attr_un);
			return s->res0;
		}
		off++;
	}

	if (off >= len)
		return NULL;

	/* try to parse the sentence */
	s->off = off;
	ch = txt[off];
	if (SCWS_CHAR_TOKEN(ch) && !(s->mode & SCWS_IGN_SYMBOL))
	{
		s->off++;
		SCWS_PUT_RES(off, 0.0, 1, attr_un);
		return s->res0;
	}
	clen = SCWS_CHARLEN(ch);
	zlen = 1;
	pflag = (clen > 1 ? PFLAG_WITH_MB : (SCWS_IS_ALNUM(ch) ? PFLAG_ALNUM : 0));
	while ((off = (off+clen)) < len)
	{
		ch = txt[off];
		if (ch <= 0x20 || SCWS_CHAR_TOKEN(ch)) break;		
		clen = SCWS_CHARLEN(ch);
		if (!(pflag & PFLAG_WITH_MB))
		{
			// pure single-byte -> multibyte (2bytes)
			if (clen == 1)
			{
				if (pflag & PFLAG_ALNUM)
				{
					if (SCWS_IS_ALPHA(ch))
					{
						if (!(pflag & PFLAG_LONGALPHA) && SCWS_IS_ALPHA(txt[off-1]))
							pflag |= PFLAG_LONGALPHA;
					}
					else if (SCWS_IS_DIGIT(ch))
					{
						if (!(pflag & PFLAG_LONGDIGIT) && SCWS_IS_DIGIT(txt[off-1]))
							pflag |= PFLAG_LONGDIGIT;
					}
					else
						pflag ^= PFLAG_ALNUM;
				}
			}
			else
			{
				if (!(pflag & PFLAG_ALNUM) || zlen > 2)
					break;

				pflag |= PFLAG_WITH_MB;
				/* zlen = 1; */
			}
		}
		else if ((pflag & PFLAG_WITH_MB) && clen == 1)
		{
			int i;

			// mb + single-byte. allowd: alpha+num + 中文
			if (!SCWS_IS_ALNUM(ch))
				break;
			
			pflag &= ~PFLAG_VALID;
			for (i = off+1; i < (off+3); i++)
			{
				ch = txt[i];
				if ((i >= len) || (ch <= 0x20) || (SCWS_CHARLEN(ch) > 1))
				{
					pflag |= PFLAG_VALID;
					break;
				}

				if (!SCWS_IS_ALNUM(ch))
					break;
			}		
			
			if (!(pflag & PFLAG_VALID))
				break;

			clen += (i - off - 1);
		}
		/* hightman.070813: add max zlen limit */
		if (++zlen >= SCWS_MAX_ZLEN)
		    break;
	}

	/* hightman.070624: 处理半个字的问题 */
	if ((ch = off) > len)	
		off -= clen;

	/* do the real segment */
	if (off <= s->off)
		return NULL;
	else if (pflag & PFLAG_WITH_MB)
		_scws_msegment(s, off, zlen);
	else if (!(pflag & PFLAG_ALNUM) || ((off - s->off) >= SCWS_MAX_EWLEN))
		_scws_ssegment(s, off);
	else
	{
		zlen = off - s->off;
		if ((pflag & (PFLAG_LONGALPHA|PFLAG_LONGDIGIT)) == (PFLAG_LONGALPHA|PFLAG_LONGDIGIT))
			_scws_alnum_multi(s, s->off, zlen);
		else
		{
			float idf;

			idf = SCWS_EN_IDF(zlen);
			SCWS_PUT_RES(s->off, idf, zlen, attr_en);
		
			/* hightman.090523: 为字母数字混合再度拆解, 纯数字, (>1 ? 纯字母 : 数字+字母) */
			if ((s->mode & SCWS_MULTI_DUALITY) && zlen > 2)
				_scws_alnum_multi(s, s->off, zlen);
		}
	}

	/* reutrn the result */
	s->off = (ch > len ? len : off);
	if (s->res0 == NULL)
		return scws_get_result(s);

	return s->res0;
}

/* free the result retunned by scws_get_result */
void scws_free_result(scws_res_t result)
{
	scws_res_t cur;

	while ((cur = result) != NULL)
	{
		result = cur->next;
		free(cur);
	}
}

/* top words count */
// xattr = ~v,p,c
// xattr = v,pn,c

static int _tops_cmp(a, b)
	scws_top_t *a,*b;
{
	if ((*b)->weight > (*a)->weight)
		return 1;
	return -1;
}

static void _tops_load_node(node_t node, scws_top_t *values, int *start)
{
	int i = *start;

	if (node == NULL)
		return;
	
	values[i] = node->value;
	values[i]->word = node->key;

	*start = ++i;
	_tops_load_node(node->left, values, start);
	_tops_load_node(node->right, values, start);
}

static void _tops_load_all(xtree_t xt, scws_top_t *values)
{
	int i, start;
	
	for (i = 0, start = 0; i < xt->prime; i++)	
		_tops_load_node(xt->trees[i], values, &start);
}

typedef char word_attr[4];
static inline int _attr_belong(const char *a, word_attr *at)
{
	if ((*at)[0] == '\0') return 1;	
	while ((*at)[0])
	{
		if (!strcmp(a, *at)) return 1;
		at++;
	}
	return 0;
}

/* macro to parse xattr -> xmode, at */
#define	__PARSE_XATTR__		do {						\
	if (xattr == NULL) break;							\
	if (*xattr == '~') { xattr++; xmode = SCWS_YEA; }	\
	if (*xattr == '\0') break;							\
	cnt = ((strlen(xattr)/2) + 2) * sizeof(word_attr);	\
	at = (word_attr *) malloc(cnt);						\
	memset(at, 0, cnt);									\
	cnt = 0;											\
	for (cnt = 0; (word = strchr(xattr, ',')); cnt++) {	\
		at[cnt][0] = *xattr++;							\
		at[cnt][1] = xattr == word ? '\0' : *xattr;		\
		xattr = word + 1;								\
	}													\
	strncpy(at[cnt], xattr, 2);							\
} while (0)

scws_top_t scws_get_tops(scws_t s, int limit, char *xattr)
{
	int off, cnt, xmode = SCWS_NA;
	xtree_t xt;	
	scws_res_t res, cur;
	scws_top_t top, *list, tail, base;
	char *word;
	word_attr *at = NULL;

	if (!s || !s->txt || !(xt = xtree_new(0,1)))
		return NULL;

	__PARSE_XATTR__;

	// save the offset.
	off = s->off;
	s->off = cnt = 0;
	while ((cur = res = scws_get_result(s)) != NULL)
	{
		do
		{
			if (cur->idf < 0.2 || cur->attr[0] == '#')
				continue;

			/* check attribute filter */
			if (at != NULL)
			{
				if ((xmode == SCWS_NA) && !_attr_belong(cur->attr, at))
					continue;

				if ((xmode == SCWS_YEA) && _attr_belong(cur->attr, at))
					continue;
			}

			/* check stopwords */
			if (!strncmp(cur->attr, attr_en, 2) && cur->len > 6)
			{
				word = _mem_ndup(s->txt + cur->off, cur->len);
				_str_tolower(word, word);
				if (SCWS_IS_NOSTATS(word, cur->len))
				{
					free(word);
					continue;
				}
				free(word);
			}

			/* put to the stats */
			if (!(top = xtree_nget(xt, s->txt + cur->off, cur->len, NULL)))
			{
				top = (scws_top_t) pmalloc_z(xt->p, sizeof(struct scws_topword));
				top->weight = cur->idf;
				top->times = 1;
				strncpy(top->attr, cur->attr, 2);
				xtree_nput(xt, top, sizeof(struct scws_topword), s->txt + cur->off, cur->len);
				cnt++;
			}
			else
			{
				top->weight += cur->idf;
				top->times++;
			}
		}
		while ((cur = cur->next) != NULL);
		scws_free_result(res);
	}

	// free at
	if (at != NULL)
		free(at);
	top = NULL;
	if (cnt > 0)
	{
		/* sort the list */
		list = (scws_top_t *) malloc(sizeof(scws_top_t) * cnt);
		_tops_load_all(xt, list);
		qsort(list, cnt, sizeof(scws_top_t), _tops_cmp);

		/* save to return pointer */
		if (!limit || limit > cnt)
			limit = cnt;
		
		top = tail = (scws_top_t) malloc(sizeof(struct scws_topword));
		memcpy(top, list[0], sizeof(struct scws_topword));
		top->word = strdup(list[0]->word);
		top->next = NULL;

		for (cnt = 1; cnt < limit; cnt++)
		{
			base = (scws_top_t) malloc(sizeof(struct scws_topword));
			memcpy(base, list[cnt], sizeof(struct scws_topword));
			base->word = strdup(list[cnt]->word);
			base->next = NULL;
			tail->next = base;
			tail = base;
		}
		free(list);
	}

	// restore the offset
	s->off = off;
	xtree_free(xt);	
	return top;
}

// word check by attr.
int scws_has_word(scws_t s, char *xattr)
{
	int off, cnt, xmode = SCWS_NA;
	scws_res_t res, cur;
	char *word;
	word_attr *at = NULL;	

	if (!s || !s->txt)
		return 0;

	__PARSE_XATTR__;

	// save the offset. (cnt -> return_value)
	off = s->off;
	cnt = s->off = 0;
	while (!cnt && (cur = res = scws_get_result(s)) != NULL)
	{
		do
		{
			/* check attribute filter */
			if (at != NULL)
			{
				if ((xmode == SCWS_NA) && _attr_belong(cur->attr, at))
					cnt = 1;

				if ((xmode == SCWS_YEA) && !_attr_belong(cur->attr, at))
					cnt = 1;
			}		
		}
		while (!cnt && (cur = cur->next) != NULL);
		scws_free_result(res);
	}
	// memory leak fixed, thanks to lauxinz
	if (at != NULL)
		free(at);
	s->off = off;
	return cnt;
}

// get words by attr (rand order)
scws_top_t scws_get_words(scws_t s, char *xattr)
{
	int off, cnt, xmode = SCWS_NA;
	xtree_t xt;	
	scws_res_t res, cur;
	scws_top_t top, tail, base;
	char *word;
	word_attr *at = NULL;

	if (!s || !s->txt || !(xt = xtree_new(0,1)))
		return NULL;

	__PARSE_XATTR__;

	// save the offset.
	off = s->off;
	s->off = 0;
	base = tail = NULL;
	while ((cur = res = scws_get_result(s)) != NULL)
	{
		do
		{
			/* check attribute filter */
			if (at != NULL)
			{
				if ((xmode == SCWS_NA) && !_attr_belong(cur->attr, at))
					continue;

				if ((xmode == SCWS_YEA) && _attr_belong(cur->attr, at))
					continue;
			}

			/* put to the stats */
			if (!(top = xtree_nget(xt, s->txt + cur->off, cur->len, NULL)))
			{
				top = (scws_top_t) malloc(sizeof(struct scws_topword));
				top->weight = cur->idf;
				top->times = 1;
				top->next = NULL;
				top->word = (char *)_mem_ndup(s->txt + cur->off, cur->len);
				strncpy(top->attr, cur->attr, 2);
				// add to the chain
				if (tail == NULL)
					base = tail = top;
				else
				{
					tail->next = top;
					tail = top;
				}
				xtree_nput(xt, top, sizeof(struct scws_topword), s->txt + cur->off, cur->len);
			}
			else
			{
				top->weight += cur->idf;
				top->times++;
			}
		}
		while ((cur = cur->next) != NULL);
		scws_free_result(res);
	}

	// free at & xtree
	if (at != NULL)
		free(at);
	xtree_free(xt);

	// restore the offset
	s->off = off;
	return base;
}

void scws_free_tops(scws_top_t tops)
{
	scws_top_t cur;

	while ((cur = tops) != NULL)
	{
		tops = cur->next;
		if (cur->word)
			free(cur->word);			
		free(cur);
	}
}
