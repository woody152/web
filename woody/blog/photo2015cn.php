<?php 
require('php/_blogphoto.php');

function GetMetaDescription()
{
	return 'Woody的2015年网络日志中使用的图片列表和日志链接。包括经典测试图像Lenna部分原始图片等。';
}

function EchoAll()
{
	$strLenna = GetBlogPictureParagraph(20150818, 'ImgCompleteLenna');

    echo <<<END
$strLenna
END;
}

require('../../php/ui/_dispcn.php');
?>
