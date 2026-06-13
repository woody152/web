<?php

function _getFundPairLink($ref)
{
	static $arSymbol = [];
	
	$strSymbol = $ref->GetSymbol();
	if (in_array($strSymbol, $arSymbol))		return $ref->GetDisplay();
	
	$arSymbol[] = $strSymbol;
	return $ref->GetMyStockLink();
}

function _echoFundListItem($ref, $sql, $last_sql, $callback)
{
    $strStockId = $ref->GetStockId();
    $fPos = $ref->GetPosition();
    $fFactor = $ref->GetFactor();
    $strDate = $sql->GetDateNow($strStockId);
    
	$ar = [];
	$ar[] = SymCalibrationHistoryLink($ref);
    $ar[] = _getFundPairLink($ref->GetPairRef());
    $ar[] = GetNumberDisplay($fPos);
    $ar[] = GetNumberDisplay($fFactor, CALIBRATION_PRECISION);
    $ar[] = $strDate;
    if ($callback)
    {
    	$ar[] = GetNumberDisplay(call_user_func($callback, $ref->GetSymbol()), 0);
    }
    else
    {
    	if ($strVal = $last_sql->ReadVal($strStockId, true))	$ar[] = $ref->GetPriceDisplay(floatval($strVal));
    }
    RefEchoTableColumn($ref, $ar);
}

function EchoFundListParagraph($arRef, $callback = false)
{
	$str = GetFundListLink();
	if (EchoTableParagraphBegin([new TableColumnSymbol(),
								 new TableColumnSymbol('跟踪'),
								 new TableColumnPosition(),
								 new TableColumnCalibration(),
								 new TableColumnDate(),
								 ($callback ? new TableColumnHedge() : new TableColumn('参考值'))
								], 'fundlist', $str))
	{
		$sql = GetCalibrationSql();
		$last_sql = new LastCalibrationSql();
		foreach ($arRef as $ref)	_echoFundListItem($ref, $sql, $last_sql, $callback);
    	EchoTableParagraphEnd();
	}							   
}
