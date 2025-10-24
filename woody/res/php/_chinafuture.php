<?php
require_once('_fundgroup.php');

class _ChinaFutureAccount extends FundGroupAccount
{
    function Create() 
    {
        $strSymbol = $this->GetName();
        StockPrefetchExtendedData($strSymbol);

        $this->ref = new FundPairReference($strSymbol);
		
        SzseGetLofShares($this->ref);
   		$this->ref->DailyCalibration();
   		
        $this->CreateGroup(array($this->ref->GetPairRef(), $this->ref));
    }
}

function EchoAll()
{
    global $acct;

    $ref = $acct->GetRef();
    
	EchoFundEstParagraph($ref);
    EchoReferenceParagraph($acct->GetStockRefArray(), $acct->IsAdmin());
    EchoFundListParagraph(array($ref));
    EchoFundPairTradingParagraph($ref);
    EchoFundPairSmaParagraph($ref);
    EchoFundHistoryParagraph($ref);
   	EchoFundShareParagraph($ref);

    if ($group = $acct->EchoTransaction()) 
    {
    	$acct->EchoMoneyParagraph($group);
	}
	
    $acct->EchoLinks('chinafuture', 'GetChinaFutureLinks');
}

function GetChinaFutureLinks($sym)
{
	$str = GetStockCategoryLinks($sym->GetSymbol());
	return $str.GetChinaFutureRelated($sym->GetDigitA());
}

function GetMetaDescription()
{
    global $acct;

    $ref = $acct->GetRef();
    
    $strDescription = RefGetStockDisplay($ref);
    $strEst = RefGetStockDisplay($ref->GetPairRef());
    $str = "用{$strEst}估算{$strDescription}净值，同时测算无风险套利的国内期货市场对冲比例等数据。";
    return CheckMetaDescription($str);
}

   	$acct = new _ChinaFutureAccount();
   	$acct->Create();
?>
