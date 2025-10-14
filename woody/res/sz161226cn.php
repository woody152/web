<?php 
require('php/_chinafuture.php');

function GetChinaFutureRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetUbsSdicSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
