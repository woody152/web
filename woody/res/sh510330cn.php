<?php 
require('php/_chinaindex.php');

function GetChinaIndexRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetHuaXiaSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
