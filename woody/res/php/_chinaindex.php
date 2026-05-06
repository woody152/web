<?php
require_once('_fundgroup.php');

class _ChinaIndexAccount extends FundGroupAccount
{
	var $us_ref = false;
	var $a50_ref = false;
	
    function Create() 
    {
        $strSymbol = $this->GetName();
		$ar = array($strSymbol);

        if (in_arrayAshrSymbol($strSymbol))
        {
        	$strUS = 'ASHR';
            $strA50 = 'hf_CHA50CFD';
            $ar[] = $strUS;
            $ar[] = $strA50;
           	$callback = '_RealtimeCallback';
        }
        else
        {
            $strUS = false;
            $strA50 = false;
            $callback = false;
        }
        StockPrefetchArrayExtendedData($ar);

        $this->ref = new FundPairReference($strSymbol, $callback);
        if ($strUS)     $this->us_ref = new FundPairReference($strUS, $callback);
        if ($strA50)    $this->a50_ref = new FundPairReference($strA50);
		
        GetChinaMoney($this->ref);
        SzseGetLofShares($this->ref);
		if ($strUS)
		{
	        YahooUpdateNetValue($this->us_ref);
   			$this->us_ref->DailyCalibration();
		}
   		$this->ref->DailyCalibration();

       	$arRef = [$this->ref->GetPairRef(), $this->ref];
		if ($strUS)		$arRef[] = $this->us_ref;
		if ($strA50)	$arRef[] = $this->a50_ref;
        $this->CreateGroup($arRef);
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

    if ($us_ref)	$cnh_ref = $us_ref->GetCnyRef();
	else			$cnh_ref = false;
    
	$arRef = [$ref];
	if ($us_ref)	$arRef[] = $us_ref;
	EchoFundArrayEstParagraph($arRef, '');
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), array($cnh_ref)), $acct->IsAdmin());

	if ($a50_ref)	$arRef[] = $a50_ref;
    EchoFundListParagraph($arRef);
    EchoFundPairTradingParagraph($ref);
    EchoFundPairSmaParagraph($ref);
    if ($us_ref)	EchoFundPairSmaParagraph($us_ref, '');
    if ($a50_ref)	EchoFundPairSmaParagraph($a50_ref, '');
    EchoFundHistoryParagraph($ref);
    if ($us_ref)
	{
		EchoFundHistoryParagraph($us_ref);
		EchoNetValueCloseParagraph($us_ref);
	}
  	EchoFundShareParagraph($ref);
//  if ($us_ref)	EchoFundShareParagraph($us_ref);

    if ($group = $acct->EchoTransaction()) 
    {
    	$acct->EchoMoneyParagraph($group, $cnh_ref);
	}
	
    $acct->EchoLinks('chinaindex', 'GetChinaIndexLinks');
}

function GetChinaIndexLinks($sym)
{
    global $acct;

	$str = '';
    if ($us_ref = $acct->GetUsRef())
	{
		if ($us_ref->GetSymbol() == 'ASHR')
		{
			$str = GetExternalLink('https://dws.com/US/EN/Product-Detail-Page/ASHR', 'ASHR官网');
		}	
	}

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
    $str = "用{$strEst}估算{$strDescription}".STOCK_DISP_NETVALUE.'，同时提供五档交易、历史价格相对于净值的溢价和场内份额等数据。';
	if ($us_ref)
	{
    	$strUS = RefGetStockDisplay($us_ref);
    	$strCNY = RefGetStockDisplay($us_ref->GetCnyRef());
	    $str .= "参考{$strCNY}比较{$strUS}".STOCK_DISP_NETVALUE.'。';
	}	
    return CheckMetaDescription($str);
}

   	$acct = new _ChinaIndexAccount();
   	$acct->Create();
?>
