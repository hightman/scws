/**
 * @file command.c (segment by command line)
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifdef HAVE_CONFIG_H
#	include "config.h"
#endif

#include "scws.h"
#include <unistd.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/time.h>

#undef PACKAGE_NAME
#define	PACKAGE_NAME		"scws-cli"

static char *program_name;
static void show_usage(int code, const char *msg)
{
	if (code)
	{
		if (msg != NULL)
			fprintf(stderr, "%s: %s\n", PACKAGE_NAME, msg);
		fprintf(stderr, "Try `%s -h' for more information.\n", program_name);
		exit(code);
	}

	printf("%s (%s/%s)\n", program_name, PACKAGE_NAME, PACKAGE_VERSION);
	printf("Simple Chinese Word Segmentation - Command line usage.\n");
	printf("Copyright (C)2007 by hightman.\n\n");
	printf("Usage: %s [options] [input] [output]\n", program_name);
	printf("  -i <file|string> input string or filepath \n");
	printf("                   (default: try to read from <stdin> everyline)\n");
	printf("  -o <file>        output filepath (default to <stdout>)\n");
	printf("  -c <charset>     set the charset (default: gbk)\n");
	printf("                   charset must been same with dictionary & ruleset\n");
	printf("  -r <file>        set the ruleset file (default: none)\n");
	printf("  -d <file>        set the dictionary file[s] (default: none)\n");
	printf("                   if there are multi files, split filepath use ':'\n");
	printf("                   if the file suffix is .txt, it will be treated as plain text dict.\n");
	printf("  -M <1~15>        use multi child words mode(中国人->中国+人+中国人)\n");
	printf("                   1|2|4|8: short|duality|zmain|zall\n");
	printf("  -I               ignore the all mark symbol such as ,:\n");
	printf("  -A               show the word attribute\n");
	printf("  -E               import the xdb dict into xtree(memory)\n");
	printf("  -N               don't show time usage and warnings\n");
	printf("  -D               debug segment, see the segment detail\n");
	printf("  -U               use duality algorithm for single chinese\n");		   
	printf("  -t <NUM>         fetch the top words instead of segment\n");
	printf("  -a [~]<attr1,attr2,...>   prefix by ~ means exclude them.\n");
	printf("                   For topwords, exclude or include some word attrs\n");
	printf("  -v        Show the version.\n");
	printf("  -h        Show this page for help.\n");
	printf("Report bugs to <hightman2@yahoo.com.cn>\n");
	exit(0);
}

#define	___DOSEGMENT___										\
bytes += (fsize = strlen(str));								\
scws_send_text(s, str, fsize);								\
while ((cur = res = scws_get_result(s)) != NULL)			\
{															\
	while (cur != NULL)										\
	{														\
		fprintf(fout, "%.*s", cur->len, str + cur->off);	\
		if (cur->len != 1 || ((*(str + cur->off) != '\n')	\
			&& (*(str + cur->off) != '\r')))				\
		{													\
			if (xmode & XMODE_SHOW_ATTR)					\
				fprintf(fout, "/%.2s", cur->attr);			\
			fprintf(fout, " ");								\
		}													\
		cur = cur->next;									\
	}														\
	fflush(fout);											\
	scws_free_result(res);									\
}

#define	XMODE_SHOW_ATTR		0x01
#define	XMODE_DICT_MEM		0x02
#define	XMODE_DO_STAT		0x04
#define	XMODE_STAT_FILE		0x08
#define	XMODE_NO_TIME		0x10

int main(int argc, char *argv[])
{	
	int c, xmode, fsize, tlimit, bytes;
	FILE *fin, *fout;
	char *str, buf[2048], *attr;
	scws_t s;
	struct stat st;
	scws_res_t res, cur;
	struct timeval t1, t2, t3;

	fin = fout = (FILE *) NULL;
	str = attr = NULL;
	bytes = xmode = fsize = tlimit = 0;
	if ((program_name = strrchr(argv[0], '/')) != NULL)
		program_name++;
	else
		program_name = argv[0];	

	/* try to log the time */
	gettimeofday(&t1, NULL);

	/* create the scws engine */
	s = scws_new();

	/* parse the arguments */
	while ((c = getopt(argc, argv, "i:o:c:r:d:t:a:M:NDUEIAvh")) != -1)
	{
		switch (c)
		{
			case 'i' :
				if (fin != NULL)
					fclose(fin);
				if (stat(optarg, &st) || !S_ISREG(st.st_mode) || !(fin = fopen(optarg, "r")))
					str = optarg;
				fsize = st.st_size;
				break;
			case 'o' :
				if (fout != NULL)
					break;
				if (!stat(optarg, &st) || !lstat(optarg, &st))
				{
					fprintf(stderr, "ERROR: output file exists. '%s'\n", optarg);
					goto cws_end;
				}
				if (!(fout = fopen(optarg, "w")))
				{
					fprintf(stderr, "ERROR: output file write failed. '%s'\n", optarg);
					goto cws_end;
				}
				break;
			case 'c' :
				scws_set_charset(s, optarg);
				break;
			case 'r' :
				scws_set_rule(s, optarg);
				if (s->r == NULL && !(xmode & XMODE_NO_TIME))
					fprintf(stderr, "WARNING: input ruleset fpath load failed. '%s'\n", optarg);
				break;
			case 'd' :
				{
					char *d_str, *p_str, *q_str;
					int dmode;
					d_str = optarg;
					do
					{
						if ((p_str = strchr(d_str, ':')) != NULL) *p_str++ = '\0';
						
						dmode = (xmode & XMODE_DICT_MEM) ? SCWS_XDICT_MEM : SCWS_XDICT_XDB;
						if ((q_str = strrchr(d_str, '.')) != NULL && !strcasecmp(q_str, ".txt")) 
							dmode |= SCWS_XDICT_TXT;
						dmode = scws_add_dict(s, d_str, dmode);
						if (dmode < 0 && !(xmode & XMODE_NO_TIME))
							fprintf(stderr, "WARNING: failed to add dict file: %s\n", d_str);
					}
					while ((d_str = p_str) != NULL);
				}
				break;
			case 'M' :
				scws_set_multi(s, (atoi(optarg)<<12));
				break;
			case 'I' :
				scws_set_ignore(s, SCWS_YEA);
				break;
			case 'A' :
				xmode |= XMODE_SHOW_ATTR;
				break;
			case 'E' :
				xmode |= XMODE_DICT_MEM;
				break;
			case 'N' :
				xmode |= XMODE_NO_TIME;
				break;
			case 'D' :
				scws_set_debug(s, SCWS_YEA);
				break;
			case 'U' :
				scws_set_duality(s, SCWS_YEA);
				break;
			case 't' :
				xmode |= XMODE_DO_STAT;
				tlimit = atoi(optarg);
				break;
			case 'a' :
				attr = optarg;
				break;
			case 'v' :
				printf("%s (%s/%s: Simpled Chinese Words Segment - Command line usage)\n",
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
	if (argc > 0 && fin == NULL && str == NULL)
	{
		optarg = argv[optind++];
		if (*optarg != '-')
		{		
			if (stat(optarg, &st) || !S_ISREG(st.st_mode) || !(fin = fopen(optarg, "r")))
				str = optarg;
			fsize = st.st_size;
			argc--;
		}
	}
	if (argc > 0 && fout == NULL)
	{
		optarg = argv[optind];
		if (*optarg != '-' && !(fout = fopen(optarg, "w")))
		{
			fprintf(stderr, "ERROR: output file write failed. '%s'\n", optarg);
			goto cws_end;
		}
	}

	if (fout == NULL)
		fout = stdout;

	if (!(xmode & XMODE_NO_TIME))
		gettimeofday(&t2, NULL);

	if (xmode & XMODE_DO_STAT)
	{
		/* do the stats only */		
		if (str == NULL && fin == NULL)		
			fprintf(stderr, "ERROR: top stats require input string or file\n");			
		else
		{
			scws_top_t top, xtop;

			if (str == NULL)
			{
				int b;

				c = b = 0;
				str = (char *) malloc(fsize);
				while (fsize > 0)
				{
					b = fread(str + c, 1, fsize, fin);
					fsize -= b;
					c += b;
				}
				xmode |= XMODE_STAT_FILE;
			}
			else
			{
				c = strlen(str);
			}
			
			scws_send_text(s, str, c);
			bytes = c;
			fprintf(fout, "No. WordString               Attr  Weight(times)\n");
			fprintf(fout, "-------------------------------------------------\n");
			if ((top = xtop = scws_get_tops(s, tlimit, attr)) != NULL)
			{
				tlimit = 1;
				while (xtop != NULL)
				{
					fprintf(fout, "%02d. %-24.24s %-4.2s  %.2f(%d)\n",
						tlimit, xtop->word, xtop->attr, xtop->weight, xtop->times);
					xtop = xtop->next;
					tlimit++;
				}
				scws_free_tops(top);
			}
			else
			{
				fprintf(fout, "EMPTY records!\n");
			}

			if (xmode & XMODE_STAT_FILE)
				free(str);	
		}
	}
	else if (str == NULL)
	{
		str = buf;
		if (fin == NULL)
			fin = stdin;		
		while (fgets(buf, sizeof(buf)-1, fin) != NULL)
		{
			___DOSEGMENT___
		}
	}
	else
	{		
		___DOSEGMENT___
	}

	/* show the prepare time & real time & total bytes? */
	if (!(xmode & XMODE_NO_TIME))
	{	
		gettimeofday(&t3, NULL);
		fprintf(stderr, "\n+--[%s(%s/%s)]----------+\n",
						program_name, PACKAGE_NAME, PACKAGE_VERSION);
		fprintf(stderr, "| TextLen:   %-10d          |\n", bytes);
		fprintf(stderr, "| Prepare:   %-10.4f(sec)     |\n",
						(t2.tv_sec - t1.tv_sec) + (float)(t2.tv_usec - t1.tv_usec)/1000000);
		fprintf(stderr, "| Segment:   %-10.4f(sec)     |\n",
						(t3.tv_sec - t2.tv_sec) + (float)(t3.tv_usec - t2.tv_usec)/1000000);
		fprintf(stderr, "+--------------------------------+\n");
	}

cws_end:

	if (fin != NULL && fin != stdin)
		fclose(fin);
	if (fout != NULL && fout != stdout)
		fclose(fout);

	scws_free(s);
	return 0;	
}
