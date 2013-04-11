<?php
/********************************************************
	SD-PR
	powered by Script Developers Group (SD-Group)
	email: info@sd-group.org.ua
	url: http://sd-group.org.ua/
	Copyright 2010-2015 (c) SD-Group
	All rights reserved
=========================================================
	Функции получения ТИЦ и вычисления PR
	взяты из готового скрипта (кто автор не знаю)
=========================================================
	Класс вычисления Google PageRank и Яндекс-ТИЦ
********************************************************/
/**
* @package
* @todo
*/

class pr
{
	/////////////////////////////////////////////////
	// VARS - свойства класса
	/////////////////////////////////////////////////

	private $google_magic = 0xE6359A60;

	/////////////////////////////////////////////////
	// CONSTRUCTOR - конструктор класса
	/////////////////////////////////////////////////
	/////////////////////////////////////////////////
	// METHODS - методы класса
	/////////////////////////////////////////////////
	private function createURL($url)
	{
		return $url = rtrim(str_replace('http://', '', $url), '/');
	}

	/**
	* Определение ТИЦ
	* 
	* @return string
	*/
	public function getTIC($url)
	{
		$url = $this -> createURL($url);

		if (!$fp = fsockopen('bar-navig.yandex.ru', 80, $errno, $errstr, 30))
		{
			return false;
		}
		else
		{ 
			$out = 'GET /u?ver=2&url=http://' . $url . '/&show=1 HTTP/1.1' . "\r\n"
				 . 'Host: bar-navig.yandex.ru' . "\r\n"
				 . 'Connection: Close' . "\r\n\r\n";

			fwrite($fp, $out);

			$data = '';

			while (!feof($fp))
			{
				$data .= fgets($fp, 128);
			}

			fclose($fp);

			$pos = strpos($data, 'value') + 7;
			$itog = substr($data, $pos, 5);
			$itog = substr($itog, 0, strpos($itog,'"'));

			if ($itog)
			{
				return $itog;
			}
			else
			{
				return 'Нет данных';
			}
		} 
	}


	/************ GOOGLE ************/
	private function noOverFlow($a) 
	{ 
		while ($a<-2147483648)
		{
			$a+=2147483648+2147483648;
		}

		while ($a>2147483647)
		{
			$a-=2147483648+2147483648;
		}

		return $a; 
	} 

	private function zeroFill ($x, $bits)
	{
		if ($bits === 0)
		{
			return $x;
		}

		if ($bits === 32)
		{
			return 0;
		}

		$y = ($x & 0x7FFFFFFF) >> $bits;

		if (0x80000000 & $x)
		{
			$y |= (1 << (31-$bits));
		}

		return $y;
	}

	private function mix($a, $b, $c)
	{
		$a = (int) $a; $b = (int) $b; $c = (int) $c;
		$a -= $b; $a -= $c; $a = $this -> noOverFlow($a); $a ^= ($this -> zeroFill($c, 13)); 
		$b -= $c; $b -= $a; $b = $this -> noOverFlow($b); $b ^= ($a << 8); 
		$c -= $a; $c -= $b; $c = $this -> noOverFlow($c); $c ^= ($this -> zeroFill($b, 13)); 
		$a -= $b; $a -= $c; $a = $this -> noOverFlow($a); $a ^= ($this -> zeroFill($c, 12)); 
		$b -= $c; $b -= $a; $b = $this -> noOverFlow($b); $b ^= ($a << 16); 
		$c -= $a; $c -= $b; $c = $this -> noOverFlow($c); $c ^= ($this -> zeroFill($b, 5)); 
		$a -= $b; $a -= $c; $a = $this -> noOverFlow($a); $a ^= ($this -> zeroFill($c, 3)); 
		$b -= $c; $b -= $a; $b = $this -> noOverFlow($b); $b ^= ($a << 10); 
		$c -= $a; $c -= $b; $c = $this -> noOverFlow($c); $c ^= ($this -> zeroFill($b, 15)); 

		return array($a, $b, $c);
	} 

	private function GCH($url, $length = null)
	{ 
	    if (is_null($length))
	    { 
			$length = sizeof($url);
	    } 

    	$a = $b = 0x9E3779B9;
    	$c = $this -> google_magic;
    	$k = 0;
    	$len = $length;

    	while($len >= 12)
		{
	        $a += ($url[$k+0] + ($url[$k+1] << 8) + ($url[$k+2] << 16) + ($url[$k+3] << 24));
	        $b += ($url[$k+4] + ($url[$k+5] << 8) + ($url[$k+6] << 16) + ($url[$k+7] << 24));
	        $c += ($url[$k+8] + ($url[$k+9] << 8) + ($url[$k+10] << 16) + ($url[$k+11] << 24));
	        $mix = $this -> mix($a, $b, $c);
	        $a = $mix[0]; $b = $mix[1]; $c = $mix[2];
	        $k += 12;
	        $len -= 12;
    	}

    	$c += $length;

		switch($len)
		{ 
	        case 11: $c += ($url[$k+10] << 24);
	        case 10: $c += ($url[$k+9] << 16);
	        case 9 : $c += ($url[$k+8] << 8);
	        case 8 : $b += ($url[$k+7] << 24);
	        case 7 : $b += ($url[$k+6] << 16);
	        case 6 : $b += ($url[$k+5] << 8);
	        case 5 : $b += ($url[$k+4]);
	        case 4 : $a += ($url[$k+3] << 24);
	        case 3 : $a += ($url[$k+2] << 16);
	        case 2 : $a += ($url[$k+1] << 8);
	        case 1 : $a += ($url[$k+0]);
		}

		$mix = $this -> mix($a, $b, $c);
    	return $mix[2];
	} 

	private function strOrd($string)
	{
	    for($i = 0; $i < strlen($string); $i++)
	    {
	        $result[$i] = ord($string{$i});
	    }
	    return $result;
	}

	public function getPageRank($aUrl)
	{ 
		$aUrl = str_replace('www.', '', $this -> createURL($aUrl));
		$url = 'info:' . $aUrl;
		$ch = $this -> GCH($this -> strOrd($url));
		$url='info:' . urlencode($aUrl);
		$pr = file('http://www.google.com/search?client=navclient-auto&ch=6' . $ch . '&features=Rank&q=' . $url);

		if ($pr)
		{
			$pr_str = implode('', $pr);
			$ret = substr($pr_str, strrpos($pr_str, ":") + 1);
		}
		else
		{
			$ret = 'Нет данных';
		}

	    return $ret;
	}  

	/////////////////////////////////////////////////
	// END OF CLASS
	/////////////////////////////////////////////////
}

?>