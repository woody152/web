<?php

function GetMyPhotoYears()
{
	return [2006, 2007, 2008, 2009, 2010, 2011, 2012, 2014, 2015, 2016, 2019, 2020, 2021, 2023];
}

function GetMiaPhotoYears()
{
	return [2014, 2015, 2016, 2018, 2022, 2023, 2024];
}

function GetBlogPhotoYears()
{
	return [2006, 2007, 2008, 2009, 2010, 2011, 2012, 2013, 2014, 2015, 2016, 2020, 2023, 2025];
}

function GetPhotoPageArray($arYears)
{
	$arPhoto = [];
	foreach ($arYears as $iYear)
	{
		$arPhoto[] = 'photo'.strval($iYear);
	}
	return $arPhoto;
}

function GetPhotoMenuArray($arYears)
{
	$arPhoto = [];
	foreach ($arYears as $iYear)
	{
		$strYear = strval($iYear);
		$arPhoto["photo{$strYear}"] = $strYear;	// substr($strYear, -2, 2);
	}
	return $arPhoto;
}

function GetBlogPhotoLinks($bChinese = true)
{
	return GetCategoryLinks(GetPhotoMenuArray(GetBlogPhotoYears()), PATH_BLOG, $bChinese);
}

function GetMyPhotoLinks($bChinese = true)
{
	return GetCategoryLinks(GetPhotoMenuArray(GetMyPhotoYears()), '/woody/myphoto/', $bChinese);
}

function GetMiaPhotoLinks($bChinese = true)
{
	return GetCategoryLinks(GetPhotoMenuArray(GetMiaPhotoYears()), '/woody/mia/', $bChinese);
}

function GetMia30DaysDisplay($bChinese = true)
{
	return $bChinese ? '满月' : '30 Days';
}

const PATH_MIA_30DAYS = '/woody/mia/30days/';
function GetMia30DaysLink($bChinese = true)
{
	return GetPageLink(PATH_MIA_30DAYS, 'index', false, GetMia30DaysDisplay($bChinese), $bChinese);
}

function GetBlogMenuArray($bChinese)
{
    if ($bChinese)  return ['ar1688' => 'AR1688', 'entertainment' => '娱乐', 'pa1688' => 'PA1688', 'pa3288' => 'PA3288', 'pa6488' => 'PA6488', 'palmmicro' => 'Palmmicro'];
    return ['ar1688' => 'AR1688', 'entertainment' => 'Entertainment', 'pa1688' => 'PA1688', 'pa3288' => 'PA3288', 'pa6488' => 'PA6488', 'palmmicro' => 'Palmmicro'];
}

function GetBlogMenuLinks($bChinese = true)
{
	return GetCategoryLinks(GetBlogMenuArray($bChinese), PATH_BLOG, $bChinese);
}

function LayoutWoodyMenuArray($bChinese)
{
	LayoutBegin();
	EchoHtmlElement(GetCategoryLinks(GetWoodyMenuArray($bChinese), '/woody/', $bChinese));
	LayoutEnd();
}

function LayoutBlogMenuArray($bChinese)
{
	LayoutBegin();
	EchoHtmlElement(GetBlogMenuLinks($bChinese));
	LayoutEnd();
	
	LayoutWoodyMenuArray($bChinese);
}

function LayoutMiaPhotoArray($bChinese)
{
	LayoutBegin();
	EchoHtmlElement(GetMiaPhotoLinks($bChinese));
	LayoutEnd();

	LayoutWoodyMenuArray($bChinese);
}

function PhpMenuItem($arName, $iLevel, $strItem, $bChinese)
{
    foreach ($arName as $strKey => $strDisplay)
    {
        if ($strItem == $strKey)
        {
          	MenuWriteItemLink($iLevel, $strItem, UrlGetPhp($bChinese), $strDisplay);
        	break;
        }
    }
}

function GetWoodyMenuArray($bChinese)
{
    if ($bChinese)	return ['index' => '资源共享', 'image' => '相片',  'blog' => '网络日志']; 
    return ['index' => 'Resource', 'image' => 'Image', 'blog' => 'Blog'];
}

function WoodyMenuItem($iLevel, $strItem, $bChinese = true)
{
    PhpMenuItem(GetWoodyMenuArray($bChinese), $iLevel, $strItem, $bChinese);
}
