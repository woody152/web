<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetIsharesOfficialLink('IEO').' '.GetSpindicesOfficialLink('DJSOEP');
	$str .= GetGuangFaSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
