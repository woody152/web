<?php

class MysqlReference extends StockReference
{
    private $strSqlId = false;      // ID in mysql database
	var $bConvertGB2312 = false;

    var $fFactor = 1.0;			// 'close' field in calibrationhistory table
    private $fRatio = 1.0;
    
    private $iNetValueCount = 0;
    
    public function __construct($strSymbol) 
    {
        parent::__construct($strSymbol);
		$this->SetTimeZone();
        $this->LoadData();
        if (IsZeroString($this->strPrice))	$this->strPrice = $this->strPrevPrice;

		// CnyReference and NetValueReference set StockId in LoadData()
    	if ($this->strSqlId === false)	$this->SetStockId();
    	if ($this->strSqlId)
    	{
    		$net_sql = GetNetValueHistorySql();
    		$this->iNetValueCount = $net_sql->Count($this->strSqlId);
    		
    		$pos_sql = GetPositionSql();
    		if ($fRatio = $pos_sql->ReadVal($this->strSqlId))
			{
				$this->fRatio = $fRatio;
			}
			else if ($this->IsLofA())
			{
				$this->fRatio = 0.95;
				$pos_sql->WritePos($this->strSqlId, $this->fRatio);
			}
    	}
    }

    public function LoadData()
    {
    	$this->SetHasData(false);
    }
    
	public function GetClose($strDate)
	{
		if ($strDate == $this->GetDate())	return $this->GetPrice();
		return SqlGetHistoryByDate($this->strSqlId, $strDate);
	}
	
	public function GetVal($strDate = false)
	{
		if ($strDate)
		{
			if ($strClose = $this->GetClose($strDate))		return floatval($strClose);
			return false;
		}
		return floatval($this->GetPrice());
	}
	
	public function GetNetValue($strDate)
	{
		if ($this->IsSinaFutureExceptGoldCN())
		{
			if ($strClose = SqlGetAdjCloseByDate($this->strSqlId, $strDate))	return floatval($strClose);
			return false;
		}
		if ($this->iNetValueCount > 0)
		{
			if ($strClose = SqlGetNetValueByDate($this->strSqlId, $strDate))	return floatval($strClose);
			return false;
		}
		return $this->GetVal($strDate);
	}

	function GetNetValueDisplay($fNetValue)
	{
		if ($fNetValue === false)	return '';
		return $this->GetPriceDisplay($fNetValue, false, ($this->iNetValueCount > 0 ? NETVALUE_PRECISION : $this->GetPrecision()));
	}

    public function GetOfficialNetValue()
    {
    	return false;
    }
    
    public function GetFairNetValue()
    {
    	return false;
    }
    
    public function GetRealtimeNetValue()
    {
    	return false;
    }

	function GetEstNetValue()
	{
		if ($fEst = $this->GetRealtimeNetValue())		return $fEst;
		else if ($fEst = $this->GetFairNetValue())		return $fEst;
		else if ($fEst = $this->GetOfficialNetValue())	return $fEst;
		return false;
	}

    function SetStockId()
    {
		$strSymbol = $this->GetSymbol();
		$sql = GetStockSql();
    	$this->strSqlId = $sql->GetId($strSymbol);
		if ($this->strSqlId === false)
		{
	        if ($this->HasData())
			{
	            if ($sql->InsertSymbol($strSymbol, $this->GetStockName()))	$this->strSqlId = $sql->GetId($strSymbol);
			}	
		}	
    }
    
    public function GetStockId()
    {
        return $this->strSqlId;
    }

	function GetFactor()
    {
    	return $this->fFactor;
    }
    
    function GetPosition()
    {
    	return $this->fRatio;
    }
    
	function LoadDailySqlData($sql)
    {
       	if ($record = $sql->GetRecordNow($this->strSqlId))
       	{
   			$this->strPrice = $record['close'];
   			$this->strDate = $record['date'];
   			$this->strPrevPrice = $sql->GetClosePrev($this->strSqlId, $this->strDate);
   			return $this->strPrevPrice;
   		}
   		return false;
    }

    function LoadSqlNetValueData()
    {
    	$this->LoadDailySqlData(GetNetValueHistorySql());
    }

    function CountNetValue()
    {
    	return $this->iNetValueCount;
    }
    
    function IsFund()
    {
    	if ($this->CountNetValue() > 0)	return true;
    	if ($this->IsFundA())			return true;
    	return false;
    }
    
    function GetStockName()
    {
    	if ($this->bConvertGB2312)
    	{
    		$this->strName = GbToUtf8($this->strName);
			$this->bConvertGB2312 = false;
    	}
   		return $this->strName;
    }
}
