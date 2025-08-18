<?php

/*
function SqlSetNetValue($strStockId, $strDate, $strNetValue)
{
	$net_sql = GetNetValueHistorySql();
	return $net_sql->InsertDaily($strStockId, $strDate, $strNetValue);
}
*/

function RefAdjustForex($ref, $fAdjustHKD, $fAdjustUSD)
{
	if ($ref->IsSymbolA())		$fAdjust = 1.0;
	else if ($ref->IsSymbolH())	$fAdjust = $fAdjustHKD;
	else						$fAdjust = $fAdjustUSD;
	return $fAdjust;
}

class HoldingsReference extends MyStockReference
{
	var $netvalue_ref;
    var $uscny_ref = false;
    var $usdcny_ref = false;
    var $hkcny_ref = false;
    var $hkdcny_ref = false;
    
    var $ar_holdings_ref = array();
    var $ar_realtime_ref = array();

    var $strNetValue;
    var $strHoldingsDate;
    var $arHoldingsRatio = array();
    
    public function __construct($strSymbol) 
    {
        parent::__construct($strSymbol);
       	$this->netvalue_ref = new NetValueReference($strSymbol);
//       	$this->uscny_ref = new CnyReference('USCNY');

        $strStockId = $this->GetStockId();
    	$date_sql = new HoldingsDateSql();
    	if ($this->strHoldingsDate = $date_sql->ReadDate($strStockId))
    	{
			if ($this->strNetValue = SqlGetNetValueByDate($strStockId, $this->strHoldingsDate))
			{
				$holdings_sql = GetHoldingsSql();
				$this->arHoldingsRatio = $holdings_sql->GetHoldingsArray($strStockId);
				$sql = GetStockSql();
				foreach ($this->arHoldingsRatio as $strId => $strRatio)
				{
					$holding_ref = $this->_selectReference($sql->GetStockSymbol($strId));
					$this->ar_holdings_ref[] = $holding_ref;
					if ($holding_ref->IsSymbolA())
					{
					}
					else if ($holding_ref->IsSymbolH())
					{
						if ($this->hkcny_ref === false)		$this->hkcny_ref = new CnyReference('HKCNY');
						if ($this->hkdcny_ref === false)	$this->hkdcny_ref = new MyStockReference('fx_shkdcny');
					}
					else
					{
						if ($this->uscny_ref === false)		$this->uscny_ref = new CnyReference('USCNY');
						if ($this->usdcny_ref === false)	$this->usdcny_ref = new MyStockReference('fx_susdcny');
					}
				}
			}
			else	
			{
				DebugString(__CLASS__.'->'.__FUNCTION__.': Missing net value on '.$this->strHoldingsDate);
//				$netvalue_ref = new NetValueReference($strSymbol);
//				SqlSetNetValue($strStockId, $this->strHoldingsDate, $netvalue_ref->GetPrevPrice());
//				if ($this->strHoldingsDate == $netvalue_ref->GetDate())		SqlSetNetValue($strStockId, $this->strHoldingsDate, $netvalue_ref->GetPrice());
			}
    	}
    }

    function _selectReference($strSymbol)
    {
    	if (SqlGetFundPair($strSymbol))
    	{
    		$ref = new FundPairReference($strSymbol);
    		$this->ar_realtime_ref[] = $ref;
    		return $ref;
    	}	
    	return new MyStockReference($strSymbol);
    }
    
    function GetNetValue()
    {
    	return $this->strNetValue;
    }
    
    function SetNetValue($strVal)
    {
    	$this->strNetValue = $strVal;
    }
    
    function GetNetValueRef()
    {
    	return $this->netvalue_ref;
    }
    
    function GetCnyRef()
    {
    	return $this->uscny_ref;
    }
    
    function GetUsdcnyRef()
    {
    	return $this->usdcny_ref;
    }
    
    function GetHkcnyRef()
    {
    	return $this->hkcny_ref;
    }
    
    function GetHkdcnyRef()
    {
    	return $this->hkdcny_ref;
    }
    
    function GetHoldingsDate()
    {
    	return $this->strHoldingsDate;
    }
    
    function SetHoldingsDate($strDate)
    {
    	$this->strHoldingsDate = $strDate;
    }
    
    function GetHoldingsRatioArray()
    {
    	return $this->arHoldingsRatio;
    }
    
    function SetHoldingsRatioArray($ar)
    {
    	$iCount = 0;
		foreach ($this->arHoldingsRatio as $strId => $strRatio)
		{
			$this->arHoldingsRatio[$strId] = $ar[$iCount];
			$iCount ++;
		}
    }
    
    function GetHoldingRefArray()
    {
    	return $this->ar_holdings_ref;
    }
    
    function GetHoldingDisplay()
    {
    	$str = '';
    	foreach ($this->ar_holdings_ref as $ref)	$str .= $ref->GetSymbol().'*'.$this->arHoldingsRatio[$ref->GetStockId()].';';
    	return rtrim($str, ';');
    }
    
    function GetRealtimeRefArray()
    {
    	return $this->ar_realtime_ref;
    }
    
    function UseRealtimeEst()
    {
    	return count($this->ar_realtime_ref) > 0 ? true : false;
    }

/*
    function GetAdjustUSD($strDate = false)
    {
    	if ($this->uscny_ref === false)		return 1.0;
    	
		$fUSDCNY = floatval($this->uscny_ref->GetPrice());
		if ($strOldUSDCNY = $this->uscny_ref->GetClose($this->strHoldingsDate))
		{
			$fOldUSDCNY = floatval($strOldUSDCNY);
		}
		else
		{
			$fOldUSDCNY = $fUSDCNY;
		}
		
		if ($strDate)
		{
			if ($strUSDCNY = $this->uscny_ref->GetClose($strDate))		$fUSDCNY = floatval($strUSDCNY);
		}
		return $fOldUSDCNY / $fUSDCNY;
    }
    
    function GetAdjustHkd($strDate = false)
    {
    	if ($this->hkcny_ref === false)		return 1.0;
    	
        if ($strHKDCNY = $this->hkcny_ref->GetClose($this->strHoldingsDate))
        {
        	$fOldUSDHKD = floatval($this->uscny_ref->GetClose($this->strHoldingsDate)) / floatval($strHKDCNY);
        }
        else
        {
        	return 1.0;
        }
        
		$fUSDHKD = floatval($this->uscny_ref->GetPrice()) / floatval($this->hkcny_ref->GetPrice());
		if ($strDate)	
		{
			if ($strHKDCNY = $this->hkcny_ref->GetClose($strDate))	
			{
				if ($strUSDCNY = $this->uscny_ref->GetClose($strDate))		$fUSDHKD = floatval($strUSDCNY) / floatval($strHKDCNY);
			}
		}
		return $fOldUSDHKD / $fUSDHKD;
    }
*/
    
    function _adjustToCNY($cny_ref, $strDate = false)
    {
    	if ($cny_ref === false)		return 1.0;
    	
		$fCNY = floatval($cny_ref->GetPrice());
		if ($strOldCNY = $cny_ref->GetClose($this->strHoldingsDate))
		{
			$fOldCNY = floatval($strOldCNY);
		}
		else
		{
			$fOldCNY = $fCNY;
		}
		
		if ($strDate)
		{
			if ($strCNY = $cny_ref->GetClose($strDate))		$fCNY = floatval($strCNY);
		}
		return $fOldCNY / $fCNY;
    }
    
    function GetAdjustUSD($strDate = false)
    {
		return $this->_adjustToCNY($this->uscny_ref, $strDate);
    }
    
    function GetAdjustHKD($strDate = false)
    {
		return $this->_adjustToCNY($this->hkcny_ref, $strDate);
    }
    
/*
    function _getStrictRef($strSymbol)
    {
    	foreach ($this->ar_holdings_ref as $ref)
		{
			if ($ref->GetSymbol() == $strSymbol)	return $ref;
		}
		return false;
	}
*/					
    // (x - x0) / x0 = sum{ r * (y - y0) / y0} 
    function _estNetValue($strDate = false, $bRealtime = false)
    {
//    	$arStrict = GetSecondaryListingArray();    	
    	$fAdjustHKD = $this->GetAdjustHKD($strDate);
		$fAdjustUSD = $this->GetAdjustUSD($strDate);
    	
		$his_sql = GetStockHistorySql();
		$fTotalChange = 0.0;
		$fTotalRatio = 0.0;
		foreach ($this->ar_holdings_ref as $ref)
		{
			$strStockId = $ref->GetStockId();
			$fRatio = floatval($this->arHoldingsRatio[$strStockId]) / 100.0;
			$fTotalRatio += $fRatio;
/*			
			if ($bStrict)
			{
				$strSymbol = $ref->GetSymbol();
				if (isset($arStrict[$strSymbol]))
				{	// Hong Kong secondary listings
					if ($us_ref = $this->_getStrictRef($arStrict[$strSymbol]))
					{
						$ref = $us_ref;
						$strStockId = $ref->GetStockId();
					}
					else															DebugString('Missing '.$arStrict[$strSymbol], true);
				}
			}
*/			
			$strPrice = $ref->GetPrice();
			if ($strDate)
			{
				if ($str = $his_sql->GetAdjClose($strStockId, $strDate))		$strPrice = $str;
			}
			$fPrice = floatval($strPrice);
			if ($bRealtime)
			{
				if (method_exists($ref, 'EstFromPair'))		$fPrice = $ref->EstFromPair();
			}
			
			if ($strAdjClose = $his_sql->GetAdjClose($strStockId, $this->strHoldingsDate))
			{
				$fChange = $fRatio * $fPrice / floatval($strAdjClose);
				$fChange /= RefAdjustForex($ref, $fAdjustHKD, $fAdjustUSD);
				$fTotalChange += $fChange;
//				DebugString(__CLASS__.'->'.__FUNCTION__.': '.$ref->GetSymbol().' '.strval_round($fChange, 4), true);
			}
		}
		
		$fTotalChange -= $fTotalRatio;
		$fTotalChange *= $this->GetPosition();

		$fNewNetValue = floatval($this->strNetValue) * (1.0 + $fTotalChange);
		$fNewNetValue *= RefAdjustForex($this, $fAdjustHKD, $fAdjustUSD);
		return $fNewNetValue; 
    }

    function GetNetValueChange()
    {
    	$fNetValue = $this->_estNetValue();
//    	DebugString(__CLASS__.'->'.__FUNCTION__.': '.strval($fNetValue).' '.$this->strNetValue, true);
    	return $fNetValue / floatval($this->strNetValue);
    }
    
    function _getEstDate()
    {
    	$strH = false;
   		foreach ($this->ar_holdings_ref as $ref)
   		{
   			if ($ref->IsSymbolH())
   			{
    			$strH = $ref->GetDate();
    			break;
   			}
   		}
   		
    	$strUS = false;
   		foreach ($this->ar_holdings_ref as $ref)
   		{
   			if ($ref->IsSymbolUS())
   			{
    			$strUS = $ref->GetDate();
    			break;
   			}
   		}
   		
   		if ($strH)
   		{
   			if (($strUS === false) || ($strH == $strUS) || (strtotime($strH) < strtotime($strUS)))		return $strH;
   		}
		return $strUS;
    }
    
    function _needAdjustOfficialDate($strDate)
    {
		if ($this->uscny_ref)
		{
			if ($this->uscny_ref->GetClose($strDate) === false)		return true;
		}
		else if ($this->hkcny_ref)
		{
			if ($this->hkcny_ref->GetClose($strDate) === false)		return true;
		}
		return false;
    }
    
    function GetOfficialDate()
    {
   		$strDate = $this->GetDate();
    	if ($this->IsFundA())
    	{
			if ($str = $this->_getEstDate())		$strDate = $str;

			$fund_est_sql = GetFundEstSql();
			if ($this->_needAdjustOfficialDate($strDate))		$strDate = $fund_est_sql->GetDatePrev($this->GetStockId(), $strDate);	// Load last value from database
    	}
    	return $strDate;
    }
    
    public function GetOfficialNetValue()
    {
    	$strDate = $this->GetOfficialDate();
    	$strVal = strval($this->_estNetValue($strDate));
   		StockUpdateEstResult($this->GetStockId(), $strVal, $strDate);
   		return $strVal;
    }
    
    function _needFairNetValue()
    {
    	$strDate = $this->GetOfficialDate();
    	if ($this->_getEstDate() != $strDate)	return true;
		if ($this->uscny_ref)
		{
			if ($this->uscny_ref->GetDate() != $strDate)	return true;
		}
		else if ($this->hkcny_ref)
		{
			if ($this->hkcny_ref->GetDate() != $strDate)	return true;
		}
		return false;
    }

    public function GetFairNetValue()
    {
		if ($this->_needFairNetValue())
		{
			return strval($this->_estNetValue());
		}
		return false;
    }

    public function GetRealtimeNetValue()
    {
    	if ($this->UseRealtimeEst())		return strval($this->_estNetValue(false, true));
   		return false;
    }
}

?>
