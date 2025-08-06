<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('php/_updateinvesconetvalue.php');
require_once('php/_spdrnetvaluexls.php');

class _AdminNetValueAccount extends SymbolAccount
{
    public function AdminProcess()
    {
	    if ($ref = $this->GetSymbolRef())
	    {
	    	$strSymbol = $ref->GetSymbol();
	    	if (GetInvescoOfficialUrl($strSymbol))	_updateInvescoNetValue($strSymbol);
	    	else							        DebugNetValueXlsStr($ref);
	    }
	}
}

   	$acct = new _AdminNetValueAccount();
	$acct->AdminRun();
?>
