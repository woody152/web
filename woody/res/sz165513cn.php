<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetIsharesOfficialLink('GSG').' '.GetSpindicesOfficialLink('SPGCCI');
	$str .= GetXinChengSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
