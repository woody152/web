<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');

function _echoExhaustiveHoldingsItem($ref, $i, $iTotal, $arOrg, $strDate, $strNetValue, $fLimit, $bAdmin)
{
	$iFirst = $iTotal - $i;
	$bMatch = (($iFirst == $arOrg[0]) && ($i == $arOrg[1])) ? true : false;
	$str1 = strval($iFirst);
	$str2 = strval($i);
	$ar = array($str1, $str2);
	$ref->SetHoldingsRatioArray($ar);
	
	$fEst = $ref->_estNetValue($strDate);
	$strEst = strval($fEst);
	if ($bAdmin && ($bMatch == false) && $ref->GetPercentageString($strNetValue, $strEst) == '0')
	{
		$strHoldings = SqlGetStockSymbol($strStockId1).'*'.$str1.';'.SqlGetStockSymbol($strStockId2).'*'.$str2;
		$ar[0] = GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&date='.$strDate.'&holdings='.$strHoldings, '确认更新持仓：'.$strDate.' '.$strHoldings.'？', $str1);
	}

	if (abs($ref->GetPercentage($strNetValue, $strEst)) < $fLimit)
	{
		$ar[] = strval_round($fEst, 3);
		$ar[] = $ref->GetPercentageDisplay($strNetValue, $strEst);
		EchoTableColumn($ar, $bMatch ? 'yellow' : false);
	}
}

function _echoExhaustiveHoldingsData($strSymbol, $fLimit, $bAdmin)
{
	$ref = new HoldingsReference($strSymbol);
    if ($strDate = $ref->GetHoldingsDate())
    {
    	$arHoldingRef = $ref->GetHoldingRefArray();
    	if (count($arHoldingRef) == 2)
    	{
    		$netvalue_sql = GetNetValueHistorySql();
    		if ($record = $netvalue_sql->GetRecordPrev($ref->GetStockId(), $strDate))
    		{
    			$strPrevDate = $record['date'];
    			$ref->SetHoldingsDate($strPrevDate);
    			
    			$strNetValue = $ref->GetNetValue();
    			$strPrevNetValue = rtrim0($record['close']);
    			$ref->SetNetValue($strPrevNetValue);
    			
    			$str = $strPrevDate.' '.$strPrevNetValue.' ==> '.$strDate.' '.$strNetValue;
    			$iTotal = 0;
    			$arOrg = array();
    			foreach ($ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
    			{
    				$iRatio = intval($strRatio);
    				$iTotal += $iRatio;
    				$arOrg[] = $iRatio;
    			}
    			$ar = array();
    			foreach ($arHoldingRef as $holding_ref)	$ar[] = new TableColumnStock($holding_ref);
				$ar[] = new TableColumnEst();
				$ar[] = new TableColumnError();
    			EchoTableParagraphBegin($ar, 'exhaustiveholdings', $str);
				for ($i = 1; $i < $iTotal; $i ++)	_echoExhaustiveHoldingsItem($ref, $i, $iTotal, $arOrg, $strDate, $strNetValue, $fLimit, $bAdmin);
				EchoTableParagraphEnd();
			}
		}
	}
}

function EchoAll()
{
	global $acct;
	
	$bAdmin = $acct->IsAdmin();
    if ($ref = $acct->EchoStockGroup())
    {
    	if (($strInput = GetEditInput()) === false)		$strInput = '0.1';
    	EchoEditInputForm('显示估值差异的阈值', $strInput);
    	if ($strInput != '')	_echoExhaustiveHoldingsData($ref->GetSymbol(), floatval($strInput), $acct->IsAdmin());
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().EXHAUSTIVE_HOLDINGS_DISPLAY;
    $str .= '。仅用于只有2个持仓用来估值的美股QDII基金，使用穷举法来计算2个持仓最可能的实际比例。';
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
