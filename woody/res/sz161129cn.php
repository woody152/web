<?php 
require('php/_qdiimix.php');

function GetQdiiMixRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetEFundSoftwareLinks($strDigitA);
	$str .= _GetKnownBugs('注意USO其实只是SZ161129跟踪和持有的标的之一，此处估算结果仅供参考。');
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
