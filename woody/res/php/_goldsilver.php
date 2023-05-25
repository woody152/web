<?php
require_once('_fundgroup.php');

class GoldSilverAccount extends FundGroupAccount
{
    function Create() 
    {
        $strSymbol = $this->GetName();
        StockPrefetchExtendedData($strSymbol);
        $this->ref = new GoldFundReference($strSymbol);

    	$stock_ref = $this->ref->GetStockRef();
        
        GetChinaMoney($stock_ref);
        SzseGetLofShares($stock_ref);
        $this->CreateGroup(array($stock_ref));
    }
} 

function EchoAll()
{
    global $acct;

	$fund = $acct->GetRef();
	$cny_ref = $fund->GetCnyRef();
    EchoFundEstParagraph($fund);
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), array($fund->GetEstRef(), $fund->GetFutureRef(), $cny_ref)), $acct->IsAdmin());
    EchoFundTradingParagraph($fund);    
    EchoFundHistoryParagraph($fund);

    if ($group = $acct->EchoTransaction()) 
    {
    	$acct->EchoMoneyParagraph($group, $cny_ref);
	}
    $acct->EchoLinks('goldsilver', 'GetGoldSilverLinks');
}

function GetGoldSilverLinks($sym)
{
	$str = GetExternalLink('http://www.spdrgoldshares.com/usa/', 'GLD官网').' '.GetJisiluGoldLink();
	$str .= GetCommoditySoftwareLinks();
	$str .= GetOilSoftwareLinks();
	$str .= GetChinaInternetSoftwareLinks();
	return $str.GetGoldSilverRelated($sym->GetDigitA());
}

function GetMetaDescription()
{
    global $acct;

    $strDescription = $acct->GetStockDisplay();

	$fund = $acct->GetRef();
    $strCNY = RefGetStockDisplay($fund->GetCnyRef());
    $strEst = RefGetStockDisplay($fund->GetEstRef());
    $strFuture = RefGetStockDisplay($fund->GetFutureRef());
    $str = '根据'.$strEst.', '.$strFuture.'和'.$strCNY.'等因素计算'.$strDescription.'净值的网页工具.';
    return CheckMetaDescription($str);
}

   	$acct = new GoldSilverAccount();
   	$acct->Create();
?>
