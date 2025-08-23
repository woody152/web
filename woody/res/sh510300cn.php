<?php 
require('php/_chinaindex.php');

function GetChinaIndexRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetHuaTaiSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
