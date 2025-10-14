<?php 
require('php/_chinafuture.php');

function GetChinaFutureRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetGuoTaiSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
