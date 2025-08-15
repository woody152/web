<?php

/* (x - x0) / x0 = r * (y - y0) / y0
  	x / x0 - 1 = r * y / y0 - r
   	x = x0 * (r * y / y0 + 1 - r) = r * (x0 * y / y0) + (1 - r) * x0 		### used in AdjustPosition
   	y = y0 * (x / x0 - 1 + r) / r = (y0 * x / x0) / r - y0 * (1 / r - 1)	### used in ReverseAdjustPosition
*/
function FundAdjustPosition($fRatio, $fVal, $fOldVal)
{
	return $fRatio * $fVal + (1.0 - $fRatio) * $fOldVal;
}

function FundReverseAdjustPosition($fRatio, $fVal, $fOldVal)
{
	return $fVal / $fRatio - $fOldVal * (1.0 / $fRatio - 1.0);
}

class FundReference extends MysqlReference
{
    var $stock_ref = false;     // MyStockReference
    var $est_ref = false;       // MyStockRefenrence for fund net value estimation
    var $cny_ref;

    // estimated data
    var $fOfficialNetValue = false;
    var $fFairNetValue = false;
    var $fRealtimeNetValue = false;

    var $strOfficialDate;
    
    public function __construct($strSymbol) 
    {
        parent::__construct($strSymbol);

        if ($this->IsFundA())
        {
            $this->stock_ref = new MyStockReference($strSymbol);
        }
        if ($strStockId = $this->GetStockId())
        {
	       	$cal_sql = GetCalibrationSql();
        	if ($strClose = $cal_sql->GetCloseNow($strStockId))		$this->fFactor = floatval($strClose); 
        }
    }
   
    public function LoadData()
    {
        $this->LoadSinaFundData();
        $this->bConvertGB2312 = true;     // Sina name is GB2312 coded
    }
    
    function GetOfficialDate()
    {
    	return $this->strOfficialDate;
    }
    
    public function GetOfficialNetValue()
    {
    	if ($this->fOfficialNetValue)
    	{
    		return strval($this->fOfficialNetValue);
    	}
    	return false;
    }
    
    public function GetFairNetValue()
    {
    	if ($this->fFairNetValue)
    	{
    		return strval($this->fFairNetValue);
    	}
    	return false;
    }
    
    public function GetRealtimeNetValue()
    {
    	if ($this->fRealtimeNetValue)
    	{
    		return strval($this->fRealtimeNetValue);
    	}
    	return false;
    }
    
    function SetForex($strForex)
    {
        $this->cny_ref = new CnyReference($strForex);
    }

    // Update database
    function UpdateEstNetValue()
    {
   		StockUpdateEstResult($this->GetStockId(), $this->GetOfficialNetValue(), $this->GetOfficialDate());
    }

    function UpdateOfficialNetValue()
    {
		return StockCompareEstResult($this->GetStockId(), $this->GetPrice(), $this->GetDate(), $this->GetSymbol());
    }

    function InsertFundCalibration()
    {
       	$cal_sql = GetCalibrationSql();
    	$cal_sql->WriteDaily($this->GetStockId(), $this->GetDate(), strval($this->fFactor));
    }

    public function GetSymbol()
    {
        if ($this->stock_ref)
        {
            return $this->stock_ref->GetSymbol();
        }
        return parent::GetSymbol();
    }

	public function GetStockId()
    {
        if ($this->stock_ref)
        {
            return $this->stock_ref->GetStockId();
        }
        return parent::GetStockId();
    }

    public function GetPriceDisplay($strDisp = false, $strPrev = false)
    {
   		if ($this->stock_ref)
   		{
   			return $this->stock_ref->GetPriceDisplay($strDisp, $strPrev);
   		}
   		return parent::GetPriceDisplay($strDisp, $strPrev);
    }
    
    public function GetPercentageDisplay($strDivisor = false, $strDividend = false)
    {
   		if ($this->stock_ref)
   		{
   			return $this->stock_ref->GetPercentageDisplay($strDivisor, $strDividend);
   		}
   		return parent::GetPercentageDisplay($strDivisor, $strDividend);
    }
    
    function GetStockRef()
    {
    	return $this->stock_ref;
    }

    function GetEstRef()
    {
    	return $this->est_ref;
    }

    function GetRealtimeRef()
    {
    	return $this->est_ref->GetPairRef();
    }

    function GetCnyRef()
    {
    	return $this->cny_ref;
    }

    function _getCalibrationBaseVal()
    {
    	$strStockId = $this->GetStockId();
       	$cal_sql = GetCalibrationSql();
		$strDate = $cal_sql->GetDateNow($strStockId);
		return floatval(SqlGetNetValueByDate($strStockId, $strDate));
    }
    
    function AdjustPosition($fVal)
    {
		return FundAdjustPosition($this->GetPosition(), $fVal, $this->_getCalibrationBaseVal());
    }
    
    function ReverseAdjustPosition($fVal)
    {
		return FundReverseAdjustPosition($this->GetPosition(), $fVal, $this->_getCalibrationBaseVal());
    }
    
}

?>
