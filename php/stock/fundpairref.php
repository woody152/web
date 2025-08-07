<?php

function PairNetValueGetClose($ref, $strDate)
{
	if ($ref->IsFund())
	{
		$sql = GetNetValueHistorySql();
	}
	else
	{
		$sql = GetStockHistorySql();
	}
    return $sql->GetClose($ref->GetStockId(), $strDate);
}

class MyPairReference extends MyStockReference
{
    var $pair_ref = false;
    var $cny_ref = false;
    
	var $fLastCalibrationVal = false;
    var $fRatio;
	
    public function __construct($strSymbol, $pair_sql) 
    {
        parent::__construct($strSymbol);
       	if ($strPair = $pair_sql->GetPairSymbol($strSymbol))	
        {
        	$this->pair_ref = new MyStockReference($strPair);
        	$strCNY = false;
        	if ($this->pair_ref->IsSymbolA())
        	{
        		if ($this->IsSymbolA())
        		{
        			if ($this->pair_ref->IsShangHaiB())			$strCNY = 'USCNY';
					else if ($this->pair_ref->IsShenZhenB())		$strCNY = 'HKCNY';
        		}
//      		else if ($this->IsSymbolH())		$strCNY = 'HKCNY';
        		else if ($this->IsSymbolUS() || $this->IsSinaFuture())	$this->cny_ref = new MyStockReference('fx_susdcnh');		//$strCNY = 'USCNY';
        	}
        	else if ($this->pair_ref->IsSymbolH())
        	{
        		if ($this->IsSymbolA())			$strCNY = 'HKCNY';
				else if ($this->IsSymbolUS())	$this->cny_ref = new UsdHkdReference();
        	}
        	else
        	{
        		if ($this->IsSymbolA())			$strCNY = 'USCNY';
        	}
        	if ($strCNY)		$this->cny_ref = new CnyReference($strCNY);
    	
        	$this->fRatio = RefGetPosition($this);
        	$this->LoadCalibration();
    	}
    }
    
    function LoadCalibration()
    {
		$strStockId = $this->GetStockId();
		$calibration_sql = GetCalibrationSql();
		if ($record = $calibration_sql->GetRecordNow($strStockId))
    	{
			$this->fFactor = floatval($record['close']);
			$sql = new LastCalibrationSql();
			$this->fLastCalibrationVal = $sql->ReadVal($strStockId); 
			if ($this->fLastCalibrationVal === false)	$this->fLastCalibrationVal = floatval(SqlGetNetValueByDate($strStockId, $record['date'])); 
    	}
		else	$this->fFactor = 1.0 / $this->fRatio; 
    }
    
    function GetRatio()
    {
    	return $this->fRatio;
    }
    
    function GetPairRef()
    {
    	return $this->pair_ref;
    }
    
    function GetCnyRef()
    {
    	return $this->cny_ref;
    }
    
    function GetDefaultCny($strDate = false)
    {
		if ($this->cny_ref)
		{
			if (method_exists($this->cny_ref, 'GetVal'))	return $this->cny_ref->GetVal($strDate);
		}
		return 1.0;
    }
    
    function EstFromPair($fPairVal = false, $fCny = false)
    {
    	if ($fPairVal == false)	$fPairVal = floatval($this->pair_ref->GetPrice());
    	if ($fCny == false)		$fCny = $this->GetDefaultCny();
    	
    	if ($this->IsSymbolA())	$fVal = QdiiGetVal($fPairVal, $fCny, $this->fFactor);
    	else						$fVal = ($fPairVal / $fCny) / $this->fFactor;
		return FundAdjustPosition($this->fRatio, $fVal, ($this->fLastCalibrationVal ? $this->fLastCalibrationVal : $fVal));
    }
    
    function EstToPair($fMyVal = false, $fCny = false)
    {
    	if ($fMyVal == false)	$fMyVal = floatval($this->GetPrice());
    	if ($fCny == false)		$fCny = $this->GetDefaultCny();
    	
		$fVal = FundReverseAdjustPosition($this->fRatio, $fMyVal, ($this->fLastCalibrationVal ? $this->fLastCalibrationVal : $fMyVal));
		if ($this->IsSymbolA())	return QdiiGetPeerVal($fVal, $fCny, $this->fFactor);
		return ($fVal * $fCny) * $this->fFactor;
    }

    function GetPriceRatio($strDate = false)
    {
    	if ($this->pair_ref)
    	{
    		if ($strDate)
    		{
    			$strPrice = $this->GetClose($strDate);
    			$strPair = $this->pair_ref->GetClose($strDate);
    		}
    		else
    		{
    			$strPrice = $this->GetPrice();
    			$strPair = $this->pair_ref->GetPrice();
    		}
    		if ((empty($strPrice) == false) && (empty($strPair) == false))		return floatval($strPrice) / $this->EstFromPair(floatval($strPair), $this->GetDefaultCny($strDate));
    	}
    	return 1.0;
    }
}

class AbPairReference extends MyPairReference
{
    public function __construct($strSymbolA) 
    {
        parent::__construct($strSymbolA, new AbPairSql());
    }
}

class AdrPairReference extends MyPairReference
{
    public function __construct($strAdr) 
    {
        parent::__construct($strAdr, new AdrPairSql());
    }
}

class AhPairReference extends MyPairReference
{
    public function __construct($strSymbolA) 
    {
        parent::__construct($strSymbolA, new AhPairSql());
    }
}

class FundPairReference extends MyPairReference
{
	var $netvalue_ref = false;
	var $realtime_callback;
 
    public function __construct($strSymbol, $callback = false) 
    {
        parent::__construct($strSymbol, new FundPairSql());
        
		if ($this->pair_ref)
		{
			$this->realtime_callback = $callback;
			$strPair = $this->pair_ref->GetSymbol();
			if ($this->pair_ref->IsSinaFuture())
			{
        		if ($this->pair_ref->CheckAdjustFactorTime($this))	$this->AverageCalibraion();
       		}
        	
			if ($this->IsSinaFuture())
			{
				// $this->netvalue_ref = $this; Can NOT do this in __construct
        		if ($this->CheckAdjustFactorTime($this->pair_ref))	$this->AverageCalibraion();
			}
			else	$this->netvalue_ref = new NetValueReference($strSymbol);
        }
    }

    function AverageCalibraion()
    {
		$strStockId = $this->GetStockId();
		$strDate = $this->GetDate();
		$strPrice = $this->GetPrice();
		
		$fFactor = $this->CalcFactor($this->pair_ref->GetPrice(), $strPrice, $strDate);
		$calibration_sql = GetCalibrationSql();
        $calibration_sql->WriteDailyAverage($strStockId, $strDate, strval($fFactor));
        			
        $sql = new LastCalibrationSql();
        $sql->WriteVal($strStockId, $strPrice); 
        $this->LoadCalibration();
    }

	function DailyCalibration()
	{
		$strStockId = $this->GetStockId();
		$netvalue_sql = GetNetValueHistorySql();
		$calibration_sql = GetCalibrationSql();
		$strDate = $netvalue_sql->GetDateNow($strStockId);
		if ($strDate == $calibration_sql->GetDateNow($strStockId))	return;
		
		if ($strNetValue = $netvalue_sql->GetCloseNow($strStockId))
		{
			if ($strPairNetValue = PairNetValueGetClose($this->pair_ref, $strDate))	
			{
				$fFactor = $this->CalcFactor($strPairNetValue, $strNetValue, $strDate);
				$calibration_sql->WriteDaily($strStockId, $strDate, strval($fFactor));
        	
				$this->LoadCalibration();
			}
		}
   	}
    
    function GetNetValueRef()
    {
    	return $this->netvalue_ref ? $this->netvalue_ref : $this;
    }
    
 	function CalcFactor($strPairNetValue, $strNetValue, $strDate)
 	{
 		$fPairNetValue = floatval($strPairNetValue); 
 		$fNetValue = floatval($strNetValue); 
 		if ($this->cny_ref)
 		{
 			$fCny = $this->cny_ref->GetVal($strDate);
 			if ($this->IsSymbolA())	$fNetValue /= $fCny;
 			else					$fNetValue *= $fCny;
 		}
		return $fPairNetValue / $fNetValue;
 	}

    function GetOfficialDate()
    {
        $strOfficialDate = $this->pair_ref->GetDate();
        if ($this->cny_ref)
        {
        	if ($this->cny_ref->GetClose($strOfficialDate) === false)
        	{	// Load last value from database
        		$fund_est_sql = GetFundEstSql();
        		$strOfficialDate = $fund_est_sql->GetDateNow($this->GetStockId());
        	}
        }
    	return $strOfficialDate;
    }
    
    public function GetOfficialNetValue()
    {
        $strOfficialDate = $this->GetOfficialDate();
        $fCny = $this->cny_ref ? $this->cny_ref->GetVal($strOfficialDate) : false;
		if (($strEst = PairNetValueGetClose($this->pair_ref, $strOfficialDate)) == false)
		{
			$strEst = $this->pair_ref->GetPrice();
		}
		
   		$strVal = strval($this->EstFromPair(floatval($strEst), $fCny));
   		StockUpdateEstResult($this->GetStockId(), $strVal, $strOfficialDate);
        return $strVal;
    }

    public function GetFairNetValue()
    {
        $strOfficialDate = $this->GetOfficialDate();
        if ($this->cny_ref)
        {
        	if ($strOfficialDate != $this->cny_ref->GetDate())		return strval($this->EstFromPair($this->pair_ref->GetVal(), $this->cny_ref->GetVal()));
        }
       	if ($strOfficialDate != $this->pair_ref->GetDate())			return strval($this->EstFromPair($this->pair_ref->GetVal()));
    	return false;
    }

    public function GetRealtimeNetValue()
    {
		if ($this->realtime_callback)
		{
			$fCny = $this->cny_ref ? $this->cny_ref->GetVal() : false;
			return strval($this->EstFromPair(call_user_func($this->realtime_callback), $fCny));
		}
   		return false;
    }
}

?>
