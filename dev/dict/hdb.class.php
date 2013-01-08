<?php
/* ----------------------------------------------------------------------- *\
   PHP4代码版HDB - (HashTreeDB.class.php)
   -----------------------------------------------------------------------
   作者: 马明练(hightman) (MSN: MingL_Mar@msn.com) (php-QQ群: 17708754)
   网站: http://www.hi-php.com
   时间: 2007/05/01 (update: 2007/05/08)
   版本: 0.1
   目的: 取代 cdb/gdbm 快速存取分词词典, 因大部分用户缺少这些基础配件和知识
   功能:
         这是一个类似于 cdb/gdbm 的 PHP 代码级数据类库, 通过 key, value 的方
		 式存取数据, 使用非常简单.

		 适用于快速根据唯一主键查找数据

   效能:
         1. 效率高(20万记录以上比php内建的cdb还要快), 经过优化后 35万记录时
		    树的最大深度为5, 查找效率高,单个文件
		 2. 文件小(缺省设置下, 基础数据约 100KB, 之后每条记录为 key, value的
		    总长度+13bytes
		 3. 无系统依赖, 跨操作系统, 不受 little endian 和 big endian 影响
		 4. PHP 代码级, 修改维护方便
		 5. 提供内建二叉树优化函数, 提供存取结构图绘制接口, 提供遍历接口
		 6. 数据可快速更新, 而 cdb 是只读的或只写的

   缺点:
         1. 对于unique key来说, 一经增加不可清除 (可以将value设为空值)
		 2. 当更新 value 时, 如果新 value 较长则旧的记录直接作废, 长期修改
		    可能会导致文件有一些无用的膨胀, 这类情况可以调用遍历接口完全重
			建整个数据库
		 3. 由于是 php 代码级的引擎, 性能上比 gdbm 没有什么优势
		 4. IO操作, 可以考虑将数据文件放到 memfs 上 (linux/bsd)
		 5. key 最大长度为 240bytes, value 最大长度为 65279 bytes, 整个文件最大为 4G
		 6. 不可排序和随机分页读取

   用法: (主要的方法)

   1. 建立类操作句柄, 构造函数: HashTreeDB([int mask [, int base ]])
	  可选参数(仅针对新建数据有效): mask, base 均为整型数, 其中
	    mask 是 hash 求模的基数, 建议选一个质数, 大约为总记录数的 1/10 即可.
		base 是 hash 数据计算的基数, 建议使用默认值. ``h = ((h << 5) + h) ^ c''

      $HDB = new HashTreeDB;

   2. 打开数据文件, Bool Open(string fpath [, string mode])
      必要参数 fpath 为数据文件的路径, 可选参数 mode 的值为 r 或 w, 分别表示只
	  读或读写方式打开数据库. 成功返回 true, 失败返回 false.

      缺省情况下是以只读方式打开, 即 mode 的缺省值为 'r'
      $HDB->Open('/path/to/dict.hdb');

	  或以读写方式打开(新建数据时必须), mode 值为 'w', 此时数据库可读可写
	  $HDB->Open('/path/to/dict.hdb', 'w');

   3. 根据 key 读取数据 mixed Get(string key [, bool verbose])
      成功查找到 key 所对应的数据时返回数据内容, 类型为 string
	  当 key 不存在于数据库中时或产生错误直接返回 false
	  (*注* 当 verbose 被设为 true 时, 则返回一个完整的记录数组, 含 key&value, 仅用于调试目的)

      $value = $HDB->Get($key);
	  或
	  $debug = $HDB->Get($key, true); print_r($debug);

   4. 存入数据 bool Put(string key [, string value])
      成功返回 true, 失败或出错返回 false , 必须以读写方式打开才可调用
	  注意存入的数据目前只支持 string 类型, 有特殊需要可以使用 php 内建的 serialize 将 array 转换
	  成 string 取出时再用 unserialize() 还原

	  $result = $HDB->Put($key, $value);

   5. 关闭数据库, void Close()
      $HDB->Close();

   6. 查询文件版本号, string Version()
      返回类似 HDB/0.1 之类的格式, 是当前文件的版本号

   7. 记录遍历, mixed Next()
      返回一条记录key, value 组成的数组, 并将内部指针往后移一位, 可调用 Reset() 重置指针
	  当没有记录时会返回 false, 典型应用如下

	  $HDB->Reset();
	  while ($tmp = $HDB->Next())
	  {
		  echo "$tmp[key] => $tmp[value]\n";
	  }
	  也可用于导出数据库重建新的数据库, 以清理过多的重写导致的文件空档.

   8. 遍历指针复位, void Reset()
      此函数仅为搭配 Next() 使用
	  $HDB->Reset();

   9. 优化数据库, 将数据库中的 btree 转换成完全二叉树. void Optimize([int index])
      由于数据库针对 key 进行 hash 分散到 mask 颗二叉树中, 故这里的 index 为 0~[mask-1]
	  缺省情况下 index 值为 -1 会优化整个数据库, 必须以读写方式打开的数据库才能用该方法

	  $HDB->Optimize();

  10. 打印分析树, 绘出存贮结构的树状图, void Draw([int index])
      参数 index 同 Optimize() 的参数, 本函数无返回值, 直接将结果 echo 出来, 仅用于调试和观看
	  分析

	  $HDB->Draw(0);
	  $HDB->Draw(1);
	  ...

\* ----------------------------------------------------------------------- */
// Constant Define
define ('_HDB_BASE_KEY',	0x238f13af);
define ('_HDB_BASE_MSK',	0x3ffd);	// 最好是素数: 16381
define ('_HDB_VERSION',		0x02);		// 0x01 ~ 0xff
define ('_HDB_TAGNAME',		'HDB');		// First bytes
define ('_HDB_MAXKLEN',		0xf0);		// max key length (<242)
define ('_HDB_MAXVLEN',		0xfeff);	// max value length (0xffff - 0x100);

// Class object Declare
class HashTreeDB
{
	// Public var
	var $fd = false;
	var $mode = 'r';
	var $hash_key = _HDB_BASE_KEY;
	var $hash_mask = _HDB_BASE_MSK;
	var $version = _HDB_VERSION;

	// Private
	var $rec_off = 0;
	var $trave_stack = array();
	var $trave_index = -1;

	// Debug test
	var $_io_times = 0;

	// Constructor Function
	function HashTreeDB($mask = 0, $key = 0)
	{
		if (0 != $mask) $this->hash_mask = $mask;
		if (0 != $key) $this->hash_key = $key;
	}

	// Open the database: read | write
	function Open($fpath, $mode = 'r')
	{
		// open the file
		$newdb = false;
		if ($mode == 'w')
		{
			// write & read only
			if (!($fd = @fopen($fpath, 'rb+')))
			{
				if (!($fd = @fopen($fpath, 'wb+')))
				{
					trigger_error("HDB::Open(), failed to write the db `" . basename($fpath) . "`", E_USER_WARNING);
					return false;
				}
				// create the header
				$this->_write_header($fd);
				$newdb = true;
			}
		}
		else
		{
			// read only
			if (!($fd = @fopen($fpath, 'rb')))
			{
				trigger_error("HDB::Open(), faild to read the db `" . basename($fpath) . "`", E_USER_WARNING);
				return false;
			}
		}

		// check the header
		if (!$newdb && !$this->_check_header($fd))
		{
			trigger_error("HDB::Open(), invalid db file `" . basename($fpath) . "`", E_USER_WARNING);
			fclose($fd);
			return false;
		}

		// set the variable
		if ($this->fd !== false) fclose($this->fd);
		$this->fd = $fd;
		$this->mode = $mode;
		$this->rec_off = ($this->hash_mask + 1) * 6 + 32;
		$this->Reset();
		return true;
	}

	// Insert Or Update the value
	function Put($key, $value)
	{
		// check the file description
		if (!$this->fd || $this->mode != 'w')
		{
			trigger_error("HDB::Put(), null db handler or readonly.", E_USER_WARNING);
			return false;
		}

		// check the length
		$klen = strlen($key);
		$vlen = strlen($value);
		if ($klen > _HDB_MAXKLEN || $vlen > _HDB_MAXVLEN)
			return false;

		// try to find the old data
		$rec = $this->_get_record($key);
		if (isset($rec['vlen']) && ($vlen <= $rec['vlen']))
		{
			// update the old value & length
			flock($this->fd, LOCK_EX);
			fseek($this->fd, $rec['voff'], SEEK_SET);
			fwrite($this->fd, $value, $vlen);
			if ($vlen < $rec['vlen'])
			{
				$newlen = $rec['len'] + $vlen - $rec['vlen'];
				$newbuf = pack('v', $newlen);
				fseek($this->fd, $rec['poff'] + 4, SEEK_SET);
				fwrite($this->fd, $newbuf, 2);
			}
			fflush($this->fd);
			flock($this->fd, LOCK_UN);
			return true;
		}

		// 构造数据
		$new = array('loff' => 0, 'llen' => 0, 'roff' => 0, 'rlen' => 0);
		if (isset($rec['vlen']))
		{
			$new['loff'] = $rec['loff'];
			$new['llen'] = $rec['llen'];
			$new['roff'] = $rec['roff'];
			$new['rlen'] = $rec['rlen'];
		}
		$buf  = pack('VvVvC', $new['loff'], $new['llen'], $new['roff'], $new['rlen'], $klen);
		$buf .= $key . $value;

		$len  = $klen + $vlen + 13;
		flock($this->fd, LOCK_EX);
		fseek($this->fd, 0, SEEK_END);
		$off = ftell($this->fd);
		if ($off < $this->rec_off)
		{
			$off = $this->rec_off;
			fseek($this->fd, $off, SEEK_SET);
		}
		fwrite($this->fd, $buf, $len);
		$pbuf = pack('Vv', $off, $len);
		fseek($this->fd, $rec['poff'], SEEK_SET);
		fwrite($this->fd, $pbuf, 6);
		fflush($this->fd);
		flock($this->fd, LOCK_UN);
		return true;
	}

	// Read the value by key
	function Get($key, $verbose = false)
	{
		// check the file description
		if (!$this->fd)
		{
			trigger_error("HDB::Get(), null db handler.", E_USER_WARNING);
			return false;
		}

		// get the data?
		$rec = $this->_get_record($key);
		if ($verbose) return $rec;
		if (!isset($rec['value'])) return false;
		return $rec['value'];
	}

	// Read the each key & value
	// return array(key => xxx, value => xxx)
	function Next()
	{
		// check the file description
		if (!$this->fd)
		{
			trigger_error("HDB::Next(), null db handler.", E_USER_WARNING);
			return false;
		}

		// Traversal the all tree
		if (!($pointer = array_pop($this->trave_stack)))
		{
			do
			{
				$this->trave_index++;
				if ($this->trave_index >= $this->hash_mask) break;

				$poff = $this->trave_index * 6 + 32;
				fseek($this->fd, $poff, SEEK_SET);
				$buf = fread($this->fd, 6);
				if (strlen($buf) != 6) { $pointer = false; break; }
				$pointer = unpack('Voff/vlen', $buf);
			}
			while (!$pointer['len']);
		}

		// end the all records?
		if (!$pointer || $pointer['len'] == 0)
			return false;

		$rec = $this->_tree_get_record($pointer['off'], $pointer['len']);

		// push the left & right
		if ($rec['llen'] != 0)
		{
			$left = array('off' => $rec['loff'], 'len' => $rec['llen']);
			array_push($this->trave_stack, $left);
		}
		if ($rec['rlen'] != 0)
		{
			$right = array('off' => $rec['roff'], 'len' => $rec['rlen']);
			array_push($this->trave_stack, $right);
		}

		// return value
		$ret = array('key' => $rec['key'], 'value' => $rec['value']);
		return $ret;
	}

	// Traversal every tree... & debug to test
	function Draw($i = -1)
	{
		if ($i < 0 || $i >= $this->hash_mask)
		{
			$i = 0;
			$j = $this->hash_mask;
		}
		else
		{
			$j = $i + 1;
		}
		while ($i < $j)
		{
			$poff = $i * 6 + 32;
			fseek($this->fd, $poff, SEEK_SET);
			$buf = fread($this->fd, 6);
			if (strlen($buf) != 6) break;
			$pot = unpack('Voff/vlen', $buf);
			if ($pot['len'] == 0)
				echo "EMPTY tree [$i]\n";
			else
			{
				$this->_cur_depth = 0;
				$this->_cur_lkey = '';
				$this->_node_num = 0;
				$this->_draw_node($pot['off'], $pot['len']);
				echo "-------------------------------------------\n";
				echo "Tree[$i] max_depth: {$this->_cur_depth} ";
				echo "nodes_num: {$this->_node_num} bottom_key: {$this->_cur_lkey}\n";
			}
			$i++;
		}
	}

	// Reset the inner pointer
	function Reset()
	{
		$this->trave_stack = array();
		$this->trave_index = -1;
	}

	// Show the version
	function Version()
	{
		$ver = (is_null($this) ? _HDB_VERSION : $this->version);
		$str = sprintf("%s/%d.%d\n", _HDB_TAGNAME, ($ver >> 4), ($ver & 0x0f));
		return $str;
	}

	// Close the DB
	function Close()
	{
		if ($this->fd)
		{
			fclose($this->fd);
			$this->fd = false;
		}
	}

	// Optimize the tree
	function Optimize($i = -1)
	{
		// check the file description
		if (!$this->fd || $this->mode != 'w')
		{
			trigger_error("HDB::Optimize(), null db handler or readonly.", E_USER_WARNING);
			return false;
		}

		// get the index zone:
		if ($i < 0 || $i >= $this->hash_mask)
		{
			$i = 0;
			$j = $this->hash_mask;
		}
		else
		{
			$j = $i + 1;
		}
		while ($i < $j)
		{
			$this->_optimize_index($i);
			$i++;
		}
	}

	// optimize a node
	function _optimize_index($index)
	{
		static $cmp = false;
		$poff = $index * 6 + 32;

		// save all nodes into array()
		$this->_sync_nodes = array();
		$this->_load_tree_nodes($poff);

		$count = count($this->_sync_nodes);
		if ($count < 3) return;

		// sync the nodes, sort by key first
		if ($cmp == false) $cmp = create_function('$a,$b', 'return strcmp($a[key],$b[key]);');
		usort($this->_sync_nodes, $cmp);
		$this->_reset_tree_nodes($poff, 0, $count - 1);
		unset($this->_sync_nodes);
	}

	// load tree nodes
	function _load_tree_nodes($poff)
	{
		fseek($this->fd, $poff, SEEK_SET);
		$buf = fread($this->fd, 6);
		if (strlen($buf) != 6) return;

		$tmp = unpack('Voff/vlen', $buf);
		if ($tmp['len'] == 0) return;
		fseek($this->fd, $tmp['off'], SEEK_SET);
		$buf = fread($this->fd, $tmp['len']);
		$rec = unpack('Vloff/vllen/Vroff/vrlen/Cklen', substr($buf, 0, 13));
		$rec['off'] = $tmp['off'];
		$rec['len'] = $tmp['len'];
		$rec['key'] = substr($buf, 13, $rec['klen']);
		$this->_sync_nodes[] = $rec;
		unset($buf);
		// left
		if ($rec['llen'] != 0) $this->_load_tree_nodes($tmp['off']);
		// right
		if ($rec['rlen'] != 0) $this->_load_tree_nodes($tmp['off'] + 6);
	}

	// sync the tree
	function _reset_tree_nodes($poff, $low, $high)
	{
		if ($low <= $high)
		{
			$mid = ($low+$high)>>1;
			$node = $this->_sync_nodes[$mid];
			$buf = pack('Vv', $node['off'], $node['len']);
			// left
			$this->_reset_tree_nodes($node['off'], $low, $mid - 1);
			// right
			$this->_reset_tree_nodes($node['off'] + 6, $mid + 1, $high);
		}
		else
		{
			$buf = pack('Vv', 0, 0);
		}
		fseek($this->fd, $poff, SEEK_SET);
		fwrite($this->fd, $buf, 6);
	}

	// Privated Function
	function _get_index($key)
	{
		$l = strlen($key);
		$h = $this->hash_key;
		while ($l--)
		{
			$h += ($h << 5);
			$h ^= ord($key[$l]);
			$h &= 0x7fffffff;
		}
		return ($h % $this->hash_mask);
	}

	// draw the tree nodes by off & len
	//
	function _draw_node($off, $len, $rl = 'T', $icon = '', $depth = 0)
	{
		if ($rl == 'T')	echo '(Ｔ) ';
		else
		{
			echo $icon;
			if ($rl == 'L')
			{
				$icon .= ' ┃';
				echo ' ┟(Ｌ) ';
			}
			else
			{
				$icon .= ' 　';
				echo ' └(Ｒ) ';
			}
		}
		if ($len == 0)
		{
			echo "<NULL>\n";
			return;
		}
		$rec = $this->_tree_get_record($off, $len);
		//echo "$rec[key] => $rec[value]\n";
		echo "$rec[key] (vlen: $rec[vlen])\n";

		// debug used
		$this->_node_num++;
		if ($depth >= $this->_cur_depth)
		{
			$this->_cur_depth = $depth;
			$this->_cur_lkey = $rec['key'];
		}

		// Left node & Right Node
		$this->_draw_node($rec['loff'], $rec['llen'], 'L', $icon, $depth + 1);
		$this->_draw_node($rec['roff'], $rec['rlen'], 'R', $icon, $depth + 1);
	}

	// Check HDB Header
	function _check_header($fd)
	{
		fseek($fd, 0, SEEK_SET);
		$buf = fread($fd, 32);
		if (strlen($buf) !== 32) return false;
		$hdr = unpack('a3tag/Cver/Vkey/Vmask/a20reversed', $buf);
		if ($hdr['tag'] != _HDB_TAGNAME) return false;
		$this->hash_key = $hdr['key'];
		$this->hash_mask = $hdr['mask'];
		$this->version = $hdr['ver'];
		return true;
	}

	// Write HDB Header
	function _write_header($fd)
	{
		$buf = pack('a3CVVa20', _HDB_TAGNAME, $this->version, $this->hash_key, $this->hash_mask, '');
		fseek($fd, 0, SEEK_SET);
		fwrite($fd, $buf, 32);
	}

	// get the record by first key
	function _get_record($key)
	{
		$this->_io_times = 1;
		$index = $this->_get_index($key);
		$poff = $index * 6 + 32;
		fseek($this->fd, $poff, SEEK_SET);
		$buf = fread($this->fd, 6);

		if (strlen($buf) == 6) $tmp = unpack('Voff/vlen', $buf);
		else $tmp = array('off' => 0, 'len' => 0);

		return $this->_tree_get_record($tmp['off'], $tmp['len'], $poff, $key);
	}

	// get the record by tree
	function _tree_get_record($off, $len, $poff = 0, $key = '')
	{
		$ret = array('poff' => $poff);
		if ($len == 0) return $ret;

		$this->_io_times++;
		// get the data & compare the key data
		fseek($this->fd, $off, SEEK_SET);
		$buf = fread($this->fd, $len <= 256 ? $len : 256);
		$rec = unpack('Vloff/vllen/Vroff/vrlen/Cklen', substr($buf, 0, 13));
		$fkey = substr($buf, 13, $rec['klen']);
		$cmp = ($key ? strcmp($key, $fkey) : 0);
		if ($cmp > 0)
		{
			// --> right
			return $this->_tree_get_record($rec['roff'], $rec['rlen'], $off + 6, $key);
		}
		else if ($cmp < 0)
		{
			// <-- left
			return $this->_tree_get_record($rec['loff'], $rec['llen'], $off, $key);
		}
		else {
			// found!!
			$rec['poff'] = $poff;
			$rec['off'] = $off;
			$rec['len'] = $len;
			$rec['voff'] = $off + 13 + $rec['klen'];
			$rec['vlen'] = $len - 13 - $rec['klen'];
			$rec['key'] = $fkey;

			// get the value
			if ($len <= 256)
				$rec['value'] = substr($buf, $rec['klen'] + 13, $rec['vlen']);
			else
			{
				fseek($this->fd, $rec['voff'], SEEK_SET);
				$rec['value'] = fread($this->fd, $rec['vlen']);
			}
			return $rec;
		}
	}
}
?>
