<?php 
require('php/_qdiimix.php');

function GetQdiiMixRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetKraneOfficialLink('KWEB').' '.GetCsindexOfficialLink('H11136');
	$str .= GetJiaoYinSchroderSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
