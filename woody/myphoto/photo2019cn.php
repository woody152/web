<?php
require('php/_myphoto.php');

function EchoAll($bChinese)
{
	$strEat = GetHtmlElement(GetBlogMonthDay('20190128').GetHtmlNewLine().ImgWoody20190128());
	
    echo <<<END
$strEat
END;
}

require('../../php/ui/_dispcn.php');
?>
