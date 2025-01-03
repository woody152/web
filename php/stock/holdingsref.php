<?php

class HoldingsReference extends MyStockReference
{
	var $nav_ref;
    var $uscny_ref;
    var $hkcny_ref;
    
    var $ar_holdings_ref = array();
    var $bRealtime = false;

    var $strNav;
    var $strHoldingsDate;
    var $arHoldingsRatio = array();
    
    public function __construct($strSymbol) 
    {
        parent::__construct($strSymbol);
       	$this->nav_ref = new NetValueReference($strSymbol);
   		$this->hkcny_ref = new CnyReference('HKCNY');
   		$this->uscny_ref = new CnyReference('USCNY');

        $strStockId = $this->GetStockId();
    	$date_sql = new HoldingsDateSql();
    	if ($this->strHoldingsDate = $date_sql->ReadDate($strStockId))
    	{
			if ($this->strNav = SqlGetNavByDate($strStockId, $this->strHoldingsDate))
			{
				$holdings_sql = GetHoldingsSql();
				$this->arHoldingsRatio = $holdings_sql->GetHoldingsArray($strStockId);
				$sql = GetStockSql();
				foreach ($this->arHoldingsRatio as $strId => $strRatio)
				{
					$this->ar_holdings_ref[] = $this->_selectReference($sql->GetStockSymbol($strId));
				}
			}
			else	
			{
				DebugString(__CLASS__.'->'.__FUNCTION__.': Missing NAV on '.$this->strHoldingsDate);
//				$nav_ref = new NetValueReference($strSymbol);
//				SqlSetNav($strStockId, $this->strHoldingsDate, $nav_ref->GetPrevPrice());
//				if ($this->strHoldingsDate == $nav_ref->GetDate())		SqlSetNav($strStockId, $this->strHoldingsDate, $nav_ref->GetPrice());
			}
    	}
    }

    function _selectReference($strSymbol)
    {
    	if (SqlGetFundPair($strSymbol))
    	{
    		$this->bRealtime = true;
    		return new FundPairReference($strSymbol);
    	}	
    	return new MyStockReference($strSymbol);
    }
    
    function GetNavRef()
    {
    	return $this->nav_ref;
    }
    
    function GetCnyRef()
    {
    	return $this->uscny_ref;
    }
    
    function GetHkcnyRef()
    {
    	return $this->hkcny_ref;
    }
    
    function GetHoldingsDate()
    {
    	return $this->strHoldingsDate;
    }
    
    function GetHoldingsRatioArray()
    {
    	return $this->arHoldingsRatio;
    }
    
    function GetHoldingRefArray()
    {
    	return $this->ar_holdings_ref;
    }
    
    function GetAdjustHkd($strDate = false)
    {
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
    
    function GetAdjustCny($strDate = false)
    {
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
    function _estNav($strDate = false, $bRealtime = false)
    {
//    	$arStrict = GetSecondaryListingArray();    	
    	$fAdjustHkd = $this->GetAdjustHkd($strDate);
		$fAdjustCny = $this->GetAdjustCny($strDate);
    	
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
				if ($ref->IsSymbolA())		$fChange *= $fAdjustCny;
				else if ($ref->IsSymbolH())	$fChange *= $fAdjustHkd; 
				$fTotalChange += $fChange;
//				DebugString(__CLASS__.'->'.__FUNCTION__.': '.$ref->GetSymbol().' '.strval_round($fChange, 4), true);
			}
		}
		
		$fTotalChange -= $fTotalRatio;
		$fTotalChange *= RefGetPosition($this);

		$fNewNav = floatval($this->strNav) * (1.0 + $fTotalChange);
		if ($this->IsFundA())		$fNewNav /= $fAdjustCny;
		return $fNewNav; 
    }

    function GetNavChange()
    {
    	$fNav = $this->_estNav();
//    	DebugString(__CLASS__.'->'.__FUNCTION__.': '.strval($fNav).' '.$this->strNav, true);
    	return $fNav / floatval($this->strNav);
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
    
    function GetOfficialDate()
    {
   		$strDate = $this->GetDate();
    	if ($this->IsFundA())
    	{
			if ($str = $this->_getEstDate())		$strDate = $str;
    		
    		if ($this->uscny_ref->GetClose($strDate) === false)
    		{   // Load last value from database
    			$fund_est_sql = GetFundEstSql();
    			$strDate = $fund_est_sql->GetDatePrev($this->GetStockId(), $strDate);
    		}
    	}
    	return $strDate;
    }
    
    public function GetOfficialNav()
    {
    	$strDate = $this->GetOfficialDate();
    	$strNav = strval($this->_estNav($strDate));
   		StockUpdateEstResult($this->GetStockId(), $strNav, $strDate);
   		return $strNav;
    }

    public function GetFairNav()
    {
    	$strDate = $this->GetOfficialDate(); 
		if (($this->uscny_ref->GetDate() != $strDate) || ($this->_getEstDate() != $strDate))
		{
			return strval($this->_estNav());
		}
		return false;
    }

    public function GetRealtimeNav()
    {
    	if ($this->bRealtime)		return strval($this->_estNav(false, true));
   		return false;
    }
}

?>
