<?php
require('php/_myphoto.php');

function GetMetaDescription($bChinese)
{
	return 'Woody 2014 personal photos and related links. Including sunshine on my shoulders in Mei Lin, and the testing shot after my daughter was born.';
}

function EchoAll($bChinese)
{
    echo <<<END
<p><a href="http://www.doyouhike.net/forum/leisure/1013822,0,0,1.html" target=_blank>Jan 11</a>. Sunshine on my shoulders. <a href="2014/large/sunshine.jpg" target=_blank>Large</a>
<br /><img src=2014/sunshine.jpg alt="Sunshine on my shoulders in Mei Lin" /></p>

<p>Nov 16. When a child is born. <a href="2014/large/hospital.jpg" target=_blank>Large</a>
<br /><img src=2014/hospital.jpg alt="In the hospital where Sapphire was born" /></p>

<p>Nov 21. The Origin of <a href="../blog/entertainment/20141204.php">Sapphire Lin</a>. <a href="../blog/photo/large/20141204.jpg" target=_blank>Large</a>
<br /><img src=../blog/photo/20141204.jpg alt="Woody and Sapphire Lin are both worried!" /></p>

<p>Dec 13. Sapphire <a href="../sapphire/photo30days.php">30 Days</a>. <a href="../sapphire/30days/large/dad10.jpg" target=_blank>Large</a>
<br /><img src=../sapphire/30days/dad10.jpg alt="Sapphire Lin in red hat and red dot dress with Woody." /></p>
END;
}

require('../../php/ui/_disp.php');
?>
