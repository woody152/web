<?php
require_once('stocktrans.php');
require_once('class/multi_currency.php');

class StockGroup
{
    var $multi_amount;
    var $multi_profit;
    
    var $strGroupId = false;
    
    public function __construct() 
    {
        $this->multi_amount = new MultiCurrency();
        $this->multi_profit = new MultiCurrency();
    }

    function GetGroupId()
    {
    	return $this->strGroupId;
    }
    
    function OnStockTransaction($trans)
    {
        $sym = $trans->ref;
        if ($sym->IsSymbolA() || $sym->IsSinaFutureCN() || $sym->GetSymbol() == 'fx_susdcnh')
        {
            $this->multi_amount->fCNY += $trans->GetValue();
            $this->multi_profit->fCNY += $trans->GetProfit();
        }
        else if ($sym->IsSymbolH())
        {
            $this->multi_amount->fHKD += $trans->GetValue();
            $this->multi_profit->fHKD += $trans->GetProfit();
        }
        else 
        {
            $this->multi_amount->fUSD += $trans->GetValue();
            $this->multi_profit->fUSD += $trans->GetProfit();
        }
    }
    
    function ConvertCurrency($strUSDCNY, $strHKDCNY) 
    {
        $this->multi_amount->Convert($strUSDCNY, $strHKDCNY);
        $this->multi_profit->Convert($strUSDCNY, $strHKDCNY);
    }
}

class MyStockGroup extends StockGroup
{
    var $arStockTransaction = array();
    
    function GetStockTransactionArray()
    {
    	return $this->arStockTransaction;
    }
/*    
    function GetStockTransactionByStockGroupItemId($strStockGroupItemId)
    {
        foreach ($this->arStockTransaction as $trans)
        {
            if ($trans->strStockGroupItemId == $strStockGroupItemId)     return $trans;
        }
        return false;
    }
    
    function GetStockTransactionByStockId($strStockId)
    {
        foreach ($this->arStockTransaction as $trans)
        {
            if ($trans->ref->GetStockId() == $strStockId)     return $trans;
        }
        return false;
    }
*/    
    function GetStockTransactionBySymbol($strSymbol)
    {
        foreach ($this->arStockTransaction as $trans)
        {
            if ($trans->GetSymbol() == $strSymbol)   return $trans;
        }
        return false;
    }
    
    function _addTransaction($ref)
    {
        $this->arStockTransaction[] = new MyStockTransaction($ref, $this->strGroupId);
    }
    
    function _checkSymbol($strSymbol)
    {
        if ($this->GetStockTransactionBySymbol($strSymbol))  return;
		$this->_addTransaction(StockGetReference($strSymbol));
    }

    function SetValue($strSymbol, $iTotalRecords, $iTotalShares, $fTotalCost)
    {
        $this->_checkSymbol($strSymbol);
        foreach ($this->arStockTransaction as $trans)
        {
            if ($trans->GetSymbol() == $strSymbol)
            {
                $trans->SetValue($iTotalRecords, $iTotalShares, $fTotalCost);
                $this->OnStockTransaction($trans);
                break;
            }
        }
    }

    function GetTotalRecords()
    {
        $iTotal = 0;
        foreach ($this->arStockTransaction as $trans)
        {
            $iTotal += $trans->GetTotalRecords();
        }
        return $iTotal;
    }
    
    public function __construct($strGroupId, $arRef) 
    {
        parent::__construct();
        
        $this->strGroupId = $strGroupId;
        foreach ($arRef as $ref)
        {
            $this->_addTransaction($ref);
        }
        
        $sql = new StockGroupItemSql($strGroupId);
        if ($result = $sql->GetAll()) 
        {   
            while ($record = mysqli_fetch_assoc($result)) 
            {
                if (intval($record['record']) > 0)
                {
                    $this->SetValue(SqlGetStockSymbol($record['stock_id']), intval($record['record']), intval($record['quantity']), floatval($record['cost']));
                }
            }
            mysqli_free_result($result);
        }
    }
}

?>
