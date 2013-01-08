<?php
/* ----------------------------------------------------------------------- *\
   PHP版简易中文分词第四版(PSCWS v4.0) - 分词核心类库代码
   -----------------------------------------------------------------------
   作者: 马明练(hightman) (MSN: MingL_Mar@msn.com) (php-QQ群: 17708754)
   网站: http://www.ftphp.com/scws/
   时间: 2007/05/20
   修订: 2008/12/20
   编辑: set number ; syntax on ; set autoindent ; set tabstop=4 (vim)
   -----------------------------------------------------------------------
   核心类的功能:

     这是 scws-1.0 (纯C实现) 的一个 PHP 实现方式, 算法和功能一样
     针对输入的字符串文本执行分词, 根据词典N-路径最大概率法分词.
	 
	 支持人名、地名、数字识别；能识别 .NET, C++, Q币 之类特殊词汇
	 支持 UTF-8/GBK 编码, 特别为搜索引擎考量而支持长词再细分的复方分词法
	 使用 UTF-8 可扩展到任何多字节语言分词(如日语，韩语等)

   用法(主要类方法, 与 scws 之 PHP 扩展版兼容用法):

   class PSCWS4 { 
	   void close(void);
	   void set_charset(string charset);
	   bool set_dict(string dict_path);
	   void set_rule(string rule_path);
	   void set_ignore(bool set);
	   void set_multi(int level);
	   void set_debug(bool set);
	   void set_duality(bool set);	   

	   void send_text(string text);
	   mixed get_result(void);
	   mixed get_tops( [int limit [, string attr]] );

	   string version(void);
   };
   
\* ----------------------------------------------------------------------- */

/** 词典读取代码 (xdb_r) */
require_once (dirname(__FILE__) . '/xdb_r.class.php');

/** defines for ruleset */
define ('PSCWS4_RULE_MAX',		31);	// just 31, PHP do not support unsigined Int
define ('PSCWS4_RULE_SPECIAL',	0x80000000);
define ('PSCWS4_RULE_NOSTATS',	0x40000000);
define ('PSCWS4_ZRULE_NONE',	0x00);
define ('PSCWS4_ZRULE_PREFIX',	0x01);
define ('PSCWS4_ZRULE_SUFFIX',	0x02);
define ('PSCWS4_ZRULE_INCLUDE',	0x04);	// with include
define ('PSCWS4_ZRULE_EXCLUDE',	0x08);	// with exclude
define ('PSCWS4_ZRULE_RANGE',	0x10);	// with znum range

/** defines for mode of scws <= 0x800 */
define ('PSCWS4_IGN_SYMBOL',	0x01);
define ('PSCWS4_DEBUG',			0x02);
define ('PSCWS4_DUALITY',		0x04);

/** multi segment policy >= 0x1000 */
define ('PSCWS4_MULTI_NONE',    0x0000);		// nothing
define ('PSCWS4_MULTI_SHORT',	0x1000);		// split long words to short words from left to right
define ('PSCWS4_MULTI_DUALITY',	0x2000);		// split every long words(3 chars?) to two chars
define ('PSCWS4_MULTI_ZMAIN',   0x4000);		// split to main single chinese char atr = j|a|n?|v?
define ('PSCWS4_MULTI_ZALL',	0x8000);		// attr = ** , all split to single chars
define ('PSCWS4_MULTI_MASK',	0xf000);		// mask check for multi set
define ('PSCWS4_ZIS_USED',		0x8000000);

/** single bytes segment flag (纯单字节字符) */
define ('PSCWS4_PFLAG_WITH_MB',	0x01);
define ('PSCWS4_PFLAG_ALNUM',	0x02);
define ('PSCWS4_PFLAG_VALID',	0x04);
define ('PSCWS4_PFLAG_DIGIT',	0x08);
define ('PSCWS4_PFLAG_ADDSYM',	0x10);

/** constant var define */
define ('PSCWS4_WORD_FULL',		0x01);	// 多字: 整词
define ('PSCWS4_WORD_PART',		0x02);	// 多字: 前词段
define ('PSCWS4_WORD_USED',		0x04);	// 多字: 已使用
define ('PSCWS4_WORD_RULE',		0x08);	// 多字: 自动识别的

define ('PSCWS4_ZFLAG_PUT',		0x02);	// 单字: 已使用
define ('PSCWS4_ZFLAG_N2',		0x04);	// 单字: 双字名词头
define ('PSCWS4_ZFLAG_NR2',		0x08);	// 单字: 词头且为双字人名
define ('PSCWS4_ZFLAG_WHEAD',	0x10);	// 单字: 词头
define ('PSCWS4_ZFLAG_WPART',	0x20);	// 单字: 词尾或词中
define ('PSCWS4_ZFLAG_ENGLISH',	0x40);	// 单字: 夹在中间的英文
define ('PSCWS4_ZFLAG_SYMBOL',	0x80);	// 单字: 符号系列

define ('PSCWS4_MAX_EWLEN',		16);
define ('PSCWS4_MAX_ZLEN',		128);

/** 主类库代码 */
class PSCWS4
{	
	var $_xd;		// xdb dict handler
	var $_rs;		// ruleset resource
	var $_rd;		// ruleset data
	var $_cs = '';	// charset
	var $_ztab;		// zi len table
	var $_mode = 0;	// scws mode
	var $_txt;		// text string
	var $_res;
	var $_zis;		// z if used?(duality)
	var $_off = 0;
	var $_len = 0;
	var $_wend = 0;
	var $_wmap;
	var $_zmap;

	// 构造函数
	function PSCWS4($charset = 'gbk')
	{
		$this->_xd = false;
		$this->_rs = $this->_rd = array();
		$this->set_charset($charset);
	}

	// FOR PHP5
	function __construct() { $this->PSCWS4(); }
	function __destruct() { $this->close(); }

	// 设置字符集(ztab)
	function set_charset($charset = 'gbk')
	{
		$charset = strtolower(trim($charset));
		if ($charset !== $this->_cs)
		{
			$this->_cs = $charset;
			
			// charset's mblen map, only for utf-8 & gbk(big5)
			$this->_ztab = array_fill(0, 0x81, 1);
			if ($charset == 'utf-8' || $charset == 'utf8')
			{
				// UTF-8
				$this->_ztab = array_pad($this->_ztab, 0xc0, 1);
				$this->_ztab = array_pad($this->_ztab, 0xe0, 2);
				$this->_ztab = array_pad($this->_ztab, 0xf0, 3);
				$this->_ztab = array_pad($this->_ztab, 0xf8, 4);
				$this->_ztab = array_pad($this->_ztab, 0xfc, 5);
				$this->_ztab = array_pad($this->_ztab, 0xfe, 6);
				$this->_ztab[] = 1;
			}
			else
			{
				// GBK & BIG5
				$this->_ztab = array_pad($this->_ztab, 0xff, 2);
			}
			$this->_ztab[] = 1;
		}		
	}

	// 设置词典
	function set_dict($fpath)
	{
		$xdb = new XDB_R;
		if (!$xdb->Open($fpath)) return false;
		$this->_xd = $xdb;
	}

	// 设置规则集
	function set_rule($fpath)
	{
		$this->_rule_load($fpath);
	}

	// 设置忽略符号与无用字符
	function set_ignore($yes)
	{
		if ($yes == true) $this->_mode |= PSCWS4_IGN_SYMBOL;
		else $this->_mode &= ~PSCWS4_IGN_SYMBOL;
	}

	// 设置复合分词等级 ($level = 0,15)
	function set_multi($level)
	{	
		$level = (intval($level) << 12);

		$this->_mode &= ~PSCWS4_MULTI_MASK;
		if ($level & PSCWS4_MULTI_MASK) $this->_mode |= $level;
	}

	// 设置是否显示分词调试信息
	function set_debug($yes)
	{
		if ($yes == true) $this->_mode |= PSCWS4_DEBUG;
		else $this->_mode &= ~PSCWS4_DEBUG;
	}

	// 设置是否自动将散字二元化
	function set_duality($yes)
	{
		if ($yes == true) $this->_mode |= PSCWS4_DUALITY;
		else $this->_mode &= ~PSCWS4_DUALITY;
	}

	// 设置要分词的文本字符串
	function send_text($text)
	{
		$this->_txt = (string) $text;
		$this->_len = strlen($this->_txt);
		$this->_off = 0;
	}

	// 取回一批分词结果(需要多次调用, 直到返回 false)
	function get_result()
	{
		$off = $this->_off;
		$len = $this->_len;
		$txt = $this->_txt;
		$this->_res = array();

		while (($off < $len) && (ord($txt[$off]) <= 0x20))
		{
			if ($txt[$off] == "\r" || $txt[$off] == "\n")
			{
				$this->_off = $off + 1;
				$this->_put_res($off, 0, 1, 'un');
				return $this->_res;
			}
			$off++;
		}
		if ($off >= $len) return false;
		
		// try to parse the sentence
		$this->_off = $off;
		$ch = $txt[$off];
		$cx = ord($ch);
		if ($this->_char_token($ch))
		{
			$this->_off++;
			$this->_put_res($off, 0, 1, 'un');
			return $this->_res;
		}

		$clen = $this->_ztab[$cx];
		$zlen = 1;
		$pflag = ($clen > 1 ? PSCWS4_PFLAG_WITH_MB : ($this->_is_alnum($cx) ? PSCWS4_PFLAG_ALNUM : 0));
		while (($off = ($off + $clen)) < $len)
		{
			$ch = $txt[$off];
			$cx = ord($ch);
			if ($cx <= 0x20 || $this->_char_token($ch)) break;
			$clen = $this->_ztab[$cx];
			if (!($pflag & PSCWS4_PFLAG_WITH_MB))
			{
				// pure single-byte -> multibyte (2bytes)
				if ($clen == 1)
				{
					if (($pflag & PSCWS4_PFLAG_ALNUM) && !$this->_is_alnum($cx))
						$pflag ^= PSCWS4_PFLAG_ALNUM;
				}
				else
				{
					if (!($pflag & PSCWS4_PFLAG_ALNUM) || $zlen > 2) break;
					$pflag |= PSCWS4_PFLAG_WITH_MB;
				}
			}
			else if (($pflag & PSCWS4_PFLAG_WITH_MB) && $clen == 1)
			{
				// mb + single-byte. allowd: alpha+num + 中文
				if (!$this->_is_alnum($cx)) break;

				$pflag &= ~PSCWS4_PFLAG_VALID;
				for ($i = $off+1; $i < ($off+3); $i++)
				{
					$ch = $txt[$i];
					$cx = ord($ch);
					if (($i >= $len) || ($cx <= 0x20) || ($this->_ztab[$cx] > 1))
					{
						$pflag |= PSCWS4_PFLAG_VALID;
						break;
					}
					if (!$this->_is_alnum($cx)) break;
				}

				if (!($pflag & PSCWS4_PFLAG_VALID)) break;
				$clen += ($i - $off - 1);
			}
			// hightman.070813: add max zlen limit
			if (++$zlen >= PSCWS4_MAX_ZLEN) break;
		}
		
		// hightman.070624: 处理半个字的问题
		if (($ch = $off) > $len)	
			$off -= $clen;
		
		// do the real segment
		if ($off <= $this->_off) return false;
		else if ($pflag & PSCWS4_PFLAG_WITH_MB) $this->_msegment($off, $zlen);
		else if (!($pflag & PSCWS4_PFLAG_ALNUM) || (($off - $this->_off) >= PSCWS4_MAX_EWLEN)) $this->_ssegment($off);
		else
		{
			$zlen = $off - $this->_off;
			$this->_put_res($this->_off, 2.5*log($zlen), $zlen, 'en');
		}
		
		// reutrn the result
		$this->_off = ($ch > $len ? $len : $off);
		if (count($this->_res) == 0)
			return $this->get_result();

		return $this->_res;
	}

	// 取回频率和权重综合最大的前 N 个词
	function get_tops($limit = 10, $xattr = '')
	{
		$ret = array();
		if (!$this->_txt) return false;

		$xmode = false;
		$attrs = array();
		if ($xattr != '')
		{
			if (substr($xattr, 0, 1) == '~')
			{
				$xattr = substr($xattr, 1);
				$xmode = true;
			}
			foreach (explode(',', $xattr) as $tmp)
			{
				$tmp = strtolower(trim($tmp));
				if (!empty($tmp)) $attrs[$tmp] = true;
			}
		}

		// save the old offset
		$off = $this->_off;
		$this->_off = $cnt = 0;
		$list = array();

		while ($tmpa = $this->get_result())
		{
			foreach ($tmpa as $tmp)
			{
				if ($tmp['idf'] < 0.2 || substr($tmp['attr'], 0, 1) == '#') continue;

				// check attr filter
				if (count($attrs) > 0)
				{
					if ($xmode == true && !isset($attrs[$tmp['attr']])) continue;
					if ($xmode == false && isset($attrs[$tmp['attr']])) continue;
				}

				// check stopwords
				$word = strtolower($tmp['word']);
				if ($this->_rule_checkbit($word, PSCWS4_RULE_NOSTATS)) continue;

				// put to list
				if (isset($list[$word]))
				{
					$list[$word]['weight'] += $tmp['idf'];
					$list[$word]['times']++;
				}
				else
				{
					$list[$word] = array('word'=>$tmp['word'], 'times'=>1, 'weight'=>$tmp['idf'], 'attr'=>$tmp['attr']);
				}
			}
		}
		
		// restore the offset
		$this->_off = $off;

		// sort it & return
		$cmp_func = create_function('$a,$b', 'return ($b[\'weight\'] > $a[\'weight\'] ? 1 : -1);');
		usort($list, $cmp_func);
		if (count($list) > $limit) $list = array_slice($list, 0, $limit);

		return $list;
	}

	// 关闭释放资源
	function close()
	{
		// free the dict
		if ($this->_xd)
		{
			$this->_xd->Close();
			$this->_xd = false;
		}

		// free the ruleset
		$this->_rd = array();
		$this->_rs = array();
	}

	// 版本
	function version()
	{
		return sprintf('PSCWS/4.0 - by hightman');
	}

	////////////////////////////////////////////
	// these are all private functions
	////////////////////////////////////////////
	function _rule_load($fpath)
	{
		if (!($fd = fopen($fpath, 'r'))) return false;
		$this->_rs = array();
		
		// quick scan to add the name to list
		$i = $j = 0;
		while ($buf = fgets($fd, 512))
		{
			if (substr($buf, 0, 1) != '[' || !($pos = strpos($buf, ']')))
				continue;
			if ($pos == 1 || $pos > 15) continue;

			$key = strtolower(substr($buf, 1, $pos - 1));
			if (isset($this->_rs[$key])) continue;
			$item = array('tf'=>5.0, 'idf'=>3.5, 'attr'=>'un', 'bit'=>0, 'flag'=>0, 'zmin'=>0, 'zmax'=>0, 'inc'=>0, 'exc'=>0);
			if ($key == 'special') $item['bit'] = PSCWS4_RULE_SPECIAL;
			else if ($key == 'nostats') $item['bit'] = PSCWS4_RULE_NOSTATS;
			else 
			{
				$item['bit'] = (1<<$j);
				$j++;
			}
			$this->_rs[$key] = $item;
			if (++$i >= PSCWS4_RULE_MAX)
				break;
		}

		// load the ruleset
		rewind($fd);
		$rbl = false;
		unset($item);
		while ($buf = fgets($fd, 512))
		{
			$ch = substr($buf, 0, 1);
			if ($ch == ';') continue;
			if ($ch == '[')
			{
				unset($item);
				if (($pos = strpos($buf, ']')) > 1)
				{
					$key = strtolower(substr($buf, 1, $pos - 1));
					if (isset($this->_rs[$key]))
					{
						$rbl = true;	// defalut read by line = yes
						$item = &$this->_rs[$key];
					}
				}
				continue;
			}

			// param set: line|znum|include|exclude|type|tf|idf|attr */
			if ($ch == ':')
			{
				$buf = substr($buf, 1);
				if (!($pos = strpos($buf, '='))) continue;
				list($pkey, $pval) = explode('=', $buf, 2);
				$pkey = trim($pkey);
				$pval = trim($pval);
				if ($pkey == 'line') $rbl = (strtolower(substr($pval, 0, 1)) == 'n' ? false : true);
				else if ($pkey == 'tf') $item['tf'] = floatval($pval);
				else if ($pkey == 'idf') $item['idf'] = floatval($pval);
				else if ($pkey == 'attr') $item['attr'] = $pval;	// 2bytes?
				else if ($pkey == 'znum') 
				{
					if ($pos = strpos($pval, ','))
					{
						$item['zmax'] = intval(trim(substr($pval, $pos+1)));
						$item['flag'] |= PSCWS4_ZRULE_RANGE;
						$pval = substr($pval, 0, $pos);
					}
					$item['zmin'] = intval($pval);
				}
				else if ($pkey == 'type')
				{
					if ($pval == 'prefix') $item['flag'] |= PSCWS4_ZRULE_PREFIX;
					if ($pval == 'suffix') $item['flag'] |= PSCWS4_ZRULE_SUFFIX;
				}
				else if ($pkey == 'include' || $pkey == 'exclude')
				{
					$clude = 0;
					foreach (explode(',', $pval) as $tmp)
					{
						$tmp = strtolower(trim($tmp));
						if (!isset($this->_rs[$tmp])) continue;
						$clude |= $this->_rs[$tmp]['bit'];
					}
					if ($pkey == 'include') 
					{
						$item['inc'] |= $clude;
						$item['flag'] |= PSCWS4_ZRULE_INCLUDE;
					}
					else
					{
						$item['exc'] |= $clude; 
						$item['flag'] |= PSCWS4_ZRULE_EXCLUDE;
					}
				}
				continue;
			}
			
			// read the entries
			if (!isset($item)) continue;
			$buf = trim($buf);
			if (empty($buf)) continue;

			// save the record
			if ($rbl) $this->_rd[$buf] = &$item;
			else
			{
				$len = strlen($buf);
				for ($off = 0; $off < $len; )
				{
					$ord = ord(substr($buf, $off, 1));
					$zlen = $this->_ztab[$ord];
					if ($off + $zlen >= $len) break;
					$zch = substr($buf, $off, $zlen);
					$this->_rd[$zch] = &$item;
					$off += $zlen;
				}
			}
		}
	}

	// get the ruleset
	function _rule_get($str)
	{
		if (!isset($this->_rd[$str])) return false;
		return $this->_rd[$str];
	}

	// check the bit with str
	function _rule_checkbit($str, $bit)
	{
		if (!isset($this->_rd[$str])) return false;
		$bit2 = $this->_rd[$str]['bit'];
		return ($bit & $bit2 ? true : false);
	}

	// check the rule include | exclude
	function _rule_check($rule, $str)
	{
		if (($rule['flag'] & PSCWS4_ZRULE_INCLUDE) && !$this->_rule_checkbit($str, $rule['bit']))
			return false;
		if (($rule['flag'] & PSCWS4_ZRULE_EXCLUDE) && $this->_rule_checkbit($str, $rule['bit']))
			return false;
		return true;
	}

	// bulid res
	function _put_res($o, $i, $l, $a)
	{
		$word = substr($this->_txt, $o, $l);
		$item = array('word'=>$word, 'off'=>$o, 'idf'=>$i, 'len'=>$l, 'attr'=>$a);
		$this->_res[] = $item;		
	}

	// alpha, numeric check by ORD value
	function _is_alnum($c)
	{
		return (($c>=48&&$c<=57)||($c>=65&&$c<=90)||($c>=97&&$c<=122));
	}

	function _is_alpha($c)
	{
		return (($c>=65&&$c<=90)||($c>=97&&$c<=122));
	}

	function _is_ualpha($c)
	{
		return ($c>=65&&$c<=90);
	}

	function _is_digit($c)
	{
		return ($c>=48&&$c<=57);
	}

	function _no_rule1($f)
	{
		return (($f & (PSCWS4_ZFLAG_SYMBOL|PSCWS4_ZFLAG_ENGLISH)) || (($f & (PSCWS4_ZFLAG_WHEAD|PSCWS4_ZFLAG_NR2)) == PSCWS4_ZFLAG_WHEAD));
	}

	function _no_rule2($f)
	{
		//return (($f & PSCWS4_ZFLAG_ENGLISH) || (($f & (PSCWS4_ZFLAG_WHEAD|PSCWS4_ZFLAG_N2)) == PSCWS4_ZFLAG_WHEAD));
		return $this->_no_rule1($f);
	}

	function _char_token($c)
	{
		return ($c=='('||$c==')'||$c=='['||$c==']'||$c=='{'||$c=='}'||$c==':'||$c=='"');
	}

	// query the dict
	function _dict_query($word)
	{
		if (!$this->_xd) return false;
		$value = $this->_xd->Get($word);
		if (!$value) return false;
		
		$tmp = unpack('ftf/fidf/Cflag/a3attr', $value);
		return $tmp;
	}

	// ssegment, 单字节用语切割
	function _ssegment($end)
	{
		$start = $this->_off;
		$wlen = $end - $start;
		
		// check special words (need strtoupper)
		if ($wlen > 1)
		{
			$txt = strtoupper(substr($this->_txt, $start, $wlen));
			if ($this->_rule_checkbit($txt, PSCWS4_RULE_SPECIAL))
			{
				$this->_put_res($start, 9.5, $wlen, 'nz');
				return;
			}
		}
		
		$txt = $this->_txt;	
		
		// check brief words such as S.H.E M.R.
		if ($this->_is_ualpha(ord($txt[$start])) && $txt[$start+1] == '.')
		{
			for ($ch = $start + 2; $ch < $end; $ch++)
			{
				if (!$this->_is_ualpha(ord($txt[$ch]))) break;
				$ch++;
				if ($ch == $end || $txt[$ch] != '.') break;
			}
			if ($ch == $end)
			{
				$this->_put_res($start, 7.5, $wlen, 'nz');
				return;
			}
		}
		
		// 取出单词及标点. 数字允许一个点且下一个为数字,不连续的. 字母允许一个不连续的'
		while ($start < $end)
		{
			$ch = $txt[$start++];
			$cx = ord($ch);
			if ($this->_is_alnum($cx))
			{
				$pflag = $this->_is_digit($cx) ? PSCWS4_PFLAG_DIGIT : 0;
				$wlen = 1;
				while ($start < $end)
				{
					$ch = $txt[$start];
					$cx = ord($ch);
					if ($pflag & PSCWS4_PFLAG_DIGIT)
					{
						if (!$this->_is_digit($cx))
						{
							if (($pflag & PSCWS4_PFLAG_ADDSYM) || $cx != 0x2e || !$this->_is_digit(ord($txt[$start+1])))
								break;
							$pflag |= PSCWS4_PFLAG_ADDSYM;
						}
					}
					else
					{
						if (!$this->_is_alpha($cx))
						{
							if (($pflag & PSCWS4_PFLAG_ADDSYM) || $cx != 0x27 || !$this->_is_alpha(ord($txt[$start+1])))
								break;
							$pflag |= PSCWS4_PFLAG_ADDSYM;
						}
					}
					$start++;					
					if (++$wlen >= PSCWS4_MAX_EWLEN) break;
				}
				$this->_put_res($start - $wlen, 2.5*log($wlen), $wlen, 'en');
			}
			else if (!($this->_mode & PSCWS4_IGN_SYMBOL))
			{
				$this->_put_res($start-1, 0, 1, 'un');
			}
		}
	}

	// get one z by ZMAP
	function _get_zs($i, $j = -1)
	{
		if ($j == -1) $j = $i;
		return substr($this->_txt, $this->_zmap[$i]['start'], $this->_zmap[$j]['end'] - $this->_zmap[$i]['start']);
	}

	// mget_word
	function _mget_word($i, $j)
	{
		$wmap = $this->_wmap;

		if (!($wmap[$i][$i]['flag'] & PSCWS4_ZFLAG_WHEAD)) return $i;		
		for ($r = $i, $k = $i+1; $k <= $j; $k++)
		{
			if ($wmap[$i][$k] && ($wmap[$i][$k]['flag'] & PSCWS4_WORD_FULL)) $r = $k;			
		}
		return $r;
	}

	// mset_word
	function _mset_word($i, $j)
	{
		$wmap = $this->_wmap;
		$zmap = $this->_zmap;
		$item = $wmap[$i][$j];
		
		// hightman.070705: 加入 item == null 判断, 防止超长词(255字以上)unsigned char溢出
		if (($item == false) || (($this->_mode & PSCWS4_IGN_SYMBOL) 
			&& !($item['flag'] & PSCWS4_ZFLAG_ENGLISH) && $item['attr'] == 'un'))
		{
			return;
		}
		
		// hightman.070701: 散字自动二元聚合
		if ($this->_mode & PSCWS4_DUALITY)
		{
			$k = $this->_zis;
			if ($i == $j && !($item['flag'] & PSCWS4_ZFLAG_ENGLISH) && $item['attr'] == 'un')
			{
				$this->_zis = $i;
				if ($k < 0) return;
				
				$i = ($k & ~PSCWS4_ZIS_USED);
				if (($i != ($j-1)) || (!($k & PSCWS4_ZIS_USED) && $this->_wend == $i))
				{
					$this->_put_res($zmap[$i]['start'], $wmap[$i][$i]['idf'], $zmap[$i]['end'] - $zmap[$i]['start'], $wmap[$i][$i]['attr']);
					if ($i != ($j-1)) return;
				}
				$this->_zis |= PSCWS4_ZIS_USED;
			}
			else
			{
				if (($k >= 0) && (!($k & PSCWS4_ZIS_USED) || ($j > $i)))
				{
					$k &= ~PSCWS4_ZIS_USED;
					$this->_put_res($zmap[$k]['start'], $wmap[$k][$k]['idf'], $zmap[$k]['end'] - $zmap[$k]['start'], $wmap[$k][$k]['attr']);
				}
				if ($j > $i) $this->_wend = $j + 1;
				$this->_zis = -1;
			}
		}

		// save the res
		$this->_put_res($zmap[$i]['start'], $item['idf'], $zmap[$j]['end'] - $zmap[$i]['start'], $item['attr']);
		
		// hightman.070902: multi segment
		// step1: split to short words
		if (($j-$i) > 1)
		{
			$m = $i;
			if ($this->_mode & PSCWS4_MULTI_SHORT)
			{
				while ($m < $j)
				{
					$k = $m;
					for ($n = $m + 1; $n <= $j; $n++)
					{
						if ($n == $j && $m == $i) break;
						$item = $wmap[$m][$n];
						if ($item && ($item['flag'] & PSCWS4_WORD_FULL))
						{
							$k = $n;
							$this->_put_res($zmap[$m]['start'], $item['idf'], $zmap[$n]['end'] - $zmap[$m]['start'], $item['attr']);
							if (!($item['flag'] & PSCWS4_WORD_PART)) break; 
						}
					}
					if ($k == $m)
					{
						if ($m == $i) break;									
						$item = $wmap[$m][$m];
						$this->_put_res($zmap[$m]['start'], $item['idf'], $zmap[$m]['end'] - $zmap[$m]['start'], $item['attr']);
					}
					if (($m = ($k+1)) == $j)
					{
						$m--;
						break;
					}
				}
			}
			if ($this->_mode & PSCWS4_MULTI_DUALITY)
			{
				while ($m < $j)
				{
					$this->_put_res($zmap[$m]['start'], $wmap[$m][$m]['idf'], $zmap[$m+1]['end'] - $zmap[$m]['start'], $wmap[$m][$m]['attr']);
					$m++;
				}
			}
		}
		
		// step2, split to single char
		if (($j > $i) && ($this->_mode & (PSCWS4_MULTI_ZMAIN|PSCWS4_MULTI_ZALL)))
		{
			if (($j - $i) == 1 && !$wmap[$i][$j])
			{
				if ($wmap[$i][$i]['flag'] & PSCWS4_ZFLAG_PUT) $i++;
				else $wmap[$i][$i]['flag'] |= PSCWS4_ZFLAG_PUT;
				$wmap[$j][$j]['flag'] |= PSCWS4_ZFLAG_PUT;
			}
			do
			{
				if ($wmap[$i][$i]['flag'] & PSCWS4_ZFLAG_PUT) continue;
				if (!($this->_mode & PSCWS4_MULTI_ZALL) && !strchr("jnv", substr($wmap[$i][$i]['attr'],0,1))) continue;
				$this->_put_res($zmap[$i]['start'], $wmap[$i][$i]['idf'], $zmap[$i]['end'] - $zmap[$i]['start'], $wmap[$i][$i]['attr']);
			}
			while (++$i <= $j);
		}
	}

	// mseg_zone
	function _mseg_zone($f, $t)
	{
		$weight = $nweight = 0.0;
		$wmap = &$this->_wmap;
		$zmap = $this->_zmap;
		$mpath = $npath = false;
		
		for ($x = $i = $f; $i <= $t; $i++)
		{
			$j = $this->_mget_word($i, $t);
			if ($j == $i || $j <= $x || (/* $i > $x && */($wmap[$i][$j]['flag'] & PSCWS4_WORD_USED))) continue;
			
			// one word only
			if ($i == $f && $j == $t)
			{
				$mpath = array($j - $i, 0xff);
				break;
			}
			if ($i != $f && ($wmap[$i][$j]['flag'] & PSCWS4_WORD_RULE)) continue;
			
			// create the new path 
			$wmap[$i][$j]['flag'] |= PSCWS4_WORD_USED;
			$nweight = $wmap[$i][$j]['tf'] * ($j - $i + 1);
			if ($i == $f) $nweight *= 1.2;
			else if ($j == $t) $nweight *= 1.4;

			// create the npath
			if ($npath == false)			
				$npath = array_fill(0, $t-$f+2, 0xff);
			
			// lookfor backward
			$x = 0;
			for ($m = $f; $m < $i; $m = $n+1)
			{
				$n = $this->_mget_word($m, $i-1);
				$nweight *= $wmap[$m][$n]['tf'] * ($n-$m+1);
				$npath[$x++] = $n - $m;
				if ($n > $m) $wmap[$m][$n]['flag'] |= PSCWS4_WORD_USED;
			}
			
			// my self 
			$npath[$x++] = $j - $i;
			
			// lookfor forward
			for ($m = $j+1; $m <= $t; $m = $n+1)
			{
				$n = $this->_mget_word($m, $t);
				$nweight *= $wmap[$m][$n]['tf'] * ($n-$m+1);
				$npath[$x++] = $n - $m;
				if ($n > $m) $wmap[$m][$n]['flag'] |= PSCWS4_WORD_USED;			
			}
			
			$npath[$x] = 0xff;
			$nweight /= pow($x-1,4);
			
			// draw the path for debug 
			if ($this->_mode & PSCWS4_DEBUG)
			{
				printf("PATH by keyword = %s, (weight=%.4f):\n", $this->_get_zs($i, $j), $nweight);
				for ($x = 0, $m = $f; ($n = $npath[$x]) != 0xff; $x++)
				{
					$n += $m;
					echo $this->_get_zs($m, $n) . " ";
					$m = $n + 1;
				}
				echo "\n--\n";
			}
			
			$x = $j;
			
			// check better path
			if ($nweight > $weight)
			{
				$weight = $nweight;
				$swap = $mpath;
				$mpath = $npath;
				$npath = $swap;
				unset($swap);
			}		
		}
		
		// set the result, mpath != NULL
		if ($mpath == false) return;
		for ($x = 0, $m = $f; ($n = $mpath[$x]) != 0xff; $x++)
		{
			$n += $m;
			$this->_mset_word($m, $n);
			$m = $n + 1;
		}
	}

	// msegment(重点函数)
	function _msegment($end, $zlen)
	{
		$this->_wmap = array_fill(0, $zlen, array_fill(0, $zlen, false));
		$this->_zmap = array_fill(0, $zlen, false);
		$wmap = &$this->_wmap;
		$zmap = &$this->_zmap;
		$txt = $this->_txt;
		$start = $this->_off;
		$this->_zis = -1;

		// load the zmap
		for ($i = 0; $start < $end; $i++)
		{
			$ch = $txt[$start];
			$cx = ord($ch);
			$clen = $this->_ztab[$cx];
			if ($clen == 1)
			{
				while ($start++ < $end)
				{
					$cx = ord($txt[$start]);
					if ($this->_ztab[$cx] > 1) break;
					$clen++;				
				}
				$wmap[$i][$i] = array('tf'=>0.5, 'idf'=>0, 'flag'=>PSCWS4_ZFLAG_ENGLISH, 'attr'=>'un');
			}
			else
			{
				$query = $this->_dict_query(substr($txt, $start, $clen));
				if (!$query) $wmap[$i][$i] = array('tf'=>0.5, 'idf'=>0, 'flag'=>0, 'attr'=>'un');
				else 
				{					
					if (substr($query['attr'],0,1) == '#') $query['flag'] |= PSCWS4_ZFLAG_SYMBOL;
					$wmap[$i][$i] = $query;
				}
				$start += $clen;
			}
			
			$zmap[$i] = array('start'=>$start-$clen, 'end'=>$start);
		}	
		
		// fixed real zlength 
		$zlen = $i;
		
		// create word query table
		for ($i = 0; $i < $zlen; $i++)
		{
			$k = 0;
			for ($j = $i+1; $j < $zlen; $j++)
			{
				$query = $this->_dict_query($this->_get_zs($i, $j));
				if (!$query) break;
				
				$ch = $query['flag'];
				if ($ch & PSCWS4_WORD_FULL)
				{
					$wmap[$i][$j] = $query;
					$wmap[$i][$i]['flag'] |= PSCWS4_ZFLAG_WHEAD;
					
					for ($k = $i+1; $k <= $j; $k++) $wmap[$k][$k]['flag'] |= PSCWS4_ZFLAG_WPART;
				}
				if (!($ch & PSCWS4_WORD_PART)) break;
			}

			if ($k--)
			{
				// set nr2 to some short name
				if ($k == ($i+1))
				{
					if ($wmap[$i][$k]['attr'] == 'nr') $wmap[$i][$i]['flag'] |= PSCWS4_ZFLAG_NR2;
					//if (substr($wmap[$i][$k]['attr'], 0, 1) == 'n') $wmap[$i][$i]['flag'] |= PSCWS4_ZFLAG_N2;
				}
				
				// clean the PART flag for the last word
				if ($k < $j) $wmap[$i][$k]['flag'] ^= PSCWS4_WORD_PART;
			}
		}

		// try to do the ruleset match
		// for name & zone & chinese numeric
		if (count($this->_rd) > 0)
		{
			// check for 'one word'
			for ($i = 0; $i < $zlen; $i++)
			{
				if ($this->_no_rule1($wmap[$i][$i]['flag'])) continue;
				$r1 = $this->_rule_get($this->_get_zs($i));
				if (!$r1) continue;
				$clen = ($r1['zmin'] > 0 ? $r1['zmin'] : 1);
				
				if (($r1['flag'] & PSCWS4_ZRULE_PREFIX) && ($i < ($zlen - $clen)))
				{
					// prefix, check after (zmin~zmax)
					// 先检查 zmin 字内是否全部符合要求, 再在 zmax 范围内取得符合要求的字
					for ($ch = 1; $ch <= $clen; $ch++)
					{
						$j = $i + $ch;
						if ($j >= $zlen || $this->_no_rule2($wmap[$j][$j]['flag'])) break;
						if (!$this->_rule_check($r1, $this->_get_zs($j))) break;
					}
					if ($ch <= $clen) continue;
					
					// no limit znum or limit to a range
					$j = $i + $ch;
					while (true)
					{
						if ((!$r1['zmax'] && $r1['zmin']) || ($r1['zmax'] && ($clen >= $r1['zmax']))) break;
						if ($j >= $zlen || $this->_no_rule2($wmap[$j][$j]['flag'])) break;
						if (!$this->_rule_check($r1, $this->_get_zs($j))) break;
						$clen++;
						$j++;
					}
					
					// 注意原来2字人名,识别后仍为2字的情况
					if ($wmap[$i][$i]['flag'] & PSCWS4_ZFLAG_NR2)
					{
						if ($clen == 1) continue;
						$wmap[$i][$i+1]['flag'] |= PSCWS4_WORD_PART;
					}

					// ok, got: i & clen 
					$k = $i + $clen;
					$wmap[$i][$k] = array('tf'=>$r1['tf'], 'idf'=>$r1['idf'], 'flag'=>(PSCWS4_WORD_RULE|PSCWS4_WORD_FULL), 'attr'=>$r1['attr']);
					$wmap[$i][$i]['flag'] |= PSCWS4_ZFLAG_WHEAD;
					for ($j = $i+1; $j <= $k; $j++) $wmap[$j][$j]['flag'] |= PSCWS4_ZFLAG_WPART;
					
					if (!($wmap[$i][$i]['flag'] & PSCWS4_ZFLAG_WPART)) $i = $k;
					continue;
				}
				
				if (($r1['flag'] & PSCWS4_ZRULE_SUFFIX) && ($i >= $clen))
				{
					// suffix, check before
					for ($ch = 1; $ch <= $clen; $ch++)
					{
						$j = $i - $ch;
						if ($j < 0 || $this->_no_rule2($wmap[$j][$j]['flag'])) break;
						if (!$this->_rule_check($r1, $this->_get_zs($j))) break;
					}
					if ($ch <= $clen) continue;
					
					// no limit znum or limit to a range
					$j = $i - $ch;
					while (true)
					{
						if ((!$r1['zmax'] && $r1['zmin']) || ($r1['zmax'] && ($clen >= $r1['zmax']))) break;
						if ($j < 0 || $this->_no_rule2($wmap[$j][$j]['flag'])) break;
						if (!$this->_rule_check($r1, $this->_get_zs($j))) break;
						$clen++;
						$j--;
					}
					
					// ok, got: i & clen (maybe clen=1 & [k][i] isset)
					$k = $i - $clen;
					if ($wmap[$k][$i] != false) continue;
					$wmap[$k][$i] = array('tf'=>$r1['tf'], 'idf'=>$r1['idf'], 'flag'=>PSCWS4_WORD_FULL, 'attr'=>$r1['attr']);
					$wmap[$k][$k]['flag'] |= PSCWS4_ZFLAG_WHEAD;
					for ($j = $k+1; $j <= $i; $j++)
					{
						$wmap[$j][$j]['flag'] |= PSCWS4_ZFLAG_WPART;
						if (($j != $i) && ($wmap[$k][$j] != false)) $wmap[$k][$j]['flag'] |= PSCWS4_WORD_PART;
					}
					continue;
				}
			}

			// check for 'two words' (such as: 欧阳** , **西路)
			for ($i = $zlen - 2; $i >= 0; $i--)
			{
				// with value ==> must be have SCWS_WORD_FULL, so needn't check it ag.
				if (($wmap[$i][$i+1] == false) || ($wmap[$i][$i+1]['flag'] & PSCWS4_WORD_PART)) continue;
				
				$k = $i+1;
				$r1 = $this->_rule_get($this->_get_zs($i, $k));
				if (!$r1) continue;

				$clen = $r1['zmin'] > 0 ? $r1['zmin'] : 1;
				if (($r1['flag'] & PSCWS4_ZRULE_PREFIX) && ($k < ($zlen - $clen)))
				{
					for ($ch = 1; $ch <= $clen; $ch++)
					{
						$j = $k + $ch;
						if ($j >= $zlen || $this->_no_rule2($wmap[$j][$j]['flag'])) break;
						if (!$this->_rule_check($r1, $this->_get_zs($j))) break;
					}
					if ($ch <= $clen) continue;
					
					// no limit znum or limit to a range
					$j = $k + $ch;
					while (true)
					{
						if ((!$r1['zmax'] && $r1['zmin']) || ($r1['zmax'] && ($clen >= $r1['zmax']))) break;
						if ($j >= $zlen || $this->_no_rule2($wmap[$j][$j]['flag'])) break;
						if (!$this->_rule_check($r1, $this->_get_zs($j))) break;
						$clen++;
						$j++;
					}
					
					// ok, got: i & clen
					$k = $k + $clen;
					$wmap[$i][$k] = array('tf'=>$r1['tf'], 'idf'=>$r1['idf'], 'flag'=>PSCWS4_WORD_FULL, 'attr'=>$r1['attr']);
					$wmap[$i][$i+1]['flag'] |= PSCWS4_WORD_PART;
					for ($j = $i+2; $j <= $k; $j++) $wmap[$j][$j]['flag'] |= PSCWS4_ZFLAG_WPART;
					$i--;
					continue;
				}
				
				if (($r1['flag'] & PSCWS4_ZRULE_SUFFIX) && ($i >= $clen))
				{
					// suffix, check before
					for ($ch = 1; $ch <= $clen; $ch++)
					{
						$j = $i - $ch;
						if ($j < 0 || $this->_no_rule2($wmap[$j][$j]['flag'])) break;
						if (!$this->_rule_check($r1, $this->_get_zs($j))) break;
					}
					if ($ch <= $clen) continue;

					// no limit znum or limit to a range 
					$j = $i - $ch;
					while (true)
					{
						if ((!$r1['zmax'] && $r1['zmin']) || ($r1['zmax'] && ($clen >= $r1['zmax']))) break;
						if ($j < 0 || $this->_no_rule2($wmap[$j][$j]['flag'])) break;
						if (!$this->_rule_check($r1, $this->_get_zs($j))) break;
						$clen++;
						$j--;
					}

					// ok, got: i & clen (maybe clen=1 & [k][i] isset) 
					$k = $i - $clen;
					$i = $i + 1;
					$wmap[$k][$i] = array('tf'=>$r1['tf'], 'idf'=>$r1['idf'], 'flag'=>PSCWS4_WORD_FULL, 'attr'=>$r1['attr']);
					$wmap[$k][$k]['flag'] |= PSCWS4_ZFLAG_WHEAD;
					for ($j = $k+1; $j <= $i; $j++)
					{
						$wmap[$j][$j]['flag'] |= PSCWS4_ZFLAG_WPART;
						if ($wmap[$k][$j] != false) $wmap[$k][$j]['flag'] |= PSCWS4_WORD_PART;
					}
					$i -= ($clen+1);
					continue;
				}
			}
		}

		// do the segment really
		// find the easy break point 
		for ($i = 0, $j = 0; $i < $zlen; $i++)
		{
			if ($wmap[$i][$i]['flag'] & PSCWS4_ZFLAG_WPART) continue;
			if ($i > $j) $this->_mseg_zone($j, $i-1);

			$j = $i;
			if (!($wmap[$i][$i]['flag'] & PSCWS4_ZFLAG_WHEAD))
			{
				$this->_mset_word($i, $i);
				$j++;
			}
		}
		
		// the lastest zone 
		if ($i > $j) $this->_mseg_zone($j, $i-1);
		
		// the last single for duality
		if (($this->_mode & PSCWS4_DUALITY) && ($this->_zis >= 0) && !($this->_zis & PSCWS4_ZIS_USED))	
		{
			$i = $this->_zis;
			$this->_put_res($zmap[$i]['start'], $wmap[$i][$i]['idf'], $zmap[$i]['end'] - $zmap[$i]['start'], $wmap[$i][$i]['attr']);	
		}
	}
}
?>
