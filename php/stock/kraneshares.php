<?php

/*
Array
(
    [0] => Array
        (
            [0] => 1728950400000
            [1] => -0.013070608
        )
)
*/

function GetKraneNetValue($ref, $strPrevDate)
{
	$strStockId = $ref->GetStockId();
	$his_sql = GetStockHistorySql();
	
	date_default_timezone_set('Europe/London');
	$strSymbol = $ref->GetSymbol();
	if ($ar = StockDebugJson(DebugGetNetValueFile($strSymbol), GetKraneUrl()."product-json/?pid=7615&type=premium-discount&start=$strPrevDate&end=$strPrevDate"))
	{
		if (!isset($ar[0]))			
		{
			DebugString('no data');
			return false;
		}
		$ar0 = $ar[0];
		$iTick = intval($ar0[0]) / 1000;
        $ymd = new TickYMD($iTick);
        if ($ymd->GetYMD() != $strPrevDate)
        {
        	DebugString($ymd->GetYMD().' '.$strPrevDate.' miss match date');
        	DebugPrint(localtime($iTick, true));
        	return false;
        }
        $fNetValue = floatval($his_sql->GetClose($strStockId, $strPrevDate)) / (1.0 + floatval($ar0[1]));
        DebugVal($fNetValue, __FUNCTION__);
		return number_format($fNetValue, NETVALUE_PRECISION, '.', '');
   	}
    return false;
}

?>
