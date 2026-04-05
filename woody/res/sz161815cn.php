<?php 
require('php/_qdiimix.php');

function GetQdiiMixRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetIsharesOfficialLink('GSG').' '.GetSpindicesOfficialLink('SPGCCI');
	$str .= GetYinHuaSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
