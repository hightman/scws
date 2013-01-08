/**
 * @file darray.h (double array)
 * @author Hightman Mar
 * @editor set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
 * $Id$
 */

#ifndef	_SCWS_DARRAY_20070525_H_
#define	_SCWS_DARRAY_20070525_H_

#ifdef __cplusplus
extern "C" {
#endif

void **darray_new(int row, int col, int size);
void darray_free(void **arr);

#ifdef __cplusplus
}
#endif

#endif
