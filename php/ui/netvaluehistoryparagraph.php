<?php
require_once('stocktable.php');

define('POSITION_EST_LEVEL', '4.0');

// (est * cny / estPrev * cnyPrev - 1) * position = (nv / nvPrev - 1) 
function QdiiGetStockPosition($fEstPrev, $fEst, $fPrev, $fNetValue, $fCnyPrev, $fCny, $strInput = POSITION_EST_LEVEL)
{
	$f = StockGetPercentage($fEstPrev, $fEst);
	if (($f !== false) && (abs($f) > floatval($strInput)))
	{
		$f = StockGetPercentage($fEstPrev * $fCnyPrev, $fEst * $fCny);
		if (($f !== false) && (abs($f) > MIN_FLOAT_VAL))
		{
			$fVal = StockGetPercentage($fPrev, $fNetValue) / $f;
			if ($fVal > 0.1)
			{
				return number_format($fVal, 2);
			}
		}
	}
	return false;
}

function GetNetValueTableColumn($est_ref, $cny_ref)
{
	$change_col = new TableColumnChange();
	$ar = array(new TableColumnDate(), new TableColumnNetValue(), $change_col);
	if ($est_ref)
	{
		$ar[] = new TableColumnStock($cny_ref);
		$ar[] = $change_col;
		$ar[] = RefGetTableColumnNetValue($est_ref);
		$ar[] = $change_col;
		$ar[] = new TableColumnPosition();
	}
	return $ar;
}

function EchoNetValueItem($csv, $ref, $cny_ref, $est_ref, $strDate, $strNetValue, $strPrevDate, $strInput = POSITION_EST_LEVEL, $bAdmin = false)
{
	$bWritten = false;
	$ar = array($strDate);
	
	$fNetValue = floatval($strNetValue);
	$ar[] = $ref->GetNetValueDisplay($fNetValue);
	
	$fPrev = $ref->GetNetValue($strPrevDate);
	$ar[] = $ref->GetPercentageDisplay($fPrev, $fNetValue);

	if ($est_ref)
	{
		$fCny = $cny_ref->GetVal($strDate);
		$ar[] = $cny_ref->GetPriceDisplay($fCny);
		if ($fCnyPrev = $cny_ref->GetVal($strPrevDate))
		{
			$ar[] = $cny_ref->GetPercentageDisplay($fCnyPrev, $fCny);
			if ($fEst = $est_ref->GetNetValue($strDate))
			{
				$ar[] = $est_ref->GetNetValueDisplay($fEst);
				if ($fEstPrev = $est_ref->GetNetValue($strPrevDate))
				{
					$ar[] = $est_ref->GetPercentageDisplay($fEstPrev, $fEst);
					if ($strPosition = QdiiGetStockPosition($fEstPrev, $fEst, $fPrev, $fNetValue, $fCnyPrev, $fCny, $strInput))
					{
						$bWritten = true;
						if ($csv)	$csv->Write($strDate, $strNetValue, $strPosition);
						if ($bAdmin)	$strPosition = GetOnClickLink('/php/_submitoperation.php?stockid='.$ref->GetStockId().'&fundposition='.$strPosition, "确认使用{$strPosition}作为估值仓位？", $strPosition);
						$ar[] = $strPosition;
					}
				}
			}
		}
	}

	if ($bWritten == false)
	{
		if ($csv)	$csv->Write($strDate, $strNetValue);
	}
	EchoTableColumn($ar);
}

function _echoNetValueData($csv, $ref, $est_ref, $cny_ref, $iStart, $iNum)
{
	$net_sql = GetNetValueHistorySql();
	$strStockId = $ref->GetStockId();
    if ($result = $net_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
        	$strDate = $record['date'];
        	EchoNetValueItem($csv, $ref, $cny_ref, $est_ref, $strDate, $record['close'], $net_sql->GetDatePrev($strStockId, $strDate));
        }
        mysqli_free_result($result);
    }
}

function EchoNetValueHistoryParagraph($ref, $csv = false, $iStart = 0, $iNum = TABLE_COMMON_DISPLAY, $bAdmin = false)
{
	if (($iTotal = $ref->CountNetValue()) == 0)	return;
	
	$strSymbol = $ref->GetSymbol();
	if (IsTableCommonDisplay($iStart, $iNum))
	{
   		$strMenuLink = '';
   		$strLink = GetNetValueHistoryLink($strSymbol);
	}
	else
	{
		$strMenuLink = StockGetMenuLink($strSymbol, $iTotal, $iStart, $iNum);
   		$strLink = GetFundLinks($strSymbol);
   		if ($bAdmin)	$strLink .= '<br />'.StockGetAllLink($strSymbol);
   		$strLink .= '<br />'.$strMenuLink;
   	}
	
   	if ($fund_ref = StockGetQdiiReference($strSymbol))
   	{
   		$cny_ref = $fund_ref->GetCnyRef();
   		$est_ref = $fund_ref->GetEstRef();
   	}
    else
    {
    	$cny_ref = false;
    	$est_ref = false;
    }
    
	EchoTableParagraphBegin(GetNetValueTableColumn($est_ref, $cny_ref), 'netvaluehistory', $strLink);
	_echoNetValueData($csv, $ref, $est_ref, $cny_ref, $iStart, $iNum);
    EchoTableParagraphEnd($strMenuLink);
}

?>
