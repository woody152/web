<?php
require_once('stocktable.php');

define('POSITION_EST_LEVEL', '4.0');

function _CheckPositionEstLevel($fPrev, $fNetValue, $strInput = POSITION_EST_LEVEL)
{
	$f = StockGetPercentage($fPrev, $fNetValue);
	return (($f !== false) && (abs($f) > floatval($strInput))) ? true : false;
}

// (est * cny / estPrev * cnyPrev - 1) * position = (nv / nvPrev - 1) 
function _QdiiGetStockPosition($fEstPrev, $fEst, $fPrev, $fNetValue, $fCnyPrev, $fCny)
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
	return false;
}

function _QdiiMixCalcEstValue($arRatio, $strDate)
{
	$fEst = 0.0;
	foreach ($arRatio as $strHoldingId => $strRatio)
	{
		if ($str = SqlGetHistoryByDate($strHoldingId, $strDate))
		{
			$fEst += floatval($str) * floatval($strRatio);
		}
		else
		{
//			DebugString(__FUNCTION__.' missing '.SqlGetStockSymbol($strHoldingId).' data on '.$strDate);
		 	return false;
		}
	}
	return $fEst;
}

function _QdiiMixGetPosition($ref, $strDate, $strPrevDate, $fPrev, $fNetValue, $fCnyPrev, $fCny)
{
	$arRatio = $ref->GetHoldingsRatioArray();
	// DebugPrint($arRatio);
	$fEst = _QdiiMixCalcEstValue($arRatio, $strDate);
	$fEstPrev = _QdiiMixCalcEstValue($arRatio, $strPrevDate);
	if ($fEst !== false && $fEstPrev !== false)		return  _QdiiGetStockPosition($fEstPrev, $fEst, $fPrev, $fNetValue, $fCnyPrev, $fCny);
	return false;
}

function GetNetValueTableColumn($est_ref, $cny_ref)
{
	$change_col = new TableColumnChange();
	$ar = array(new TableColumnDate(), new TableColumnNetValue(), $change_col);
	if ($cny_ref)
	{
		$ar[] = new TableColumnStock($cny_ref);
		$ar[] = $change_col;
		if ($est_ref)
		{
			$ar[] = RefGetTableColumnNetValue($est_ref);
			$ar[] = $change_col;
		}
		$ar[] = new TableColumnPosition();
	}
	return $ar;
}

function _adjustAdminPositionDisplay($ref, $strPosition)
{
	return GetOnClickLink('/php/_submitoperation.php?stockid='.$ref->GetStockId().'&fundposition='.$strPosition, "确认使用{$strPosition}作为估值仓位？", $strPosition);
}

function EchoNetValueItem($csv, $ref, $cny_ref, $est_ref, $strDate, $strNetValue, $strPrevDate, $strInput = POSITION_EST_LEVEL, $bAdmin = false)
{
	$bWritten = false;
	$ar = array($strDate);
	
	$fNetValue = floatval($strNetValue);
	$ar[] = $ref->GetNetValueDisplay($fNetValue);
	
	$fPrev = $ref->GetNetValue($strPrevDate);
	$ar[] = $ref->GetPercentageDisplay($fPrev, $fNetValue);

	if ($cny_ref)
	{
		$fCny = $cny_ref->GetVal($strDate);
		$ar[] = $cny_ref->GetPriceDisplay($fCny);
		$fCnyPrev = $cny_ref->GetVal($strPrevDate);
		$ar[] = $cny_ref->GetPercentageDisplay($fCnyPrev, $fCny);
	}

	if ($est_ref)
	{
		if ($fEst = $est_ref->GetNetValue($strDate))
		{
			$ar[] = $est_ref->GetNetValueDisplay($fEst);
			if ($fEstPrev = $est_ref->GetNetValue($strPrevDate))
			{
				$ar[] = $est_ref->GetPercentageDisplay($fEstPrev, $fEst);
				if (_CheckPositionEstLevel($fPrev, $fNetValue, $strInput))
				{
					if ($strPosition = _QdiiGetStockPosition($fEstPrev, $fEst, $fPrev, $fNetValue, $fCnyPrev, $fCny))
					{
						$bWritten = true;
						if ($csv)	$csv->Write($strDate, $strNetValue, $strPosition);
						if ($bAdmin)	$strPosition = _adjustAdminPositionDisplay($ref, $strPosition);
						$ar[] = $strPosition;
					}
				}
			}
		}
	}
	else if ($cny_ref)
	{
		if (_CheckPositionEstLevel($fPrev, $fNetValue, $strInput))
		{
			if ($strPosition = _QdiiMixGetPosition($ref, $strDate, $strPrevDate, $fPrev, $fNetValue, $fCnyPrev, $fCny))
			{
				$bWritten = true;
				if ($csv)	$csv->Write($strDate, $strNetValue, $strPosition);
				if ($bAdmin)	$strPosition = _adjustAdminPositionDisplay($ref, $strPosition);
				$ar[] = $strPosition;
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
	else if (in_array($strSymbol, GetQdiiGoldOilSymbolArray()))
	{
		$ref = new HoldingsReference($strSymbol);
		$cny_ref = $ref->GetCnyRef();
		$est_ref = false;				
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
