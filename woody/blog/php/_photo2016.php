<?php
require_once('_blogphoto.php');

function EchoAll($bChinese)
{
	$strWechat = 	GetHtmlElement(GetBlogTitle(20161014, $bChinese).' '.GetBreakElement().' '.GetWechatPay(2, $bChinese));
	
    echo <<<END
$strWechat
END;
}

?>
