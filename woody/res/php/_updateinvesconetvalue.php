<?php
require_once('../../php/csvfile.php');

class _InvescoCsvFile extends DebugCsvFile
{
	var $iCount = 0;
	var $ref = false;
	
	var $strSymbol;
	var $strStockId;
	var $oldest_ymd;
	var $net_sql;
	var $cal_sql;
    
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
		$this->net_sql = GetNetValueHistorySql();
		$this->cal_sql = GetCalibrationSql();
    }
    
    // Ticker,NetValue,Date
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
   				$strNetValue = mysql_round($arWord[1]);
//   				DebugString($strDate.' '.$strNetValue);
				if ($this->ref)
				{
					if ($this->net_sql->WriteDaily($this->strStockId, $strDate, $strNetValue))
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

function UpdateInvescoNetValue($strSymbol)
{
	$strFileName = $strSymbol.'_daily_netvalue.csv';
	$strUrl = GetInvescoNetValueUrl($strSymbol);
	if (StockSaveDebugCsv($strFileName, $strUrl))
	{
		$csv = new _InvescoCsvFile($strFileName, $strSymbol);
		$csv->Read();
		DebugVal($csv->GetCount(), 'Net value updated');
	}
}

?>
