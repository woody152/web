<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');

function _echoExhaustiveHoldingsItem($ref, $arNew, $arOrg, $strDate, $strNetValue, $strPrevDate, $strPrevNetValue, $fLimit, $bAdmin)
{
	static $strOldDate = false;
	
	$ar = array();
	$bMatch = true;
	$iCount = 0;
	foreach ($arNew as $iPos)
	{
		if ($iPos != $arOrg[$iCount])	$bMatch = false;
		$iCount ++;
		$ar[] = strval($iPos);
	}
	$ref->SetHoldingsRatioArray($ar);
	$ref->SetHoldingsDate($strPrevDate);
	$ref->SetNetValue($strPrevNetValue);
	
	$fEst = $ref->_estNetValue($strDate);
	$strEst = strval($fEst);

	if (abs($ref->GetPercentage($strNetValue, $strEst)) < $fLimit)
	{
		if ($strPrevDate != $strOldDate)
		{
			$ar[] = $strPrevDate;
			$ar[] = $strPrevNetValue;
			$strOldDate = $strPrevDate;
		}
		else
		{
			$ar[] = '';
			$ar[] = '';
		}
	
		$ar[] = strval_round($fEst, 3);
		$bHit = ($ref->GetPercentageString($strNetValue, $strEst) == '0') ? true : false;
		if ($bAdmin && ($bMatch == false) && $bHit)
		{
			$strHoldings = $ref->GetHoldingsDisplay();
			$ar[] = GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&holdings='.$strHoldings, '确认更新持仓：'.$strHoldings.'？', '0');
		}	
		else	$ar[] = $ref->GetPercentageDisplay($strNetValue, $strEst);
		EchoTableColumn($ar, $bMatch ? 'yellow' : false);
		return $bHit;
	}
	return false;
}

function __getPrevHoldingsDate($ref, $strStockId, $strDate, $net_sql)
{
	while ($strPrevDate = $net_sql->GetDatePrev($strStockId, $strDate))
	{
		if ($ref->CheckHoldingsDate($strPrevDate))	break;
		else										$strDate = $strPrevDate;
	}
	return $strPrevDate;
}

function _echoExhaustiveHoldingsData($ref, $strDate, $strNetValue, $iCount, $fLimit, $bAdmin)
{
	$strDebug = '';
	$iHit = 0;
	$arHit = array();
	
	$iTotal = 0;
	$arOrg = array();
	foreach ($ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
	{
		$iRatio = intval($strRatio);
		$iTotal += $iRatio;
		$arOrg[] = $iRatio;
	}
	
	$strStockId = $ref->GetStockId();
	$net_sql = GetNetValueHistorySql();
	$strPrevDate = __getPrevHoldingsDate($ref, $strStockId, $strDate, $net_sql);
	$strPrevNetValue = $net_sql->GetClose($strStockId, $strPrevDate);
	
	if ($iCount == 2)
	{
		for ($i = 1; $i <= $iTotal - 1; $i ++)
    	{
    		$arNew = array($i, $iTotal - $i);
    		if (_echoExhaustiveHoldingsItem($ref, $arNew, $arOrg, $strDate, $strNetValue, $strPrevDate, $strPrevNetValue, $fLimit, $bAdmin))
    		{
    			$iHit ++;
    			$arHit['H'.strval($iHit)] = $arNew;
    		}
    	}
    }
    else
    {
    	for ($i = 1; $i <= $iTotal - 2; $i ++) 
    	{
    		for ($j = 1; $j <= $iTotal - 1 - $i; $j ++)
    		{
    			$arNew = array($i, $j, $iTotal - $i - $j);
    			if (_echoExhaustiveHoldingsItem($ref, $arNew, $arOrg, $strDate, $strNetValue, $strPrevDate, $strPrevNetValue, $fLimit, $bAdmin))
    			{
    				$iHit ++;
    				$arHit['H'.strval($iHit)] = $arNew;
    			}
    		}
    	}
   	}
	$strDebug .= $strPrevDate.'拟合0误差数量'.strval($iHit);
			
	$iCount = 1;
	while ($iHit > 0)
	{
		$strDate = $strPrevDate;
		$strNetValue = $strPrevNetValue;
		
		$strPrevDate = __getPrevHoldingsDate($ref, $strStockId, $strDate, $net_sql);
		$strPrevNetValue = $net_sql->GetClose($strStockId, $strPrevDate);
		foreach ($arHit as $strHit => $arNew)
		{
			if (_echoExhaustiveHoldingsItem($ref, $arNew, $arOrg, $strDate, $strNetValue, $strPrevDate, $strPrevNetValue, $fLimit, $bAdmin) == false)	unset($arHit[$strHit]);
		}
		$iHit = count($arHit);
		$strDebug .= '，'.$strPrevDate.'剩余'.strval($iHit);
		$iCount ++;
	}
	$strDebug .= '，共'.strval($iCount).'天。';
	return $strDebug;
}

function _echoExhaustiveHoldingsParagraph($strSymbol, $fLimit, $bAdmin)
{
	$ref = new HoldingsReference($strSymbol);
    if ($strDate = $ref->GetHoldingsDate())
    {
    	$arHoldingRef = $ref->GetHoldingsRefArray();
    	$iCount = count($arHoldingRef);
    	if ($iCount <= 3)
    	{
    		$strNetValue = $ref->GetNetValue();
			$str = GetXueqiuLink($ref).' '.$strDate.'最新公布净值'.$strNetValue;
			$ar = array();
			foreach ($arHoldingRef as $holding_ref)	$ar[] = new TableColumnStock($holding_ref);
			$ar[] = new TableColumnDate();
			$ar[] = new TableColumnNetValue();
			$ar[] = new TableColumnEst();
			$ar[] = new TableColumnError();
    		EchoTableParagraphBegin($ar, 'exhaustiveholdings', $str);
    		$strDebug = _echoExhaustiveHoldingsData($ref, $strDate, $strNetValue, $iCount, $fLimit, $bAdmin);
			EchoTableParagraphEnd($strDebug);
		}
	}
}

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
    	if (($strInput = GetEditInput()) === false)		$strInput = '0.1';
    	EchoEditInputForm('显示估值差异的阈值', $strInput);
    	if ($strInput != '')	_echoExhaustiveHoldingsParagraph($ref->GetSymbol(), floatval($strInput), $acct->IsAdmin());
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().EXHAUSTIVE_HOLDINGS_DISPLAY;
    $str .= '。仅用于只有2到3个持仓用来估值的美股QDII基金，使用穷举法来计算这些持仓最可能的实际比例。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetSymbolDisplay().EXHAUSTIVE_HOLDINGS_DISPLAY;
}

    $acct = new SymbolAccount();

require('../../php/ui/_dispcn.php');
?>
