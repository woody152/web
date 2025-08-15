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

function GetKraneNetValue($ref)
{
	$strStockId = $ref->GetStockId();
	$strDate = $ref->GetDate();
	$net_sql = GetNetValueHistorySql();
 	$strNetValueDate = $net_sql->GetDateNow($strStockId);	
	if ($strNetValueDate == $strDate)	return false;		// already have current net value
	$his_sql = GetStockHistorySql();
	$strPrevDate = $his_sql->GetDatePrev($strStockId, $strDate);
	if ($strNetValueDate == $strPrevDate)	return false;		// already up to date
	
//	$ref->SetTimeZone();
	date_default_timezone_set('Europe/London');
	$strSymbol = $ref->GetSymbol();
	$strFileName = DebugGetPathName('netvalue_'.$strSymbol.'.txt');
	if (StockNeedFile($strFileName) == false)	return false; 	// updates on every minute

	$strUrl = GetKraneUrl()."product-json/?pid=477&type=premium-discount&start=$strPrevDate&end=$strPrevDate";
   	if ($str = url_get_contents($strUrl))
   	{
   		DebugString($strUrl.' save new file to '.$strFileName);
   		file_put_contents($strFileName, $str);
   		$ar = json_decode($str, true);
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
//		return strval_round(floatval($his_sql->GetClose($strStockId, $strPrevDate)) * (1.0 - floatval($ar0[1])), 4);
		return strval_round(floatval($his_sql->GetClose($strStockId, $strPrevDate)) / (1.0 + floatval($ar0[1])), 4);
   	}
    return false;
}

?>
