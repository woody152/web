<?php
require_once('../php/debug.php');
require_once('../php/ui/echoelement.php');

define('DISP_TITLE', '429 Too Many Requests');

function EchoHead($bChinese = true)
{
	EchoTitle(DISP_TITLE);
//	EchoViewPort();
//	EchoCSS();
}

	$acct = false;
	$strRetry = 'Retry-After: '.strval(SECONDS_IN_DAY/2);
	
	http_response_code(429);
	header($strRetry);

	EchoNobody();
	echo '<body>';
	EchoHeading(DISP_TITLE);
	EchoHtmlElement($strRetry);
    echo <<<END

</body>
</html>
END;
?>
