<?php
require_once('_qdiigroup.php');

class _QdiiHkAccount extends QdiiGroupAccount
{
    function Create() 
    {
        $strSymbol = $this->GetName();

        $this->GetLeverageSymbols(QdiiHkGetEstSymbol($strSymbol));
        StockPrefetchArrayExtendedData(array_merge($this->GetLeverage(), array($strSymbol)));

        $this->ref = new QdiiHkReference($strSymbol);
		$this->QdiiCreateGroup();
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
        $acct->EchoMoneyParagraph($group, false, $ref->GetCnyRef());
	}
	    
    $acct->EchoDebugParagraph();
    $acct->EchoLinks('qdiihk', 'GetQdiiHkLinks');
}

function GetQdiiHkLinks($sym)
{
	$str = GetJisiluQdiiLink(true);	// .' '.GetExternalLink('https://www.hkex.com.hk/market-data/securities-prices/exchange-traded-products', '港股ETF汇总');
	$str .= GetStockCategoryLinks($sym->GetSymbol());
	return $str.GetQdiiHkRelated($sym->GetDigitA());
}

   	$acct = new _QdiiHkAccount();
   	$acct->Create();
?>
