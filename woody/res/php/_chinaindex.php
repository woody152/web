<?php
require_once('_fundgroup.php');
require_once('../../php/ui/fundlistparagraph.php');

class _ChinaIndexAccount extends FundGroupAccount
{
	var $us_ref;
	var $a50_ref;
    var $cnh_ref;
	
    function Create() 
    {
        $strSymbol = $this->GetName();
    	$strUS = 'ASHR';
    	$strA50 = 'hf_CHA50CFD';
        $strCNH = 'fx_susdcnh';
        StockPrefetchExtendedData($strSymbol, $strUS, $strA50, $strCNH);

        $this->ref = new FundPairReference($strSymbol);
        $this->us_ref = new FundPairReference($strUS);
        $this->a50_ref = new MyStockReference($strA50);
        $this->cnh_ref = new ForexReference($strCNH);

        GetChinaMoney($this->ref);
        SzseGetLofShares($this->ref);
        YahooUpdateNetValue($this->us_ref);
    	$this->us_ref->SetTimeZone();
    	if ($this->us_ref->IsStockMarketTrading(GetNowYMD()))	$this->us_ref->ManualCalibration();
    		
        $this->CreateGroup(array($this->ref, $this->ref->GetPairNavRef(), $this->us_ref));
    }
}

function EchoAll()
{
    global $acct;

    $ref = $acct->GetRef();
    
	EchoFundArrayEstParagraph(array($ref, $acct->us_ref), '');
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), array($acct->a50_ref, $acct->cnh_ref)), $acct->IsAdmin());
    EchoFundListParagraph(array($ref, $acct->us_ref));
    EchoFundPairTradingParagraph($ref);
    EchoFundPairSmaParagraph($ref);
    EchoFundPairSmaParagraph($acct->us_ref, '');
    EchoFundHistoryParagraph($ref);
    EchoFundHistoryParagraph($acct->us_ref);
//   	EchoFundShareParagraph($ref);
//   	EchoFundShareParagraph($acct->us_ref);

    if ($group = $acct->EchoTransaction()) 
    {
    	$acct->EchoMoneyParagraph($group, $acct->us_ref->cny_ref);
	}
	
    $acct->EchoLinks('chinaindex', 'GetChinaIndexLinks');
}

function GetChinaIndexLinks($sym)
{
	$str = GetExternalLink('https://dws.com/US/EN/Product-Detail-Page/ASHR', 'ASHR官网');
	$str .= GetASharesSoftwareLinks();
	$str .= GetChinaInternetSoftwareLinks();
	$str .= GetOilSoftwareLinks();
	return $str.GetChinaIndexRelated($sym->GetDigitA());
}

function GetMetaDescription()
{
    global $acct;

    $strDescription = RefGetStockDisplay($acct->ref);
    $strEst = RefGetStockDisplay($acct->ref->GetPairNavRef());
    $strUS = RefGetStockDisplay($acct->us_ref);
    $strCNY = RefGetStockDisplay($acct->us_ref->cny_ref);
    $str = "用{$strEst}估算{$strDescription}净值. 参考{$strCNY}比较{$strUS}净值.";
    return CheckMetaDescription($str);
}

   	$acct = new _ChinaIndexAccount();
   	$acct->Create();
?>
