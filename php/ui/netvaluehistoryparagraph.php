<?php
require_once('stocktable.php');

function _echoNetValueItem($csv, $net_sql, $strStockId, $est_sql, $strEstId, $strNetValue, $strDate, $ref, $est_ref, $cny_ref)
{
	$bWritten = false;
	$ar = array($strDate, $strNetValue);
	if ($record = $net_sql->GetRecordPrev($strStockId, $strDate))
    {
    	$strPrevDate = $record['date'];
    	$strPrev = rtrim0($record['close']);
		$ar[] = $ref->GetPercentageDisplay($strPrev, $strNetValue);

		if ($est_sql)
		{
			$strCny = $cny_ref->GetClose($strDate);
			$ar[] = $strCny;
			if ($strCnyPrev = $cny_ref->GetClose($strPrevDate))
			{
				$ar[] = $cny_ref->GetPercentageDisplay($strCnyPrev, $strCny);
			}
			else
			{
				$ar[] = '';
			}
		
			if ($strEst = $est_sql->GetClose($strEstId, $strDate))
			{
				$ar[] = $strEst;
				if ($strEstPrev = $est_sql->GetClose($strEstId, $strPrevDate))
				{
					$ar[] = $est_ref->GetPercentageDisplay($strEstPrev, $strEst);
					if ($strVal = QdiiGetStockPosition($strEstPrev, $strEst, $strPrev, $strNetValue, $strCnyPrev, $strCny))
					{
						$bWritten = true;
						if ($csv)	$csv->Write($strDate, $strNetValue, $strVal);
						$ar[] = $strVal;
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
	if ($est_ref)
	{
		$strEstId = $est_ref->GetStockId();
		$est_sql = $net_sql;
		if ($est_sql->Count($strEstId) == 0 || $est_ref->IsIndex())
		{
			$est_sql = GetStockHistorySql();
		}
	}
	else
	{
		$est_sql = false;
		$strEstId = false;
	}

	$strStockId = $ref->GetStockId();
    if ($result = $net_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
			_echoNetValueItem($csv, $net_sql, $strStockId, $est_sql, $strEstId, rtrim0($record['close']), $record['date'], $ref, $est_ref, $cny_ref);
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
   	else if ($strSymbol == 'SZ164906')
   	{
   		$cny_ref = new CnyReference('USCNY');
   		$est_ref = new MyStockReference('KWEB');
   	}
    else
    {
    	$cny_ref = false;
    	$est_ref = false;
    }
    
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
	EchoTableParagraphBegin($ar, 'netvaluehistory', $strLink);
	_echoNetValueData($csv, $ref, $est_ref, $cny_ref, $iStart, $iNum);
    EchoTableParagraphEnd($strMenuLink);
}

?>
