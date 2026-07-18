<?php
require_once('yahoostock.php');

/* https://query1.finance.yahoo.com/v7/finance/chart/AAPL?range=5d&interval=1d&indicators=quote&includeTimestamps=true
Array
(
    [chart] => Array
        (
            [result] => Array
                (
                    [0] => Array
                        (
                            [meta] => Array
                                (
                                    [currency] => USD
                                    [symbol] => AAPL
                                    [exchangeName] => NMS
                                    [fullExchangeName] => NasdaqGS
                                    [instrumentType] => EQUITY
                                    [firstTradeDate] => 345479400
                                    [regularMarketTime] => 1727467204
                                    [hasPrePostMarketData] => 1
                                    [gmtoffset] => -14400
                                    [timezone] => EDT
                                    [exchangeTimezoneName] => America/New_York
                                    [regularMarketPrice] => 227.79
                                    [fiftyTwoWeekHigh] => 229.52
                                    [fiftyTwoWeekLow] => 227.3
                                    [regularMarketDayHigh] => 229.52
                                    [regularMarketDayLow] => 227.3
                                    [regularMarketVolume] => 33706549
                                    [longName] => Apple Inc.
                                    [shortName] => Apple Inc.
                                    [chartPreviousClose] => 228.2
                                    [priceHint] => 2
                                    [currentTradingPeriod] => Array
                                        (
                                            [pre] => Array
                                                (
                                                    [timezone] => EDT
                                                    [start] => 1727424000
                                                    [end] => 1727443800
                                                    [gmtoffset] => -14400
                                                )
                                            [regular] => Array
                                                (
                                                    [timezone] => EDT
                                                    [start] => 1727443800
                                                    [end] => 1727467200
                                                    [gmtoffset] => -14400
                                                )
                                            [post] => Array
                                                (
                                                    [timezone] => EDT
                                                    [start] => 1727467200
                                                    [end] => 1727481600
                                                    [gmtoffset] => -14400
                                                )
                                        )
                                    [dataGranularity] => 1d
                                    [range] => 5d
                                    [validRanges] => Array
                                        (
                                            [0] => 1d
                                            [1] => 5d
                                            [2] => 1mo
                                            [3] => 3mo
                                            [4] => 6mo
                                            [5] => 1y
                                            [6] => 2y
                                            [7] => 5y
                                            [8] => 10y
                                            [9] => ytd
                                            [10] => max
                                        )
                                )
                            [timestamp] => Array
                                (
                                    [0] => 1727098200
                                    [1] => 1727184600
                                    [2] => 1727271000
                                    [3] => 1727357400
                                    [4] => 1727443800
                                )
                            [indicators] => Array
                                (
                                    [quote] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [low] => Array
                                                        (
                                                            [0] => 225.80999755859
                                                            [1] => 225.72999572754
                                                            [2] => 224.02000427246
                                                            [3] => 225.41000366211
                                                            [4] => 227.30000305176
                                                        )
                                                    [volume] => Array
                                                        (
                                                            [0] => 54146000
                                                            [1] => 43556100
                                                            [2] => 42308700
                                                            [3] => 36636700
                                                            [4] => 33993600
                                                        )
                                                    [open] => Array
                                                        (
                                                            [0] => 227.33999633789
                                                            [1] => 228.64999389648
                                                            [2] => 224.92999267578
                                                            [3] => 227.30000305176
                                                            [4] => 228.46000671387
                                                        )
                                                    [high] => Array
                                                        (
                                                            [0] => 229.44999694824
                                                            [1] => 229.35000610352
                                                            [2] => 227.28999328613
                                                            [3] => 228.5
                                                            [4] => 229.52000427246
                                                        )
                                                    [close] => Array
                                                        (
                                                            [0] => 226.4700012207
                                                            [1] => 227.36999511719
                                                            [2] => 226.36999511719
                                                            [3] => 227.52000427246
                                                            [4] => 227.78999328613
                                                        )
                                                )
                                        )
                                    [adjclose] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [adjclose] => Array
                                                        (
                                                            [0] => 226.4700012207
                                                            [1] => 227.36999511719
                                                            [2] => 226.36999511719
                                                            [3] => 227.52000427246
                                                            [4] => 227.78999328613
                                                        )
                                                )
                                        )
                                )
                        )
                )
            [error] => 
        )
)*/

function UpdateYahooHistoryChart($ref)
{
	$strStockId = $ref->GetStockId();
	$strCurDate = $ref->GetDate();
   	$date_sql = new StockHistoryDateSql();
   	if ($strCurDate == $date_sql->ReadDate($strStockId))
    {
        DebugString(__FUNCTION__.' already updated', true);
		return false;
	}
    
	$ref->SetTimeZone();
	$strYahooSymbol = $ref->GetYahooSymbol();
   	if ($arResult = GetYahooChartData($strYahooSymbol, DebugGetYahooFileName("{$strYahooSymbol}Chart")))
   	{
   		$arTimeStamp = $arResult['timestamp'];
   		$arIndicators = $arResult['indicators'];
		$arLow = $arIndicators['quote'][0]['low'];
		$arVolume = $arIndicators['quote'][0]['volume'];
		$arOpen = $arIndicators['quote'][0]['open'];
		$arHigh = $arIndicators['quote'][0]['high'];
		$arClose = $arIndicators['quote'][0]['close'];
		$arAdjClose = $arIndicators['adjclose'][0]['adjclose'];

        $his_sql = GetStockHistorySql();
        $oldest_ymd = new OldestYMD();
        $iTotal = 0;
        $iModified = 0;
        $strLastDate = '';
		for ($i = 0; $i < count($arTimeStamp); $i ++)
		{
    		$ymd = new TickYMD(intval($arTimeStamp[$i]));
    		$strDate = $ymd->GetYMD();
    		if ($oldest_ymd->IsTooOld($strDate))	continue;
    		if ($strDate == $strLastDate)			continue;	// future have continue data 23 hours a day
    		$strLastDate = $strDate; 
    		
    		$strOpen = mysql_round($arOpen[$i]);
    		$strHigh = mysql_round($arHigh[$i]);
    		$strLow = mysql_round($arLow[$i]);
    		$strClose = $arClose[$i];
    		$strVolume = $arVolume[$i];
    		$strAdjClose = mysql_round($arAdjClose[$i]);

	        if ($strClose == '-' || $strClose == 'null' || IsZeroString($strClose))
	        {
	        	DebugString("Empty data: $strDate $strOpen $strHigh $strLow $strClose $strVolume $strAdjClose");		// debug wrong data
	        	continue;
	        }
	        $strClose = mysql_round($strClose);
	        
	        if (IsZeroString($strVolume))
	        {
        		DebugString("Zero volume, holiday? $strDate $strOpen $strHigh $strLow $strClose $strVolume $strAdjClose");
        		continue;
	        }
	        if ($oldest_ymd->IsInvalid($strDate) == false)
	        {
	        	$iTotal ++;
	        	if ($his_sql->WriteHistory($strStockId, $strDate, $strClose, $strVolume, $strAdjClose))
	        	{
	        		$iModified ++;
	        	}
	        }
		}
		
		DebugVal($iTotal, 'Total');
		DebugVal($iModified, 'Modified');
        // Yahoo has wrong Chinese and Hongkong holiday record with '0' volume 
		$his_sql->DeleteByZeroVolume($strStockId);
		$date_sql->WriteDate($strStockId, $strCurDate);
		unlinkConfigFile($ref->GetSymbol());
		return true;
   	}
    return false;
}
