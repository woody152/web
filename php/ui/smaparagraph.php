<?php
//require_once('stocktable.php');
require_once('calibrationhistoryparagraph.php');

function _getSmaRow($strKey)
{
    $arRow = array('D' => '日', 'W' => '周', 'M' => '月', 'BOLLUP' => '布林上轨', 'BOLLDN' => '布林下轨', 'EMA50' => '小牛熊分界', 'EMA200' => '牛熊分界');
    $strFirst = substr($strKey, 0, 1);
    if ($strFirst == 'E')		return $arRow[$strKey];

   	$strRest = substr($strKey, 1, strlen($strKey) - 1);
    if (substr($strKey, 1, 1) == 'B')
    {
    	return ($strFirst == 'D') ? $arRow[$strRest] : $arRow[$strFirst].$arRow[$strRest];
    }
    return $strRest.$arRow[$strFirst];
}

function _getSmaCallbackPriceDisplay($callback, $ref, $strVal)
{
	if ($strVal)
	{
		$display_ref = call_user_func($callback, $ref);
		return $display_ref->GetPriceDisplay(strval(call_user_func($callback, $ref, $strVal)));
	}
	return '';
}

function _echoSmaTableItem($his, $strKey, $strVal, $cb_ref, $callback, $callback2, $strColor, $bAfterHour)
{
    $stock_ref = $his->GetRef();

    $ar = array();
    $ar[] = _getSmaRow($strKey);
    $ar[] = $stock_ref->GetPriceDisplay($strVal);
    $ar[] = $stock_ref->GetPercentageDisplay($strVal);
   	if ($strNext = $his->arNext[$strKey])
   	{
   		$ar[] = $stock_ref->GetPriceDisplay($strNext);
   		$ar[] = $stock_ref->GetPercentageDisplay($strNext);
   	}
   	else
   	{
   		$ar[] = '';
   		$ar[] = '';
   	}
   	if ($bAfterHour)
   	{
   		if ($strAfterHour = $his->arAfterHour[$strKey])	$ar[] = $stock_ref->GetPriceDisplay($strAfterHour);
   		else									   				$ar[] = '';
   	}
   	
    if ($callback)
    {
    	$ar[] = _getSmaCallbackPriceDisplay($callback, $cb_ref, $strVal);
    	$ar[] = _getSmaCallbackPriceDisplay($callback, $cb_ref, $strNext);
    	if ($bAfterHour)	$ar[] = _getSmaCallbackPriceDisplay($callback, $cb_ref, $strAfterHour);
    }
    
    if ($callback2)	$ar[] = call_user_func($callback2, $strVal, $strNext);
    
    EchoTableColumn($ar, $strColor);
}

function _getSmaParagraphMemo($his)
{
	$sym = $his->GetRef();
	$strSymbol = $sym->GetSymbol();
	$bAdmin = DebugIsAdmin();
	$str = $bAdmin ? GetStockChartsLink($strSymbol) : GetYahooStockLink($sym);
	$str .= ' '.$his->GetStartDate().'数据';
	if ($strBullBear = $his->GetBullBear())		$str .= ' '.GetBoldElement($strBullBear);
    $str .= ' '.GetStockHistoryLink($strSymbol);
	if ($bAdmin)	$str .= ' '.GetUpdateStockHistoryLink($sym, STOCK_HISTORY_UPDATE);
    return $str;
}

function _getSmaParagraphWarning($ref)
{
	if (RefHasData($ref) && ($ref->IsSinaFuture() == false))
	{
		$his_sql = GetStockHistorySql();
		if ($record = $his_sql->GetRecordPrev($ref->GetStockId(), $ref->GetDate()))
		{
			$fDiff = floatval($record['adjclose']) - floatval($ref->GetPrevPrice()); 
			if (abs($fDiff) > 0.0005)
			{
				$strSymbol = $ref->GetSymbol();
				$str = '<br />'.GetFontElement($strSymbol.' '.$record['date'].'收盘价冲突：').$record['adjclose'].' - '.$ref->GetPrevPrice().' = '.strval_round($fDiff, 6);
				if (DebugIsAdmin())
				{
					$str .= ' '.GetStockOptionLink(STOCK_OPTION_CLOSE, $strSymbol);
				}
				return $str;
			}
		}
	}
	return '';
}

function EchoSmaParagraph($ref, $str = false, $cb_ref = false, $callback = false, $callback2 = false)
{
   	$bAfterHour = LayoutUseWide();
	$his = new StockHistory($ref, $bAfterHour);
	if ($bAfterHour)	$bAfterHour = $his->NeedAfterHourEst();
	
	if ($str === false)	$str = _getSmaParagraphMemo($his);
	$str .= _getSmaParagraphWarning($ref);

	$premium_col = new TableColumnPremium();
	$next_col = new TableColumnEst('T+1');
	$afterhour_col = new TableColumnEst('盘后');
	$ar = array(new TableColumn('均线', 90), new TableColumnEst(), $premium_col, $next_col, $premium_col);
	if ($bAfterHour)	$ar[] = $afterhour_col;
	if ($callback)
    {
    	$est_ref = call_user_func($callback, $cb_ref);
    	$str .= _getSmaParagraphWarning($est_ref);

//    	$ar[] = new TableColumnEst(TableColumnGetStock($est_ref));
    	$ar[] = new TableColumnStock($est_ref, 90);
    	$ar[] = $next_col;
    	if ($bAfterHour)	$ar[] = $afterhour_col;
    }
    if ($callback2)	$ar[] = new TableColumn(call_user_func($callback2), 90);

	EchoTableParagraphBegin($ar, 'smatable', $str);
    foreach ($his->GetSMA() as $strKey => $strVal)
    {
        _echoSmaTableItem($his, $strKey, $strVal, $cb_ref, $callback, $callback2, $his->GetColor($strKey), $bAfterHour);
    }
    $str = DebugIsAdmin() ? implode(', ', $his->GetOrderArray()) : '';
    EchoTableParagraphEnd($str);
}

function _callbackQdiiSma($qdii_ref, $strEst = false)
{
	return $strEst ? $qdii_ref->GetQdiiValue($strEst) : $qdii_ref->GetStockRef();
}

function EchoQdiiSmaParagraph($qdii_ref, $callback2 = false)
{
    EchoSmaParagraph($qdii_ref->GetEstRef(), false, $qdii_ref, '_callbackQdiiSma', $callback2);
}

function _callbackFundPairSma($ref, $strEst = false)
{
	return $strEst ? $ref->EstFromPair($strEst) : $ref;
}

function EchoFundPairSmaParagraphs($ref, $arFundPairRef, $callback2 = false)
{
	foreach ($arFundPairRef as $fund_pair_ref)
	{
		EchoSmaParagraph($ref, '', $fund_pair_ref, '_callbackFundPairSma', $callback2);
	}
}

function EchoFundPairSmaParagraph($ref, $str = false, $callback2 = false)
{
	EchoSmaParagraph($ref->GetPairRef(), $str, $ref, '_callbackFundPairSma', $callback2);
}

function _callbackAhPairSma($ref, $strEst = false)
{
	return $strEst ? $ref->EstToPair($strEst) : $ref->GetPairRef();
}

function EchoAhPairSmaParagraph($ref, $str = false, $callback2 = false)
{
	EchoSmaParagraph($ref, $str, $ref, '_callbackAhPairSma', $callback2);
}

function GetFutureInterestPremium($fRate = 0.0500625, $strEndDate = '2025-03-21')
{
	$end_ymd = new StringYMD($strEndDate);
	date_default_timezone_set('America/New_York');
	$now_ymd = GetNowYMD();
	$begin_ymd = new StringYMD($now_ymd->GetYMD());
	$iDay = ($end_ymd->GetTick() - $begin_ymd->GetTick()) / SECONDS_IN_DAY;
	return 1.0 + $fRate * $iDay / 365.0;
}

function RefGetFuturePremium($ref)
{
	$strStockId = $ref->GetStockId();
	$premium_sql = new FuturePremiumSql();
	if ($strClose = $premium_sql->GetCloseNow($strStockId))
	{
		return GetFutureInterestPremium(floatval($strClose) / 100.0, $premium_sql->GetDateNow($strStockId));
	}
	return false;
}

function _callbackFutureSma($ref, $strEst = false)
{
	if ($strEst)
	{
		$f = floatval($strEst) * RefGetFuturePremium($ref);
		$f = round(4.0 * $f) / 4.0;
//		DebugVal($f, __FUNCTION__, true);
		return strval(round($f, 2));
	}
	return $ref;
}

function EchoFutureSmaParagraph($ref, $callback2 = false)
{
	if ($realtime_ref = $ref->GetRealtimeRef())
	{
		if ($fPremium = RefGetFuturePremium($realtime_ref))
		{
			EchoCalibrationHistoryParagraph($ref->GetEstRef(), 0, 1);
			$str = '理论溢价：'.strval_round($fPremium, 4).' '.GetStockOptionLink(STOCK_OPTION_PREMIUM, $realtime_ref->GetSymbol());
			EchoSmaParagraph($ref->GetEstRef(), $str, $realtime_ref, '_callbackFutureSma', $callback2);
		}
	}
}

?>
