<?php
require_once('_blogphoto.php');

function EchoAll($bChinese)
{
 	$strOvernightTrading = GetBlogPictureParagraph(20250223, 'ImgFreeFood', $bChinese);
	
    echo <<<END
$strOvernightTrading
END;
}
