<?php
// word_single
// if (F < 500) F = 10000 - F*18;
// TF = log(L)^2 * log(F);

// IDF  [4 ~ 20]
// TF   [10 ~ 15]
//
// IDF = log(5000000000/F)
//
// php calc_text.php <w|z|all> 
//
// TF for single Z:
// TF(z) = log(F)/10 * log(2)
// IDF for single Z: (0)
//
/*
$attr_tw2pku = array(
	'A' => 'Ag',
	'Caa' => 'c',
	'Cab' => 'c',
	'Cba' => 'c',
	'Cbb' => 'c',
	'Da' => 'd',
	'Dfa' => 'd',
	'Dfb' => 'd',
	'Di' => 'd',
	'Dk' => 'd',
	'D' => 'd',
	'Na' => 'n',
	'Nb' => 'nz',
	'Nc' => 'ns',
	'Ncd' => 'ns',
	'Nd' => 'n',
	'Neu' => 'n',
	'Nes' => 'r',
	'Nep' => 'r',
	'Neqa' => 'mq',
	'Neqb' => 'mq',
	'Nf' => 'q',
	'Ng' => 'f',
	'Nh' => 'r',
	'I' => 'e',
	'P' => 'p',
	'T' => 'y',
	'VA' => 'vn',
	'VAC' => 'v',
	'VB' => 'v',
	'VCL' => 'v',
	'VD' => 'vn',
	'VE' => 'vn',
	'VF' => 'vn',
	'VG' => 'v',
	'VH' => 'a',
	'VHC' => 'vn',
	'VI' => 'v',
	'VJ' => 'v',
	'VK' => 'v',
	'VL' => 'vn',
	'V_2' => 'v',
	'DE' => 'uj',
	'SHI' => 'v',
	'FW' => 'un'
);
*/
$total = 0;
$type = $_SERVER['argv'][1];
$root = dirname(__FILE__);

//
if (!$type || $type[0] != 'z')
{
	$fd = fopen("$root/w.txt", "r");
	while ($line = fgets($fd, 256))
	{
		list($word, $freq, $attr) = explode("\t", $line);
		$freq = intval($freq);
		$attr = trim($attr);
		if ($freq < 1000) $freq = 21000 - $freq * 18;
		$tf = log($freq);
		$tf = pow($tf, 5) * log(strlen($word));
		$tf = log($tf);
		$idf = log(5000000000/$freq);
		if (strlen($attr) == 2 && $attr[0] == 'n' && $tf > 13) $idf *= 1.6;
		printf("%s\t%.2f\t%.2f\t%s\n", $word, $tf, $idf, $attr);
		$total++;
	}
	fclose($fd);
}

// single z
if (!$type || $type[0] != 'w')
{
	$fd = fopen("$root/z.txt", "r");
	while ($line = fgets($fd, 256))
	{
		list($word, $freq, $attr) = explode("\t", $line);
		$freq = intval($freq);
		$tf = log($freq) * log(2) / 10;
		$idf = 0;
		//$tf = log($tf);
		printf("%s\t%.2f\t%.2f\t%s", $word, $tf, $idf, $attr);
		$total++;
	}
	fclose($fd);
}
