<?php
require_once('_stock.php');
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
	$cny_ref = $ref->GetCnyRef();
    EchoFundEstParagraph($ref);
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), array($ref->GetFutureRef(), $cny_ref)));
    $acct->EchoCommonParagraphs();

    if ($group = $acct->EchoTransaction()) 
    {
        $acct->EchoMoneyParagraph($group, false, $cny_ref);
        $acct->EchoArbitrageParagraph($group);
	}
	    
    $acct->EchoDebugParagraph();
    $acct->EchoLinks(QDII_HK_PAGE, 'GetQdiiHkRelated');
}

function GetQdiiHkLinks($sym)
{
	$str = GetExternalLink('https://www.hkex.com.hk/market-data/securities-prices/exchange-traded-products', '港股ETF汇总');
	
	$str .= '<br />&nbsp';
	$str .= GetHangSengSoftwareLinks();
	$str .= GetASharesSoftwareLinks();
	return $str;
}

   	$acct = new _QdiiHkAccount();
   	$acct->Create();
?>
