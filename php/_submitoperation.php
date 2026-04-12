<?php
require_once('account.php');
require_once('stock.php');

class _AdminOperationAccount extends Account
{
    public function AdminProcess()
    {
    	if ($strIp = UrlGetQueryValue('ip'))
    	{
    		$this->SetCrawler($strIp);
    	}
    	else if ($strIp = UrlGetQueryValue('maliciousip'))
    	{
    		$this->SetMalicious($strIp);
    	}
    }
}

   	$acct = new _AdminOperationAccount();
	$acct->AdminRun();
?>
