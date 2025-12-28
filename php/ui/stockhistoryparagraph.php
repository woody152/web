<?php
require_once('stocktable.php');

function _echoStockHistoryItem($record, $ref, $compare_ref, $forex_ref, $csv, $his_sql, $strCompareId, $strForexId, $bAdmin)
{
	$ar = array();
	
	$strDate = $record['date'];
   	$ar[] = $bAdmin ? GetOnClickLink('/php/_submitdelete.php?'.'stockhistory'.'='.$record['id'], '确认删除'.$strDate.STOCK_HISTORY_DISPLAY.'？', $strDate) : $strDate;
   	
	$fPrev = floatval($ref->GetPrevPrice());
 	$ar[] = $ref->GetPriceDisplay(floatval($record['close']), $fPrev);
    $ar[] = $record['volume'];
    $fAdjClose = floatval($record['adjclose']);
	$ar[] = $ref->GetPriceDisplay($fAdjClose, $fPrev);
	
	if ($compare_ref)
	{
//		if ($strCompare = $his_sql->GetAdjClose($strCompareId, $strDate))
		if ($strCompare = $his_sql->GetClosePrev($strCompareId, $strDate))
		{
			$fCompare = floatval($strCompare);
			$ar[] = $compare_ref->GetPriceDisplay($fCompare);
			$fAdjClose /= 1000.0;
			$fAdjClose *= 31.1035;
			$fRatio = $fAdjClose / $fCompare;
			if ($forex_ref)
			{
				if ($strForex = $his_sql->GetAdjClose($strForexId, $strDate))
				{
					$fForex = floatval($strForex); 
					$ar[] = $forex_ref->GetPriceDisplay($fForex);
					$fRatio /= $fForex;
				}
			}
			$ar[] = GetRatioDisplay($fRatio);
		}
	}
	
 	EchoTableColumn($ar);
}

function _echoStockHistoryData($ref, $compare_ref, $forex_ref, $csv, $his_sql, $strStockId, $iStart, $iNum, $bAdmin)
{
	$strCompareId = $compare_ref ? $compare_ref->GetStockId() : false; 
	$strForexId = $forex_ref ? $forex_ref->GetStockId() : false; 
    if ($result = $his_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
            _echoStockHistoryItem($record, $ref, $compare_ref, $forex_ref, $csv, $his_sql, $strCompareId, $strForexId, $bAdmin);
        }
        mysqli_free_result($result);
    }
}

function EchoStockHistoryParagraph($ref, $compare_ref = false, $forex_ref = false, $str = false, $csv = false, $iStart = 0, $iNum = TABLE_COMMON_DISPLAY, $bAdmin = false)
{
    $strSymbol = $ref->GetSymbol();
    $strStockId = $ref->GetStockId();
	if ($str == false)	$str = GetStockHistoryLink($strSymbol);
	$his_sql = GetStockHistorySql();
    $strMenuLink = IsTableCommonDisplay($iStart, $iNum) ? '' : StockGetMenuLink($strSymbol, $his_sql->Count($strStockId), $iStart, $iNum);
    
    $ar = array(new TableColumnDate(), new TableColumnPrice(), new TableColumnQuantity(false, 120), new TableColumnPrice('复权'));
    if ($compare_ref)
    {
    	$ar[] = new TableColumnStock($compare_ref);
    	if ($forex_ref)	$ar[] = new TableColumnStock($forex_ref);
    	$ar[] = new TableColumnRatio();
    }
	EchoTableParagraphBegin($ar, $strSymbol.'stockhistory', $str.'<br />'.$strMenuLink);
   
    _echoStockHistoryData($ref, $compare_ref, $forex_ref, $csv, $his_sql, $strStockId, $iStart, $iNum, $bAdmin);
    EchoTableParagraphEnd($strMenuLink);
}

?>
