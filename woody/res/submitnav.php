<?php
require_once('../../php/csvfile.php');
require_once('php/_stock.php');
require_once('php/_spdrnavxls.php');
require_once('php/_emptygroup.php');

class _InvescoCsvFile extends DebugCsvFile
{
	var $iCount = 0;
	var $ref = false;
	
	var $strSymbol;
	var $strStockId;
	var $oldest_ymd;
	var $nav_sql;
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
		$this->nav_sql = GetNavHistorySql();
		$this->calibration_sql = GetCalibrationSql();
    }
    
    // Ticker,NAV,Date
    // QQQ,513.149999999999977,05/21/2025
    public function OnLineArray($arWord)
    {
    	if (count($arWord) != 3)				return;
    	if ($arWord[0] != $this->strSymbol)		return;
    	if ($iTick = strtotime($arWord[2]))
		{
    		$ymd = new TickYMD($iTick);
    		$strDate = $ymd->GetYMD();
			if ($this->oldest_ymd->IsTooOld($strDate))	return;
   			if ($this->oldest_ymd->IsInvalid($strDate) === false)
   			{
   				$strNav = mysql_round($arWord[1]);
//   				DebugString($strDate.' '.$strNav);
				if ($this->ref)
				{
					if ($this->nav_sql->WriteDaily($this->strStockId, $strDate, $strNav))
					{
						$this->iCount ++;
					}
				}
   			}
		}
    }
    
    function GetCount()
    {
    	return $this->iCount;
    }
}    

class _AdminNavAccount extends SymbolAccount
{
	function _updateInvescoNav($strSymbol)
	{
		$strFileName = $strSymbol.'_daily_nav.csv';
		$strUrl = GetInvescoNavUrl($strSymbol);
		if (StockSaveDebugCsv($strFileName, $strUrl))
		{
			$csv = new _InvescoCsvFile($strFileName, $strSymbol);
			$csv->Read();
			DebugVal($csv->GetCount(), 'NAV updated');
		}
	}
	
    public function AdminProcess()
    {
	    if ($ref = $this->GetSymbolRef())
	    {
	    	$strSymbol = $ref->GetSymbol();
	    	if (GetInvescoOfficialUrl($strSymbol))	$this->_updateInvescoNav($strSymbol);
	    	else							        DebugNavXlsStr($ref);
	    }
	}
}

   	$acct = new _AdminNavAccount();
	$acct->AdminRun();
	
?>
