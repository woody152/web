<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetQqqSoftwareLinks();
	$str .= GetChinaInternetSoftwareLinks();
	$str .= GetHtmlNewLine();
	$str .= GetEFundSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
