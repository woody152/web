<?php 
require('php/_qdiihk.php');

function GetQdiiHkRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetHuaXiaSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
