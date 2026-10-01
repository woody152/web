<?php

// Provide enhanced function replacement of /js/analytics.js
function EchoAnalyticsOptimize()
{
    echo <<< END
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-DQ4P3FHV66"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', 'G-DQ4P3FHV66');
</script>

END;
}
