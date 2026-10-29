<?php
require_once('_mia.php');

function ImgMiaCabrillo($bChinese = true)
{
	return WoodyImgAutoQuote('20240701/IMG_20240701_120025.jpg', 'Cabrillo国家纪念碑', 'Cabrillo National Monument', $bChinese);
}

function ImgMiaWalnutCanyon($bChinese = true)
{
	return WoodyImgAutoQuote('20240628/IMG_20240628_182742.jpg', 'Walnut Canyon国家纪念碑', 'Walnut Canyon National Monument', $bChinese);
}

function ImgMiaChineseNewYear($bChinese = true)
{
	return WoodyImgAutoQuote('20240210/mmexport1707577854407.jpg', '在陌生的长沙过春节', 'Lost in translation', $bChinese);
}

function VidMiaFireworks($bChinese = true)
{
	return GetVideoControl(PATH_WOODY_IMAGE.'20240214/mmexport1707872481031.mp4', ($bChinese ? '小心翼翼放烟花' : 'Play fireworks carefully'), $bChinese);
}

function EchoAll($bChinese)
{
	$strCabrillo = GetPhotoDirParagraph(20240701, 'ImgMiaCabrillo', $bChinese, ($bChinese ? 'Cabrillo国家纪念碑' : 'Cabrillo National Monument'));
	$strWalnutCanyon = GetPhotoDirParagraph(20240628, 'ImgMiaWalnutCanyon', $bChinese, ($bChinese ? 'Walnut Canyon国家纪念碑' : 'Walnut Canyon National Monument'));
	$strFireworks = GetPhotoDirParagraph(20240214, 'VidMiaFireworks', $bChinese, ($bChinese ? '跟小姨奶奶家的聚会, 刘瑞翔拍摄。' : "Party with dad's aunt family"));
	$strChineseNewYear = GetPhotoDirParagraph(20240210, 'ImgMiaChineseNewYear', $bChinese, ($bChinese ? '疫情后第一次回长沙过年, 林九霞拍摄。' : 'Chinese New Year holidays in Changsha'));
	
    echo <<<END
$strCabrillo
$strWalnutCanyon
$strFireworks
$strChineseNewYear
END;
}
