<?php
require_once('../../../php/_blogcomments.php');
require_once('../../../php/bloglink.php');
require_once('../../../php/stocklink.php');
require_once('../../../php/externallink.php');
require_once('../../../php/ui/link.php');
require_once('../../../php/ui/blogtable.php');
require_once('../../../php/ui/imagedisp.php');
require_once('../../../php/ui/videodisp.php');
require_once('../../php/_woodymenu.php');

function _menuBlogGroup($bChinese)
{
    $iLevel = 2;
	$ar = GetBlogMenuArray($bChinese);
    
	MenuBegin();
	WoodyMenuItem($iLevel, 'blog', $bChinese);
	MenuContinueNewLine();
	MenuWriteItemLink($iLevel - 1, BLOG_GROUP, UrlGetPhp($bChinese), $ar[BLOG_GROUP]);
	MenuContinueNewLine();
    MenuDirFirstLast(GetBlogArray());
	MenuContinueNewLine();
    MenuSwitchLanguage($bChinese);
    MenuEnd();
}

function _LayoutTopLeft($bChinese = true, $bAdsense = true)
{
    LayoutTopLeft('_menuBlogGroup', true, $bChinese, $bAdsense);
}

function _LayoutBottom($bChinese = true, $bAdsense = true)
{
	EchoBlogMenuArray($bChinese);
	EchoBlogComments($bChinese);
    LayoutTail($bChinese, $bAdsense);
}

function GetTitle($bChinese)
{
	return GetBlogTitle(intval(UrlGetPage()), $bChinese, false);
}

function EchoAll($bChinese)
{
	echo '<p>'.GetBlogYmd($bChinese);
	EchoAllBlog($bChinese);
}

   	$acct = new EditCommentAccount();
?>
