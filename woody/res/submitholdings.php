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
			$strStockId = $ref->GetStockId();
			if ($strHoldings = UrlGetQueryValue('holdings'))
			{
				DebugString(__CLASS__.'->'.__FUNCTION__.' '.$strHoldings);
				$strSymbol = $ref->GetSymbol();
				$ref = new HoldingsReference($strSymbol);
				if ($strDate = $ref->GetHoldingsDate())		UpdateStockOptionHoldings($strStockId, $strDate, $strHoldings);
			}
	    	if ($strPosition = UrlGetQueryValue('fundposition'))
    		{
    			$pos_sql = GetPositionSql();
    			$pos_sql->WriteVal($strStockId, $strPosition);
    		}
	    }
	}
}

   	$acct = new _AdminHoldingsAccount();
	$acct->AdminRun();
?>
