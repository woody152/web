<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetSpdrOfficialLink('XLY');
	$str .= GetHuaBaoSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
