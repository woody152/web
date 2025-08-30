<?php 
require('php/_qdii.php');

function GetQdiiRelated($strDigitA)
{
	$str = GetHuaBaoSoftwareLinks($strDigitA);
	$str .= _GetKnownBugs('2022年9月16日周五收盘后，XOP季度分红除权。因为采用XOP净值替代SPSIOP，不处理的话19日周一华宝油气的估值会不准，要等华宝油气19日实际净值出来自动校准后才能恢复正常。系统管理员已经在XOP个股页面选择分红链接完成了手工干预。');
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
