<?php
require_once('echoelement.php');

function EchoInsideHead()
{
	$_SESSION['mobile'] = LayoutIsMobilePhone();
	$strCanonical = str_replace('www.', '', UrlGetServer()).UrlGetUri().UrlPassQuery();
	$strFavicon = '/image/favicon.ico';
	
    echo <<<END

    <link rel="canonical" href="$strCanonical" />
    <link rel="shortcut icon" href="$strFavicon" type="image/x-icon">
END;

	if ($_SESSION['mobile'])	EchoViewPort();
}

function EchoHead($bChinese = true)
{
	EchoTitle(GetTitle($bChinese));
	EchoNewLine('<meta name="description" content="'.GetMetaDescription($bChinese).'">');
	EchoInsideHead();
	EchoCSS();
}

function EchoBody($bChinese = true, $bDisplay = true)
{
	$bAdsense = DebugIsPalmmicro() ? false : $bDisplay;
	_LayoutTopLeft($bChinese, $bAdsense);

	LayoutBegin();
	EchoHeading(GetTitle($bChinese));
	EchoAll($bChinese);
	LayoutEnd();
	
	if ($bDisplay)	_LayoutBottom($bChinese, $bAdsense);
	else			LayoutTail($bChinese);
	
//	DebugNow('总时间');
}

?>
