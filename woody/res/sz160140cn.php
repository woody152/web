<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetNanFangSoftwareLinks($strDigitA);
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
