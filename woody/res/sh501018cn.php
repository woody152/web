<?php 
require('php/_qdiimix.php');

function GetQdiiMixRelated($strDigitA)
{
	$str = GetHtmlNewLine();
	$str .= GetNanFangSoftwareLinks($strDigitA);
	$str .= _GetKnownBugs('注意USO其实只是SH501018跟踪和持有的标的之一，此处估算结果仅供参考。',
						  '2016年12月21日周三，CL期货换月。因为CL和USO要等当晚美股开盘才会自动校准，白天按照CL估算的实时净值不准。');
	return $str;
}

require('../../php/ui/_dispcn.php');
?>
