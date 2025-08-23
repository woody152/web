<?php
require_once('account.php');
require_once('menu.php');
require_once('copyright.php');
require_once('analytics.php');
require_once('adsense.php');
require_once('ui/echohtml.php');

require_once('Mobile-Detect/standalone/autoloader.php');
require_once('Mobile-Detect/src/MobileDetectStandalone.php');
use Detection\Exception\MobileDetectException;
use Detection\MobileDetectStandalone;

define('DEFAULT_WIDTH', 640);
define('DEFAULT_DISPLAY_WIDTH', 900);
define('LEFT_MENU_WIDTH', 30 + 120 + 30 + 50);										// 最左边菜单宽度 width=30, 120, 30, 50
define('MIN_SCRREN_WIDTH', DEFAULT_DISPLAY_WIDTH + 10 + DEFAULT_ADSENSE_WIDTH);		// 隔10个像素再显示最右边的广告, 见下面width=10

function LayoutIsMobilePhone()
{
	$detect = new MobileDetectStandalone();
	$detect->setUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
	try 
	{
		if ($detect->isMobile() && !$detect->isTablet())	return true;
	}
	catch (MobileDetectException $e) 
	{
		DebugPrint($e);
	}
    return false;
}

function ResizeJpg($strPathName, $iNewWidth = 300, $iNewHeight = false)
{
	$strNewName = substr($strPathName, 0, strlen($strPathName) - 4).'x'.strval($iNewWidth).'__'.substr($strPathName, -4, 4);
	$strNewRootName = UrlModifyRootFileName($strNewName); 
	if (!file_exists($strNewRootName))
	{
		$imgOrg = imagecreatefromjpeg(UrlModifyRootFileName($strPathName));
		$iWidth = imagesx($imgOrg);
		$iHeight = imagesy($imgOrg);
		DebugString('Converting '.$strNewName);
		if ($iNewHeight === false)		$iNewHeight = intval($iNewWidth * $iHeight / $iWidth);
		$imgNew = imagecreatetruecolor($iNewWidth, $iNewHeight);
		imagecopyresampled($imgNew, $imgOrg, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $iWidth, $iHeight);
		imagejpeg($imgNew, $strNewRootName);
		imagedestroy($imgNew);
		imagedestroy($imgOrg);
	}
	return $strNewName;
}
/*
function ResizePng($strPathName, $iNewWidth = 300, $iNewHeight = false)
{
	$strNewName = substr($strPathName, 0, strlen($strPathName) - 4).'x'.strval($iNewWidth).'__.jpg';
	$strNewRootName = UrlModifyRootFileName($strNewName); 
	if (!file_exists($strNewRootName))
	{
		$imgOrg = imagecreatefrompng(UrlModifyRootFileName($strPathName));
		$iWidth = imagesx($imgOrg);
		$iHeight = imagesy($imgOrg);
		DebugString('Converting '.$strNewName);
		if ($iNewHeight === false)		$iNewHeight = intval($iNewWidth * $iHeight / $iWidth);
		$imgNew = imagecreatetruecolor($iNewWidth, $iNewHeight);
		imagefill($imgNew, 0, 0, imagecolorallocate($bg, 255, 255, 255));	// 处理透明背景
		imagecopyresampled($imgNew, $imgOrg, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $iWidth, $iHeight);
		imagejpeg($imgNew, $strNewRootName, 90);
		imagedestroy($imgNew);
		imagedestroy($imgOrg);
	}
	return $strNewName;
}
*/
//	https://ibkr.com/referral/rongrong586
function GetWechatPay($iType = 0, $bChinese = true)
{
	if ($iType == 0)	$iType = rand(1, 4);
	switch ($iType)
	{
	case 1:
		$strRemark = '觉得这个网站有用？可以用微信打赏一块钱给Woody！';
		$strImage = GetImgElement('/woody/image/wxpay.jpg', '微信打赏一块钱给Woody的二维码');
		break;
		
	case 2:
		$strRemark = '觉得这个网站有用？可以用微信打赏支持一下！';
		$strImage = GetImgElement(ResizeJpg('/debug/wechat/29e0a407b577177b.jpg'), $strRemark);
		break;
		
	case 3:
		$strRemark = $bChinese ? 'Palmmicro微信公众号小狐狸二维码' : 'Palmmicro Wechat Public Account QR code';
		$strImage = GetImgElement('/woody/image/wx.jpg', $strRemark);
		break;
		
	case 4:
//		$strPathName = ResizeJpg('/debug/wechat/59692929fecbbe0d.jpg');
//		$strRemark = '华宝拖拉机开户微信群二维码';
		$strRemark = '华宝拖拉机开户群已经超过200人扫码入群限制，扫上面二维码后可以请小瓶子拉进群。';
		$strImage = GetImgElement(ResizeJpg('/debug/wechat/bec5dabc01d8c812.jpg'), $strRemark);
		break;
/*        	
	case 5:
		$strRemark = '香港保诚保险投保微信群二维码';
		$strImage = GetImgElement(ResizeJpg('/debug/wechat/25e7591ce033c357.jpg'), $strRemark);
		break;
       	
	case 6:
		$strRemark = '扫描Palmmicro微信插件二维码然后关注，可以直接在微信中接收企业微信义工群的消息。';
		$strImage = GetImgElement(ResizeJpg('/debug/wechat/a39e5891dad44379.jpg'), $strRemark);
		break;
*/		
	}
	
	return $strImage.GetHtmlNewLine().GetRemarkElement($strRemark);
}

function LayoutScreenWidthOk()
{
	if (isset($_COOKIE['screenwidth']))
	{
		if ($strWidth = $_COOKIE['screenwidth'])
		{	// cookie in _layoutBanner worked 
			$iWidth = intval($strWidth);
			$iWidth -= 20;	// 假设右侧垂直滚动条最多20像素
			if ($iWidth >= MIN_SCRREN_WIDTH)	return $iWidth;
		}
	}
	return false;
}

function LayoutGetDisplayWidth()
{
	if ($iWidth = LayoutScreenWidthOk())
	{
		$iWidth -= 10 + DEFAULT_ADSENSE_WIDTH + LEFT_MENU_WIDTH;
		return ($iWidth < DEFAULT_WIDTH) ? DEFAULT_WIDTH : $iWidth;
	}
	return DEFAULT_WIDTH;
}

function LayoutUseWide()
{
	if ($_SESSION['mobile'])	return true;
	return (LayoutGetDisplayWidth() >= 1080) ? true : false;
}

function LayoutGetDisplayHeight()
{
	if (isset($_COOKIE['screenheight']))
	{
		if ($strHeight = $_COOKIE['screenheight'])
		{	// cookie in _layoutBanner worked 
			$iHeight = intval($strHeight);
			$iHeight -= 144;	// image_palmmicro.jpg 800*105像素
			return $iHeight;
		}
	}
	return 480;
}

function _layoutBanner($bChinese)
{
//    $ar = explode('/', UrlGetUri());
//	if ($ar[1] == 'woody')	$strImage = GetImgElement('/woody/image/image.jpg', 'Woody Home Page');
//	else
	$strImage = GetImgElement('/image/image_palmmicro.jpg', 'Palmmicro Name Logo');
	$strLink = GetLinkElement($strImage, '/index'.($bChinese ? 'cn' : '').'.html');
    
    echo <<<END
<div id="banner">
    <div class="logo">$strLink</div>
    <div class="blue"></div>
</div>
<script>
	var width = window.screen.width;
	var height = window.screen.height;
	document.cookie = "screenheight=" + height + "; path=/";
	document.cookie = "screenwidth=" + width + "; path=/";
</script>
END;
}

function _layoutAboveMenu($iWidth)
{
	if ($iWidth == false)	$iWidth = DEFAULT_DISPLAY_WIDTH;
	$strWidth = strval($iWidth);
	
    echo <<<END

<table width=$strWidth height=85% border=0 cellpadding=0 cellspacing=0>
<tbody>
<tr>
<td width=30 valign=top bgcolor=#66CC66>&nbsp;</td>
<td width=120 valign=top bgcolor=#66CC66>
<div>
END;
/*    echo <<<END
        <div id="main">
            <div class="green">&nbsp;</div>
            <div class="nav">
END;*/
}

function _layoutBelowMenu($iWidth)
{
	if ($iWidth)		$strExtra = 'width='.strval($iWidth - MIN_SCRREN_WIDTH + DEFAULT_DISPLAY_WIDTH - LEFT_MENU_WIDTH);
	else 				$strExtra = '';
	
    echo <<<END
    
</div>
</td>
<td width=30 valign=top bgcolor=#66CC66>&nbsp;</td>
<td width=50 valign=top bgcolor=#ffffff>&nbsp;</td>
<td $strExtra valign=top>
END;
/*    echo <<<END
            </div>
            <div class="green2">&nbsp;</div>
            <div class="white">&nbsp;</div>
            <div class="edit">
END;*/
}

function GetSwitchLanguageLink($bChinese)
{
	if ($_SESSION['switchlanguage'] == false)	return '';

	// /woody/blog/entertainment/20140615cn.php ==> 20140615.php
    $str = UrlGetPage();
    $str .= UrlGetPhp(UrlIsEnglish());
    $str .= UrlPassQuery();
    return MenuGetLink($str, $bChinese ? GetImgElement('/image/us.gif', 'Switch to ').'English' : GetImgElement('/image/zh.jpg', '切换成').'中文');
}

function LayoutTopLeft($callback = false, $bSwitchLanguage = false, $bChinese = true, $bAdsense = true)
{
    if ($bAdsense)	EchoAnalyticsOptimize();
	$_SESSION['switchlanguage'] = $bSwitchLanguage;
    if ($_SESSION['mobile'])
    {
    	if ($bAdsense)	AdsensePalmmicroUser();
    }
    else
    {
        _layoutBanner($bChinese);
        
        $iWidth = LayoutScreenWidthOk();
        _layoutAboveMenu($iWidth);
        call_user_func($callback, $bChinese);
        _layoutBelowMenu($iWidth);
    }
}

function LayoutBegin()
{
    echo <<<END

<div>
END;
}

function LayoutEnd()
{
    echo <<<END

</div>
END;
}

function _echoWechatPay($bChinese)
{
	LayoutBegin();
	EchoHtmlElement(GetWechatPay(0, $bChinese));
	LayoutEnd();
}

// According to google policy, do NOT show Adsense in pages with no contents, such as input pages
function LayoutTail($bChinese = true, $bAdsense = false)
{
    if ($_SESSION['mobile'])
    {
		if ($bAdsense)	AdsenseContent();
   		else				_echoWechatPay($bChinese);
    }
    else
    {
    	if (LayoutScreenWidthOk())
    	{
    		echo <<<END

</td>
<td width=10 valign=top>&nbsp;</td>
<td valign=top>
END;
    		if ($bAdsense)	AdsenseLeft();
    		else				_echoWechatPay($bChinese);
    	}
    	else
    	{
    		if ($bAdsense)	AdsenseWoodyBlog();
    		else				_echoWechatPay($bChinese);
    	}
    	echo <<<END2

</td>
</tr>
</tbody>
</table>
END2;
    	
//        echo '</td></tr></tbody></table>';
//        echo '    </div>';
//        echo '</div>';
    }
    EchoCopyRight($_SESSION['mobile'], $bChinese);
}

?>
