<?php 
require('php/_chinafuture.php');

function GetChinaFutureRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetHuaAnSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
