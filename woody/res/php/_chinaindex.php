<?php
require_once('_fundgroup.php');
require_once('../../php/stock/kraneshares.php');

class _ChinaIndexAccount extends FundGroupAccount
{
	private $us_ref = false;
	private $a50_ref = false;
	
    function Create() 
    {
        $strSymbol = $this->GetName();
		$ar = [$strSymbol];

		$strUS = false;
		$strA50 = false;
		$callback = false;
		if (in_arrayAshrSymbol($strSymbol))
        {
        	$strUS = 'ASHR';
            $strA50 = 'hf_CHA50CFD';
            $ar[] = $strUS;
            $ar[] = $strA50;
           	$callback = '_RealtimeCallback';
        }
		else if (in_arrayKstrSymbol($strSymbol))
        {
        	$strUS = 'KSTR';
            $ar[] = $strUS;
        }
        StockPrefetchArrayExtendedData($ar);

        $this->ref = new FundPairReference($strSymbol, $callback);
        if ($strUS)     $this->us_ref = new FundPairReference($strUS, $callback);
        if ($strA50)    $this->a50_ref = new FundPairReference($strA50);
		
        GetChinaMoney($this->ref);
        SzseGetLofShares($this->ref);
		if ($strUS)
		{
			switch ($strUS)
			{
			case 'KSTR':
    			if ($strDate = NeedOfficialWebData($this->us_ref))	UpdateKraneNetValue($this->us_ref, $strDate);
				break;

			default:
		        YahooUpdateNetValue($this->us_ref);
				break;
    		}
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
	/** @var _ChinaIndexAccount $acct */
    
    $a50_ref = $acct->GetA50Ref();
    return $a50_ref->EstToPair();
}

function EchoAll()
{
    global $acct;
	/** @var _ChinaIndexAccount $acct */

    $ref = $acct->GetRef();
    $a50_ref = $acct->GetA50Ref();
    $us_ref = $acct->GetUsRef();
    $cnh_ref = $us_ref ? $us_ref->GetCnyRef() : false;
    
	$arRef = [$ref];
	if ($us_ref)	$arRef[] = $us_ref;
	EchoFundArrayEstParagraph($arRef, '');
    EchoReferenceParagraph([...$acct->GetStockRefArray(), $cnh_ref], $acct->IsAdmin());

	if ($a50_ref)	$arRef[] = $a50_ref;
    EchoFundListParagraph($arRef);
    EchoFundPairTradingParagraph($ref);
    EchoFundPairSmaParagraph($ref);
    if ($a50_ref)	EchoFundPairSmaParagraph($a50_ref, '');
    if ($us_ref)
	{
        EchoFundPairSmaParagraph($us_ref, '');
		EchoFundHistoryParagraph($us_ref);
		EchoNetValueCloseParagraph($us_ref);
        // EchoFundShareParagraph($us_ref);
	}
    EchoFundHistoryParagraph($ref);
  	EchoFundShareParagraph($ref);

    if ($group = $acct->EchoTransaction()) 
    {
    	$acct->EchoMoneyParagraph($group, $cnh_ref);
	}
    $acct->EchoLinks('chinaindex', 'GetChinaIndexLinks');
}

function GetChinaIndexLinks($sym)
{
    global $acct;
	/** @var _ChinaIndexAccount $acct */

	$str = '';
    if ($us_ref = $acct->GetUsRef())
	{
		$strSymbol = $us_ref->GetSymbol();
		$str = match($strSymbol)
				{'ASHR' => GetExternalLink('https://etf.dws.com/en-us/ASHR-harvest-csi-300-china-a-shares-etf/', 'ASHR官网'),
				 'KSTR' => GetKraneOfficialLink($strSymbol),
				 default => ''
				};	
	}

	$str .= GetStockCategoryLinks($sym->GetSymbol());
	return $str.GetChinaIndexRelated($sym->GetDigitA());
}

function GetMetaDescription()
{
    global $acct;
	/** @var _ChinaIndexAccount $acct */

    $ref = $acct->GetRef();
    $strDescription = RefGetStockDisplay($ref);
    $strEst = RefGetStockDisplay($ref->GetPairRef());
    $str = "用{$strEst}估算{$strDescription}".STOCK_DISP_NETVALUE.'，同时提供五档交易、历史价格相对于净值的溢价和场内份额等数据。';
	if ($us_ref = $acct->GetUsRef())
	{
    	$strUS = RefGetStockDisplay($us_ref);
    	$strCNY = RefGetStockDisplay($us_ref->GetCnyRef());
	    $str .= "参考{$strCNY}比较{$strUS}".STOCK_DISP_NETVALUE.'。';
	}	
    return CheckMetaDescription($str);
}

   	$acct = new _ChinaIndexAccount();
   	$acct->Create();
