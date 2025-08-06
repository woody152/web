<?php

function SinaFundNeedFile($sym, $strFileName)
{
//	$sym->SetTimeZone();
    $now_ymd = GetNowYMD();
	if (file_exists($strFileName))
	{
		if ($strDigit = $sym->IsSinaFund())
		{
			if ($strSymbol = BuildChinaFundSymbol($strDigit))		$sym = new StockSymbol($strSymbol);
			else
			{
				DebugString(__FUNCTION__.' unknown symbol:'.$sym->GetSymbol());
				return false;
			}
		}
		else	$strSymbol = $sym->GetSymbol();

		$strStockId = SqlGetStockId($strSymbol);
		$his_sql = GetStockHistorySql();
		$strDate = $his_sql->GetDateNow($strStockId);
		$strNavDate = UseSameDayNetValue($sym) ? $strDate : $his_sql->GetDatePrev($strStockId, $strDate);
		if (SqlGetNetValueByDate($strStockId, $strNavDate))		return false;

    	if (($now_ymd->GetYMD() == $strDate) && $now_ymd->GetHourMinute() < 1600)	return false;		// Market not closed
    }

	return $now_ymd->NeedFile($strFileName, 30 * SECONDS_IN_MIN);
}

function _checkBetweenMarketClose($now_ymd, $iFileTime, $iWeekday, $iWeekend)
{
	$iDiff = $now_ymd->GetTick() - $iFileTime;
	if ($now_ymd->IsSunday())
	{
		if ($iDiff > ($iWeekday + $iWeekend + 24) * SECONDS_IN_HOUR)							return true;
	}
	else if ($now_ymd->IsSaturday())
	{
		if ($iDiff > ($iWeekday + $iWeekend) * SECONDS_IN_HOUR)								return true;
	}
	else
	{
		if ($iDiff > $iWeekday * SECONDS_IN_HOUR)												return true;
	}

	return false;
}

function StockNeedNewQuotes($sym, $strFileName, $iInterval = SECONDS_IN_MIN)
{
    $now_ymd = GetNowYMD();
	if (($iFileTime = $now_ymd->NeedFile($strFileName, $iInterval)) == false)		return false;	// update on every minute
	
	if ($sym->IsStockMarketTrading($now_ymd))											return true;
	$file_ymd = new TickYMD($iFileTime);
	if ($sym->IsStockMarketTrading($file_ymd))											return true;
	
	if ($now_ymd->GetDay() == $file_ymd->GetDay())
	{
		if ($sym->IsBeforeStockMarket($now_ymd->GetHourMinute()))						return false;
		if ($sym->IsAfterStockMarket($file_ymd->GetHourMinute()))						return false;
	}

//	if ($sym->IsSinaGlobalIndex())
	if ($sym->IsSymbolA())		return _checkBetweenMarketClose($now_ymd, $iFileTime, 2, 8);
	else if ($sym->IsSymbolH())	return _checkBetweenMarketClose($now_ymd, $iFileTime, 3, 8);
	return _checkBetweenMarketClose($now_ymd, $iFileTime, 16, 4);
}

function _isFutureMarketTrading($ymd)
{
   	$iHour = $ymd->GetHour();
   	if ($ymd->IsSunday())
   	{
   		if ($iHour < 18)		return false;
   	}
   	else if ($ymd->IsSaturday())
   	{
   		return false;
   	}
   	else if ($ymd->IsFriday())
   	{
   		if ($iHour >= 17)		return false;
   	}
   	else
   	{
   		if ($iHour == 17)		return false;
   	}
   	return true;
}

function FutureNeedNewFile($strFileName, $iInterval = SECONDS_IN_MIN)
{
	$now_ymd = GetNowYMD();
	if (($iFileTime = $now_ymd->NeedFile($strFileName, $iInterval)) == false)		return false;	// update on every minute
	
	if (_isFutureMarketTrading($now_ymd))    												return true;
    if (_isFutureMarketTrading(new TickYMD($iFileTime)))								return true;
    return _checkBetweenMarketClose($now_ymd, $iFileTime, 23, 7);
}

function PrefetchSaveSinaData($strFileName, $strData)
{
//	DebugString(__FUNCTION__.$strFileName.' '.$strData, true);
	file_put_contents($strFileName, $strData);
}

function PrefetchLoadSinaData($strFileName)
{
    clearstatcache();
	if (file_exists($strFileName))	return file_get_contents($strFileName);
	return false;
}

function _prefetchSinaData($arSym)
{
    $arFileName = array();
    $arSymbol = array();
    
    foreach ($arSym as $str => $sym)
    {
        $strFileName = DebugGetSinaFileName($str);
        $sym->SetTimeZone();
		if ($sym->IsSinaFund())
        {   // fund, IsSinaFund must be called before IsSinaFuture
			if (SinaFundNeedFile($sym, $strFileName) == false)		continue;
        }
        else if ($sym->IsSinaFuture() || $sym->IsSinaForex())
        {   // forex and future
            if (FutureNeedNewFile($strFileName) == false)    continue;
        }
        else
        {   // Stock symbol
            if (StockNeedNewQuotes($sym, $strFileName) == false)  continue;
        }
        $arFileName[] = $strFileName; 
		$arSymbol[] = $str;
    }
    if (($iCount = count($arFileName)) < 1)    return;
//	DebugPrint($arSymbol, __FUNCTION__, true);
//	DebugString(implode(',', $arSymbol), true);
    
    if (($str = GetSinaQuotes($arSymbol)) == false)   return;
    $arLine = explode("\n", $str);
//    if (count($arLine) == $iCount)
//    {
    	for ($i = 0; $i < $iCount; $i ++)
    	{
    		PrefetchSaveSinaData($arFileName[$i], $arLine[$i]);
    	}
//    }
}

function PrefetchSinaStockData($arSymbol)
{
//    DebugPrint($arSymbol, __FUNCTION__, true);
    
    $arUnknown = array();
    $arPrefetch = array();
    foreach ($arSymbol as $strSymbol)
    {
        if ($strSymbol)
        {
            $sym = new StockSymbol($strSymbol);
            if ($sym->IsSinaFund() || $sym->IsSinaForex())
            {  	// IsSinaFund must be called before IsSinaFuture
                $arPrefetch[$strSymbol] = $sym;
            }
            else if ($sym->IsSinaFuture())
            {
                $arPrefetch[$strSymbol] = $sym;
            }
            else if ($sym->IsEastMoneyForex())
            {	// Only WX call may into this, nothing need to be done. Do not put it in $arUnknown neither!
            }	
            else if ($strSinaSymbol = $sym->GetSinaSymbol())
            {
                $arPrefetch[$strSinaSymbol] = $sym;
                if ($sym->IsFundA())
                {
                	$strSinaFundSymbol = $sym->GetSinaFundSymbol();
                	$arPrefetch[$strSinaFundSymbol] = new StockSymbol($strSinaFundSymbol);
                }
            }
            else
            {
                $arUnknown[] = $strSymbol;
            }
        }
    }
//    ksort($arPrefetch);
//    DebugPrint($arPrefetch, __FUNCTION__, true);
    _prefetchSinaData($arPrefetch);
    return $arUnknown;
}

?>
