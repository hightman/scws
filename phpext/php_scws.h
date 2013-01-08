/*
  +----------------------------------------------------------------------+
  | PHP Version 4                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2006 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: hightman Mar(MingL_Mar@msn.com) QQ = 16139558                |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifndef PHP_SCWS_H
#define PHP_SCWS_H

extern zend_module_entry scws_module_entry;
#define phpext_scws_ptr &scws_module_entry

#ifdef PHP_WIN32
#define PHP_SCWS_API __declspec(dllexport)
#else
#define PHP_SCWS_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

PHP_MINIT_FUNCTION(scws);
PHP_MSHUTDOWN_FUNCTION(scws);
PHP_RSHUTDOWN_FUNCTION(scws);
PHP_MINFO_FUNCTION(scws);

/* scws core functions */
PHP_FUNCTION(scws_open);
PHP_FUNCTION(scws_new);
PHP_FUNCTION(scws_close);

PHP_FUNCTION(scws_set_charset);
PHP_FUNCTION(scws_add_dict);
PHP_FUNCTION(scws_set_dict);
PHP_FUNCTION(scws_set_rule);
PHP_FUNCTION(scws_set_ignore);
PHP_FUNCTION(scws_set_multi);
PHP_FUNCTION(scws_set_duality);

PHP_FUNCTION(scws_send_text);
PHP_FUNCTION(scws_get_result);
PHP_FUNCTION(scws_get_tops);
PHP_FUNCTION(scws_has_word);
PHP_FUNCTION(scws_get_words);
PHP_FUNCTION(scws_version);

/* xdb support functions (TODO) */
 
ZEND_BEGIN_MODULE_GLOBALS(scws)
	char *default_charset;
	char *default_fpath;
ZEND_END_MODULE_GLOBALS(scws)

/* In every utility function you add that needs to use variables 
   in php_scws_globals, call TSRMLS_FETCH(); after declaring other 
   variables used by that function, or better yet, pass in TSRMLS_CC
   after the last function argument and declare your utility function
   with TSRMLS_DC after the last declared argument.  Always refer to
   the globals in your function as SCWS_G(variable).  You are 
   encouraged to rename these macros something shorter, see
   examples in any other php module directory.
*/

#ifdef ZTS
#define SCWS_G(v) TSRMG(scws_globals_id, zend_scws_globals *, v)
#else
#define SCWS_G(v) (scws_globals.v)
#endif

#endif	/* PHP_SCWS_H */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
