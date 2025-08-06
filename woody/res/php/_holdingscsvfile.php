<?php
require_once('../../php/csvfile.php');

class _HoldingsCsvFile extends DebugCsvFile
{
	var $strStockId;
	var $strDate = false;

    var $fSum;
    var $fUSDCNY;
    var $fHKDCNY;
    var $fUSDHKD;
    
    public function __construct($strFileName, $strStockId) 
    {
        parent::__construct($strFileName);
        
        $this->fSum = 0.0;
        $this->strStockId = $strStockId;
    }
    
    function InsertHolding($strSymbol, $strName, $strRatio)
    {
        $strSymbol = str_replace('/', '.', $strSymbol);	// BRK/B -> BRK.B
        $sql = GetStockSql();
		$sql->InsertSymbol($strSymbol, $strName);
    	if ($strStockId = $sql->GetId($strSymbol))
    	{
    		$his_sql = GetStockHistorySql();
    		if ($his_sql->GetRecord($strStockId, $this->strDate) == false)
    		{
    			$this->DebugReport(GetMyStockLink($strSymbol).' missing data on '.$this->strDate);
//    			UpdateYahooHistoryChart(new MyStockReference($strSymbol));
    		}
        
    		$holdings_sql = GetHoldingsSql();
			return $holdings_sql->InsertHolding($this->strStockId, $strStockId, $strRatio);
		}
		return false;
    }

    function DeleteAllHoldings()
    {
   		$holdings_sql = GetHoldingsSql();
		$holdings_sql->DeleteAll($this->strStockId);
    }
    
    function UpdateHoldingsDate()
    {
    	if ($this->strDate)
    	{
    		DebugString(__CLASS__.'->'.__FUNCTION__.' '.$this->strDate);
    		$date_sql = new HoldingsDateSql();
    		return $date_sql->WriteDate($this->strStockId, $this->strDate);
    	}
    	return false;
    }
                               
    function GetDate()
    {
    	return $this->strDate;
    }
    
    public function SetDate($strDate)
    {
		DebugString(__CLASS__.'->'.__FUNCTION__.': '.$strDate);
    	$this->strDate = $strDate;
    }

    function CalcCurrency($strDate)
    {
    	$strDebug = __CLASS__.'->'.__FUNCTION__.': '.$strDate;
    	$sql = GetStockSql();
        $strUscnyId = $sql->GetId('USCNY');
        $strHkcnyId = $sql->GetId('HKCNY');
        $netvalue_sql = GetNetValueHistorySql();
        
        $str = ($strUSDCNY = $netvalue_sql->GetClose($strUscnyId, $strDate)) ? $strUSDCNY : $netvalue_sql->GetCloseNow($strUscnyId);
        $this->fUSDCNY = floatval($str);
        $strDebug .= ' '.$str;
        
        $str = ($strHKDCNY = $netvalue_sql->GetClose($strHkcnyId, $strDate)) ? $strHKDCNY : $netvalue_sql->GetCloseNow($strHkcnyId);
        $this->fHKDCNY = floatval($str);
        $strDebug .= ' '.$str;
        
       	$this->fUSDHKD = $this->fUSDCNY / $this->fHKDCNY;
		DebugString($strDebug);
    }
/*    
    function GetUSDCNY()
    {
    	return $this->fUSDCNY;
    }
    
    function GetHKDCNY()
    {
    	return $this->fHKDCNY;
    }

    function GetUSDHKD()
    {
    	return $this->fUSDHKD;
    }
*/    
    function AddSum($fVal)
    {
    	$this->fSum += $fVal;
    }
    
    function GetSum()
    {
    	return $this->fSum;
    }
    
    function GetMarketVal($strHolding, $iQuantity)
    {
		if ($strStockId = SqlGetStockId($strHolding))
		{
			$fForex = is_numeric($strHolding) ? $this->fHKDCNY : $this->fUSDCNY;
			$his_sql = GetStockHistorySql();
			return $iQuantity * floatval($his_sql->GetAdjClose($strStockId, $this->strDate)) * $fForex;
		}
		DebugString('GetMarketVal failed with '.$strHolding);
		return 0.0;
    }
}

?>
