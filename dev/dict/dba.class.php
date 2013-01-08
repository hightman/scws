<?php
// hightman
// DBA class object
class DbaHandler
{
	var $handler = false;

	// Open
	function Open($file, $mode = 'n')
	{
		$this->Close();
		$ext = substr(strrchr($file, '.'), 1);
		$ext = strtolower($ext);
		if ($ext == 'cdb' && $mode != 'r') $ext = 'cdb_make';
		$this->handler = dba_open($file, $mode, $ext);
		return $this->handler;
	}

	// Close
	function Close()
	{
		if (!$this->handler) return;

		dba_close($this->handler);
		$this->handler = false;
	}

	// Put
	function Put($key, $value)
	{
		return dba_insert($key, $value, $this->handler);
	}

	// Get
	function Get($key)
	{
		return dba_fetch($key, $this->handler);
	}
}
// End this simpled class object
?>
