<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetEFundOfficialLink($strDigitA);
	$str .= GetQqqSoftwareLinks();
	$str .= GetChinaInternetSoftwareLinks();
	$str .= GetEFundSoftwareLinks();
	return $str;
}

require('/php/ui/_dispcn.php');
?>
