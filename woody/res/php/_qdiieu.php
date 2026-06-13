<?php
require_once('_qdiigroup.php');

class _QdiiEuAccount extends QdiiGroupAccount
{
    function Create() 
    {
        $strSymbol = $this->GetName();
        $arLev = $this->GetLeverageSymbols(QdiiEuGetEstSymbol($strSymbol));
        StockPrefetchArrayExtendedData([...$arLev, $strSymbol]);

        $this->ref = new QdiiEuReference($strSymbol);
		$this->QdiiCreateGroup($arLev);
    } 
} 

function EchoAll()
{
   	global $acct;
	/** @var _QdiiEuAccount $acct */
	
   	$ref = $acct->GetRef();
   	
    EchoFundEstParagraph($ref);
    EchoReferenceParagraph([...$acct->GetStockRefArray(), ...$ref->GetForexRefArray()], $acct->IsAdmin());
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
