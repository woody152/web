<?php
require_once('_stock.php');
require_once('_stockgroup.php');
require_once('../../php/ui/fundestparagraph.php');
require_once('../../php/ui/referenceparagraph.php');
require_once('../../php/ui/tradingparagraph.php');
require_once('../../php/ui/smaparagraph.php');
require_once('../../php/ui/fundhistoryparagraph.php');
require_once('../../php/ui/fundlistparagraph.php');
require_once('../../php/ui/fundshareparagraph.php');
require_once('../../php/ui/netvaluecloseparagraph.php');

function GetTitle()
{
    global $acct;
	return $acct->GetStockDisplay().STOCK_DISP_NETVALUE;
}

class FundGroupAccount extends GroupAccount 
{
    function GetStockDisplay()
    {
    	$ref = $this->GetRef();
    	if (method_exists($ref, 'GetStockRef'))
    	{
    		$stock_ref = $ref->GetStockRef();
    		$netvalue_ref = $ref;
    	}
    	else
    	{
    		$stock_ref = $ref;
    		$netvalue_ref = $ref->GetNetValueRef();
    	}

    	$str = $netvalue_ref->GetChineseName();
    	$str = str_replace('(人民币份额)', '', $str);
    	$str = str_replace('(人民币)', '', $str);
//    	return RefGetStockDisplay($stock_ref).$str;
    	return $stock_ref->GetSymbol().$str;
    }
}

?>
