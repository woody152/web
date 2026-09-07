<?php
require_once('_qdiigroup.php');

class _QdiiEuAccount extends QdiiGroupAccount
{
    private $eng_ref;
    private $swi_ref;

    function Create() 
    {
        $strSymbol = $this->GetName();
        $strEng = 'znb_UKX';
        $strSwi = 'znb_SWI20';
        $arLev = $this->GetLeverageSymbols(QdiiEuGetEstSymbol($strSymbol));
        StockPrefetchArrayExtendedData([...$arLev, $strSymbol, $strSwi, $strEng]);

        $this->ref = new QdiiEuReference($strSymbol);
        $this->eng_ref = new MyStockReference($strEng);
        $this->swi_ref = new MyStockReference($strSwi);
		$this->QdiiCreateGroup($arLev);
    }

	function GetEngRef()
	{
		return $this->eng_ref;
	}

	function GetSwiRef()
	{
		return $this->swi_ref;
	}
}

function EchoAll()
{
   	global $acct;
	/** @var _QdiiEuAccount $acct */
	
   	$ref = $acct->GetRef();
   	
    EchoFundEstParagraph($ref);
    EchoReferenceParagraph([...$acct->GetStockRefArray(), $acct->GetEngRef(), $acct->GetSwiRef(), ...$ref->GetForexRefArray()], $acct->IsAdmin());
    $acct->EchoCommonParagraphs();
    if ($group = $acct->EchoTransaction()) 
    {
        $acct->EchoMoneyParagraph($group);
	}
	    
    $acct->EchoDebugParagraph();
    $acct->EchoLinks('qdiieu', 'GetQdiiEuLinks');
}

function GetQdiiEuLinks($sym)
{
	$str = GetJisiluQdiiLink();
	$str .= GetStockCategoryLinks($sym->GetSymbol());
	return $str.GetQdiiEuRelated($sym->GetDigitA());
}

   	$acct = new _QdiiEuAccount();
   	$acct->Create();
