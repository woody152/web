<?php
require_once('stocktable.php');

function _echoFundPurchaseTableItem($strStockId, $strAmount, $bChinese)
{
	$strSymbol = SqlGetStockSymbol($strStockId);
    EchoTableColumn(array(GetGroupStockLink($strSymbol), $strAmount));
}

function _echoFundPurchaseTableData($strMemberId, $bChinese)
{
	$group_sql = new StockGroupSql();
	$item_sql = new StockGroupItemSql();
	$amount_sql = new GroupItemAmountSql();
   	if ($result = $amount_sql->GetData())
   	{
   		while ($record = mysqli_fetch_assoc($result)) 
   		{
   			if ($item_record = $item_sql->GetRecordById($record['id']))
   			{
   				if ($group_record = $group_sql->GetRecordById($item_record['stockgroup_id']))
   				{
   					if ($group_record['member_id'] == $strMemberId)
   					{
   						_echoFundPurchaseTableItem($item_record['stock_id'], $record['num'], $bChinese);
   					}
   				}
			}
    	}
   		mysqli_free_result($result);
    }
	
/*	if ($result = SqlGetFundPurchase($strMemberId, $iStart, $iNum)) 
	{
		while ($record = mysqli_fetch_assoc($result)) 
		{
			_echoFundPurchaseTableItem($record['stock_id'], $record['amount'], $bChinese);
		}
		mysqli_free_result($result);
	}*/
}

function EchoFundPurchaseParagraph($str, $strMemberId, $bChinese)
{
	if (EchoTableParagraphBegin(array(new TableColumnSymbol(), new TableColumnAmount()), 'fund', $str))
	{
		_echoFundPurchaseTableData($strMemberId, $bChinese);
    	EchoTableParagraphEnd();
	}
}

?>
