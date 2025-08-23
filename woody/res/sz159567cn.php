<?php 
require('php/_qdiimix.php');

function GetQdiiMixRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetYinHuaSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
