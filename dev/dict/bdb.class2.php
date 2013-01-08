<?php
// BinaryLength_DB (.bdb)
//
// Author: hightman
// Create: 2007/05/02
//
// Index_Struct: [data_off:uint32][data_len:uint16][key_data:char*]  (6 + key_len)
//
//
// [File Header] [BDB(3)/Version(1)/MaxKlen(4)/Reversed(24)] = 32bytes
// [IndexOff_byte1, IndexLength_byte1] ... [IndexOff_byten][IndexLenght_byten]
//
// Hash key calculate: h = ((h<<5) + h) ^ c , with a starting hash of 0xf422f
//
// 百万级小型关系数据解决方案
//

// Constant Define
define ('_BDB_MAXKLEN',		0x80);		// default max key length
define ('_BDB_VERSION',		0x02);		// 0x01 ~ 0xff
define ('_BDB_TAGNAME',		'BDB');		// First bytes

// ReadOnly Or WriteOnly(Lock++)
Class BinaryLenDB
{
	// public var
	var $fd;
	var $mode;			// read or write
	var $key_buffer;	// index buffer by key length
	var $key_index;		// key index by length (off & length)
	var $put_pool;		// Put Pool index by key length
	var $dat_buffer;	// new data buffer
	var $dat_offset;
	var $max_klen	= _BDB_MAXKLEN;
	var $version	= _BDB_VERSION;

	// trac
	var $trac_idx	= 0;
	var $trac_off	= 0;

	// Constrctor function
	function BinaryLenDB($max_klen = 0)
	{
		if (0 != $max_klen) $this->max_klen = $max_klen;
	}

	// Open function
	function Open($fpath, $mode = 'r')
	{
		// open the file
		$this->Close();
		if ($mode == 'w')
		{
			// write only
			if (!($fd = @fopen($fpath, 'rb+')))
			{
				if (!($fd = @fopen($fpath, 'wb+')))
				{
					trigger_error("BDB::Open(), failed to write the db `" . basename($fpath) . "`", E_USER_WARNING);
					return false;
				}
				// create the header
				$this->_write_header($fd);
			}
		}
		else
		{
			// read only
			if (!($fd = @fopen($fpath, 'rb')))
			{
				trigger_error("BDB::Open(), faild to read the db `" . basename($fpath) . "`", E_USER_WARNING);
				return false;
			}
		}

		// check the header
		if (!$this->_check_header($fd))
		{
			trigger_error("BDB::Open(), invalid db file `" . basename($fpath) . "`", E_USER_WARNING);
			fclose($fd);
			return false;
		}

		// set the variable
		$this->fd = $fd;
		$this->mode = $mode;

		// Write mode, lock until close...
		if ($mode == 'w')
		{
			$this->dat_offset = $this->key_index[0]['off'];
			$this->put_pool = array();
			flock($this->fd, LOCK_EX);
		}
		return true;
	}

	// Read the value by key
	function Get($key)
	{
		// check the file description
		if (!$this->fd)
		{
			trigger_error("BDB::Get(), null db handler.", E_USER_WARNING);
			return false;
		}

		// find from the write cache
		$kidx = strlen($key) - 1;
		if (is_array($this->put_pool) && isset($this->put_pool[$kidx])
			&& isset($this->put_pool[$kidx][$key]))
		{
			$doff = $this->put_pool[$kidx][$key]['off'] - $this->key_index[0]['off'];
			$dlen = $this->put_pool[$kidx][$key]['len'];
			return substr($this->dat_buffer, $doff, $dlen);
		}

		// find from the disk
		$off = 0;
		if (!($ret = $this->_key_search($key, $off)))
			return false;

		fseek($this->fd, $ret['doff'], SEEK_SET);
		$val = fread($this->fd, $ret['dlen']);
		return $val;
	}

	// Write the value for key
	function Put($key, $value)
	{
		static $count = 0;

		// check the file description
		if (!$this->fd && $this->mode != 'w')
		{
			trigger_error("BDB::Put(), null db handler or readonly.", E_USER_WARNING);
			return false;
		}

		$klen = strlen($key);
		$vlen = strlen($value);
		if ($klen > $this->max_klen) return false;

		if ($count > 4096)
		{
			$this->Commit();
			$count = 0;
		}

		// just save on temp write cache (pool)
		$i = $klen - 1;
		if (!isset($this->put_pool[$i])) $this->put_pool[$i] = array();
		$this->put_pool[$i][$key] = array('off' => $this->dat_offset, 'len' => $vlen);
		$this->dat_buffer .= $value;
		$this->dat_offset += $vlen;
		$count++;
		return true;
	}

	// Read the each records [traversal]
	function Next()
	{
		// check the file description
		// Notice: this function not check the write cache.
		if (!$this->fd || $this->mode != 'r')
		{
			trigger_error("BDB::Next(), null db handler or writable.", E_USER_WARNING);
			return false;
		}
		$idx = $this->trac_idx;
		$off = $this->trac_off;
		$size = $idx + 9;
		$valid = false;
		while ($idx < $this->max_klen)
		{
			// check key_index
			if (!isset($this->key_index[$idx])) { $idx++; continue; }

			// load the key_buffer
			if (!isset($this->key_buffer[$idx]) && ($this->key_index[$idx]['len'] > 0))
			{
				fseek($this->fd, $this->key_index[$idx]['off'], SEEK_SET);
				$this->key_buffer[$idx] = fread($this->fd, $this->key_index[$idx]['len']);
			}

			if (isset($this->key_buffer[$idx])
				&& (($off + $size) <= strlen($this->key_buffer[$idx])))
			{
				$valid = true;
				break;
			}

			$idx++;
			$size++;
			$off = 0;
		}
		if (!$valid) return false;
		$this->trac_idx = $idx;
		$this->trac_off = $off + $size;

		$tmp = unpack('Vdoff/Vdlen/a*key', substr($this->key_buffer[$idx], $off, $size));
		fseek($this->fd, $tmp['doff'], SEEK_SET);
		$val = fread($this->fd, $tmp['dlen']);

		// return the entry
		$ret = array('key' => $tmp['key'], 'value' => $val);
		return $ret;
	}

	// Reset for next()
	function Reset()
	{
		$this->trac_idx = 0;
		$this->trac_off = 0;
	}

	// Show the version
	function Version()
	{
		$ver = (is_null($this) ? _BDB_VERSION : $this->version);
		$str = sprintf("%s/%d.%d\n", _BDB_TAGNAME, ($ver >> 4), ($ver & 0x0f));
		return $str;
	}

	// Close the DB
	function Close()
	{
		if ($this->fd)
		{
			if ($this->mode == 'w')
			{
				// commit the all changes
				$this->Commit();
				flock($this->fd, LOCK_UN);
			}
			fclose($this->fd);
			$this->fd = false;
		}
		$this->_free_keybuf();
	}

	// Commit all changes for DB
	function Commit()
	{
		// save the all changes!! (append data & update the indexes)
		if (!$this->fd || $this->mode != 'w' || $this->dat_buffer == '') return;

		// 1. load all unloaded index_buffer
		for ($i = 0; $i < $this->max_klen; $i++)
		{
			if (isset($this->key_buffer[$i])) continue;
			$this->key_buffer[$i] = '';
			if (!isset($this->key_index[$i]) || $this->key_index[$i]['len'] == 0) continue;
			fseek($this->fd, $this->key_index[$i]['off'], SEEK_SET);
			$this->key_buffer[$i] = fread($this->fd, $this->key_index[$i]['len']);
		}

		// 2. save the append data
		if ($this->dat_buffer != '')
		{
			fseek($this->fd, $this->key_index[0]['off'], SEEK_SET);
			fwrite($this->fd, $this->dat_buffer);
			unset($this->dat_buffer);
			$this->dat_buffer = '';
		}
		// 3. save the key_buffer
		$off = $this->dat_offset;
		$kbuf = '';
		for ($i = 0; $i < $this->max_klen; $i++)
		{
			// check the index modified or not
			$kblen = strlen($this->key_buffer[$i]);
			if (!isset($this->put_pool[$i]))
			{
				if ($kblen > 0)
					fwrite($this->fd, $this->key_buffer[$i], $kblen);
			}
			else
			{
				// modified!!
				$size = $i + 9;		// record_size

				// sort the put_pool by key
				$pool = &$this->put_pool[$i];
				ksort($pool);

				$buffer = '';
				$o = $n = 0;
				while ($o < $kblen)
				{
					$pval = each($pool);
					if (!$pval)
					{
						// end of pool
						$buffer .= substr($this->key_buffer[$i], $o);
						break;
					}
					$okey = substr($this->key_buffer[$i], $o + 8, $size - 8);
					$cmp = strcmp($pval['key'], $okey);

					// 直到找到大于的?
					while ($cmp < 0)
					{
						$buffer .= pack('VVa*', $pval['value']['off'], $pval['value']['len'], $pval['key']);
						$n += $size;

						// find the next
						$pval = each($pool);
						if (!$pval) $cmp = 1;	// set > 0
						else $cmp = strcmp($pval['key'], $okey);
					}

					if ($cmp == 0)
						$buffer .= pack('VVa*', $pval['value']['off'], $pval['value']['len'], $pval['key']);
					else if ($cmp > 0)
					{
						$buffer .= substr($this->key_buffer[$i], $o, $size);
						if ($pval && !prev($pool)) end($pool);
					}
					$o += $size;
				}

				// other pool data
				while ($pval = each($pool))
				{
					$buffer .= pack('VVa*', $pval['value']['off'], $pval['value']['len'], $pval['key']);
					$n += $size;
				}

				// save to disk
				$kblen += $n;
				fwrite($this->fd, $buffer, $kblen);

				// delete the pool
				unset($buffer, $pool);
				unset($this->put_pool[$i]);
			}

			$kbuf .= pack('VV', $off, $kblen);
			$this->key_index[$i] = array('off' => $off, 'len' => $kblen);
			$off += $kblen;
			unset($this->key_buffer[$i]);
		}
		// 4. save the head offset & length (key_index)
		fseek($this->fd, 32, SEEK_SET);
		fwrite($this->fd, $kbuf);
		unset($kbuf);

		// 5. flush the fd
		fflush($this->fd);
	}

	// binary search the matched key [ioff = insert off]
	// not found return false;
	// found: return array('doff', 'dlen');
	function _key_search($key, &$ioff)
	{
		$kidx = strlen($key) - 1;
		$ioff = 0;

		// non-exists key length
		if (!isset($this->key_index[$kidx]) || $this->key_index[$kidx]['len'] == 0)
			return false;

		// load the key buffer
		if (!isset($this->key_buffer[$kidx]))
		{
			fseek($this->fd, $this->key_index[$kidx]['off'], SEEK_SET);
			$this->key_buffer[$kidx] = fread($this->fd, $this->key_index[$kidx]['len']);
		}

		// index size [dpos][dlen][key]
		// binary search the match key
		$size = $kidx + 9;
		$buf = &$this->key_buffer[$kidx];

		$low = 0;
		$high = intval(strlen($buf) / $size) - 1;
		$ret = false;
		while ($low <= $high)
		{
			$mid = (($low + $high) >> 1);
			$tmp = unpack('Vdoff/Vdlen/a*key', substr($buf, $mid * $size, $size));
			$cmp = strcmp($key, $tmp['key']);
			//echo "low=$low, high=$high, mid=$mid , cmp($key, $tmp[key]) = $cmp ... \n";

			// compare result
			if ($cmp < 0) $high = $mid - 1;
			else if ($cmp > 0) $low = $mid + 1;
			else
			{	// found!!
				$ret = $tmp;
				break;
			}
		}
		if (!$ret) $mid = $low;
		$ioff = $mid * $size;
		return $ret;
	}

	// Free the key buffer
	function _free_keybuf()
	{
		// free the buffers
		for ($i = 0; $i < $this->max_klen; $i++)
		{
			if (isset($this->key_buffer[$i]))
				unset($this->key_buffer[$i]);
			unset($this->key_index[$i]);
		}
		unset($this->dat_buffer);
		$this->key_buffer = array();
		$this->key_index = array();
		$this->dat_buffer = '';
	}

	// Check HDB Header
	function _check_header($fd)
	{
		fseek($fd, 0, SEEK_SET);
		$buf = fread($fd, 32);
		if (strlen($buf) != 32) return false;
		$hdr = unpack('a3tag/Cver/Vklen/a24reversed', $buf);
		if ($hdr['tag'] != _BDB_TAGNAME) return false;
		$this->max_klen = $hdr['klen'];
		$this->version = $hdr['ver'];

		// get key buf & length
		$len = 8 * $hdr['klen'];
		$buf = fread($fd, $len);
		if (strlen($buf) != $len) return false;
		for ($i = 0; $i < $hdr['klen']; $i++)
		{
			$tmp = unpack('Voff/Vlen', substr($buf, $i * 8, 8));
			$this->key_index[$i] = $tmp;
		}
		return true;
	}

	// Write HDB Header
	function _write_header($fd)
	{
		$buf = pack('a3CVa24', _BDB_TAGNAME, $this->version, $this->max_klen, '');
		fseek($fd, 0, SEEK_SET);
		fwrite($fd, $buf, 32);
		$head = str_repeat(pack('VV', 32 + 8 * $this->max_klen, 0), $this->max_klen);
		fwrite($fd, $head);
	}
}
?>
