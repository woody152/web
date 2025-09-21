<?php
require_once('stocktable.php');

function _echoFundHistoryTableItem($csv, $strNetValue, $arHistory, $arFundEst, $ref, $est_ref, $bAdmin)
{
	$fNetValue = floatval($strNetValue);
	$fClose = floatval($arHistory['close']);
	$strDate = $arHistory['date'];
    if ($csv)		$csv->Write($strDate, $strNetValue, $ref->GetPercentageString($fNetValue, $fClose));

   	$ar = array($strDate);
   	$ar[] = $ref->GetPriceDisplay($fClose, $fNetValue);
   	$ar[] = $ref->GetNetValueDisplay($fNetValue);
   	$ar[] = $ref->GetPercentageDisplay($fNetValue, $fClose);
    if ($arFundEst)
    {
    	if ($strEstValue = $arFundEst['close'])
    	{
    		$ar[] = $ref->GetPriceDisplay(floatval($strEstValue), floatval($strNetValue));
    		$strTime = GetHM($arFundEst['time']); 
    		$ar[] = $bAdmin ? GetOnClickLink('/php/_submitdelete.php?'.'fundest'.'='.$arFundEst['id'], '确认删除估值记录'.$strEstValue.'？', $strTime) : $strTime;
    		$ar[] = $ref->GetPercentageDisplay(floatval($strNetValue), floatval($strEstValue));
    		if ($est_ref)	$ar[] = $est_ref->GetNetValueDisplay($est_ref->GetNetValue($arFundEst['date']));
    	}
    }
    
    EchoTableColumn($ar);
}

function _echoHistoryTableData($his_sql, $fund_est_sql, $csv, $ref, $strStockId, $est_ref, $iStart, $iNum, $bAdmin)
{
	$bSameDay = UseSameDayNetValue($ref);
	$net_sql = GetNetValueHistorySql();
    if ($result = $his_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($arHistory = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $bSameDay ? $arHistory['date'] : $his_sql->GetDatePrev($strStockId, $arHistory['date']);
        	if ($strNetValue = $net_sql->GetClose($strStockId, $strDate))
        	{
   				$arFundEst = $fund_est_sql ? $fund_est_sql->GetRecord($strStockId, $strDate) : false;
        		_echoFundHistoryTableItem($csv, $strNetValue, $arHistory, $arFundEst, $ref, $est_ref, $bAdmin);
        	}
        }
        mysqli_free_result($result);
    }
}

function _echoFundHistoryParagraph($ref, $est_ref, $csv, $iStart, $iNum, $bAdmin)
{
	if ($ref->IsYahooNetValue())	return;
	
	$close_col = new TableColumnPrice();
	$netvalue_col = new TableColumnNetValue();
	$premium_col = new TableColumnPremium();
	
    $str = $ref->IsFundA() ? GetEastMoneyFundLink($ref) : GetXueqiuLink($ref);
    $str .= '的历史'.$close_col->GetDisplay().'相对于'.$netvalue_col->GetDisplay().'的'.$premium_col->GetDisplay();
    
    $strSymbol = $ref->GetSymbol();
	$strStockId = $ref->GetStockId();
    $his_sql = GetStockHistorySql();
    if (IsTableCommonDisplay($iStart, $iNum))
    {
        $str .= ' '.GetFundHistoryLink($strSymbol);
        $strMenuLink = '';
    }
    else	$strMenuLink = StockGetMenuLink($strSymbol, $his_sql->Count($strStockId), $iStart, $iNum);

	$ar = array(new TableColumnDate(), $close_col, $netvalue_col, $premium_col);
	$fund_est_sql = GetFundEstSql();
	if ($fund_est_sql->Count($strStockId) > 0)
	{
		$ar[] = new TableColumnOfficalEst();
		$ar[] = new TableColumnTime();
		$ar[] = new TableColumnError();
		if ($est_ref)		$ar[] = RefGetTableColumnNetValue($est_ref);
	}
	else	$fund_est_sql = false;
	
	EchoTableParagraphBegin($ar, $strSymbol.'fundhistory', $str.' '.$strMenuLink);
	_echoHistoryTableData($his_sql, $fund_est_sql, $csv, $ref, $strStockId, $est_ref, $iStart, $iNum, $bAdmin);
    EchoTableParagraphEnd($strMenuLink);
}

function EchoFundHistoryParagraph($ref, $csv = false, $iStart = 0, $iNum = TABLE_COMMON_DISPLAY, $bAdmin = false)
{
	if (method_exists($ref, 'GetStockRef'))
	{
		_echoFundHistoryParagraph($ref->GetStockRef(), $ref->GetEstRef(), $csv, $iStart, $iNum, $bAdmin);
	}
	else
	{
		$est_ref = method_exists($ref, 'GetPairRef') ? $ref->GetPairRef() : false;
		_echoFundHistoryParagraph($ref, $est_ref, $csv, $iStart, $iNum, $bAdmin);
	}
}

?>
