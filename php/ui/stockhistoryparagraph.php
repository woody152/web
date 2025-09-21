<?php
require_once('stocktable.php');

function _echoStockHistoryItem($record, $ref, $csv, $bAdmin)
{
	$ar = array();
	
	$strDate = $record['date'];
   	$ar[] = $bAdmin ? GetOnClickLink('/php/_submitdelete.php?'.'stockhistory'.'='.$record['id'], '确认删除'.$strDate.STOCK_HISTORY_DISPLAY.'？', $strDate) : $strDate;
   	
	$strPrev = $ref->GetPrevPrice();
 	$ar[] = $ref->GetPriceDisplay(floatval($record['close']), floatval($strPrev));
    $ar[] = $record['volume'];
	$ar[] = $ref->GetPriceDisplay(floatval($record['adjclose']), floatval($strPrev));
	
 	EchoTableColumn($ar);
}

function _echoStockHistoryData($ref, $csv, $his_sql, $strStockId, $iStart, $iNum, $bAdmin)
{
    if ($result = $his_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
            _echoStockHistoryItem($record, $ref, $csv, $bAdmin);
        }
        mysqli_free_result($result);
    }
}

function EchoStockHistoryParagraph($ref, $str = false, $csv = false, $iStart = 0, $iNum = TABLE_COMMON_DISPLAY, $bAdmin = false)
{
    $strSymbol = $ref->GetSymbol();
    $strStockId = $ref->GetStockId();
	if ($str == false)	$str = GetStockHistoryLink($strSymbol);
	$his_sql = GetStockHistorySql();
    $strMenuLink = IsTableCommonDisplay($iStart, $iNum) ? '' : StockGetMenuLink($strSymbol, $his_sql->Count($strStockId), $iStart, $iNum);
    
	EchoTableParagraphBegin(array(new TableColumnDate(),
								   new TableColumnPrice(),
								   new TableColumnQuantity(false, 120),
								   new TableColumnPrice('复权')
								   ), $strSymbol.'stockhistory', $str.'<br />'.$strMenuLink);
   
    _echoStockHistoryData($ref, $csv, $his_sql, $strStockId, $iStart, $iNum, $bAdmin);
    EchoTableParagraphEnd($strMenuLink);
}

?>
