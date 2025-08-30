<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetSpindicesOfficialLink('DJI');
	$str .= GetPenghuaSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
