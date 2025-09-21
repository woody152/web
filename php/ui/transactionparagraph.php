<?php
require_once('stockgroupparagraph.php');

function _echoTransactionTableItem($ref, $record, $bReadOnly, $bAdmin)
{
	$strDate = GetSqlTransactionDate($record);
	$strQuantity = $record['quantity']; 
    
    $ar = array($strDate, $ref->GetDisplay(), $strQuantity);
    $strPrice = $record['price'];
	$iPrecision = $ref->GetPrecision();
	if ($ref->IsFundA())	$iPrecision = 4;
    $ar[] = number_format(floatval($strPrice), $iPrecision);
    $ar[] = number_format(floatval($record['fees']), 2);

    $strId = $record['id'];
    $strRemark = $record['remark'];
   	if ($bReadOnly == false)
   	{
   		if (strlen($strRemark) > 0)
   		{
			$strRemark = GetOnClickLink(PATH_STOCK.'submittransaction.php?empty='.$strId, '确认清空'.STOCK_DISP_REMARK.'：'.$strRemark.'？', '清空').$strRemark;
			if (strpos($strRemark, STOCK_DISP_ORDER) !== false)
			{
				$net_sql = GetNetValueHistorySql();
				$strStockId = $ref->GetStockId();
				$strSymbol = $ref->GetSymbol();
				if (in_arrayQdii($strSymbol) || in_arrayQdiiMix($strSymbol))		$strNetValue = $net_sql->GetClosePrev($strStockId, $strDate);
				else																$strNetValue = $net_sql->GetClose($strStockId, $strDate);
				
				if ($strNetValue != $strPrice)
				{
					$strRemark .= GetOnClickLink(PATH_STOCK.'submittransaction.php?adjust='.$strId.'&netvalue='.$strNetValue, '确认校准到净值: '.$strNetValue.'？', '校准');
				}
   			}
   		}
   	}
	$ar[] = $strRemark;
    	
    $strEdit = '';
   	$strDelete = GetDeleteLink(PATH_STOCK.'submittransaction.php?delete='.$strId, $strDate.' '.$strQuantity.STOCK_TRANSACTION_DISPLAY);
    if ($bReadOnly == false)
    {
    	$strEdit = GetEditLink(PATH_STOCK.'editstocktransaction', $strId);
    }
    else if ($bAdmin == false)
    {
    	$strDelete = '';
    }
    $ar[] = $strEdit.' '.$strDelete;

    EchoTableColumn($ar);
}

function _echoSingleTransactionTableData($sql, $ref, $iStart, $iNum, $bReadOnly, $bAdmin)
{
	if ($result = $sql->GetStockTransaction($ref->GetStockId(), $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
            _echoTransactionTableItem($ref, $record, $bReadOnly, $bAdmin);
        }
        mysqli_free_result($result);
    }
}

function _echoAllTransactionTableData($sql, $iStart, $iNum, $bReadOnly, $bAdmin)
{
    $ar = array();
    if ($result = $sql->GetAllStockTransaction($iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
        	$strGroupItemId = $record['groupitem_id'];
        	if (array_key_exists($strGroupItemId, $ar))
        	{
        		$ref = $ar[$strGroupItemId];
        	}
        	else
        	{
        		$strStockId = $sql->GetStockId($strGroupItemId);
        		$strSymbol = SqlGetStockSymbol($strStockId);
        		$ref = new MyStockReference($strSymbol);
        		$ar[$strGroupItemId] = $ref;
        	}
            _echoTransactionTableItem($ref, $record, $bReadOnly, $bAdmin);
        }
        mysqli_free_result($result);
    }
}

function _echoTransactionTableData($sql, $ref, $iStart, $iNum, $bReadOnly, $bAdmin)
{
    if ($ref)
    {
    	_echoSingleTransactionTableData($sql, $ref, $iStart, $iNum, $bReadOnly, $bAdmin);
    }
    else
    {
    	_echoAllTransactionTableData($sql, $iStart, $iNum, $bReadOnly, $bAdmin);
    }
}

function EchoTransactionParagraph($acct, $strGroupId, $ref = false, $bAll = true)
{
    $iStart = $acct->GetStart();
    $iNum = $bAll ? $acct->GetNum() : TABLE_COMMON_DISPLAY;
    
	$sql = new StockGroupItemSql($strGroupId);
    if ($bAll)
    {
    	if ($ref)
    	{
            $iTotal = $sql->CountStockTransaction($ref->GetStockId());
           	$strMenuLink = GetMenuLink('groupid='.$strGroupId.'&symbol='.$ref->GetSymbol(), $iTotal, $iStart, $iNum);
    	}
    	else
    	{
            $iTotal = $sql->CountAllStockTransaction();
           	$strMenuLink = GetMenuLink('groupid='.$strGroupId, $iTotal, $iStart, $iNum);
        }
        $str = $strMenuLink;
    }
    else
    {
    	$str = StockGetAllTransactionLink($strGroupId, $ref);
        $strMenuLink = '';
    }

	EchoTableParagraphBegin(array(new TableColumnDate(),
								   new TableColumnSymbol(),
								   new TableColumnQuantity(),
								   new TableColumnPrice(),
								   new TableColumn('费用', 60),
								   new TableColumnRemark(),
								   new TableColumn('操作')
								   ), 'transaction', $str);
    _echoTransactionTableData($sql, $ref, $iStart, $iNum, $acct->IsGroupReadOnly($strGroupId), $acct->IsAdmin());
    EchoTableParagraphEnd($strMenuLink);
}

?>
