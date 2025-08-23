<?php 
require('php/_qdiimix.php');

function GetQdiiMixRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetExternalLink('https://indexes.nasdaqomx.com/Index/Overview/NDXTMC', 'NDXTMC'); 
	$str .= ' '.GetJingShunSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
