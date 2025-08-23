<?php 
require('php/_qdiieu.php');

function GetQdiiEuRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetHuaAnSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
