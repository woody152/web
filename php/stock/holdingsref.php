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
    
    var $strEstDate;
    
    var $strOfficialNetValue = false;
    var $strFairNetValue = false;
    var $strRealtimeNetValue = false;
    
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
				$strDateH = false;
				$strDateUS = false;
				$holdings_sql = GetHoldingsSql();
				$this->arHoldingsRatio = $holdings_sql->GetHoldingsArray($strStockId);
				$sql = GetStockSql();
				$fund_pair_sql = GetFundPairSql();
				foreach ($this->arHoldingsRatio as $strId => $strRatio)
				{
					$strHoldingSymbol = $sql->GetStockSymbol($strId);
					if ($fund_pair_sql->GetPairSymbol($strHoldingSymbol))
					{
						$holding_ref = new FundPairReference($strHoldingSymbol);
						$this->ar_realtime_ref[] = $holding_ref;
					}
					else
					{
						$holding_ref = new MyStockReference($strHoldingSymbol);
					}
					$this->ar_holdings_ref[] = $holding_ref;
					
					if ($holding_ref->IsSymbolA())
					{
					}
					else if ($holding_ref->IsSymbolH())
					{
						if ($this->hkcny_ref === false)		$this->hkcny_ref = new CnyReference('HKCNY');
						if ($this->hkdcny_ref === false)	$this->hkdcny_ref = new MyStockReference('fx_shkdcny');
						if ($strDateH === false)			$strDateH = $holding_ref->GetDate();
					}
					else
					{
						if ($this->uscny_ref === false)		$this->uscny_ref = new CnyReference('USCNY');
						if ($this->usdcny_ref === false)	$this->usdcny_ref = new MyStockReference('fx_susdcny');
						if ($strDateUS === false)			$strDateUS = $holding_ref->GetDate();
					}
				}
				
				if ($strDateH)
				{
					if (($strDateUS === false) || ($strDateH == $strDateUS) || (strtotime($strDateH) < strtotime($strDateUS)))		$this->strEstDate = $strDateH;
				}
				else
				{
					$this->strEstDate = $strDateUS;
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

    function _adjustToCNY($old_ref, $new_ref, $strDate = false)
    {
    	if ($old_ref === false)		return 1.0;
    	
		if ($strOldCNY = $old_ref->GetClose($this->strHoldingsDate))
		{
			$fOldCNY = floatval($strOldCNY);
		}
		else
		{
			$fOldCNY = floatval($old_ref->GetPrice());;
		}
		
		$fCNY = floatval($new_ref->GetPrice());
		if ($strDate)
		{
			if ($strCNY = $new_ref->GetClose($strDate))		$fCNY = floatval($strCNY);
		}
		return $fOldCNY / $fCNY;
    }
    
    function GetAdjustUSD($strDate = false)
    {
    	if (($strDate === false) && $this->IsEtfA())	return $this->_adjustToCNY($this->uscny_ref, $this->usdcny_ref);
		return $this->_adjustToCNY($this->uscny_ref, $this->uscny_ref, $strDate);
    }
    
    function GetAdjustHKD($strDate = false)
    {
    	if (($strDate === false) && $this->IsEtfA())	return $this->_adjustToCNY($this->hkcny_ref, $this->hkdcny_ref);
		return $this->_adjustToCNY($this->hkcny_ref, $this->hkcny_ref, $strDate);
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
			if ($this->strEstDate)		$strDate = $this->strEstDate;

			$fund_est_sql = GetFundEstSql();
			if ($this->_needAdjustOfficialDate($strDate))		$strDate = $fund_est_sql->GetDatePrev($this->GetStockId(), $strDate);	// Load last value from database
    	}
    	return $strDate;
    }
    
    public function GetOfficialNetValue()
    {
    	if ($this->strOfficialNetValue === false)
    	{
    		$strDate = $this->GetOfficialDate();
    		$this->strOfficialNetValue = strval($this->_estNetValue($strDate));
    		StockUpdateEstResult($this->GetStockId(), $this->strOfficialNetValue, $strDate);
    	}
   		return $this->strOfficialNetValue;
    }
    
    function _needFairNetValue()
    {
    	if ($this->IsEtfA())								return true;
    	
    	$strDate = $this->GetOfficialDate();
    	if ($this->strEstDate != $strDate)					return true;
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
    	if ($this->strFairNetValue === false)
    	{
    		if ($this->_needFairNetValue())		$this->strFairNetValue = strval($this->_estNetValue());
    	}
		return $this->strFairNetValue;
    }

    public function GetRealtimeNetValue()
    {
    	if ($this->strRealtimeNetValue === false)
    	{
    		if ($this->UseRealtimeEst())	$this->strRealtimeNetValue = strval($this->_estNetValue(false, true));
    	}
   		return $this->strRealtimeNetValue;
    }
}

?>
