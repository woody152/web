<?php 
require('php/_qdii.php');

function GetQdiiRelated($sym)
{
	$str = GetUniversalOfficialLink($sym);
	$str .= ' '.GetQdiiLinks($sym);
	$str .= GetOilSoftwareLinks();
	$str .= GetCommoditySoftwareLinks();
	$str .= GetUniversalSoftwareLinks();
	return $str;
}

require('/php/ui/_dispcn.php');
?>
