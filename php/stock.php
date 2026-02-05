<?php
require_once('regexp.php');
require_once('externallink.php');
require_once('sql.php');
require_once('gb2312.php');

require_once('sql/sqlipaddress.php');
require_once('sql/sqlstock.php');

require_once('stock/stocksymbol.php');
require_once('stock/yahoostock.php');
require_once('stock/stockprefetch.php');
require_once('stock/stockref.php');

require_once('stock/mysqlref.php');
require_once('stock/mystockref.php');
require_once('stock/cnyref.php');
require_once('stock/netvalueref.php');
require_once('stock/holdingsref.php');
require_once('stock/fundref.php');
require_once('stock/qdiiref.php');

require_once('stock/fundpairref.php');

function StockGetSymbol($str)
{
	$str = trim($str);
	if ($strSymbol = BuildChinaFundSymbol($str))		return $strSymbol;
	if ($strSymbol = BuildChinaStockSymbol($str))	return $strSymbol;
	if (strpos($str, '_') === false)	$str = strtoupper($str);
    return $str;
}

function GetInputSymbolArray($strSymbols)
{
	$strSymbols = str_replace(array(',', '，', '、', "\\n", "\\r", "\\r\\n"), ' ', $strSymbols);
    $arSymbol = array();
    foreach (explode(' ', $strSymbols) as $str)
    {
    	if (!empty($str))		$arSymbol[] = StockGetSymbol($str);
    }
    return $arSymbol;
}

function RemoveDoubleQuotationMarks($str)
{
    $str = strstr($str, '"');
    $str = ltrim($str, '"');
    $strLeft = strstr($str, '"', true);     // works with no ending "
    if ($strLeft)   return $strLeft;
    return $str;
}

function explodeQuote($str)
{
    return explode(',', RemoveDoubleQuotationMarks($str));
}

function StockNeedFile($strFileName, $iInterval = SECONDS_IN_MIN)
{
	$now_ymd = GetNowYMD();
	return $now_ymd->NeedFile($strFileName, $iInterval);
}

function GetSinaQuotes($arSymbol)
{
	$strSinaSymbols = implode(',', $arSymbol);
	$strFileName = DebugGetPathName('debugsina.txt');
	$iCount = count($arSymbol);
	if (DebugIsAdmin() && $iCount > 1)
	{
//		DebugVal($iCount, 'total prefetch - '.$strSinaSymbols);
	}
	else
	{
		if (StockNeedFile($strFileName) == false)
		{	// pause 1 minute after curl error response
//			DebugString('Ignored: '.$strSinaSymbols, true);
			return false;
		}
	}
    
    if ($str = url_get_contents(GetSinaDataUrl($strSinaSymbols), UrlGetRefererHeader(GetSinaFinanceUrl()), $strFileName))
    {
    	if ($iCount >= count(explode('=', $str)))		DebugVal($iCount, __FUNCTION__.' failed: '.$str);		// Sina returns error in an empty file
    	else											return $str;
    }
    return false;
}

function StockGetPriceDisplay($fDisp, $fPrev, $iPrecision)
{
    if ($fDisp)
    {
    	$iDiff = 0.5;
    	$iCur = $iPrecision;
    	while ($iCur > 0)
    	{
    		$iDiff /= 10.0;
    		$iCur --;
    	}
    	
        if ($fDisp > $fPrev + $iDiff)         $strColor = 'red';
        else if ($fDisp < $fPrev - $iDiff)    $strColor = 'green';
        else                                  $strColor = 'black';
        return GetFontElement(number_format($fDisp, $iPrecision, '.', ''), $strColor);
    }
    return '';
}

function GetNumberDisplay($fVal, $iPrecision = 2)
{
    return StockGetPriceDisplay($fVal, 0.0, $iPrecision);
}

function GetRatioDisplay($fVal, $iPrecision = 4)
{
    return StockGetPriceDisplay($fVal, 1.0, $iPrecision);
}

function StockGetPercentage($fDivisor, $fDividend)
{
	if (abs($fDivisor) > MIN_FLOAT_VAL)		return ($fDividend/$fDivisor - 1.0) * 100.0;
	return false;
}

function StockCompareEstResult($strStockId, $strNetValue, $strDate, $strSymbol)
{
	$net_sql = GetNetValueHistorySql();
    if ($net_sql->InsertDaily($strStockId, $strDate, $strNetValue))
    {
    	$fund_est_sql = GetFundEstSql();
       	if ($strEstValue = $fund_est_sql->GetClose($strStockId, $strDate))
       	{
       		$fPercentage = StockGetPercentage(floatval($strNetValue), floatval($strEstValue));
       		if (($fPercentage !== false) && (abs($fPercentage) > 1.0))
       		{
       			$strLink = GetNetValueHistoryLink($strSymbol);
       			$str = sprintf('%s%s 实际值%s 估值%s 误差:%.2f%%', $strSymbol, $strLink, $strNetValue, $strEstValue, $fPercentage); 
       			trigger_error('Net value estimation error '.$str);
       		}
       	}
    	return true;
    }
    return false;
}

function StockUpdateEstResult($strStockId, $fNetValue, $strDate)
{
	$net_sql = GetNetValueHistorySql();
	if ($net_sql->GetRecord($strStockId, $strDate) == false)
    {   // Only update when net value is NOT ready
    	$fund_est_sql = GetFundEstSql();
		$fund_est_sql->WriteDaily($strStockId, $strDate, strval($fNetValue));
	}
}

function StockPrefetchArrayData($arSymbol)
{
    PrefetchSinaStockData(array_unique($arSymbol));
}

function _addFundPairSymbol(&$ar, $strSymbol)
{
	$ar[] = $strSymbol;
	if ($strPairSymbol = SqlGetFundPair($strSymbol))	$ar[] = $strPairSymbol;
}

function _addHoldingsSymbol(&$ar, $strSymbol)
{
	if (SqlCountHoldings($strSymbol) > 0)
	{
		$sql = GetStockSql();
		$holdings_sql = GetHoldingsSql();
    	foreach ($holdings_sql->GetHoldingsArray($sql->GetId($strSymbol)) as $strId => $strRatio)
    	{
    		$strHoldingSymbol = $sql->GetStockSymbol($strId);
    		_addFundPairSymbol($ar, $strHoldingSymbol);
    		
    		$holding_sym = new StockSymbol($strHoldingSymbol);
    		if ($holding_sym->IsSymbolA())	{}
    		if ($holding_sym->IsSymbolH())	$ar[] = 'fx_shkdcny';
    		else							$ar[] = 'fx_susdcny';
    	}
    }
}

function _getAllSymbolArray($strSymbol)
{
   	$ar = array($strSymbol);
   	$sym = new StockSymbol($strSymbol);
    if ($sym->IsFundA())
    {
        if (in_arrayQdiiMix($strSymbol))
        {
        	_addHoldingsSymbol($ar, $strSymbol);
        }
        else if (in_arrayQdii($strSymbol))
        {
        	if ($strEstSymbol = QdiiGetEstSymbol($strSymbol))		
        	{
        		_addFundPairSymbol($ar, $strEstSymbol);
        		_addHoldingsSymbol($ar, $strEstSymbol);		// KWEB
        	}
        	$ar[] = 'fx_susdcny';
        }
        else if (in_arrayQdiiHk($strSymbol))
        {
        	if ($strEstSymbol = QdiiHkGetEstSymbol($strSymbol))		_addFundPairSymbol($ar, $strEstSymbol);
        	$ar[] = 'fx_shkdcny';
        }
        else if (in_arrayQdiiJp($strSymbol))
        {
        	if ($strEstSymbol = QdiiJpGetEstSymbol($strSymbol))		_addFundPairSymbol($ar, $strEstSymbol); 
        	$ar[] = 'fx_sjpycny';
        }
        else if (in_arrayQdiiEu($strSymbol))
        {
        	if ($strEstSymbol = QdiiEuGetEstSymbol($strSymbol))		_addFundPairSymbol($ar, $strEstSymbol); 
        	$ar[] = 'fx_seurcny';
        }
        else
        {
        	if ($strPairSymbol = SqlGetFundPair($strSymbol))		$ar[] = $strPairSymbol;
        }
    }
	else if ($sym->IsSymbolA())
    {
        if ($strSymbolB = SqlGetAbPair($strSymbol))		$ar[] = $strSymbolB;
        else if ($strSymbolA = SqlGetBaPair($strSymbol))
        {
        	$ar[] = $strSymbolA;
        	$strSymbol = $strSymbolA;
        }
    		
        if ($strSymbolH = SqlGetAhPair($strSymbol))	
        {
          	$ar[] = $strSymbolH;
            if ($strSymbolAdr = SqlGetHadrPair($strSymbolH))	$ar[] = $strSymbolAdr;
        }
    }
    else if ($sym->IsSymbolH())
    {
        if ($strSymbolA = SqlGetHaPair($strSymbol))
        {
        	$ar[] = $strSymbolA;
        	if ($strSymbolB = SqlGetAbPair($strSymbolA))		$ar[] = $strSymbolB;
        }
        if ($strSymbolAdr = SqlGetHadrPair($strSymbol))		$ar[] = $strSymbolAdr;
    }
    else
    {
       	_addHoldingsSymbol($ar, $strSymbol);
    	if ($strSymbolH = SqlGetAdrhPair($strSymbol))
        {
           	$ar[] = $strSymbolH;
            if ($strSymbolA = SqlGetHaPair($strSymbolH))
            {
            	$ar[] = $strSymbolA;
            	if ($strSymbolB = SqlGetAbPair($strSymbolA))		$ar[] = $strSymbolB;
            }
        }
        
       	if ($strPairSymbol = SqlGetFundPair($strSymbol))
       	{
       		$ar[] = $strPairSymbol;
         	if ($strSymbol == 'ASHR' || $strSymbol == 'hf_CHA50CFD')	$ar[] = 'fx_susdcnh';
      	}
    }
//   	DebugPrint($ar, __FUNCTION__, true);
    return $ar;
}

function StockPrefetchArrayExtendedData($ar)
{
    $arAll = array();
    
	$sql = GetStockSql();
    foreach ($ar as $strSymbol)
    {
   		if ($sql->GetId($strSymbol))		$arAll = array_merge($arAll, _getAllSymbolArray($strSymbol));
   		else								$arAll[] = $strSymbol;	// new stock symbol	
    }
    StockPrefetchArrayData($arAll);
}

function StockPrefetchExtendedData()
{
    StockPrefetchArrayExtendedData(func_get_args());
}

function StockGetReference($strSymbol)
{
	$sym = new StockSymbol($strSymbol);

/*    if ($sym->IsSinaFund())				return new FundReference($strSymbol);
	else*/ if ($sym->IsEastMoneyForex())	return new CnyReference($strSymbol);
    										return new MyStockReference($strSymbol);
}

function StockGetQdiiReference($strSymbol)
{
    if (in_arrayQdii($strSymbol))					return new QdiiReference($strSymbol);
    else if (in_arrayQdiiHk($strSymbol))			return new QdiiHkReference($strSymbol);
    else if (in_arrayQdiiJp($strSymbol))			return new QdiiJpReference($strSymbol);
    else if (in_arrayQdiiEu($strSymbol))			return new QdiiEuReference($strSymbol);
    return false;
}

function StockGetFundReference($strSymbol)
{
	if ($ref = StockGetQdiiReference($strSymbol))									return $ref;
	else if (in_arrayQdiiMix($strSymbol))											return new HoldingsReference($strSymbol);
	else if (in_arrayChinaIndex($strSymbol) || in_arrayChinaFuture($strSymbol))		return new FundPairReference($strSymbol);
	return new FundReference($strSymbol);
}

function GetStockRef($fund_ref)
{
	return method_exists($fund_ref, 'GetStockRef') ? $fund_ref->GetStockRef() : $fund_ref;
}

function _getAbPairReference($strSymbol)
{
	$pair_sql = GetAbPairSql();
	if ($pair_sql->GetPairSymbol($strSymbol))						return new AbPairReference($strSymbol);
	else if ($strSymbolA = $pair_sql->GetSymbol($strSymbol))		return new AbPairReference($strSymbolA);
	return false;
}

function _getAdrPairReference($strSymbol)
{
	$pair_sql = GetAdrPairSql();
	if ($pair_sql->GetPairSymbol($strSymbol))						return new AdrPairReference($strSymbol);
	else if ($strAdr = $pair_sql->GetSymbol($strSymbol))			return new AdrPairReference($strAdr);
	return false;
}

function _getAhPairReference($strSymbol)
{
	$pair_sql = GetAhPairSql();
	if ($pair_sql->GetPairSymbol($strSymbol))						return new AhPairReference($strSymbol);
	else if ($strSymbolA = $pair_sql->GetSymbol($strSymbol))		return new AhPairReference($strSymbolA);
	return false;
}

function StockGetPairReferences($strSymbol)
{
    $ab_ref = false;
    $ah_ref = false;
    $adr_ref = false;
    
	if ($ab_ref = _getAbPairReference($strSymbol))
    {
    	if ($ah_ref = _getAhPairReference($ab_ref->GetSymbol()))
    	{
    		$h_ref = $ah_ref->GetPairRef();
    		$adr_ref = _getAdrPairReference($h_ref->GetSymbol());
    	}
    }
	else if ($ah_ref = _getAhPairReference($strSymbol))
    {
    	$h_ref = $ah_ref->GetPairRef();
    	$adr_ref = _getAdrPairReference($h_ref->GetSymbol());
    	$ab_ref = _getAbPairReference($ah_ref->GetSymbol());
    }
    else if ($adr_ref = _getAdrPairReference($strSymbol))
    {
    	$h_ref = $adr_ref->GetPairRef();
    	if ($ah_ref = _getAhPairReference($h_ref->GetSymbol()))		$ab_ref = _getAbPairReference($ah_ref->GetSymbol());
    }
    
    return array($ab_ref, $ah_ref, $adr_ref);
}

function UseSameDayNetValue($sym)
{
	$strSymbol = $sym->GetSymbol();
	if (in_arrayQdii($strSymbol))			return false;
	else if (in_arrayQdiiMix($strSymbol))	return	in_arrayHkMix($strSymbol);
	return true;
}

function StockCalcHedge($fCalibration, $fPos)
{
	return $fCalibration / $fPos;
}
	
function StockCalcLeverageHedge($fCalibration, $fPos, $fEtfCalibration, $fEtfPos)
{
	return StockCalcHedge($fCalibration, $fPos) / StockCalcHedge($fEtfCalibration, $fEtfPos);
}

function GetLeverageHedgeSymbol($strSymbol)
{
	if (in_arraySpyQdii($strSymbol))	return 'SPY';
	if (in_arrayQqqQdii($strSymbol))	return 'QQQ';
    return false;
}

function GetStockHedge($strSymbol, $strStockId)
{
	$pos_sql = GetPositionSql();
	if ($fPos = $pos_sql->ReadVal($strStockId))
   	{
   		$cal_sql = GetCalibrationSql();
		if ($record = $cal_sql->GetRecordNow($strStockId))
    	{
			$fCal = floatval($record['close']);
			if ($strLev = GetLeverageHedgeSymbol($strSymbol))
			{
				$strLevId = SqlGetStockId($strLev);
				return StockCalcLeverageHedge($fCal, $fPos, floatval($cal_sql->GetCloseFrom($strLevId, $record['date'])), $pos_sql->ReadVal($strLevId));
			}
   			return StockCalcHedge($fCal, $fPos);
   		}
   	}
   	return 1.0;
}

?>
