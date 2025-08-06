<?php

class NetValueReference extends MysqlReference
{
    public function __construct($strSymbol) 
    {
        parent::__construct($strSymbol);
        
        if ($this->IsFundA())
        {
       		if (StockCompareEstResult($this->GetStockId(), $this->GetPrice(), $this->GetDate(), $this->GetSymbol()))
       		{	// new net value
       		}
        }
    }
    
    public function LoadData()
    {
    	$strSymbol = $this->GetSymbol();
    	$this->strSqlId = SqlGetStockId($strSymbol);
        if ($this->IsFundA())
        {
        	$this->LoadSinaFundData();
        	$this->bConvertGB2312 = true;     // Sina name is GB2312 coded
        }
        else
        {
        	$this->LoadSqlNetValueData();
        }
    }
}

?>
