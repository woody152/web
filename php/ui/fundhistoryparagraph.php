<?php
require_once('stocktable.php');

function _echoFundHistoryTableItem($csv, $strNav, $arHistory, $arFundEst, $ref, $est_ref, $his_sql, $bAdmin)
{
	$strClose = $arHistory['close'];
	$strDate = $arHistory['date'];
    if ($csv)		$csv->Write($strDate, $strNav, $ref->GetPercentageString($strNav, $strClose));

   	$ar = array($strDate, $ref->GetPriceDisplay($strClose, $strNav), $strNav, $ref->GetPercentageDisplay($strNav, $strClose));
    if ($arFundEst)
    {
    	if ($strEstValue = $arFundEst['close'])
    	{
    		$ar[] = $ref->GetPriceDisplay($strEstValue, $strNav);
    		$strTime = GetHM($arFundEst['time']); 
    		$ar[] = $bAdmin ? GetOnClickLink('/php/_submitdelete.php?'.'fundest'.'='.$arFundEst['id'], '确认删除估值记录'.$strEstValue.'？', $strTime) : $strTime;
    		$ar[] = $ref->GetPercentageDisplay($strNav, $strEstValue);
		
    		if ($est_ref)
    		{
    			$strEstDate = $arFundEst['date'];
    			$strEstStockId = $est_ref->GetStockId();
    			$strEstClose = $his_sql->GetClose($strEstStockId, $strEstDate);
    			$strEstClosePrev = $his_sql->GetClosePrev($strEstStockId, $strEstDate);
    			if ($strEstClose && $strEstClosePrev)		$ar[] = $est_ref->GetPriceDisplay($strEstClose, $strEstClosePrev);
    		}
    	}
    }
    
    EchoTableColumn($ar);
}

function _echoHistoryTableData($his_sql, $fund_est_sql, $csv, $ref, $strStockId, $est_ref, $iStart, $iNum, $bAdmin)
{
	$bSameDay = UseSameDayNetValue($ref);
	$netvalue_sql = GetNetValueHistorySql();
	if ($est_ref)		$est_sql = ($est_ref->CountNetValue() > 0) ? $netvalue_sql : $his_sql;
	else				$est_sql = false;
	
    if ($result = $his_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($arHistory = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $bSameDay ? $arHistory['date'] : $his_sql->GetDatePrev($strStockId, $arHistory['date']);
        	if ($strNav = $netvalue_sql->GetClose($strStockId, $strDate))
        	{
   				$arFundEst = $fund_est_sql ? $fund_est_sql->GetRecord($strStockId, $strDate) : false;
        		_echoFundHistoryTableItem($csv, $strNav, $arHistory, $arFundEst, $ref, $est_ref, $est_sql, $bAdmin);
        	}
        }
        mysqli_free_result($result);
    }
}

function _echoFundHistoryParagraph($ref, $est_ref, $csv, $iStart, $iNum, $bAdmin)
{
	$close_col = new TableColumnPrice();
	$nav_col = new TableColumnNetValue();
	$premium_col = new TableColumnPremium();
	
    $str = $ref->IsFundA() ? GetEastMoneyFundLink($ref) : GetXueqiuLink($ref);
    $str .= '的历史'.$close_col->GetDisplay().'相对于'.$nav_col->GetDisplay().'的'.$premium_col->GetDisplay();
    
    $strSymbol = $ref->GetSymbol();
	$strStockId = $ref->GetStockId();
    $his_sql = GetStockHistorySql();
    if (IsTableCommonDisplay($iStart, $iNum))
    {
        $str .= ' '.GetFundHistoryLink($strSymbol);
        $strMenuLink = '';
    }
    else	$strMenuLink = StockGetMenuLink($strSymbol, $his_sql->Count($strStockId), $iStart, $iNum);

	$ar = array(new TableColumnDate(), $close_col, $nav_col, $premium_col);
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
