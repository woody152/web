<?php
require_once('../../php/csvfile.php');

class _InvescoCsvFile extends DebugCsvFile
{
	var $iCount = 0;
	var $ref = false;
	
	var $strSymbol;
	var $strStockId;
	var $oldest_ymd;
	var $netvalue_sql;
	var $calibration_sql;
    
    public function __construct($strFileName, $strSymbol) 
    {
        parent::__construct($strFileName);
        
        $this->strSymbol = $strSymbol;
    	if (SqlGetFundPair($strSymbol))		
    	{
    		$this->ref = new FundPairReference($strSymbol);
    		$this->strStockId = $this->ref->GetStockId();
    	}
    	
		$this->oldest_ymd = new OldestYMD();
		$this->netvalue_sql = GetNetValueHistorySql();
		$this->calibration_sql = GetCalibrationSql();
    }
    
    // Ticker,NAV,Date
    // QQQ,513.149999999999977,05/21/2025
    public function OnLineArray($arWord)
    {
    	if (count($arWord) != 3)				return true;
    	if ($arWord[0] != $this->strSymbol)		return true;
    	if ($iTick = strtotime($arWord[2]))
		{
    		$ymd = new TickYMD($iTick);
    		$strDate = $ymd->GetYMD();
			if ($this->oldest_ymd->IsTooOld($strDate))	return false;
   			if ($this->oldest_ymd->IsInvalid($strDate) === false)
   			{
   				$strNav = mysql_round($arWord[1]);
//   				DebugString($strDate.' '.$strNav);
				if ($this->ref)
				{
					if ($this->netvalue_sql->WriteDaily($this->strStockId, $strDate, $strNav))
					{
						$this->iCount ++;
					}
				}
   			}
		}
    	return true;
    }
    
    function GetCount()
    {
    	return $this->iCount;
    }
}    

function _updateInvescoNetValue($strSymbol)
{
	$strFileName = $strSymbol.'_daily_nav.csv';
	$strUrl = GetInvescoNetValueUrl($strSymbol);
	if (StockSaveDebugCsv($strFileName, $strUrl))
	{
		$csv = new _InvescoCsvFile($strFileName, $strSymbol);
		$csv->Read();
		DebugVal($csv->GetCount(), 'Net value updated');
	}
}

?>
