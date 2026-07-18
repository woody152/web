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
	$iPid = match($strSymbol)
			{'KWEB' => 7615,
			 'KSTR' => 8340,
			 default => 0
			};
	if ($ar = StockDebugJson(DebugGetNetValueFile($strSymbol), GetKraneUrl().'product-json/?pid='.strval($iPid)."&type=premium-discount&start=$strPrevDate&end=$strPrevDate"))
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

function UpdateKraneNetValue($ref, $strPrevDate)
{
	if ($strNetValue = GetKraneNetValue($ref, $strPrevDate))
	{
		$net_sql = GetNetValueHistorySql();
		$net_sql->WriteDaily($ref->GetStockId(), $strPrevDate, $strNetValue);
	}
	return $strNetValue;
	
}
