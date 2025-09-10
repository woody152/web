<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetKraneOfficialLink('KWEB').' '.GetCsindexOfficialLink('H11136');
	$str .= GetJiaoYinSchroderSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
