<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetEFundSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
