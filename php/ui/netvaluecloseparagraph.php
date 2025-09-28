<?php
require_once('stocktable.php');

function _echoNetValueCloseItem($csv, $his_sql, $shares_sql, $arHistory, $fNetValue, $ref, $strStockId)
{
	$fClose = floatval($arHistory['close']);
	$strDate = $arHistory['date'];
	if (($strClosePrev = $his_sql->GetClosePrev($strStockId, $strDate)) === false)		return;
	
	$fClosePrev = floatval($strClosePrev);
   	if ($csv)	$csv->Write($strDate, $ref->GetPercentageString($fClosePrev, $fClose), $ref->GetPercentageString($fNetValue, $fClose), strval($fNetValue));

   	$ar = array($strDate);
   	$ar[] = $ref->GetPriceDisplay($fClose, $fNetValue);
   	$ar[] = $ref->GetNetValueDisplay($fNetValue);
	$ar[] = $ref->GetPercentageDisplay($fNetValue, $fClose);
   	$ar[] = $ref->GetPercentageDisplay($fClosePrev, $fClose);
    if ($strShare = $shares_sql->GetClose($strStockId, $strDate))
    {
    	$fShare = floatval($strShare);
    	$ar[] = number_format($fShare, 2);
    	$ar[] = GetTurnoverDisplay(floatval($his_sql->GetVolume($strStockId, $strDate)), $fShare);
    }
    
    EchoTableColumn($ar);
}

function _echoNetValueCloseData($his_sql, $ref, $strStockId, $csv, $iStart, $iNum)
{
	$bSameDay = UseSameDayNetValue($ref);
	$shares_sql = new SharesHistorySql();
    if ($result = $his_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($arHistory = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $bSameDay ? $arHistory['date'] : $his_sql->GetDatePrev($strStockId, $arHistory['date']);
        	if ($fNetValue = $ref->GetNetValue($strDate))	_echoNetValueCloseItem($csv, $his_sql, $shares_sql, $arHistory, $fNetValue, $ref, $strStockId);
        }
        mysqli_free_result($result);
    }
}

function EchoNetValueCloseParagraph($ref, $str = false, $csv = false, $iStart = 0, $iNum = TABLE_COMMON_DISPLAY)
{
    if ($ref->CountNetValue() == 0)	return;

	$strStockId = $ref->GetStockId();
    $strSymbol = $ref->GetSymbol();
    $his_sql = GetStockHistorySql();
   	$strMenuLink = IsTableCommonDisplay($iStart, $iNum) ? '' : StockGetMenuLink($strSymbol, $his_sql->Count($strStockId), $iStart, $iNum);
   	if ($str == false)	$str = GetYahooNetValueLink($strSymbol).'的'.GetNetValueCloseLink($strSymbol);

	EchoTableParagraphBegin(array(new TableColumnDate(),
								   new TableColumnPrice(),
								   new TableColumnNetValue(),
								   new TableColumnPremium('y'),
								   new TableColumnChange('x'),
								   new TableColumnShare(),
								   new TableColumnTurnover()
								   ), $strSymbol.'netvalueclose', $str.' '.$strMenuLink);
    _echoNetValueCloseData($his_sql, $ref, $strStockId, $csv, $iStart, $iNum);
    EchoTableParagraphEnd($strMenuLink);
}

?>
