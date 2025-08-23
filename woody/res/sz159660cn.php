<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetHtmlNewLine().GetUniversalSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
