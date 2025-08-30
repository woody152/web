<?php
require_once('_stock.php');
require_once('_emptygroup.php');
require_once('../../php/ui/referenceparagraph.php');
require_once('../../php/ui/fundestparagraph.php');
require_once('../../php/ui/ahparagraph.php');

function RefSort($arRef)
{
	$arA = array();
    $arH = array();
    $arUS = array();

    foreach ($arRef as $ref)
    {
    	if ($ref->IsSymbolA())			$arA[] = $ref;
		else if ($ref->IsSymbolH())     $arH[] = $ref;
		else			                $arUS[] = $ref;
	}
	
	return array_merge(RefSortBySymbol($arA), RefSortBySymbol($arH), RefSortBySymbol($arUS));
}

function _echoHoldingItem($ref, $arRatio, $fNetValueChange, $arHistory, $fAdjust)
{
	static $fTotalOld = 0.0;
	static $fTotalNew = 0.0;
	static $fTotalChange = 0.0;
	
	if ($ref == false)
	{
		$ar = array(DISP_ALL_CN, strval_round($fTotalOld, 2), '', strval_round(($fNetValueChange - 1.0) * 100, 2).'%', strval_round($fTotalNew, 2), strval_round($fTotalChange * $fAdjust, 2));
	    EchoTableColumn($ar);
	    return;
	}
	
	$strStockId = $ref->GetStockId();
	$fClose = $arHistory[$strStockId];
	$strClose = strval($fClose);
	
	$strPrice = $ref->GetPrice();
	$fRatio = floatval($arRatio[$strStockId]);
//	$fChange = $ref->GetPercentage($strClose, $strPrice) / 100.0;
	$fChange = ($fClose > MIN_FLOAT_VAL) ? floatval($strPrice) / $fClose : 0.0;
	$fChange /= $fAdjust;
	
	$ar = array();
	$ar[] = RefGetMyStockLink($ref);
	
	$fTotalOld += $fRatio;
    $ar[] = strval_round($fRatio, 2);
    
    $ar[] = mysql_round($strClose, 2);
    $ar[] = $ref->GetPercentageDisplay($strClose, $strPrice);
    
    $fNewRatio = $fRatio * $fChange / $fNetValueChange;
	$fTotalNew += $fNewRatio;
    $ar[] = strval_round($fNewRatio, 2);
    
    $fRatioChange = $fRatio * ($fChange - 1.0);
	$fTotalChange += $fRatioChange;
    $ar[] = strval_round($fRatioChange, 4);
    
    $ar[] = strval_round($fAdjust, 4);
    
    RefEchoTableColumn($ref, $ar);
}

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
   		//DebugNow('拿新浪数据存文件时间点');
   		$strSymbol = $ref->GetSymbol();
   		$ref = new HoldingsReference($strSymbol);
   		//DebugNow('读数据文件时间点');
    	if ($ref->GetHoldingsDate())
    	{
    		$arHoldingRef = $ref->GetHoldingsRefArray();
    		$str = '持仓和测算示意 ';
    		$iTotal = count($arHoldingRef);
    		if ($iTotal <= 3)	$str .= GetExhaustiveHoldingsLink($strSymbol);
    		else				$str .= '总数'.strval($iTotal);
    		$str .= ' '.$ref->GetHoldingsRatioDisplay();
    		
		    EchoHoldingsEstParagraph($ref);
    		EchoReferenceParagraph(array_merge(array($ref), RefSort($arHoldingRef)), $acct->IsAdmin());
    		EchoTableParagraphBegin(array(new TableColumnSymbol(),
										   new TableColumnPercentage('旧'),
										   new TableColumnPrice('旧'),
										   new TableColumnChange('此后'),
										   new TableColumnPercentage('新'),
										   new TableColumnPercentage('影响'),
										   new TableColumn('汇率调整', 100)
										   ), 'holdings', $str);
			$arAdrhRef = array();
			$arRatio = $ref->GetHoldingsRatioArray();
			$fNetValueChange = $ref->GetNetValueChange();
			$arHistory = $ref->GetHoldingDateHistory(GetStockHistorySql());
			$fAdjustUSD = $ref->GetAdjustUSD();
			$fAdjustHKD = $ref->GetAdjustHKD();
			foreach ($arHoldingRef as $holding_ref)
			{
				_echoHoldingItem($holding_ref, $arRatio, $fNetValueChange, $arHistory, RefAdjustForex($holding_ref, $fAdjustHKD, $fAdjustUSD));
				if ($holding_ref->IsSymbolH())
				{
					if ($strAdrSymbol = SqlGetHadrPair($holding_ref->GetSymbol()))	$arAdrhRef[] = new AdrPairReference($strAdrSymbol);	
				}
			}
			_echoHoldingItem(false, $arRatio, $fNetValueChange, $arHistory, RefAdjustForex($ref, $fAdjustHKD, $fAdjustUSD));
			EchoTableParagraphEnd();
			
			EchoAdrhParagraph($arAdrhRef, LayoutUseWide());
		}
    }
    $acct->EchoLinks();
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

