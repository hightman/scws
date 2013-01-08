/**
 * @file mk_dict.c (convert dict.txt -> dict.xdb)
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifdef HAVE_CONFIG_H
#	include "config.h"
#endif

#include "xtree.h"
#include "xdb.h"
#include "xdict.h"
#include "charset.h"
#include <unistd.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>

#undef PACKAGE_NAME
#define	PACKAGE_NAME		"scws-mkdict"

static char *program_name;
static void show_usage(int code, const char *msg)
{
	if (code)
	{
		if (msg != NULL)
			fprintf(stderr, "mk_dict: %s\n", msg);
		fprintf(stderr, "Try `%s -h' for more information.\n", program_name);
		exit(code);
	}

	printf("%s (%s/%s)\n", program_name, PACKAGE_NAME, PACKAGE_VERSION);
	printf("Convert the plain text dictionary to xdb format.\n");
	printf("Copyright (C)2007 by hightman.\n\n");
	printf("Usage: %s [options] [input file] [output file]\n", program_name);
	printf("  -i        Specified the plain text dictionary(default: dict.txt).\n");
	printf("  -o        Specified the output file path(default: dict.xdb)\n");
	printf("  -c        Specified the input charset(default: gbk)\n");
	printf("  -p        Specified the PRIME num for xdb\n");
	printf("  -v        Show the version.\n");
	printf("  -h        Show this page.\n");
	printf("Report bugs to <hightman2@yahoo.com.cn>\n");
	exit(0);
}

/* usage: mk_dict -i dict.txt -o dict.xdb */
int main(int argc, char *argv[])
{
	int c,t;
	char *input, *output, *charset, *delim = " \t\r\n";
	FILE *fp;
	char buf[256], *str, *ptr, *mblen;
	word_st word, *w;
	xtree_t xt;

	input = output = charset = NULL;
	if ((program_name = strrchr(argv[0], '/')) != NULL)
		program_name++;
	else
		program_name = argv[0];	

	/* parse the arguments */
	t = 0;
	while ((c = getopt(argc, argv, "i:p:o:c:vh")) != -1)
	{
		switch (c)
		{
			case 'i' :
				input = optarg;
				break;
			case 'o' :
				output = optarg;
				break;
			case 'p' :
				t = atoi(optarg);
				break;
			case 'c' :
				charset = optarg;
				break;
			case 'v' :
				printf("%s (%s/%s: convert the plain text dictionary to xdb format)\n",
							program_name, PACKAGE_NAME, PACKAGE_VERSION);
				exit(0);			
				break;
			case 'h' :
				show_usage(0, NULL);
				break;
			case '?' :
			default :
				exit(-1);
		}
	}

	/* other arguments */
	argc -= optind;
	if (argc > 0 && input == NULL)
	{
		input = argv[optind++];		
		argc--;
	}
	if (argc > 0 && output == NULL)
		output = argv[optind];

	if (input == NULL)
		input = "dict.txt";
	if (output == NULL)
		output = "dict.xdb";

	/* check the input & output */
	if (!access(output, R_OK))
	{
		perror("Output file exists");
		return -1;
	}

	if ((fp = fopen(input, "r")) == NULL)
	{
		perror("Cann't open the input file");
		return -1;
	}

	/* setup the xtree */
	if (t == 0)
		t = SCWS_XDICT_PRIME;	
	if ((xt = xtree_new(0, t)) == NULL)
	{
		perror("Failed to create the xtree");
		goto mk_end;
	}
	
	/* load the data */
	mblen = charset_table_get(charset);
	printf("Reading the input file: %s ...", input);
	fflush(stdout);

	t = 0;
	word.attr[2] = '\0';
	while (fgets(buf, sizeof(buf)-1, fp) != NULL)
	{
		// <word>\t<tf>\t<idf>\t<attr>\n
		if (buf[0] == ';' || buf[0] == '#')
			continue;

		str = strtok(buf, delim);
		if (str == NULL) continue;
		c = strlen(str);
		
		// init the word
		do
		{				
			word.tf = word.idf = 1.0;
			word.flag = SCWS_WORD_FULL;
			word.attr[0] = '@';
			word.attr[1] = '\0';

			if (!(ptr = strtok(NULL, delim))) break;
			word.tf = (float) atof(ptr);

			if (!(ptr = strtok(NULL, delim))) break;
			word.idf = (float) atof(ptr);

			if (ptr = strtok(NULL, delim))
			{
				word.attr[0] = ptr[0];
				if (ptr[1]) word.attr[1] = ptr[1];
			}
		} while (0);

		/* save the word */
		//printf("word: %s (len=%d)\n", str, c);
		if ((w = xtree_nget(xt, str, c, NULL)) == NULL)
		{
			w = (word_st *) pmalloc(xt->p, sizeof(word_st));
			memcpy(w, &word, sizeof(word));
			xtree_nput(xt, w, sizeof(word), str, c);
			t++;
		}
		else
		{
			w->tf = word.tf;
			w->idf = word.idf;
			w->flag |= SCWS_WORD_FULL;
			strcpy(w->attr, word.attr);
		}

		/* parse the part */		
		argc = mblen[(unsigned char)(str[0])];
		while (1)
		{
			argc += mblen[(unsigned char)(str[argc])];
			if (argc >= c)
				break;

			//printf("part: %.*s (len=%d)\n", argc, str, argc);			
			if ((w = xtree_nget(xt, str, argc, NULL)) == NULL)
			{
				w = (word_st *) pmalloc_z(xt->p, sizeof(word_st));
				w->flag = SCWS_WORD_PART;
				xtree_nput(xt, w, sizeof(word), str, argc);
				t++;
			}
			else
			{
				w->flag |= SCWS_WORD_PART;
			}
		}
	}

	/* save to xdb & free the xtree */
	printf("OK, total nodes=%d\nOptimizing... ", t);
	fflush(stdout);
	
	xtree_optimize(xt);
	
	printf("OK\nDump the tree data to: %s ... ", output);
	fflush(stdout);

	xtree_to_xdb(xt, output);
	xtree_free(xt);

	printf("OK, all been done!\n");

mk_end:
	fclose(fp);
	return 0;	
}
