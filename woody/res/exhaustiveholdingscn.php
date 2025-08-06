<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');

function _echoExhaustiveHoldingsItem($ref, $i, $iTotal, $iOrg, $strStockId1, $strStockId2, $strDate, $strNav, $fLimit, $bAdmin)
{
	$iFirst = $iTotal - $i;
	$bMatch = ($iFirst == $iOrg) ? true : false;
	$str1 = strval($iFirst);
	$ref->arHoldingsRatio[$strStockId1] = $str1;
	
	$str2 = strval($i);
	$ref->arHoldingsRatio[$strStockId2] = $str2;
	
	$fEst = $ref->_estNetValue($strDate);
	$strEst = strval($fEst);
	if ($bAdmin && ($bMatch == false) && $ref->GetPercentageString($strNav, $strEst) == '0')
	{
		$strHoldings = SqlGetStockSymbol($strStockId1).'*'.$str1.';'.SqlGetStockSymbol($strStockId2).'*'.$str2;
		$str1 = GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&date='.$strDate.'&holdings='.$strHoldings, '确认更新持仓：'.$strDate.' '.$strHoldings.'？', $str1);
	}

	if (abs($ref->GetPercentage($strNav, $strEst)) < $fLimit)
	{
		$ar = array($str1, $str2);
		$ar[] = strval_round($fEst, 3);
		$ar[] = $ref->GetPercentageDisplay($strNav, $strEst);
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
    		$strNav = $ref->strNav;
    		$str = $strDate.' '.$strNav;
    		$netvalue_sql = GetNetValueHistorySql();
    		if ($record = $netvalue_sql->GetRecordPrev($ref->GetStockId(), $strDate))
    		{
    			$ref->strHoldingsDate = $record['date'];
    			$ref->strNav = rtrim0($record['close']);
    			$str .= ' <== '.$ref->strHoldingsDate.' '.$ref->strNav;
    		
    			$ref1 = $arHoldingRef[0];
    			$ref2 = $arHoldingRef[1];
    			$strStockId1 = $ref1->GetStockId();
    			$strStockId2 = $ref2->GetStockId();
    			$arRatio = $ref->GetHoldingsRatioArray();
    			$fRatio1 = floatval($arRatio[$strStockId1]);
    			$iTotal = intval(round($fRatio1 + floatval($arRatio[$strStockId2])));
    			$iOrg = intval(round($fRatio1));

    			EchoTableParagraphBegin(array(new TableColumnStock($ref1),
										   new TableColumnStock($ref2),
										   new TableColumnEst(),
										   new TableColumnError()
										   ), 'exhaustiveholdings', $str);
				for ($i = 1; $i < $iTotal; $i ++)	_echoExhaustiveHoldingsItem($ref, $i, $iTotal, $iOrg, $strStockId1, $strStockId2, $strDate, $strNav, $fLimit, $bAdmin);
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
