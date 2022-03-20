<?php
require_once('_stock.php');
require_once('_emptygroup.php');
require_once('/php/ui/referenceparagraph.php');
require_once('/php/ui/fundestparagraph.php');

function RefSortBySymbol($arRef)
{
    $ar = array();
    foreach ($arRef as $ref)
    {
        $strSymbol = $ref->GetSymbol();
		if (isset($ar[$strSymbol]) == false)		 $ar[$strSymbol] = $ref; 
    }
    ksort($ar);
    
    $arSort = array();
    foreach ($ar as $str => $ref)
    {
        $arSort[] = $ref;
    }
    return $arSort;
}

function RefSort($arRef)
{
	$arA = array();
    $arH = array();
    $arUS = array();

    foreach ($arRef as $ref)
    {
    	if ($ref->IsSymbolA())			$arA[] = $ref;
		else if ($ref->IsSymbolH())      	$arH[] = $ref;
		else			                	$arUS[] = $ref;
	}
	
	return array_merge(RefSortBySymbol($arA), RefSortBySymbol($arH), RefSortBySymbol($arUS));
}

function _echoHoldingItem($ref, $arRatio, $strDate, $his_sql, $fTotalChange, $fAdjustH)
{
	$bHk = $ref->IsSymbolH() ? true : false;
	
	$strStockId = $ref->GetStockId();
	$strClose = $his_sql->GetAdjClose($strStockId, $strDate);
	$strPrice = $ref->GetPrice();
	$fRatio = floatval($arRatio[$strStockId]);
	$fChange = $ref->GetPercentage($strClose, $strPrice) / 100.0;
    if ($bHk)	$fChange *= $fAdjustH;
	
	$ar = array();
	$ar[] = RefGetMyStockLink($ref);
    $ar[] = strval($fRatio);
    $ar[] = $strClose;
    $ar[] = $ref->GetPercentageDisplay($strClose, $strPrice);
    $ar[] = strval_round($fRatio * (1 + $fChange) / $fTotalChange, 2);
    $ar[] = strval_round($fRatio * $fChange, 4);
    if ($bHk)	$ar[] = strval_round($fAdjustH, 4);
    
    RefEchoTableColumn($ref, $ar);
}

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
   		$strSymbol = $ref->GetSymbol();
   		$ref = new HoldingsReference($strSymbol);
    	if ($strDate = $ref->GetHoldingsDate())
    	{
    		$arHoldingRef = $ref->GetHoldingRefArray();
		    EchoHoldingsEstParagraph($ref);
    		EchoReferenceParagraph(array_merge(array($ref), RefSort($arHoldingRef)), $acct->IsAdmin());
    		EchoTableParagraphBegin(array(new TableColumnSymbol(),
										   new TableColumnPercentage('旧'),
										   new TableColumnPrice('旧'),
										   new TableColumnChange('此后'),
										   new TableColumnPercentage('新'),
										   new TableColumnPercentage('影响'),
										   new TableColumn('H股汇率调整', 100)
										   ), 'holdings', '持仓和测算示意');
	
			$arRatio = $ref->GetHoldingsRatioArray();
			$his_sql = GetStockHistorySql();
			foreach ($arHoldingRef as $holding_ref)
			{
				_echoHoldingItem($holding_ref, $arRatio, $strDate, $his_sql, $ref->GetNavChange(), $ref->GetAdjustHkd());
			}
			EchoTableParagraphEnd();
		}
    }
    $acct->EchoLinks('holdings');
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().HOLDINGS_DISPLAY;
    $str .= '页面. 用于显示ETF基金的成分股持仓情况, 以及各个成分股最新的价格. 基于成分股价格测算基金的官方估值和实时估值.';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetSymbolDisplay().HOLDINGS_DISPLAY;
}

    $acct = new SymbolAccount();
?>

