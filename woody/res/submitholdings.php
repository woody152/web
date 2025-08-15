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
			if ($strHoldings = UrlGetQueryValue('holdings'))
			{
				$strSymbol = $ref->GetSymbol();
				$ref = new HoldingsReference($strSymbol);
				if ($strDate = $ref->GetHoldingsDate())		_updateStockOptionHoldings($strSymbol, $ref->GetStockId(), $strDate, $strHoldings);
			}
	    }
	}
}

   	$acct = new _AdminHoldingsAccount();
	$acct->AdminRun();
?>
