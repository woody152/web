<?php
require_once('_fundgroup.php');
require_once('_updateinvesconetvalue.php');

function _tradingUserDefined($strVal = false)
{
	global $acct;
    
	$arLev = $acct->GetLeverageRef();
	$fund = $acct->GetRef();
	$est_ref = $fund->GetEstRef();

    if ($strVal)
    {
    	if ($strVal == '0')
    	{
    		return '';
    	}
    	else
    	{
    		$fEst = $fund->GetEstValue($strVal);
    		$strLev = '';
    		foreach ($arLev as $leverage_ref)
    		{
   				$fLev = $leverage_ref->EstFromPair($fEst);
   				$strLev .= '|'.$leverage_ref->GetPriceDisplay($fLev);
    		}
    		return $est_ref->GetPriceDisplay($fEst).$strLev;
    	}
    }

	$strLev = '';
	foreach ($arLev as $leverage_ref)
	{
		$strLev .= '|'.TableColumnGetStock($leverage_ref);
	}
   	return TableColumnGetStock($est_ref).$strLev.TableColumnGetPrice();
}

function _callbackFundListHedge($fPos, $fFactor, $strDate, $strStockId)
{
	global $acct;
    
	$ref = $acct->GetRef();
   	$fQdiiPos = $ref->GetPosition();
   	
   	$cal_sql = GetCalibrationSql();
	if ($record = $cal_sql->GetRecordNow($ref->GetStockId()))
    {
		$fQdiiCalibration = floatval($record['close']);
		$strQdiiDate = $record['date']; 
		if ($strQdiiDate != $strDate)
		{
			if ($strFactor = $cal_sql->GetCloseFrom($strStockId, $strQdiiDate))		$fFactor = floatval($strFactor);
//			else																		return '';
			// DebugString(__FUNCTION__.' Reload calibration factor because of difference date: '.$strQdiiDate.' '.$strDate);
		}
		return StockCalcLeverageHedge($fQdiiCalibration, $fQdiiPos, $fFactor, $fPos);
	}
	return 1.0;
}

class QdiiGroupAccount extends FundGroupAccount 
{
    var $ar_leverage_ref = array();
    
    function QdiiCreateGroup($arLev)
    {
    	$ref = $this->GetRef();
    	$stock_ref = $ref->GetStockRef();
       	$est_ref = $ref->GetEstRef();
       	$arRef = array($stock_ref, $est_ref);
		if ($realtime_ref = $ref->GetRealtimeRef())		$arRef[] = $realtime_ref;
    	
        if ($ar = YahooUpdateNetValue($est_ref))
        {
        	if ($est_ref->GetSymbol() == 'INDA')	$est_ref->DailyCalibration();
        }
        
        GetChinaMoney($stock_ref);
        SzseGetLofShares($stock_ref);
        
    	foreach ($arLev as $strSymbol)
    	{
    		$leverage_ref = new FundPairReference($strSymbol);
    		$this->ar_leverage_ref[] = $leverage_ref;
    		if ($strSymbol == 'QQQ')
    		{
//    			if (NeedOfficialWebData($leverage_ref))		UpdateInvescoNetValue($strSymbol);
    		}
    		else
    		{
    			YahooUpdateNetValue($leverage_ref);
    		}
    		$leverage_ref->DailyCalibration();
    	}
        $this->CreateGroup(array_merge($arRef, $this->ar_leverage_ref));
    }
    
    function GetLeverageRef()
    {
    	return $this->ar_leverage_ref;
    }
    
    function EchoCommonParagraphs()
    {
    	$ref = $this->GetRef();
    	$arLev = $this->GetLeverageRef();
    	
    	EchoFundTradingParagraph($ref, '_tradingUserDefined');    
    	EchoQdiiSmaParagraph($ref);
    	if (count($arLev) > 0)	
    	{
    		EchoFundListParagraph($arLev, '_callbackFundListHedge');
    		EchoFundPairSmaParagraphs($ref->GetEstRef(), $arLev);
    	}
    	EchoFutureSmaParagraph($ref);
    	EchoFundHistoryParagraph($ref);
    	EchoFundShareParagraph($ref);
    	EchoNetValueCloseParagraph($ref->GetEstRef());
    	foreach ($arLev as $leverage_ref)
    	{
    		EchoNetValueCloseParagraph($leverage_ref);
		}
    }

    function GetLeverageSymbols($strEstSymbol)
    {
   		$pair_sql = GetFundPairSql();
        return $pair_sql->GetSymbolArray($strEstSymbol);
    }
    
    function EchoDebugParagraph()
    {
    	if ($this->IsAdmin())
    	{
    		$ref = $this->GetRef();
    		$strDebug = $ref->DebugLink();
   			EchoHtmlElement($strDebug);
    	}
    }
} 

function GetMetaDescription()
{
    global $acct;
    
    $fund = $acct->GetRef();
    $cny_ref = $fund->GetCnyRef();
	$strBase = SqlGetStockName($cny_ref->GetSymbol());
    if ($est_ref = $fund->GetEstRef())     $strBase .= '、'.SqlGetStockName($est_ref->GetSymbol());
    
    $str = '根据'.$strBase.'等其它网站的数据来源估算'.$acct->GetStockDisplay().'净值的网页工具。';
    return CheckMetaDescription($str);
}

?>
