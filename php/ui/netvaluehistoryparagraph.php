<?php
require_once('stocktable.php');

// (est * cny / estPrev * cnyPrev - 1) * position = (nv / nvPrev - 1) 
function _QdiiGetStockPosition($fEstPrev, $fEst, $fPrev, $fNetValue, $fCnyPrev, $fCny, $fInput)
{
	$fPercent = StockGetPercentage($fPrev, $fNetValue);
//	DebugVal($fPercent, __FUNCTION__, true);
	if (abs($fPercent) > $fInput)
	{
		$fEstPercent = StockGetPercentage($fEstPrev * $fCnyPrev, $fEst * $fCny);
		if (abs($fEstPercent) > MIN_FLOAT_VAL)
		{
			$fVal = $fPercent / $fEstPercent;
			if ($fVal > MIN_FLOAT_VAL)
			{
				return number_format($fVal, 2);
			}
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

function _QdiiMixGetPosition($ref, $strDate, $strPrevDate, $fPrev, $fNetValue, $fCnyPrev, $fCny, $fInput)
{
	$arRatio = $ref->GetHoldingsRatioArray();
	// DebugPrint($arRatio);
	$fEst = _QdiiMixCalcEstValue($arRatio, $strDate);
	$fEstPrev = _QdiiMixCalcEstValue($arRatio, $strPrevDate);
	if ($fEst !== false && $fEstPrev !== false)		return  _QdiiGetStockPosition($fEstPrev, $fEst, $fPrev, $fNetValue, $fCnyPrev, $fCny, $fInput);
	return false;
}

function GetNetValueTableColumn($est_ref = false, $cny_ref = false)
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
	return GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&fundposition='.$strPosition, "确认使用{$strPosition}作为".STOCK_DISP_EST.STOCK_DISP_POSITION.'？', $strPosition);
}

function EchoNetValueItem($csv, $ref, $cny_ref, $est_ref, $strDate, $strNetValue, $strPrevDate, $fInput = POS_NETVALUE_DIFF, $bAdmin = false)
{
	$bWritten = false;
	$bMatch = false;
	$ar = array($strDate);
	
	$fNetValue = floatval($strNetValue);
	$ar[] = $ref->GetNetValueDisplay($fNetValue);
	
	$fPrev = $ref->GetNetValue($strPrevDate);
	$ar[] = $ref->GetPercentageDisplay($fPrev, $fNetValue);

	if ($cny_ref)
	{
		if ($fCny = $cny_ref->GetVal($strDate))				$ar[] = $cny_ref->GetPriceDisplay($fCny);
		else												$ar[] = '';
		if ($fCnyPrev = $cny_ref->GetVal($strPrevDate))		$ar[] = $cny_ref->GetPercentageDisplay($fCnyPrev, $fCny);
		else												$ar[] = '';
	}

	$strOrgPos = number_format($ref->GetPosition(), 2);
	if ($est_ref)
	{
		if ($fEst = $est_ref->GetNetValue($strDate))
		{
			$ar[] = $est_ref->GetNetValueDisplay($fEst);
			if ($fEstPrev = $est_ref->GetNetValue($strPrevDate))
			{
				$ar[] = $est_ref->GetPercentageDisplay($fEstPrev, $fEst);
				if ($strPosition = _QdiiGetStockPosition($fEstPrev, $fEst, $fPrev, $fNetValue, $fCnyPrev, $fCny, $fInput))
				{
					$bWritten = true;
					if ($csv)	$csv->Write($strDate, $strNetValue, $strPosition);
					if ($strPosition == $strOrgPos)		$bMatch = true;
					if ($bAdmin && $bMatch === false)	$strPosition = _adjustAdminPositionDisplay($ref, $strPosition);
					$ar[] = $strPosition;
				}
			}
		}
	}
	else if ($cny_ref)
	{
		if ($strPosition = _QdiiMixGetPosition($ref, $strDate, $strPrevDate, $fPrev, $fNetValue, $fCnyPrev, $fCny, $fInput))
		{
			$bWritten = true;
			if ($csv)	$csv->Write($strDate, $strNetValue, $strPosition);
			if ($strPosition == $strOrgPos)		$bMatch = true;
			if ($bAdmin && $bMatch === false)	$strPosition = _adjustAdminPositionDisplay($ref, $strPosition);
			$ar[] = $strPosition;
		}
	}

	EchoMatchTableColumn($ar, $bMatch);
	return $bWritten;
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
			$strNetValue = $record['close'];
        	$bWritten = EchoNetValueItem($csv, $ref, $cny_ref, $est_ref, $strDate, $strNetValue, $net_sql->GetDatePrev($strStockId, $strDate));
			if ($bWritten === false)
			{
				if ($csv)	$csv->Write($strDate, $strNetValue);
			}
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
		$strNewLine = GetHtmlNewLine();
   		if ($bAdmin)	$strLink .= $strNewLine.StockGetAllLink($strSymbol);
   		$strLink .= $strNewLine.$strMenuLink;
   	}
	
   	if ($fund_ref = StockGetQdiiReference($strSymbol))
   	{
   		$cny_ref = $fund_ref->GetCnyRef();
   		$est_ref = $fund_ref->GetEstRef();
   	}
	else if (in_arrayQdiiGoldOil($strSymbol))
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
    
	if (EchoTableParagraphBegin(GetNetValueTableColumn($est_ref, $cny_ref), 'netvaluehistory', $strLink))
	{
		_echoNetValueData($csv, $ref, $est_ref, $cny_ref, $iStart, $iNum);
    	EchoTableParagraphEnd($strMenuLink);
	}
}

?>
