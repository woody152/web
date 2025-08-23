<?php 
require('php/_qdiihk.php');

function GetQdiiHkRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetHuaTaiSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
