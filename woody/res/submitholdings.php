<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('php/_updateholdings.php');

class _AdminHoldingsAccount extends SymbolAccount
{
    public function AdminProcess()
    {
	    if ($ref = $this->GetSymbolRef())
	    {
			if ($strDate = UrlGetQueryValue('date'))
			{
				if ($strHoldings = UrlGetQueryValue('holdings'))
				{
					_updateStockOptionHoldings($ref->GetSymbol(), $ref->GetStockId(), $strDate, $strHoldings);
				}
			}
	    }
	}
}

   	$acct = new _AdminHoldingsAccount();
	$acct->AdminRun();
?>
