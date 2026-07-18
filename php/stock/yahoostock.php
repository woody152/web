<?php

/*
https://query1.finance.yahoo.com/v7/finance/chart/%5EXOP-IV?range=1d&interval=1d
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
                                    [symbol] => ^XOP-IV
                                    [exchangeName] => ASE
                                    [fullExchangeName] => NYSE American
                                    [instrumentType] => INDEX
                                    [firstTradeDate] => 
                                    [regularMarketTime] => 1743193785
                                    [hasPrePostMarketData] => 
                                    [gmtoffset] => -14400
                                    [timezone] => EDT
                                    [exchangeTimezoneName] => America/New_York
                                    [regularMarketPrice] => 130.646
                                    [fiftyTwoWeekHigh] => 132.013
                                    [fiftyTwoWeekLow] => 129.683
                                    [regularMarketDayHigh] => 132.013
                                    [regularMarketDayLow] => 129.683
                                    [regularMarketVolume] => 0
                                    [longName] => SPDR S&P Oil  and  Gas Explorat
                                    [shortName] => SPDR S&P Oil  and  Gas Explorat
                                    [chartPreviousClose] => 131.811
                                    [priceHint] => 2
                                    [currentTradingPeriod] => Array
                                        (
                                            [pre] => Array
                                                (
                                                    [timezone] => EDT
                                                    [end] => 1743168600
                                                    [start] => 1743148800
                                                    [gmtoffset] => -14400
                                                )
                                            [regular] => Array
                                                (
                                                    [timezone] => EDT
                                                    [end] => 1743192000
                                                    [start] => 1743168600
                                                    [gmtoffset] => -14400
                                                )
                                            [post] => Array
                                                (
                                                    [timezone] => EDT
                                                    [end] => 1743206400
                                                    [start] => 1743192000
                                                    [gmtoffset] => -14400
                                                )
                                        )
                                    [dataGranularity] => 1d
                                    [range] => 5d
                                    [validRanges] => Array
                                        (
                                            [0] => 1d
                                            [1] => 5d
                                        )
                                )
                            [timestamp] => Array
                                (
                                    [0] => 1743193785
                                )
                            [indicators] => Array
                                (
                                    [quote] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [volume] => Array
                                                        (
                                                            [0] => 0
                                                        )
                                                    [close] => Array
                                                        (
                                                            [0] => 130.64610290527
                                                        )
                                                    [open] => Array
                                                        (
                                                            [0] => 131.8072052002
                                                        )
                                                    [high] => Array
                                                        (
                                                            [0] => 132.01319885254
                                                        )
                                                    [low] => Array
                                                        (
                                                            [0] => 129.68310546875
                                                        )
                                                )
                                        )
                                    [adjclose] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [adjclose] => Array
                                                        (
                                                            [0] => 130.64610290527
                                                        )
                                                )
                                        )
                                )
                        )
                )
            [error] => 
        )
)
*/

// https://query1.finance.yahoo.com/v7/finance/chart/AAPL?range=2y&interval=1d&indicators=quote&includeTimestamps=true
function GetYahooChartData($strYahooSymbol, $strFileName, $strRange = '2y')
{
	$strUrl = GetYahooDataUrl('7')."/chart/$strYahooSymbol?range=$strRange&interval=1d&indicators=quote&includeTimestamps=true";
   	if ($ar = StockDebugJson($strFileName, $strUrl))
   	{
		if (!isset($ar['chart']))			
		{
			DebugString('no chart');
			return false;
		}
		
		$arChart = $ar['chart'];
		if (!isset($arChart['result']))
		{
			DebugString('no chart result');
			return false;
		}

		$arResult = $arChart['result'][0];
		if (!isset($arResult['timestamp']))
		{
			DebugString('no chart result 0 timestamp');
			return false;
		}
		
   		$arIndicators = $arResult['indicators'];
		if (!isset($arIndicators['quote']))
		{
			DebugString('no chart result 0 indicators quote');
			return false;
		}
		if (!isset($arIndicators['adjclose']))
		{
			DebugString('no chart result 0 indicators adjclose');
			return false;
		}
		return $arResult;
	}
	return false;
}

function _yahooStockGetData($strSymbol, $strStockId)
{ 
   	if ($arResult = GetYahooChartData($strSymbol, DebugGetYahooFileName($strSymbol), '1d'))
   	{
   		$arTimeStamp = $arResult['timestamp'];
   		$arIndicators = $arResult['indicators'];
		$arAdjClose = $arIndicators['adjclose'][0]['adjclose'];

		$net_sql = GetNetValueHistorySql();
		for ($i = 0; $i < count($arTimeStamp); $i ++)
		{
    		$ymd = new TickYMD(intval($arTimeStamp[$i]));
    		$strDate = $ymd->GetYMD();
    		$strNetValue = mysql_round($arAdjClose[$i]);
    		if ($net_sql->WriteDaily($strStockId, $strDate, $strNetValue))
    		{
    			DebugString(__FUNCTION__.' Update net value for '.$strSymbol.' '.$strDate.' '.$strNetValue);
    			return [$strNetValue, $strDate];
    		}
		}
   	}
    return false;
}

// force update, no any condition checking as in YahooUpdateNetValue
function YahooGetNetValue($ref)
{
	// date_default_timezone_set('America/New_York');
	$ref->SetTimeZone();
	$strSymbol = $ref->GetSymbol();
	return _yahooStockGetData(($ref->IsIndex() ? $strSymbol : BuildYahooNetValueSymbol($strSymbol)), $ref->GetStockId());
}

function _yahooGetNetValueSymbol($sym, $strSymbol)
{
    if ($sym->IsSinaFuture())
    {
    	return false;
    }
    else if ($sym->IsSymbolA() || $sym->IsSymbolH())
    {
    	return false;
    }
    else if ($sym->IsIndex() || $sym->IsSinaGlobalIndex())
    {
		// return $strSymbol;
    	return false;
   	}
   	else
   	{
   		switch ($strSymbol)
   		{
   		case 'IBB':
   		case 'KSTR':
   		case 'QQQ':
   		case 'USO':
   			return false;
   		}
   	}
   	return BuildYahooNetValueSymbol($strSymbol);
}

function YahooUpdateNetValue($ref)
{
	if ($ref->HasData() == false)	return;
	
	$strSymbol = $ref->GetSymbol();
	if (($strNetValueSymbol = _yahooGetNetValueSymbol($ref, $strSymbol)) === false)		return;
	
	// date_default_timezone_set('America/New_York');
	$ref->SetTimeZone();
	$net_sql = GetNetValueHistorySql();
	$strStockId = $ref->GetStockId();
	$strDate = $ref->GetDate();
    if ($net_sql->GetRecord($strStockId, $strDate))	return;	// already have today's data
	
    $now_ymd = GetNowYMD();
    $iHourMinute = $now_ymd->GetHourMinute();
   	if ($now_ymd->GetYMD() == $strDate)
   	{
   		if ($iHourMinute < 1655)
   		{
			// DebugString($strSymbol.': Market not closed');
   			return;
   		}
    }
	/* else
    {
   		if ($iHourMinute > 900)
   		{
   			DebugString($strSymbol.': a trading day has begun');
   			return;
   		}
    }
	*/
	return _yahooStockGetData($strNetValueSymbol, $strStockId);    
}
