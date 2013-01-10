<?php
/* ----------------------------------------------------------------------- *\
   PHP版 简易中文分词(SCWS 2/3) 专用词典操作类
   -----------------------------------------------------------------------
   作者: 马明练(hightman) (MSN: MingL_Mar@msn.com) (php-QQ群: 17708754)
   网站: http://www.ftphp.com/scws
   时间: 2005/11/25 (update: 2006/03/06)
   修订: 2008/12/20
   目的: 学习研究交流用, 希望有好的建议及用途能进一步交流.
   $Id: dict.class.php,v 1.1 2008/12/20 12:03:00 hightman Exp $
   -----------------------------------------------------------------------
   词典类的功能: 根据给出词返回词频. 
   支持字典格式: dba(cdb/gdbm):txt(eAccelerator):sqlite(sqlite):xdb(XTreeDB)
   , 根据后缀自动识别
   $dict = new PSCWS23_Dict('dict.xdb');
   $dict->load($fpath);
   $dict->find();   

\* ----------------------------------------------------------------------- */

/**
 * 词典查询所用的类库 
 * ->load($fpath) 加载词典(cdb/gdbm/sqlite/txt)
 * ->find($word)  查词并返回词频
 */
class PSCWS23_Dict 
{
	var $_handler;
	var $_cache;

	var $query_times;
	var $has_cache		= true;

	// 构造函数, 传入路径加载字典
	function PSCWS23_Dict($fpath = '')
	{
		if ($this->has_cache)
			$this->_cache = array();

		$this->query_times = 0;
		$this->_handler = false;

		if ('' != $fpath)
			$this->load($fpath);		
	}

	// for PHP5
	function __construct($fpath = '') { $this->PSCWS23_Dict($fpath); }
	function __destruct() { $this->unload(); }

	// 加载字典 (参数: 词典路径)
	function load($fpath)
	{
		// 重载词典
		if ($this->_handler)
			return $this->_handler->_load($fpath);

		// 建立词典操作句柄
		$ext = strtolower(strrchr($fpath, '.'));
		if ($ext == '.txt')
		{
			$this->_handler = new txt_Dictionary($fpath);
		}
		else if ($ext == '.sqlite')
		{
			$this->_handler = new sql_Dictionary($fpath);
		}
		else if ($ext == '.xdb')
		{
			$this->_handler = new xdb_Dictionary($fpath);
		}
		else
		{
			$this->_handler = new dba_Dictionary($fpath);
		}
	}

	// 查询词并返回词频 (-1: not found)
	function find($word)
	{
		if (!$this->_handler)
		{
			trigger_error('请务必在查词前加载词典', E_USER_WARNING);
			return -1;
		}
		
		$this->query_times++;
		
		// check the cache
		if ($this->has_cache && isset($this->_cache[$word]))
			return $this->_cache[$word];

		// query from dictionary
		$val = $this->_handler->_find($word);
		
		// convert to integer
		$val = (is_bool($val) ? -1 : intval($val));
		
		// save to cache
		if ($this->has_cache)
			$this->_cache[$word] = $val;
		
		return $val;
	}

	// unload the dictionary
	function unload()
	{
		if ($this->_handler)
		{
			$this->_handler->_unload();
			$this->_handler = false;
		}

		if ($this->has_cache)
			$this->_cache = array();
	}

	// 析构
	function _my_Dictionary()
	{
		$this->unload();
	}
}

/**
 * 各种格式操作类  [_load:_find]
 */

// 基于 XDB_R, 后缀为 .xdb
class xdb_Dictionary
{
	var $_dbh;
	
	function xdb_Dictionary($fpath = '')
	{
		// 环境检查
		if (!require_once(dirname(__FILE__) . '/xdb_r.class.php'))
			trigger_error('您的 PHP 环境缺少 `xdb_r` 类文件, 请检查', E_USER_ERROR);

		// 初始化操作
		$this->_dbh = false;
		if ('' != $fpath)
			$this->_load($fpath);
	}

	// for PHP5
	function __construct($fpath = '') { $this->xdb_Dictionary($fpath); }
	function __destruct() { $this->_unload(); }

	function _load($fpath)
	{
		$db = new XDB_R;
		if (!$db->Open($fpath))
			trigger_error("无法打开类型为 xdb 数据文件 `$fpath`", E_USER_ERROR);
		else
		{
			$this->_dbh = $db;
		}
	}

	function _unload()
	{		
		if ($this->_dbh)
		{
			$this->_dbh->Close();
			$this->_dbh = false;
		}
	}

	function _find($word)
	{
		if (!$this->_dbh)
		{
			trigger_error('请务在查词前先加载词典文件', E_USER_WARNING);
			return -1;
		}
		
		return $this->_dbh->Get($word);
	}
}

// 基于 DBA , 支持 cdb/gdbm 等
class dba_Dictionary
{
	var $_dbh;
	
	function dba_Dictionary($fpath = '')
	{
		// 环境检查
		if (!extension_loaded('dba'))
			trigger_error('您的 PHP 环境缺少 `dba` 扩展, 请重新配置 PHP', E_USER_ERROR);

		// 初始化操作
		$this->_dbh = false;
		if ('' != $fpath)
			$this->_load($fpath);
	}

	// for PHP5
	function __construct($fpath = '') { $this->dba_Dictionary($fpath); }
	function __destruct() { $this->_unload(); }

	function _load($fpath)
	{
		$ext = strrchr($fpath, '.');
		$type = ($ext ? strtolower(substr($ext, 1)) : 'gdbm');

		if (!in_array($type, dba_handlers()))
			trigger_error("您的 dba 扩展不支持 `$type` 这一数据类型", E_USER_ERROR);

		$this->_dbh = dba_popen($fpath, 'r', $type);
		if (!$this->_dbh)
			trigger_error("无法打开类型为 `$type` 的 dba 数据文件 `$fpath`", E_USER_ERROR);
	}

	function _unload()
	{		
		if ($this->_dbh)
		{
			dba_close($this->_dbh);
			$this->_dbh = false;
		}
	}

	function _find($word)
	{
		if (!$this->_dbh)
		{
			trigger_error('请务在查词前先加载词典文件', E_USER_WARNING);
			return -1;
		}
		
		return dba_fetch($word, $this->_dbh);
	}
}

// 基于 sqlite, 要求有 sqlite 扩展
// CREATE TABLE _wordlist (id INTEGER NOT NULL PRIMARY KEY, word CHAR(32), freq BIGINT);
// CREATE UNIQUE INDEX _wordidx ON _wordlist (word);
class sql_Dictionary
{
	var $_dbh;

	function sql_Dictionary($fpath = '')
	{
		// 环境检查
		if (!extension_loaded('sqlite'))
			trigger_error('您的 PHP 环境缺少 `sqlite` 扩展, 请重新配置 PHP', E_USER_ERROR);

		$this->_dbh = false;
		if ('' != $fpath)
			$this->_load($fpath);
	}

	// for PHP5
	function __construct($fpath = '') { $this->sql_Dictionary($fpath); }
	function __destruct() { $this->_unload(); }

	function _load($fpath)
	{
		$this->_dbh = sqlite_popen($fpath);
		if (!$this->_dbh)
			trigger_error("无法打开 sqlite 数据库文件 `$fpath`", E_USER_ERROR);
	}

	function _unload($fpath)
	{
		if ($this->_dbh)
		{
			sqlite_close($this->_dbh);
			$this->_dbh = false;
		}		
	}

	function _find($word)
	{
		$word = sqlite_escape_string($word);
		$sql = "SELECT * FROM _wordlist WHERE word = '$word' LIMIT 1";
		$rs = sqlite_unbuffered_query($sql, $this->_dbh);
		if (!$rs)
		{
			$errno = sqlite_last_error($this->_dbh);
			trigger_error("SQLite: " . sqlite_error_string($errno) . "(#{$errno})", E_USER_WARNING);
			trigger_error("SQLite: " . $sql, E_USER_WARNING);		
			return -1;
		}
		
		$ret = sqlite_fetch_array($rs, SQLITE_ASSOC);
		if (!$ret)
			return false;

		return $ret['freq'];
	}
}

// 基于纯文本, [word\tfreq\r\n]
// 自动检测 eAccelerator 的扩展运用
define ('_EAKEY_DICT_',		'ea_dict');
if (!defined('_WORD_ALONE_')) define ('_WORD_ALONE_', 0x4000000);
if (!defined('_WORD_PART_')) define ('_WORD_PART_',	0x8000000);

class txt_Dictionary
{
	var $_wordlist;
	var $_fpath		= 'dict/dict.txt';

	function txt_Dictionary($fpath = '')
	{		
		if ('' != $fpath)
			$this->_load($fpath);
	}

	// for PHP5
	function __construct($fpath = '') { $this->txt_Dictionary($fpath); }
	function __destruct() { $this->_unload(); }

	function _load($fpath)
	{
		$this->_wordlist = false;
		if ('' == $fpath)
			$fpath = $this->fpath;
		
		// 尝试从 ea 中检查数据
		$has_ea = extension_loaded('eAccelerator');
		if ($has_ea)
		{
			$cache_time = eaccelerator_get(_EAKEY_DICT_ . '_time');
			if (!file_exists($fpath) || filemtime($fpath) < $cache_time)
				$this->_wordlist = eaccelerator_get(_EAKEY_DICT_);
		}

		// try to load the wordlist from txt file:
		if ($this->_wordlist)
			return;
		
		// 重新加载
		$this->_wordlist = array();			
		if ($fd = @fopen($fpath, 'r'))
		{
			$dict = &$this->_wordlist;
			while ($line = fgets($fd, 256))
			{
				$line = trim($line);
				list($word, $freq) = explode("\t", $line, 2);
				if (strlen($word) < 4)
					continue;
					
				$first = substr($word, 0, 2);
				if (!isset($dict[$first])) $dict[$first] = array();
					
				// 处理词
				$val = $dict[$first][$word];
				if (!$val || !($val & _WORD_ALONE_))
				{
					if (!$val) $val = 0;
					else $val &= _WORD_PART_;
						
					$val |= _WORD_ALONE_;
					$val += $freq;
					$dict[$first][$word] = $val;
				}
					
				// 处理词段
				$len = strlen($word);
				while ($len > 4)
				{
					$len -= 2;
					$word = substr($word, 0, -2);
					if (!isset($dict[$first][$word]))
						$dict[$first][$word] = 0;
					$dict[$first][$word] |= _WORD_PART_;
				}
			}				
			fclose($fd);
				
			// 存入缓存
			if ($has_ea)
			{
				eaccelerator_rm(_EAKEY_DICT_);
				eaccelerator_put(_EAKEY_DICT_, $dict);
				eaccelerator_put(_EAKEY_DICT_ . '_time', time());
			}
		}		
	}

	function _unload($fpath)
	{
		unset($this->_wordlist);
		$this->_wordlist = false;
	}

	function _find($word)
	{
		if (!$this->_wordlist)
		{
			trigger_error('请务必在查词前加载词典文件', E_USER_WARNING);
			return -1;
		}

		$first = substr($word, 0, 2);
		$value = $this->_wordlist[$first][$word];
		if (!isset($value)) $value = false;
		return $value;
	}
}
?>