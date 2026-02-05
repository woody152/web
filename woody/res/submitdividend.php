<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');

/*
Array
(
    [0] => Array
        (
            [exDate] => 12/23/2024
            [longTeamCapital] => 0.000000
            [recordDate] => 12/23/2024
            [dividend] => 0.807597
            [payableDate] => 12/26/2024
            [shortTeamCapital] => 0.000000
        )
https://www.ssga.com/bin/v1/ssmp/fund/dividend-distribution?ticker=XOP&country=us&language=en&role=individual&product=etfs
*/

function _updateSpdrEtfDividend($strStockId, $strSymbol, $strFileName, $sql)
{
	$strUrl = GetSpdrUrl().'bin/v1/ssmp/fund/dividend-distribution?ticker='.$strSymbol.'&country=us&language=en&role=individual&product=etfs';
   	if ($str = url_get_contents($strUrl))
   	{
   		DebugString($strUrl.' save new file to '.$strFileName);
   		file_put_contents($strFileName, $str);
   		$ar = json_decode($str, true);
   		
//   		$sql->DeleteAll($strStockId);
		for ($i = 0; $i < count($ar); $i ++)
		{
//			DebugPrint($ar[$i]);
			if (isset($ar[$i]))
			{
				$timestamp = strtotime($ar[$i]['exDate']);
				$sql->WriteDaily($strStockId, DebugGetDate($timestamp, false), $ar[$i]['dividend']);
			}
		}
   	}
   	else
        file_put_failed($strFileName);
}

/*
Array
(
    [0] => Array
        (
            [CashDividendPerShare] => 0.275411
            [Dividend] => .275411
            [EffectiveDate] => 2024-12-20T00:00:00
            [ExDate] => 2024-12-23T00:00:00
            [LongTermCapGains] => 
            [OtherPerShare] => 
            [PayableDate] => 2024-12-31T00:00:00
            [RecordDate] => 2024-12-23T00:00:00
            [ReturnOfCapital] => 
            [ShortTermCapGains] => 
            [SpecialPerShare] => 
            [Symbol] => TQQQ
        )

    [1] => Array
        (
            [CashDividendPerShare] => 0.230214
            [Dividend] => .230214
            [EffectiveDate] => 2024-09-24T00:00:00
            [ExDate] => 2024-09-25T00:00:00
            [LongTermCapGains] => 
            [OtherPerShare] => 
            [PayableDate] => 2024-10-02T00:00:00
            [RecordDate] => 2024-09-25T00:00:00
            [ReturnOfCapital] => 
            [ShortTermCapGains] => 
            [SpecialPerShare] => 
            [Symbol] => TQQQ
        )

    [2] => Array
        (
            [CashDividendPerShare] => 0.282777
            [Dividend] => .282777
            [EffectiveDate] => 2024-06-25T00:00:00
            [ExDate] => 2024-06-26T00:00:00
            [LongTermCapGains] => 
            [OtherPerShare] => 
            [PayableDate] => 2024-07-03T00:00:00
            [RecordDate] => 2024-06-26T00:00:00
            [ReturnOfCapital] => 
            [ShortTermCapGains] => 
            [SpecialPerShare] => 
            [Symbol] => TQQQ
        )

    [3] => Array
        (
            [CashDividendPerShare] => 0.215144
            [Dividend] => .215144
            [EffectiveDate] => 2024-03-19T00:00:00
            [ExDate] => 2024-03-20T00:00:00
            [LongTermCapGains] => 
            [OtherPerShare] => 
            [PayableDate] => 2024-03-27T00:00:00
            [RecordDate] => 2024-03-21T00:00:00
            [ReturnOfCapital] => 
            [ShortTermCapGains] => 
            [SpecialPerShare] => 
            [Symbol] => TQQQ
        )
)
https://www.proshares.com/api/distributionsummary?fund=TQQQ&year=2024
*/

function _updateProsharesEtfDividend($strStockId, $strSymbol, $strFileName, $sql)
{
	$strUrl = GetProsharesUrl().'api/distributionsummary?fund='.$strSymbol.'&year=2025';
   	if ($str = url_get_contents($strUrl))
   	{
   		DebugString($strUrl.' save new file to '.$strFileName);
   		file_put_contents($strFileName, $str);
   		$ar = json_decode($str, true);
   		
//   		$sql->DeleteAll($strStockId);
		for ($i = 0; $i < count($ar); $i ++)
		{
//			DebugPrint($ar[$i]);
			if (isset($ar[$i]))
			{
				$sql->WriteDaily($strStockId, substr($ar[$i]['ExDate'], 0, 10), $ar[$i]['CashDividendPerShare']);
			}
		}
   	}
   	else
        file_put_failed($strFileName);
}

class _AdminDividendAccount extends SymbolAccount
{
    public function AdminProcess()
    {
	    if ($ref = $this->GetSymbolRef())
	    {
	    	$strSymbol = $ref->GetSymbol();
	    	$strFileName = DebugGetYahooFileName($strSymbol.'Dividend');
	    	if (StockNeedFile($strFileName))	// updates on every minute
	    	{
	    		$strStockId = $ref->GetStockId();
   		   		$sql = new StockDividendSql();
	    		if (GetSpdrOfficialUrl($strSymbol))		_updateSpdrEtfDividend($strStockId, $strSymbol, $strFileName, $sql);
	    		else									_updateProsharesEtfDividend($strStockId, $strSymbol, $strFileName, $sql);
	    	}
	    }
	}
}

   	$acct = new _AdminDividendAccount();
	$acct->AdminRun();
?>
