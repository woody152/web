<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetHuaAnSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
