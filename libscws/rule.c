/**
 * @file rule.c (auto surame & areaname & special group)
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

#include "rule.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

static inline int _rule_index_get(rule_t r, const char *name)
{
	int i;
	for (i = 0; i < SCWS_RULE_MAX; i++)
	{
		if (r->items[i].name[0] == '\0')
			break;

		if (!strcasecmp(r->items[i].name, name))
			return i;
	}
	return -1;
}

rule_t scws_rule_new(const char *fpath, unsigned char *mblen)
{
	FILE *fp;
	rule_t r;
	rule_item_t cr;
	int i, j, rbl, aflag;
	rule_attr_t a, rtail;
	unsigned char buf[512], *str, *ptr, *qtr;

	/* loaded or open file failed */
	if ((fp = fopen(fpath, "r")) == NULL)
		return NULL;

	/* alloc the memory */
	r = (rule_t) malloc(sizeof(rule_st));
	memset(r, 0, sizeof(rule_st));
	r->ref = 1;

	/* quick scan to add the name to list */
	i = j = rbl = aflag = 0;
	while (fgets(buf, sizeof(buf) - 1, fp))
	{
		if (buf[0] != '[' || !(ptr = strchr(buf, ']')))
			continue;

		str = buf + 1;
		*ptr = '\0';
		if (ptr == str || (ptr - str) > 15 || !strcasecmp(str, "attrs"))
			continue;

		if (_rule_index_get(r, str) >= 0)
			continue;

		strcpy(r->items[i].name, str);
		r->items[i].tf = 5.0;
		r->items[i].idf = 3.5;
		strncpy(r->items[i].attr, "un", 2);
		if (!strcasecmp(str, "special"))
			r->items[i].bit = SCWS_RULE_SPECIAL;
		else if (!strcasecmp(str, "nostats"))
			r->items[i].bit = SCWS_RULE_NOSTATS;
		else
		{
			r->items[i].bit = (1 << j);
			j++;
		}

		if (++i >= SCWS_RULE_MAX)
			break;
	}
	rewind(fp);

	/* load the tree data */
	if ((r->tree = xtree_new(0, 1)) == NULL)
	{
		free(r);
		return NULL;
	}
	cr = NULL;
	while (fgets(buf, sizeof(buf) - 1, fp))
	{
		if (buf[0] == ';')
			continue;

		if (buf[0] == '[')
		{
			cr = NULL;
			str = buf + 1;
			aflag = 0;
			if ((ptr = strchr(str, ']')) != NULL)
			{
				*ptr = '\0';
				if (!strcasecmp(str, "attrs"))
				{
					aflag = 1;
				}
				else if ((i = _rule_index_get(r, str)) >= 0)
				{
					rbl = 1; /* default read by line = yes */
					cr = &r->items[i];
				}
			}
			continue;
		}

		/* attr flag open? */
		if (aflag == 1)
		{
			/* parse the attr line */
			str = buf;
			while (*str == ' ' || *str == '\t') str++;
			if ((ptr = strchr(str, '+')) == NULL) continue;
			*ptr++ = '\0';
			if ((qtr = strchr(ptr, '=')) == NULL) continue;
			*qtr++ = '\0';

			/* create new memory */
			a = (rule_attr_t) malloc(sizeof(struct scws_rule_attr));
			memset(a, 0, sizeof(struct scws_rule_attr));

			/* get ratio */
			while (*qtr == ' ' || *qtr == '\t') qtr++;
			a->ratio = (short) atoi(qtr);
			if (a->ratio < 1)
				a->ratio = 1;
			a->npath[0] = a->npath[1] = 0xff;

			/* read attr1 & npath1? */
			a->attr1[0] = *str++;
			if (*str && *str != '(' && *str != ' ' && *str != '\t')
				a->attr1[1] = *str++;
			while (*str && *str != '(') str++;
			if (*str == '(')
			{
				str++;
				if ((qtr = strchr(str, ')')) != NULL)
				{
					*qtr = '\0';
					a->npath[0] = (unsigned char) atoi(str);
					if (a->npath[0] > 0)
						a->npath[0]--;
					else
						a->npath[0] = 0xff;
				}
			}

			/* read attr1 & npath2? */
			str = ptr;
			while (*str == ' ' || *str == '\t') str++;
			a->attr2[0] = *str++;
			if (*str && *str != '(' && *str != ' ' && *str != '\t')
				a->attr2[1] = *str++;
			while (*str && *str != '(') str++;
			if (*str == '(')
			{
				str++;
				if ((qtr = strchr(str, ')')) != NULL)
				{
					*qtr = '\0';
					a->npath[1] = (unsigned char) atoi(str);
					if (a->npath[1] > 0)
						a->npath[1]--;
					else
						a->npath[1] = 0xff;
				}
			}

			//printf("%c%c(%d)+%c%c(%d)=%d\n", a->attr1[0], a->attr1[1] ? a->attr1[1] : ' ', a->npath[0],
			//	a->attr2[0], a->attr2[1] ? a->attr2[1] : ' ', a->npath[1], a->ratio);

			/* append to the chain list */
			if (r->attr == NULL)
				r->attr = rtail = a;
			else
			{
				rtail->next = a;
				rtail = a;
			}

			continue;
		}

		if (cr == NULL)
			continue;

		/* param set: line|znum|include|exclude|type|tf|idf|attr */
		if (buf[0] == ':')
		{
			str = buf + 1;
			if (!(ptr = strchr(str, '=')))
				continue;
			while (*str == ' ' || *str == '\t') str++;

			qtr = ptr + 1;
			while (ptr > str && (ptr[-1] == ' ' || ptr[-1] == '\t')) ptr--;
			*ptr = '\0';
			ptr = str;
			str = qtr;
			while (*str == ' ' || *str == '\t') str++;

			if (!strcmp(ptr, "line"))
				rbl = (*str == 'N' || *str == 'n') ? 0 : 1;
			else if (!strcmp(ptr, "tf"))
				cr->tf = (float) atof(str);
			else if (!strcmp(ptr, "idf"))
				cr->idf = (float) atof(str);
			else if (!strcmp(ptr, "attr"))
				strncpy(cr->attr, str, 2);
			else if (!strcmp(ptr, "znum"))
			{
				if ((ptr = strchr(str, ',')) != NULL)
				{
					*ptr++ = '\0';
					while (*ptr == ' ' || *ptr == '\t') ptr++;
					cr->zmax = atoi(ptr);
					cr->flag |= SCWS_ZRULE_RANGE;
				}
				cr->zmin = atoi(str);
			}
			else if (!strcmp(ptr, "type"))
			{
				if (!strncmp(str, "prefix", 6))
					cr->flag |= SCWS_ZRULE_PREFIX;
				else if (!strncmp(str, "suffix", 6))
					cr->flag |= SCWS_ZRULE_SUFFIX;
			}
			else if (!strcmp(ptr, "include") || !strcmp(ptr, "exclude"))
			{
				unsigned int *clude;

				if (!strcmp(ptr, "include"))
				{
					clude = &cr->inc;
					cr->flag |= SCWS_ZRULE_INCLUDE;
				}
				else
				{
					clude = &cr->exc;
					cr->flag |= SCWS_ZRULE_EXCLUDE;
				}

				while ((ptr = strchr(str, ',')) != NULL)
				{
					while (ptr > str && (ptr[-1] == '\t' || ptr[-1] == ' ')) ptr--;
					*ptr = '\0';
					if ((i = _rule_index_get(r, str)) >= 0)
						*clude |= r->items[i].bit;

					str = ptr + 1;
					while (*str == ' ' || *str == '\t' || *str == ',') str++;
				}

				ptr = strlen(str) + str;
				while (ptr > str && strchr(" \t\r\n", ptr[-1])) ptr--;
				*ptr = '\0';
				if (ptr > str && (i = _rule_index_get(r, str)))
					*clude |= r->items[i].bit;
			}
			continue;
		}

		/* read the entries */
		str = buf;
		while (*str == ' ' || *str == '\t') str++;
		ptr = str + strlen(str);
		while (ptr > str && strchr(" \t\r\n", ptr[-1])) ptr--;
		*ptr = '\0';

		/* emptry line */
		if (ptr == str)
			continue;

		if (rbl)
			xtree_nput(r->tree, cr, sizeof(struct scws_rule_item), str, ptr - str);
		else
		{
			while (str < ptr)
			{
				j = mblen[(*str)];

#ifdef DEBUG
				/* try to check repeat */
				if ((i = (int) xtree_nget(r->tree, str, j, NULL)) != 0)
					fprintf(stderr, "Reapeat word on %s|%s: %.*s\n", cr->name, ((rule_item_t) i)->name, j, str);
#endif
				xtree_nput(r->tree, cr, sizeof(struct scws_rule_item), str, j);
				str += j;
			}
		}
	}
	fclose(fp);

	/* optimize the tree */
	xtree_optimize(r->tree);
	return r;
}

/* fork rule */
rule_t scws_rule_fork(rule_t r)
{
	if (r != NULL)
		r->ref++;
	return r;
}

/* free rule */
void scws_rule_free(rule_t r)
{
	if (r)
	{
		r->ref--;
		if (r->ref == 0)
		{
			rule_attr_t a, b;

			xtree_free(r->tree);
			a = r->attr;
			while (a != NULL)
			{
				b = a;
				a = b->next;
				free(b);
			}
			free(r);
		}
	}
}

/* get the rule */
rule_item_t scws_rule_get(rule_t r, const char *str, int len)
{
	if (!r)
		return NULL;

	return((rule_item_t) xtree_nget(r->tree, str, len, NULL));
}

/* check the bit with str */
int scws_rule_checkbit(rule_t r, const char *str, int len, unsigned int bit)
{
	rule_item_t ri;

	if (!r)
		return 0;

	ri = (rule_item_t) xtree_nget(r->tree, str, len, NULL);
	if ((ri != NULL) && (ri->bit & bit))
		return 1;

	return 0;
}

/* get rule attr x */
#define	EQUAL_RULE_ATTR(x,y)	((y[0]=='*'||y[0]==x[0])&&(y[1]=='\0'||y[1]==x[1]))
#define	EQUAL_RULE_NPATH(x,y)	((y[0]==0xff||y[0]==x[0])&&(y[1]==0xff||y[1]==x[1]))

int scws_rule_attr_ratio(rule_t r, const char *attr1, const char *attr2, const unsigned char *npath)
{
	rule_attr_t a;
	int ret = 1;

	if (!r || (a = r->attr) == NULL)
		return ret;

	while (a != NULL)
	{
		if (EQUAL_RULE_ATTR(attr1, a->attr1) && EQUAL_RULE_ATTR(attr2, a->attr2) && EQUAL_RULE_NPATH(npath, a->npath))
		{
			ret = (int) a->ratio;
			break;
		}
		a = a->next;
	}
	return ret;
}

#undef EQUAL_RULE_ATTR
#undef EQUAL_RULE_NPATH

/* check the rule */
int scws_rule_check(rule_t r, rule_item_t cr, const char *str, int len)
{
	if (!r)
		return 0;

	if ((cr->flag & SCWS_ZRULE_INCLUDE) && !scws_rule_checkbit(r, str, len, cr->inc))
		return 0;

	if ((cr->flag & SCWS_ZRULE_EXCLUDE) && scws_rule_checkbit(r, str, len, cr->exc))
		return 0;

	return 1;
}
