<?php 
require('php/_qdiijp.php');

function GetQdiiJpRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetEFundSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
