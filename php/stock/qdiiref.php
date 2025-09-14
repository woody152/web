<?php

define('POSITION_EST_LEVEL', '4.0');

function QdiiGetCalibration($strEst, $strCNY, $strNetValue)
{
	return floatval($strEst) * floatval($strCNY) / floatval($strNetValue);
}

function QdiiGetVal($fEst, $fCny, $fFactor)
{
	return $fEst * $fCny / $fFactor;
}

function QdiiGetPeerVal($fQdii, $fCny, $fFactor)
{
	return $fQdii * $fFactor / $fCny;
}

// (est * cny / estPrev * cnyPrev - 1) * position = (nv / nvPrev - 1) 
function QdiiGetStockPosition($strEstPrev, $strEst, $strPrev, $strNetValue, $strCnyPrev, $strCny, $strInput = POSITION_EST_LEVEL)
{
	$fEst = StockGetPercentage($strEstPrev, $strEst);
	if (($fEst !== false) && (abs($fEst) > floatval($strInput)))
	{
		$f = StockGetPercentage(strval(floatval($strEstPrev) * floatval($strCnyPrev)), strval(floatval($strEst) * floatval($strCny)));
		if (($f !== false) && ($f != 0.0))
		{
			$fVal = StockGetPercentage($strPrev, $strNetValue) / $f;
			if ($fVal > 0.1)
			{
				return number_format($fVal, 2);
			}
		}
	}
	return false;
}

// https://markets.ft.com/data/indices/tearsheet/charts?s=SPGOGUP:REU
function QdiiGetEstSymbol($strSymbol)
{
    if (in_arrayXopQdii($strSymbol))				return 'XOP';	// '^SPSIOP'
    else if ($strSymbol == 'SZ162719')   			return 'IEO'; // '^DJSOEP'
    else if ($strSymbol == 'SZ162415')   			return 'XLY';	// '^IXY'
    else if (in_arrayOilQdii($strSymbol)) 			return 'USO';
    else if ($strSymbol == 'SZ160140')   			return 'VNQ';	// SCHH
    else if ($strSymbol == 'SZ160416')   			return 'IXC';	// '^SPGOGUP'
    else if ($strSymbol == 'SZ161126')   			return 'RSPH';
    else if (in_arrayXbiQdii($strSymbol))   		return 'XBI';
    else if ($strSymbol == 'SZ161128')   			return 'XLK';
    else if ($strSymbol == 'SZ163208')   			return 'XLE';
    else if ($strSymbol == 'SZ164824')   			return 'INDA';
    else if ($strSymbol == 'SZ164906')   			return 'KWEB';
    else if (in_arrayCommodityQdii($strSymbol))		return 'GSG';
    else if (in_arraySpyQdii($strSymbol))			return '^GSPC';	// 'SPY';
    else if (in_arrayQqqQdii($strSymbol))			return '^NDX';	// 'QQQ';
    else if ($strSymbol == 'SH501300')   			return 'AGG';
    else if ($strSymbol == 'SH513290')   			return 'IBB';
    else if ($strSymbol == 'SH513400')   			return '^DJI';
    else 
        return false;
}

function QdiiHkGetEstSymbol($strSymbol)
{
    if ($strSymbol == 'SH501025')   		 									return 'SH000869';	// '03143'
    else if (in_arrayTechQdiiHk($strSymbol))									return '^HSTECH';
    else if (in_arrayHangSengQdiiHk($strSymbol) || $strSymbol == 'SZ161124')	return '^HSI';		// '02800'
    else if (in_arrayHSharesQdiiHk($strSymbol))									return '^HSCE';		// '02828'
    else 
        return false;
}

function QdiiJpGetEstSymbol($strSymbol)
{
    if ($strSymbol == 'SH513800')   		 		return 'znb_TPX';
	else if (in_arrayNkyQdiiJp($strSymbol))			return 'znb_NKY';
    else 
        return false;
}

function QdiiEuGetEstSymbol($strSymbol)
{
    if ($strSymbol == 'SH513080')   		 		return 'znb_CAC';
	else if (in_arrayDaxQdiiEu($strSymbol))			return 'znb_DAX';
    else 
        return false;
}

class _QdiiReference extends FundReference
{
    var $strOfficialCNY = false;
    
    public function __construct($strSymbol, $strCny)
    {
        parent::__construct($strSymbol);
        $this->SetForex($strCny);
    }
    
    function _getEstNetValue($est_ref, $strDate)
    {
		if ($str = SqlGetNetValueByDate($est_ref->GetStockId(), $strDate))
        {
        	return $str;
        }
        return false;
    }

    function _getEstPrice($est_ref, $strDate)
    {
        $str = $est_ref->GetPrice();
        if (empty($str))
        {	// SH000869 bug fix
        	$his_sql = GetStockHistorySql();
        	$str = $his_sql->GetClosePrev($est_ref->GetStockId(), $strDate);
        }
        return $str;
    }

    function _getOfficialEstVal($strDate)
    {
       	$est_ref = $this->GetEstRef();
		if ($str = $this->_getEstNetValue($est_ref, $strDate))		return $str;
       	if (method_exists($est_ref, 'GetHoldingsDate'))				return strval($est_ref->_estNetValue());
        return $this->_getEstPrice($est_ref, $strDate);
    }

    function _getFairEstVal($strDate)
    {
       	$est_ref = $this->GetEstRef();
       	if (method_exists($est_ref, 'GetHoldingsDate'))				return strval($est_ref->_estNetValue());
       	if ($str = $this->_getEstNetValue($est_ref, $strDate))		return $str;
        return $this->_getEstPrice($est_ref, $strDate);
    }
    
    function EstNetValue()
    {
		$this->AdjustFactor();
        
       	$cny_ref = $this->GetCnyRef();
       	$est_ref = $this->GetEstRef();
        if ($est_ref == false)    return;
        
        $strDate = $est_ref->GetDate();
        if ($this->strOfficialCNY = $cny_ref->GetClose($strDate))
        {
        	$this->fOfficialNetValue = $this->GetQdiiValue($this->_getOfficialEstVal($strDate), $this->strOfficialCNY);
            $this->strOfficialDate = $strDate;
            $this->UpdateEstNetValue();
        }
        else
        {   // Load last value from database
			$fund_est_sql = GetFundEstSql();
            if ($record = $fund_est_sql->GetRecordNow($this->GetStockId()))
            {
                $this->fOfficialNetValue = floatval($record['close']);
                $this->strOfficialDate = $record['date'];
                $this->strOfficialCNY = $cny_ref->GetClose($this->strOfficialDate);
            }
            else
            {
                $this->strOfficialCNY = $cny_ref->GetPrice();
            }
        }
        
        $this->EstRealtimeNetValue();
    }

    function EstRealtimeNetValue()
    {
       	$est_ref = $this->GetEstRef();
        if ($est_ref == false)    return;
        
        $strDate = $est_ref->GetDate();
       	$cny_ref = $this->GetCnyRef();
       	if ($this->IsEtfA() || ($cny_ref->GetDate() != $this->strOfficialDate) || ($strDate != $this->strOfficialDate))		$this->fFairNetValue = $this->GetQdiiValue($this->_getFairEstVal($strDate));
		if ($realtime_ref = $this->GetRealtimeRef())															           	$this->fRealtimeNetValue = $this->GetQdiiValue(strval($est_ref->EstFromPair()));
    }

    function AdjustFactor()
    {
        if ($this->UpdateOfficialNetValue())
        {
            $strDate = $this->GetDate();
	       	$cny_ref = $this->GetCnyRef();
            if ($strCNY = $cny_ref->GetClose($strDate))
            {
            	$est_ref = $this->GetEstRef();
	        	if (($strEst = $this->_getEstNetValue($est_ref, $strDate)) === false)
	        	{
	           		if (($strEst = $est_ref->GetClose($strDate)) === false)		return false;
                }
        
//                $this->fFactor = floatval($strEst) * floatval($strCNY) / floatval($this->GetPrice());
				$this->fFactor = QdiiGetCalibration($strEst, $strCNY, $this->GetPrice());
                $this->InsertFundCalibration();
                return $this->fFactor;
            }
        }
        return false;
    }

    function GetQdiiValue($strEst, $strCNY = false)
    {
    	if ($strCNY == false)
    	{
	       	$cny_ref = $this->GetForexRef();
	       	$strCNY = $cny_ref->GetPrice();
    	}
    	
    	if ($this->fFactor)
    	{
//    		$fVal = floatval($strEst) * floatval($strCNY) / $this->fFactor;
			$fVal = QdiiGetVal(floatval($strEst), floatval($strCNY), $this->fFactor);
    		return $this->AdjustPosition($fVal);
    	}
    	return 0.0;
    }
    
    function GetEstValue($strQdii)
    {
       	$cny_ref = $this->GetCnyRef();
       	$strCNY = $cny_ref->GetPrice();
       	$fQdii = $this->ReverseAdjustPosition(floatval($strQdii));
       	return strval(QdiiGetPeerVal($fQdii, floatval($strCNY), $this->fFactor));
//        return strval($this->ReverseAdjustPosition(floatval($strQdii)) * $this->fFactor / floatval($strCNY));
    }
    
    function GetEstQuantity($iQdiiQuantity)
    {
        return intval($iQdiiQuantity / $this->fFactor);
    }

    function GetQdiiQuantity($iEstQuantity)
    {
        return intval($iEstQuantity * $this->fFactor);
    }
}

class QdiiReference extends _QdiiReference
{
    public function __construct($strSymbol)
    {
        parent::__construct($strSymbol, 'USCNY');
        
        if ($strEstSymbol = QdiiGetEstSymbol($strSymbol))
        {
        	if (SqlCountHoldings($strEstSymbol) > 0)	$this->est_ref = new HoldingsReference($strEstSymbol);	// KWEB
        	else										$this->est_ref = new FundPairReference($strEstSymbol);
        }
        $this->forex_ref = new MyStockReference('fx_susdcny');
        $this->EstNetValue();
    }
}

class QdiiHkReference extends _QdiiReference
{
    public function __construct($strSymbol)
    {
        parent::__construct($strSymbol, 'HKCNY');
        
        if ($strEstSymbol = QdiiHkGetEstSymbol($strSymbol))
        {
            $this->est_ref = new FundPairReference($strEstSymbol);
        }
        $this->forex_ref = new MyStockReference('fx_shkdcny');
        $this->EstNetValue();
    }
}

class QdiiJpReference extends _QdiiReference
{
    public function __construct($strSymbol)
    {
        parent::__construct($strSymbol, 'JPCNY');
        
        if ($strEstSymbol = QdiiJpGetEstSymbol($strSymbol))
        {
            $this->est_ref = new FundPairReference($strEstSymbol);
        }
        $this->forex_ref = new MyStockReference('fx_sjpycny');
        $this->EstNetValue();
    }
}

class QdiiEuReference extends _QdiiReference
{
    public function __construct($strSymbol)
    {
        parent::__construct($strSymbol, 'EUCNY');
        
        if ($strEstSymbol = QdiiEuGetEstSymbol($strSymbol))
        {
            $this->est_ref = new FundPairReference($strEstSymbol);
        }
        $this->forex_ref = new MyStockReference('fx_seurcny');
        $this->EstNetValue();
    }
}

?>
