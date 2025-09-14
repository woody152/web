<?php
require_once('stocktable.php');

function _echoNetValueCloseItem($csv, $his_sql, $shares_sql, $arHistory, $arFundNetValue, $ref, $strStockId, $bAdmin)
{
	$strClose = $arHistory['close'];
	$strDate = $arHistory['date'];
	if (($strClosePrev = $his_sql->GetClosePrev($strStockId, $strDate)) === false)		return;
	
	$strNetValue = $arFundNetValue['close'];
   	if ($csv)	$csv->Write($strDate, $ref->GetPercentageString($strClosePrev, $strClose), $ref->GetPercentageString($strNetValue, $strClose), $strNetValue);

   	$ar = array($strDate, $ref->GetPriceDisplay($strClose, $strNetValue));
   	
   	$strNetValueDisplay = number_format(floatval($strNetValue), 2);
   	$ar[] = $bAdmin ? GetOnClickLink('/php/_submitdelete.php?'.'netvaluehistory'.'='.$arFundNetValue['id'], '确认删除净值记录'.$strNetValueDisplay.'？', $strNetValueDisplay) : $strNetValueDisplay;
	$ar[] = $ref->GetPercentageDisplay($strNetValue, $strClose);
   	$ar[] = $ref->GetPercentageDisplay($strClosePrev, $strClose);
    if ($strShare = $shares_sql->GetClose($strStockId, $strDate))
    {
    	$ar[] = rtrim0($strShare);
    	$ar[] = GetTurnoverDisplay(floatval($his_sql->GetVolume($strStockId, $strDate)), floatval($strShare));
    }
    
    EchoTableColumn($ar);
}

function _echoNetValueCloseData($his_sql, $ref, $strStockId, $csv, $iStart, $iNum, $bAdmin)
{
	$bSameDay = UseSameDayNetValue($ref);
	$net_sql = GetNetValueHistorySql();
	$shares_sql = new SharesHistorySql();
    if ($result = $his_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($arHistory = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $bSameDay ? $arHistory['date'] : $his_sql->GetDatePrev($strStockId, $arHistory['date']);
        	if ($arFundNetValue = $net_sql->GetRecord($strStockId, $strDate))	_echoNetValueCloseItem($csv, $his_sql, $shares_sql, $arHistory, $arFundNetValue, $ref, $strStockId, $bAdmin);
        }
        mysqli_free_result($result);
    }
}

function EchoNetValueCloseParagraph($ref, $str = false, $csv = false, $iStart = 0, $iNum = TABLE_COMMON_DISPLAY, $bAdmin = false)
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
    _echoNetValueCloseData($his_sql, $ref, $strStockId, $csv, $iStart, $iNum, $bAdmin);
    EchoTableParagraphEnd($strMenuLink);
}

?>
