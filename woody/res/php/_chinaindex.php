<?php
require_once('_fundgroup.php');

class _ChinaIndexAccount extends FundGroupAccount
{
	var $us_ref;
	var $a50_ref;
	
    function Create() 
    {
        $strSymbol = $this->GetName();
    	$strUS = 'ASHR';
    	$strA50 = 'hf_CHA50CFD';
        StockPrefetchExtendedData($strSymbol, $strUS, $strA50);

        $this->ref = new FundPairReference($strSymbol, '_RealtimeCallback');
        $this->us_ref = new FundPairReference($strUS, '_RealtimeCallback');
        $this->a50_ref = new FundPairReference($strA50);
		
        GetChinaMoney($this->ref);
        SzseGetLofShares($this->ref);
        YahooUpdateNetValue($this->us_ref);
   		$this->us_ref->DailyCalibration();
   		$this->ref->DailyCalibration();
   		
		// $this->arPair = array($this->ref, $this->us_ref, $this->a50_ref);
        $this->CreateGroup(array($this->ref->GetPairRef(), $this->ref, $this->us_ref, $this->a50_ref));
    }

    function GetUsRef()
    {
    	return $this->us_ref;
    }

    function GetA50Ref()
    {
    	return $this->a50_ref;
    }
}

function _RealtimeCallback()
{
    global $acct;
    
    $a50_ref = $acct->GetA50Ref();
    return $a50_ref->EstToPair();
}

function EchoAll()
{
    global $acct;

    $ref = $acct->GetRef();
    $us_ref = $acct->GetUsRef();
    $a50_ref = $acct->GetA50Ref();
    $cnh_ref = $us_ref->GetCnyRef();
    
	EchoFundArrayEstParagraph(array($ref, $us_ref), '');
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), array($cnh_ref)), $acct->IsAdmin());
    EchoFundListParagraph(array($ref, $us_ref, $a50_ref));
    EchoFundPairTradingParagraph($ref);
    EchoFundPairSmaParagraph($ref);
    EchoFundPairSmaParagraph($us_ref, '');
    EchoFundPairSmaParagraph($a50_ref, '');
    EchoFundHistoryParagraph($ref);
    EchoFundHistoryParagraph($us_ref);
	EchoNetValueCloseParagraph($us_ref);
//   	EchoFundShareParagraph($ref);
//   	EchoFundShareParagraph($us_ref);

    if ($group = $acct->EchoTransaction()) 
    {
    	$acct->EchoMoneyParagraph($group, $cnh_ref);
	}
	
    $acct->EchoLinks('chinaindex', 'GetChinaIndexLinks');
}

function GetChinaIndexLinks($sym)
{
	$str = GetExternalLink('https://dws.com/US/EN/Product-Detail-Page/ASHR', 'ASHR官网');
	$str .= GetStockCategoryLinks($sym->GetSymbol());
	return $str.GetChinaIndexRelated($sym->GetDigitA());
}

function GetMetaDescription()
{
    global $acct;

    $ref = $acct->GetRef();
    $us_ref = $acct->GetUsRef();
    
    $strDescription = RefGetStockDisplay($ref);
    $strEst = RefGetStockDisplay($ref->GetPairRef());
    $strUS = RefGetStockDisplay($us_ref);
    $strCNY = RefGetStockDisplay($us_ref->GetCnyRef());
    $str = "用{$strEst}估算{$strDescription}净值。参考{$strCNY}比较{$strUS}净值。";
    return CheckMetaDescription($str);
}

   	$acct = new _ChinaIndexAccount();
   	$acct->Create();
?>
