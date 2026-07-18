<?php
/* http://money.finance.sina.com.cn/quotes_service/api/json_v2.php/CN_MarketData.getKLineData?symbol=sh000688&scale=240&ma=no&datalen=5
Array
(
    [0] => Array
        (
            [day] => 2026-07-09
            [open] => 2047.238
            [high] => 2188.982
            [low] => 2023.544
            [close] => 2185.825
            [volume] => 1858060500
        )
    [1] => Array
        (
            [day] => 2026-07-10
            [open] => 2190.178
            [high] => 2233.538
            [low] => 2064.976
            [close] => 2064.976
            [volume] => 2307342900
        )
    [2] => Array
        (
            [day] => 2026-07-13
            [open] => 2038.362
            [high] => 2110.179
            [low] => 1974.055
            [close] => 1994.319
            [volume] => 1833793700
        )
    [3] => Array
        (
            [day] => 2026-07-14
            [open] => 1994.440
            [high] => 2026.471
            [low] => 1895.555
            [close] => 2009.730
            [volume] => 1601395000
        )
    [4] => Array
        (
            [day] => 2026-07-15
            [open] => 2021.578
            [high] => 2028.652
            [low] => 1908.178
            [close] => 1924.274
            [volume] => 1428193300
        )
)
*/

function UpdateSinaHistory($ref)
{
	$strStockId = $ref->GetStockId();
	$strCurDate = $ref->GetDate();
   	$date_sql = new StockHistoryDateSql();
   	if ($strCurDate == $date_sql->ReadDate($strStockId))
    {
        DebugString(__FUNCTION__.' already updated', true);
		return false;
	}

	$strSinaSymbol = $ref->GetSinaSymbol();
	$strUrl = "http://money.finance.sina.com.cn/quotes_service/api/json_v2.php/CN_MarketData.getKLineData?symbol=$strSinaSymbol&scale=240&ma=no&datalen=".strval(MAX_QUOTES_DAYS);
   	if ($ar = StockDebugJson(DebugGetSinaFileName("{$strSinaSymbol}Chart"), $strUrl))
   	{
        $his_sql = GetStockHistorySql();
        $oldest_ymd = new OldestYMD();
        $iTotal = 0;
        $iModified = 0;
        $strLastDate = '';
		foreach ($ar as $arResult)
		{
    		$strDate = $arResult['day'];
    		if ($oldest_ymd->IsTooOld($strDate))	continue;
    		if ($strDate == $strLastDate)			continue;	// future have continue data 23 hours a day

    		$strLastDate = $strDate; 
    		// $strOpen = mysql_round($arResult['open']);
    		// $strHigh = mysql_round($arResult['high']);
    		// $strLow = mysql_round($arResult['low']);
    		$strClose = mysql_round($arResult['close']);
    		$strVolume = $arResult['volume'];
	        if ($oldest_ymd->IsInvalid($strDate) == false)
	        {
	        	$iTotal ++;
	        	if ($his_sql->WriteHistory($strStockId, $strDate, $strClose, $strVolume))
	        	{
	        		$iModified ++;
	        	}
	        }
		}
		DebugVal($iTotal, 'Total');
		DebugVal($iModified, 'Modified');
		$date_sql->WriteDate($strStockId, $strCurDate);
		unlinkConfigFile($ref->GetSymbol());
		return true;
   	}
    return false;
}
