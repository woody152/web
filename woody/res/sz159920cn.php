<?php 
require('php/_qdiihk.php');

function GetQdiiHkRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetHuaXiaSoftwareLinks($strDigitA);
	$str .= _GetKnownBugs('2018年6月22日星期五，SZ159920成立以来首次分红0.076元，导致当日估值误差5.19%。');
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
