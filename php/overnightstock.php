<?php

function GetOverNightTradingSymbol($est_ref)
{
	if ($est_ref->IsIndex())
	{
		switch ($est_ref->GetSymbol())
		{
		case '^GSPC':
			return 'SPY';

		case '^NDX':
			return 'QQQ';
		}
	}
    return false;
}

?>
