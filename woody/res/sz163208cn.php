<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetSpdrOfficialLink('XLE').' '.GetSpindicesOfficialLink('GSPE');
	$str .= GetNuoAnSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
