<?php
require('php/_myphoto.php');

function EchoAll($bChinese)
{
	$strSnowball = PhotoSnowball();
	$strNasdaq100 = VideoNasdaq100();
	
    echo <<<END
$strSnowball
$strNasdaq100
END;
}

require('../../php/ui/_dispcn.php');
