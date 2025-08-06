<?php
require_once('_stock.php');
require_once('_emptygroup.php');
require_once('../../php/ui/referenceparagraph.php');
require_once('../../php/ui/fundestparagraph.php');

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

function _echoHoldingItem($ref, $arRatio, $strDate, $his_sql, $fNetValueChange, $fAdjustCny, $fAdjustHkd)
{
	static $fTotalOld = 0.0;
	static $fTotalNew = 0.0;
	static $fTotalChange = 0.0;
	
	if ($ref == false)
	{
		$ar = array(DISP_ALL_CN, strval_round($fTotalOld, 2), '', strval_round(($fNetValueChange - 1.0) * 100, 2).'%', strval_round($fTotalNew, 2), strval_round($fTotalChange, 2));
	    EchoTableColumn($ar);
	    return;
	}
	
	if ($ref->IsSymbolA())		$fAdjust = $fAdjustCny;
	else if ($ref->IsSymbolH())	$fAdjust = $fAdjustHkd;
	else							$fAdjust = false;
	
	$strStockId = $ref->GetStockId();
	$strClose = $his_sql->GetAdjClose($strStockId, $strDate);
	$strPrice = $ref->GetPrice();
	$fRatio = floatval($arRatio[$strStockId]);
//	$fChange = $ref->GetPercentage($strClose, $strPrice) / 100.0;
	$fClose = floatval($strClose);
	$fChange = ($fClose > MIN_FLOAT_VAL) ? floatval($strPrice) / $fClose : 0.0;
    if ($fAdjust)		$fChange *= $fAdjust;
	
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
    
    if ($fAdjust)		$ar[] = strval_round($fAdjust, 4);
    
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
    		$str = '持仓和测算示意';
    		if (count($arHoldingRef) == 2)	$str .= ' '.GetExhaustiveHoldingsLink($strSymbol);
    		
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
	
			$his_sql = GetStockHistorySql();
			$arRatio = $ref->GetHoldingsRatioArray();
			$fNetValueChange = $ref->GetNetValueChange();
			$fAdjustCny = $ref->GetAdjustCny();
			$fAdjustHkd = $ref->GetAdjustHkd();
			foreach ($arHoldingRef as $holding_ref)
			{
				_echoHoldingItem($holding_ref, $arRatio, $strDate, $his_sql, $fNetValueChange, $fAdjustCny, $fAdjustHkd);
			}
			_echoHoldingItem(false, $arRatio, $strDate, $his_sql, $fNetValueChange, $fAdjustCny, $fAdjustHkd);
			EchoTableParagraphEnd();
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

