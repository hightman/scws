<?php
/* ----------------------------------------------------------------------- *\
   PHP版 简易中文分词(SCWS) ver 2.1 (仅针对 GBK编码设计)

   基于词频词典的正向最大匹配法 (N级词频消岐, 含人名, 数字年代自动识别)
   词典根据网上的资料作进一步处理, 提供 gdbm/cdb 2种格式

   (*) 类库调用方法举例:

      require '/path/to/PSCWS2.class.php';
	  $cws = new PSCWS2('dict/dict.xdb');
	  $rs = $cws->segment($string);
	  print_r($rs);

	  // 其它可用方法介绍:
	  ->set_dict($fpath);	// 设定词典路径 (后缀名为库类型)
	  ->set_ignore_mark($trueORfalse);
							// 设定是否删除标点符号
	  ->set_autodis($trueORflase);
							// 设定是否自动进行人名识别
	  ->set_debug($trueORfalse);
							// 展示切词过程(echo)

	  ->set_statistics($trueORfalse);
							// 是否开启词汇统计
	  ->get_statistics();
							// 搭配上述开关使用, 返回统计结果数组
							// 词为键, 值是由次数和位置列表组成的数组

	  ->segment($string, [$callback]);
	  针对 $string 执行分词, $callback 作为回调函数, 可选. 参数是切割好的
	  词组成的数组. 若未设定 callback 则该函数返回切好的词组成的数组.

	  由于本函数一次性全部操作完成才返回, 若文本过长建议按行传入切割以加速

   -----------------------------------------------------------------------
   作者: 马明练(hightman) (MSN: MingL_Mar@msn.com) (php-QQ群: 17708754)
   网站: http://www.ftphp.com/scws
   时间: 2005/11/25 (update: 2006/03/06)
   修订: 2008/12/20
   目的: 学习研究交流用, 希望有好的建议及用途能进一步交流.
   $Id: pscws2.class.php,v 1.1 2008/12/20 12:03:00 hightman Exp $
   -----------------------------------------------------------------------
   中文字符的判断, 高位码 0x81 ~ 0xfe, 低位码 0x40 ~ 0xfe
   其中高位 0xa1 ~ 0xa9 是符号区, 除特别字符外作为断句处理
   -----------------------------------------------------------------------
   半角字符 (ascii < 0x80)
   大写字母: 0x41 ~ 0x5a
   小写字母: 0x61 ~ 0x7a
   数字大全: 0x31 ~ 0x39
   点和连符: 0x2d(-), 0x2e(.)
   -----------------------------------------------------------------------
   全角字符
   0xa3 (0xb0 ~ 0xb9) 是全角数字:  ０~９应该保留独立识别
   0xa3 全角英文字母: Ａ－Ｚ(0xc1 ~ 0xda) ａ－ｚ(0xe1 ~ 0xfa)
   0xa3 连词符: － (0xad)  ．(0xae)
   -----------------------------------------------------------------------
   环境: PHP 4.1.0 及更高版本含 PHP5 (编译建议 --enable-dba --with-[cdb|gdbm])
\* ----------------------------------------------------------------------- */

/**
 * 词典特性及换行回车的相关定义
 */

define ('_WORD_ALONE_',		0x4000000);	/// 成词标记
define ('_WORD_PART_',		0x8000000);	/// 词段标记
define ('_WORD_CKDEPTH_',	2);			/// 消岐复查深度
if (!defined('_CRLF_'))
	define ('_CRLF_',		"\r\n");

/**
 * 词典类今起独立一个文件
 */
require_once (dirname(__FILE__) . '/dict.class.php');

/**
 * 分词核心类: 正向最大匹配 (N(2)级复查词频比较)
 */
class PSCWS2
{
	var $_dict;				// 词典档查询类句柄
	var $_ignore_mark;		// 是否删去分句标点 (default: true)
	var $_foregin_chars;	// 常见外来译名用字
	var $_surname_chars;	// 常见姓氏用字
	var $_surname2_chars;	// 常见复姓列表
	var $_noname_chars;		// 极少用于名字的字
	var $_mb_alpha_chars;	// 双字节的字母列表
	var $_mb_num1_chars;	// 双字节数字列表1
	var $_sb_alpha_chars;
	var $_sb_num_chars;
	var $_cur_sen_buf;
	var $_cur_sen_len;
	var $_debug;
	var $_autodis;
	var $_do_stats;
	var $_statistics;			// hightman.060330: 每次统计
	var $_cur_sen_off;			// hightman.060330: 统计专用, 当前句偏移量

	/** 构造函数, 初始化相关数据 */
	function PSCWS2($dictfile = '')
	{
		$this->_ignore_mark = false;
		$this->_debug		= false;
		$this->_autodis		= false;
		$this->_do_stats	= false;

		$this->_foreign_chars  = "阿克拉加内亚斯贝巴尔姆爱兰尤利西詹乔伊费杰罗纳布可夫福赫勒柯特";
		$this->_foreign_chars .= "劳伦坦史芬尼根登都伯林伍泰胥黎俄科索沃金森奥霍瓦茨普蒂塞维大利";
		$this->_foreign_chars .= "格莱德冈萨雷墨哥弗库澳马哈多兹戈乌奇切诺戴里诸塞吉基延科达塔博";
		$this->_foreign_chars .= "卡雅来莫波艾哈迈蓬安卢什比摩曼乃休合赖米那迪凯莱温帕桑佩蒙博托";
		$this->_foreign_chars .= "谢格泽洛及希卜鲁匹齐兹印古埃努烈达累法贾图喀土穆腓基冉休盖耶沙";
		$this->_foreign_chars .= "逊宾麦华万";

		$this->_surname_chars  = "艾安敖白班包宝保鲍贝毕边卞柏卜蔡曹岑柴昌常车陈成程迟池褚楚";
		$this->_surname_chars .= "储淳崔戴刀邓狄刁丁董窦杜端段樊范方房斐费丰封冯凤伏福傅盖甘";
		$this->_surname_chars .= "高戈耿龚宫勾苟辜谷古顾官关管桂郭韩杭郝禾何贺赫衡洪侯胡花";
		$this->_surname_chars .= "华黄霍稽姬吉纪季贾简翦姜江蒋焦晋金靳荆居康柯空孔匡邝况赖蓝";
		$this->_surname_chars .= "郎朗劳乐雷冷黎李理厉利励连廉练良梁廖林凌刘柳隆龙楼娄卢吕鲁";
		$this->_surname_chars .= "陆路伦罗洛骆麻马麦满茅毛梅孟米苗缪闵明莫牟穆倪聂牛钮农潘庞";
		$this->_surname_chars .= "裴彭皮朴平蒲溥浦戚祁齐钱强乔秦丘邱仇裘屈瞿权冉饶任荣容阮";
		$this->_surname_chars .= "瑞芮萨赛沙单商邵佘申沈盛石史寿舒斯宋苏孙邰谭谈汤唐陶滕";
		$this->_surname_chars .= "田佟仝屠涂万汪王危韦魏卫蔚温闻翁巫邬伍武吴奚习夏鲜席冼";
		$this->_surname_chars .= "项萧解谢辛邢幸熊徐许宣薛荀颜阎言严彦晏燕杨阳姚叶蚁易殷银尹";
		$this->_surname_chars .= "应英游尤于於鱼虞俞余禹喻郁尉元袁岳云臧曾查翟詹湛张章招赵甄";
		$this->_surname_chars .= "郑钟周诸朱竺祝庄卓宗邹祖左";

		$this->_surname2_chars = "东郭 公孙 皇甫 慕容 欧阳 单于 司空 司马 司徒 澹台 诸葛 ";

		$this->_noname_chars   = "的说对在和是被最所那这有将会与於他为不没很";

		$this->_mb_alpha_chars  = "ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ";
		$this->_mb_alpha_chars .= "ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ";

		$this->_mb_num1_chars  .= "０１２３４５６７８９";

		$this->_sb_alpha_chars  = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ._-";
		$this->_sb_num_chars    = "0123456789.";

		if ('' !== $dictfile)
			$this->set_dict($dictfile);
	}

	// FOR PHP5
	function __construct($dictfile = '') { $this->PSCWS2($dictfile); }
	function __destruct() { }

	// 设定词典 (根据后缀名确定类型)
	function set_dict($fpath)
	{
		$this->_dict = new PSCWS23_Dict($fpath);
	}

	// 设定 _ignore_mark 参数
	function set_ignore_mark($set)
	{
		if (is_bool($set))
			$this->_ignore_mark = $set;
	}

	// 设定显示 debug 信息
	function set_debug($set)
	{
		if (is_bool($set))
			$this->_debug = $set;
	}

	// 设定智能识别年代名字
	function set_autodis($set)
	{
		if (is_bool($set))
			$this->_autodis = $set;
	}

	// hightman.060429: 是否开启统计
	function set_statistics($set)
	{
		if (is_bool($set))
			$this->_do_stats = $set;
	}

	// 返回上次统计 (是否需要加开关?)
	function &get_statistics()
	{
		return $this->_statistics;
	}

	// 加入统计
	function _put_statistics($word, $off)
	{
		if (!isset($this->_statistics[$word]))
		{
			$this->_statistics[$word] = array('times' => 1, 'poses' => array($off));
		}
		else
		{
			$this->_statistics[$word]['times']++;
			$this->_statistics[$word]['poses'][] = $off;
		}
	}

	// 将中英文混编的字符串切割成词
	function &segment($str, $cb = '')
	{
		$len = strlen($str);
		$ret = array();
		$qtr = '';
		if (!empty($cb) && !function_exists($cb)) $cb = '';

		// 统计开始
		if ($this->_do_stats)
			$this->_statistics = array();

		for ($i = 0; $i < $len; $i++)
		{
			$char = $str[$i];
			$ord = ord($char);

			// 非汉字高位码 (即: 半角字符, 含0x7f, 0x80)
			if ($ord < 0x81)
			{
				// 处理刚分好的句子
				if (!empty($qtr))
				{
					$tmp = $this->_gbk_segment($qtr);
					if (empty($cb)) $ret = array_merge($ret, $tmp);
					else call_user_func($cb, $tmp);
					$qtr = '';
				}

				$this->_cur_sen_off = $i;

				// 如果是字母, 读到非字母为止
				if ($this->_is_alpha($char, true))
				{
					for (;;)
					{
						$j = $i + 1;
						if ($j >= $len)
							break;

						$char2 = $str{$j};
						if (!$this->_is_alpha($char2))
							break;

						$char .= $char2;
						$i++;
					}
				}
				// 如果是数字, 读到非数字为止
				else if ($this->_is_num($char, true))
				{
					for (;;)
					{
						$j = $i + 1;
						if ($j >= $len)
							break;

						$char2 = $str{$j};

						if (!$this->_is_num($char2))
							break;

						$char .= $char2;
						$i++;
					}
				}
				// 忽略 \t\r 和 ' '(空格)
				else if ($ord === 0x0d || $ord === 0x20 || $ord === 0x09)
					$char = '';
				else if ($ord !== 0x0a && $this->_ignore_mark)
					$char = '';

				// 存下非空结果
				if (strlen($char) > 0)
				{
					if (empty($cb)) array_push($ret, $char);
					else call_user_func($cb, array($char));

					if ($this->_do_stats && strlen($char) > 1)
						$this->_put_statistics($char, $this->_cur_sen_off);
				}
			}
			// 汉字区
			else if ($i < ($len - 1))
			{
				$i++;
				$char .= $str[$i];

				// 是否为符号区切分区
				if ($ord > 0xa0 && $ord < 0xb0)
				{
					// 处理刚分好的句子
					if (!empty($qtr))
					{
						$tmp = $this->_gbk_segment($qtr);
						if (empty($cb)) $ret = array_merge($ret, $tmp);
						else call_user_func($cb, $tmp);
						$qtr = '';
					}

					$this->_cur_sen_off = $i - 1;

					// 双字节(字母|数字)特别处理
					if ($ord === 0xa3)
					{
						if (strpos($this->_mb_num1_chars, $char) !== false)
						{
							for (;;)
							{
								if ($i > ($len - 2))
									break;

								$char2 = substr($str, $i + 1, 2);
								if (strpos($this->_mb_num1_chars, $char2) === false)
									break;

								$char .= $char2;
								$i += 2;
							}
						}
						else if (strpos($this->_mb_alpha_chars, $char) !== false)
						{
							for (;;)
							{
								if ($i > ($len - 2))
									break;

								$char2 = substr($str, $i + 1, 2);
								if (strpos($this->_mb_alpha_chars, $char2) === false)
									break;

								$char .= $char2;
								$i += 2;
							}
						}
						else
						{
							$ord = 0xa4;	// 仅仅为了 != 0xa3
						}
					}

					// 根据需要将切句符号保存下来
					if ($ord === 0xa3 || !$this->_ignore_mark)
					{
						if (empty($cb)) array_push($ret, $char);
						else call_user_func($cb, array($char));

						if (strlen($char) > 2 && $this->_do_stats)
							$this->_put_statistics($char, $this->_cur_sen_off);
					}
				}
				else
				{
					if (empty($qtr))
						$this->_cur_sen_off = $i - 1;
					$qtr .= $char;
				}
			}
		}

		// 检查最后余留句子 (放在外面省几次判断 $i值)
		if (!empty($qtr))
		{
			$tmp = $this->_gbk_segment($qtr);
			if (empty($cb)) $ret = array_merge($ret, $tmp);
			else call_user_func($cb, $tmp);
		}

		// 将结果返回
		return (empty($cb) ? $ret : true);
	}

	// 切分纯中文句子 [核心函数] (正向最大匹配, N级词频比较消岐)
	function _gbk_segment($sen)
	{
		$ret = array();
		$len = strlen($sen);
		$this->_cur_sen_buf = $sen;
		$this->_cur_sen_len = $len;

		if ($this->_debug)
		{
			echo _CRLF_ . "中文短句: {$sen}";
			echo _CRLF_ . "____________________________////";
			flush();
		}

		$off = 0;
		while ($off < $len)
		{
			do
			{
				// 只有一个字的时候在此返回
				if (($off + ($r = 2)) >= $len)
					break;

				// 中文复姓姓名检查
				if ($this->_autodis && ($r = $this->_fetch_zhname2($off)))
					break;

				// 正向取非岐长词
				if ($r = $this->_fetch_word($off))
					break;

				if ($this->_autodis)
				{
					// 中文单姓姓名检查
					if ($r = $this->_fetch_zhname($off))
						break;

					// 外文翻译姓名检查
					if ($r = $this->_fetch_frname($off))
						break;
				}
				$r = 2;
			}
			while (0);

			$word = substr($sen, $off, $r);
			if ($this->_do_stats)
				$this->_put_statistics($word, $this->_cur_sen_off + $off);

			array_push($ret, $word);
			$off += $r;
		}

		if ($this->_debug)
		{
			echo _CRLF_ . "\\\\\\\\____________________________" . _CRLF_;
			flush();
		}

		return $ret;
	}

	// 从纯中文中正序取出一个合理的长词, 返回词长
	function _fetch_word($off)
	{
		$long = $this->_fetch_long($off);

		return $long[0];
	}

	// 返回 (length, frequency) 长词
	function _fetch_long($off, $minlen = 4, $rc = _WORD_CKDEPTH_)
	{
		$ret = array(0, 0);

		if (($off + $minlen) > $this->_cur_sen_len)
			return $ret;

		$tail = $this->_cur_sen_len;
		$wlen = $minlen;
		$plen = 0;
		while (($off + $wlen) <= $tail)
		{
			$w = substr($this->_cur_sen_buf, $off, $wlen);

			// debug message
			if ($this->_debug)
				echo _CRLF_ . str_repeat("  ", _WORD_CKDEPTH_ - $rc) . "检测: {$w} ... ";

			$r = $this->_dict->find($w);
			if ($r < 0)
				break;

			if ($r & _WORD_ALONE_)
			{
				$plen = $ret[0];	// save last length of the word
				$ret[0] = $wlen;
				$ret[1] = ($r & ~(_WORD_ALONE_ | _WORD_PART_));

				// debug message
				if ($this->_debug)
					echo "(Y 完整词) ";

				if (!$rc)
					break;
			}

			// 不是词段, 不需往下查了.
			if (!($r & _WORD_PART_))
			{
				// debug message
				if ($this->_debug)
					echo " [N 非词段]";
				break;
			}

			$wlen += 2;
		}

		// 根据需要作消岐检查 (仅作一次检查?)
		if ($ret[0] > 0 && $rc && ($off != 0 || $plen > 0 || $ret[1] < 5))
		{
			$off1 = $off + ($plen ? $plen : 2);
			$off2 = $off + $ret[0];

			// debug message
			if ($this->_debug)
			{
				echo _CRLF_ . str_repeat("  ", _WORD_CKDEPTH_ - $rc);
				echo "消岐: 复查({$off1} ~ {$off2})";
			}

			while ($off1 < $off2)
			{
				$chk = $this->_fetch_long($off1, $off2 - $off1 + 2, $rc - 1);
				if ($chk[0] > 0 && ($chk[1] * $chk[0] > $ret[1] * $ret[0]))
				{
					$ret[0] = $plen;

					if ($this->_debug)
					{
						echo _CRLF_ . str_repeat("  ", _WORD_CKDEPTH_ - $rc) . "发现较高频词汇(";
						echo substr($this->_cur_sen_buf, $off1, $chk[0]) . "),";
						echo "词语被裁断至 {$plen} 处";
					}

					break;
				}
				$off1 += 2;
			}
		}

		// debug message
		if ($this->_debug)
		{
			echo _CRLF_ . str_repeat("  ", _WORD_CKDEPTH_ - $rc) . "结果: ";
			echo substr($w, 0, $ret[0]) . "({$ret[0]}) ... ";
			flush();
		}

		return $ret;
	}

	// 检查是否为中文复姓姓名 (名: 1-2字)
	function _fetch_zhname2($off)
	{
		$ret = 0;
		if (($off + 4) < $this->_cur_sen_len)
		{
			$s2 = substr($this->_cur_sen_buf, $off, 4) . " ";
			if (($p = strpos($this->_surname2_chars, $s2)) !== false)
			{
				$ret = 4;
				$off += 4;
				$n = 0;

				if ($this->_debug)
					echo _CRLF_ . "  复姓: {$s2} ... ";

				do
				{
					if ($off >= $this->_cur_sen_len)
						break;

					$chk = $this->_fetch_long($off, 4, 0);
					if ($chk[0] > 0)
						break;

					$zh = substr($this->_cur_sen_buf, $off, 2);
					if (($p = strpos($this->_noname_chars, $zh)) !== false
						&& !($p & 0x01))
						break;

					$off += 2;
					$ret += 2;
				}
				while (++$n < 2);
			}
		}
		return $ret;
	}

	// 检查是否为中文单姓姓名 (名: 1-2字)
	function _fetch_zhname($off)
	{
		$ret = 0;
		if (($off + 2) < $this->_cur_sen_len)
		{
			$s1 = substr($this->_cur_sen_buf, $off, 2);
			if (($p = strpos($this->_surname_chars, $s1)) !== false
				&& (($p & 0x01) == 0))
			{
				$ret = 2;
				$off += 2;
				$n = 0;

				if ($this->_debug)
					echo _CRLF_ . "  单姓: {$s1} ... ";

				do
				{
					if ($off >= $this->_cur_sen_len)
						break;

					$chk = $this->_fetch_long($off, 4, 0);
					if ($chk[0] > 0)
						break;

					$zh = substr($this->_cur_sen_buf, $off, 2);
					if (($p = strpos($this->_noname_chars, $zh)) !== false
						&& !($p & 0x01))
						break;

					$off += 2;
					$ret += 2;
				}
				while (++$n < 2);
			}
		}

		if ($ret <= 2)
			$ret = 0;

		return $ret;
	}

	// 检查是否为外国人名译词
	function _fetch_frname($off)
	{
		$ret = 0;

		do
		{
			if ($off >= $this->_cur_sen_len)
				break;

			$zh = substr($this->_cur_sen_buf, $off, 2);

			if (($p = strpos($this->_mb_foreign_chars, $zh)) === false
				|| ($p & 0x01))
				break;

			if ($ret > 0)
			{
				// 注意保护词典词的完整性
				$chk = $this->_fetch_long($off, 4, 0);
				if ($chk[0] > 0)
					break;
			}

			$off += 2;
			$ret += 2;
		}
		while (1);

		if ($ret <= 2)
			$ret = 0;

		return $ret;
	}

	// 判断字符是不是字母(单词中)[a-z._-]
	function _is_alpha($char, $strict = false)
	{
		$p = strpos($this->_sb_alpha_chars, $char);

		if ($strict)
			return ($p !== false && $p < 52);

		return ($p !== false);
	}

	// 判断字符是不是数字 [0-9.]
	function _is_num($char, $strict = false)
	{
		$p = strpos($this->_sb_num_chars, $char);

		if ($strict)
			return ($p !== false && $p < 10);

		return ($p !== false);
	}
}
?>