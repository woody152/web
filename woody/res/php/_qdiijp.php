<?php
require_once('_qdiigroup.php');

class _QdiiJpAccount extends QdiiGroupAccount
{
    function Create() 
    {
        $strSymbol = $this->GetName();
        $arLev = $this->GetLeverageSymbols(QdiiJpGetEstSymbol($strSymbol));
        StockPrefetchArrayExtendedData(array_merge($arLev, array($strSymbol)));

        $this->ref = new QdiiJpReference($strSymbol);
		$this->QdiiCreateGroup($arLev);
    } 
} 

function EchoAll()
{
   	global $acct;
   	$ref = $acct->GetRef();
   	
    EchoFundEstParagraph($ref);
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), $ref->GetForexRefArray()), $acct->IsAdmin());
    $acct->EchoCommonParagraphs();
    if ($group = $acct->EchoTransaction()) 
    {
        $acct->EchoMoneyParagraph($group);
	}
	    
    $acct->EchoDebugParagraph();
    $acct->EchoLinks('qdiijp', 'GetQdiiJpLinks');
}

function GetQdiiJpLinks($sym)
{
	$str = GetJisiluQdiiLink(true).' '.GetExternalLink(GetCmeUrl('NIY'), '芝商所NIY期货');
	$str .= GetStockCategoryLinks($sym->GetSymbol());
	return $str.GetQdiiJpRelated($sym->GetDigitA());
}

   	$acct = new _QdiiJpAccount();
   	$acct->Create();
?>
