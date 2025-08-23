<?php 
require('php/_chinaindex.php');

function GetChinaIndexRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetJiaShiSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
