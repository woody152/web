<?php 
require('php/_qdiihk.php');

function GetQdiiHkRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetNanFangSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
