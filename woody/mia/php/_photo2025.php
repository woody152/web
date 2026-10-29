<?php
require_once('_mia.php');

function ImgMiaLisaRoeck($bChinese = true)
{
	return WoodyImgAutoQuote('20250712/wx_camera_1752364159418.jpg', '一年级英文老师Lisa在Satellite Beach的度假屋', 'Vacation home of first grade teacher Lisa at Las Olas Beach Club of Satellite Beach', $bChinese);
}

function ImgMiaAnimalKingdom($bChinese = true)
{
	return WoodyImgAutoQuote('20250711/IMG_20250711_174820.jpg', '再次走断腿的迪斯尼动物王国', "A little rest in Disney's Animal Kingdom", $bChinese);
}

function ImgMiaStarWars($bChinese = true)
{
	return WoodyImgAutoQuote('20250707/IMG_20250707_112155.jpg', '还不到喜欢星球大战的年龄', 'Not ready for Star Wars yet', $bChinese);
}

function ImgMiaWildHogs($bChinese = true)
{
	return WoodyImgAutoQuote('20250705/IMG_20250705_181140.jpg', '肯尼迪航天中心附件的野猪', 'Wild pigs near Kennedy Space Center', $bChinese);
}

function ImgMiaDisneySprings($bChinese = true)
{
	return WoodyImgAutoQuote('20250704/IMG_20250704_224415.jpg', 'DisneyStyle店里给人拍照的茶杯椅子', 'Sitting in a stationary tea cup in DisneyStyle of Disney Springs', $bChinese);
}

function ImgMiaCypressHarbour($bChinese = true)
{
	return WoodyImgAutoQuote('20250703/IMG_20250703_143015.jpg', 'Cypress Harbour万豪度假村里的鳄鱼讲解', "Alligator encounter in Lakehouse Activities Center at Marriott's Cypress Harbour", $bChinese);
}

function ImgMiaSharkValley($bChinese = true)
{
	return WoodyImgAutoQuote('20250626/IMG_20250626_165814.jpg', '在大沼泽国家公园Shark Valley的瞭望塔上', 'On the observation tower of Shark Valley in Everglades National Park', $bChinese);
}

function ImgMiaEverglades($bChinese = true)
{
	return WoodyImgAutoQuote('20250625/IMG_20250625_180152.jpg', '大沼泽国家公园大门', 'Main entrance of Everglades National Park', $bChinese);
}

function ImgMiaBiscayne($bChinese = true)
{
	return WoodyImgAutoQuote('20250624/IMG_20250624_154240.jpg', 'Biscayne国家公园', 'Biscayne National Park', $bChinese);
}

function ImgMiaFortMatanzas($bChinese = true)
{
	return WoodyImgAutoQuote('20250620/IMG_20250620_143149.jpg', 'Fort Matanzas国家纪念碑', 'Fort Matanzas National Monument', $bChinese);
}

function EchoAll($bChinese)
{
	$strLisaRoeck = GetPhotoDirParagraph(20250712, 'ImgMiaLisaRoeck', $bChinese, GetExternalLink('https://www.facebook.com/share/p/19BsuX8bca/', 'Satellite Beach'));
	$strAnimalKingdom = GetPhotoDirParagraph(20250711, 'ImgMiaAnimalKingdom', $bChinese, ($bChinese ? '迪斯尼动物王国' : "Disney's Animal Kingdom"));
	$strStarWars = GetPhotoDirParagraph(20250707, 'ImgMiaStarWars', $bChinese, ($bChinese ? '迪斯尼好莱坞影城' : "Disney's Hollywood Studios"));
	$strWildHogs = GetPhotoDirParagraph(20250705, 'ImgMiaWildHogs', $bChinese, ($bChinese ? '路遇野猪' : 'Feral hogs spotted'));
	$strDisneySprings = GetPhotoDirParagraph(20250704, 'ImgMiaDisneySprings', $bChinese, ($bChinese ? '以为迪斯尼小镇会有美国国庆节的烟花看' : 'No fireworks in Disney Springs tonight'));
	$strCypressHarbour = GetPhotoDirParagraph(20250703, 'ImgMiaCypressHarbour', $bChinese, ($bChinese ? 'Cypress Harbour万豪度假村' : "Marriott's Cypress Harbour"));
	$strSharkValley = GetPhotoDirParagraph(20250626, 'ImgMiaSharkValley', $bChinese, ($bChinese ? '大沼泽国家公园Shark Valley部分' : 'Shark Valley in Everglades National Park'));
	$strEverglades = GetPhotoDirParagraph(20250625, 'ImgMiaEverglades', $bChinese, ($bChinese ? '大沼泽国家公园' : 'Everglades National Park'));
	$strBiscayne = GetPhotoDirParagraph(20250624, 'ImgMiaBiscayne', $bChinese, ($bChinese ? 'Biscayne国家公园' : 'Biscayne National Park'));
	$strFortMatanzas = GetPhotoDirParagraph(20250620, 'ImgMiaFortMatanzas', $bChinese, ($bChinese ? 'Fort Matanzas国家纪念碑' : 'Fort Matanzas National Monument'));
	
    echo <<<END
$strLisaRoeck
$strAnimalKingdom
$strStarWars
$strWildHogs
$strDisneySprings
$strCypressHarbour
$strSharkValley
$strEverglades
$strBiscayne
$strFortMatanzas
END;
}
