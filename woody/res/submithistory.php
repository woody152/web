<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
//require_once('php/_yahoohistorycsv.php');
require_once('../../php/stock/yahoostockhistory.php');
require_once('../../php/stock/sinastockhistory.php');
require_once('../../php/stock/eastmoneynetvaluehistory.php');

class _AdminHistoryAccount extends SymbolAccount
{
    public function AdminProcess()
    {
	    if ($ref = $this->GetSymbolRef())
	    {
			if ($ref->IsIndexA())
			{
				UpdateSinaHistory($ref);
			}
			else if ($ref->IsFundA())
			{
				UpdateSinaHistory($ref);
				UpdateEastMoneyNetValueHistory($ref);
			}	
			else
			{
				// YahooUpdateStockHistory($ref);
				UpdateYahooHistoryChart($ref);
			}		
	    }
	}
}

   	$acct = new _AdminHistoryAccount();
	$acct->AdminRun();
