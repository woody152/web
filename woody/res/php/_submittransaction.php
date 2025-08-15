<?php
require_once('_stock.php');
require_once('_editmergeform.php');

function _getStockQuantity()
{
	$strQuantity = SqlCleanString($_POST['quantity']);
	if ($_POST['type'] == '0')    // sell
	{
	    $strQuantity = '-'.$strQuantity;
	}
	return $strQuantity; 
}

function _getStockCost($strGroupItemId, $strQuantity, $strPrice)
{
	$fTax = 0.0;
	$fQuantity = floatval($strQuantity);
	$fQuantityAbs = abs($fQuantity); 
	$fAmount = $fQuantityAbs * floatval($strPrice);
	$strCommission = SqlCleanString($_POST['commission']);
	if (empty($strCommission))
	{	// use default
   		$sym = GetGroupItemSym($strGroupItemId);
   		if ($sym->IsSinaFuture())
   		{
   			$fCommission = 0.62;
   		}
   		else if ($sym->IsSinaForex())
   		{
   			$fCommission = 15.0;
   		}
		else if ($sym->IsSymbolA())
   		{
   			if ($sym->IsFundA())			$fCommission = $fAmount * 0.0001;
   			else
   			{
   				if ($fQuantity > 0.0)		$fCommission = $fAmount * 0.0002;
   				else						$fCommission = $fAmount * 0.0015;
   				if ($fCommission < 5.0)		$fCommission = 5.0;
   			}
   		}
   		else if ($sym->IsSymbolH())			$fCommission = $fAmount * 0.002;
   		else
   		{
   			if ($fQuantityAbs < 200.0)		$fCommission = 1.0;
   			else							$fCommission = 0.005 * $fQuantityAbs;
   			if ($fQuantity < 0.0)			$fCommission += $fAmount * 0.000028;
   		}
	}
	else
	{
	    $fCommission = floatval($strCommission);
	    if ($_POST['commissionselect'] == '1')    // percentage
	    {
	    	$fCommission *= $fAmount / 1000.0;
	    }

	    if (isset($_POST['taxselect']))
	    {
	    	$fTax = floatval(SqlCleanString($_POST['tax']));
			if ($_POST['taxselect'] == '1')    // percentage
			{
				$fTax *= $fAmount / 1000.0;
			}
		}
	}
	
	return strval_round($fCommission + $fTax, 3);
}

function _getStockTransactionLink($strGroupId, $strStockId)
{
    $strSymbol = SqlGetStockSymbol($strStockId);
    return StockGetTransactionLink($strGroupId, $strSymbol); 
}

function _debugStockTransaction($strStockId, $strGroupId, $strQuantity, $strPrice, $strCost, $strRemark)
{
	if (strlen($strRemark) == 0)	return;
	
	$str = $_POST['submit'];
    $str .= '<br />Symbol: '._getStockTransactionLink($strGroupId, $strStockId); 
    $str .= '<br />Quantity: '.$strQuantity; 
    $str .= '<br />Price: '.$strPrice; 
    $str .= '<br />Cost: '.$strCost; 
    $str .= '<br />Remark: '.$strRemark; 
    trigger_error($str); 
}

function _debugFundPurchase($strGroupId, $strFundId)
{
	$str = 'Arbitrage Fund Purchase';
    $str .= '<br />Fund: '._getStockTransactionLink($strGroupId, $strFundId); 
    trigger_error($str); 
}

class _SubmitTransactionAccount extends StockAccount
{
    function _canModifyStockTransaction($strGroupItemId)
    {
    	$strGroupId = SqlGetStockGroupId($strGroupItemId);
    	if ($this->IsGroupReadOnly($strGroupId))
    	{
    		return false;
    	}
    	return $strGroupId;
    }

    // groupid=%s&fundid=%s&amount=%.2f&netvalue=%.3f
    function _onAddFundPurchase($strGroupId)
    {
    	if ($this->IsGroupReadOnly($strGroupId))    						return false;
    	if (($strFundId = UrlGetQueryValue('fundid')) == false)    			return false;
    	if (($strAmount = UrlGetQueryValue('amount')) == false)    			return false;
    	if (($strNetValue = UrlGetQueryValue('netvalue')) == false)    		return false;
	
    	$sql = new StockGroupItemSql($strGroupId);
    	if (($strGroupItemId = $sql->GetId($strFundId)) == false)    return false;
    	
    	$strFundSymbol = SqlGetStockSymbol($strFundId);
   		$fFeeRatio = StockGetFundFeeRatio($strFundSymbol);
    	$fAmount = floatval($strAmount);
    	$fQuantity = $fAmount / (1.0 + $fFeeRatio) / floatval($strNetValue);
    	$strRemark = '}'.GetArbitrageQuantity($strFundSymbol, $strFundId, $fQuantity).' '.STOCK_DISP_ORDER;
    	if ($sql->trans_sql->Insert($strGroupItemId, strval(intval($fQuantity)), $strNetValue, strval_round($fAmount * $fFeeRatio * 0.1), $strRemark))
    	{
	       	_debugFundPurchase($strGroupId, $strFundId);
	    }
	    return $strGroupItemId;
	}
	
    function _onNew($strGroupItemId, $strQuantity, $strPrice, $strCost, $strRemark)
    {
    	if ($strGroupId = $this->_canModifyStockTransaction($strGroupItemId))
    	{
    		$sql = new StockGroupItemSql($strGroupId);
    		if ($sql->trans_sql->Insert($strGroupItemId, $strQuantity, $strPrice, $strCost, $strRemark))
    		{
    			_debugStockTransaction($sql->GetStockId($strGroupItemId), $strGroupId, $strQuantity, $strPrice, $strCost, $strRemark);
    		}
    	}
    	return $strGroupId;
    }

    function _onEdit($strId, $strGroupItemId, $strQuantity, $strPrice, $strCost, $strRemark)
    {
    	if ($strGroupId = $this->_canModifyStockTransaction($strGroupItemId))
    	{
    		$sql = new StockGroupItemSql($strGroupId);
    		if ($sql->trans_sql->Update($strId, $strGroupItemId, $strQuantity, $strPrice, $strCost, $strRemark))
    		{
    			_debugStockTransaction($sql->GetStockId($strGroupItemId), $strGroupId, $strQuantity, $strPrice, $strCost, $strRemark);
    		}
    	}
    	return $strGroupId;
    }

    function _onMergeTransaction()
    {
    	if ($_POST['type0'] == '0')    // From
    	{
    		$strSrcGroupItemId = $_POST['symbol0'];
    		$strDstGroupItemId = $_POST['symbol1'];
    	}
    	else
    	{
    		$strSrcGroupItemId = $_POST['symbol1'];
    		$strDstGroupItemId = $_POST['symbol0'];
    	}

    	if ($strSrcGroupId = $this->_canModifyStockTransaction($strSrcGroupItemId))
    	{
    		if ($strDstGroupId = $this->_canModifyStockTransaction($strDstGroupItemId))
    		{
    			$sql = new StockGroupItemSql($strSrcGroupId);
    			if ($sql->trans_sql->Merge($strSrcGroupItemId, $strDstGroupItemId))
    			{
    				UpdateStockGroupItemTransaction($sql, $strSrcGroupItemId);
    				UpdateStockGroupItemTransaction(new StockGroupItemSql($strDstGroupId), $strDstGroupItemId);
    			}
    		}
    	}
    }
    
    public function Process($strLoginId)
    {
    	$strGroupId = false;
    	$strGroupItemId = false;
    	if (isset($_POST['submit']))
    	{
    		$strSubmit = $_POST['submit'];
    		if ($strSubmit == STOCK_TRANSACTION_MERGE || $strSubmit == STOCK_TRANSACTION_MERGE_CN)
    		{
    			$this->_onMergeTransaction();
    			unset($_POST['submit']);
    			return;
    		}
		
    		$strGroupItemId = $_POST['symbol'];
    		$strQuantity = _getStockQuantity();
    		$strPrice = SqlCleanString($_POST['price']);
    		$strCost = _getStockCost($strGroupItemId, $strQuantity, $strPrice);
    		$strRemark = SqlCleanString($_POST['remark']);
    		switch ($strSubmit)
    		{
    		case STOCK_TRANSACTION_NEW:
    			$strGroupId = $this->_onNew($strGroupItemId, $strQuantity, $strPrice, $strCost, $strRemark);
    			break;
		    
    		case STOCK_TRANSACTION_EDIT:
    			if ($strId = UrlGetQueryValue('edit'))
    			{
    				$strGroupId = $this->_onEdit($strId, $strGroupItemId, $strQuantity, $strPrice, $strCost, $strRemark);
    			}
    			break;
    		}
    		unset($_POST['submit']);
    	}
    	else if ($strId = UrlGetQueryValue('delete'))
    	{
    		$trans_sql = new StockTransactionSql();
    		if ($record = $trans_sql->GetRecordById($strId))
    		{
    			$strGroupItemId = $record['groupitem_id'];
   				$strGroupId = $this->IsAdmin() ? SqlGetStockGroupId($strGroupItemId) : $this->_canModifyStockTransaction($strGroupItemId);
    			if ($strGroupId)
    			{
    				$trans_sql->DeleteById($strId);
    			}
    		}
    	}
    	else if ($strId = UrlGetQueryValue('adjust'))
    	{
    		$trans_sql = new StockTransactionSql();
    		if ($record = $trans_sql->GetRecordById($strId))
    		{
    			$strGroupItemId = $record['groupitem_id'];
   				if ($strGroupId = $this->_canModifyStockTransaction($strGroupItemId))
    			{
    				$strNetValue = UrlGetQueryValue('netvalue');
    				$trans_sql->Update($strId, $strGroupItemId, strval(floatval($record['quantity']) * floatval($record['price']) / floatval($strNetValue)), $strNetValue, $record['fees'], $record['remark']);
    			}
    		}
    	}
    	else if ($strId = UrlGetQueryValue('empty'))
    	{
    		$trans_sql = new StockTransactionSql();
    		if ($record = $trans_sql->GetRecordById($strId))
    		{
    			$strGroupItemId = $record['groupitem_id'];
   				if ($strGroupId = $this->_canModifyStockTransaction($strGroupItemId))
    			{
    				$trans_sql->Update($strId, $strGroupItemId, $record['quantity'], $record['price'], $record['fees']);
    			}
    		}
    	}
    	else if ($strGroupId = UrlGetQueryValue('groupid'))
    	{
    		$strGroupItemId = $this->_onAddFundPurchase($strGroupId);
    	}

    	UpdateStockGroupItem($strGroupId, $strGroupItemId);
    }
}

?>
