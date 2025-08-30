<?php
require_once('_qdiigroup.php');

class QdiiAccount extends QdiiGroupAccount
{
    var $oil_ref = false;
    var $cnh_ref;

    function Create() 
    {
//        $strUSD = 'DINIW';
        $strCNH = 'fx_susdcnh';
        $strSymbol = $this->GetName();
        $this->GetLeverageSymbols(QdiiGetEstSymbol($strSymbol));
		$ar = array($strSymbol, $strCNH);
		if ($bOil = in_arrayOilQdii($strSymbol))
		{
			$strOil = 'hf_OIL';
			$ar[] = $strOil; 
		}
        StockPrefetchArrayExtendedData(array_merge($this->GetLeverage(), $ar));
        
        $this->ref = new QdiiReference($strSymbol);
        $this->cnh_ref = new MyStockReference($strCNH);
        if ($bOil)		$this->oil_ref = new MyStockReference($strOil);
        
		$this->QdiiCreateGroup();
    }
} 

function EchoAll()
{
   	global $acct;
   	$ref = $acct->GetRef();
    
    EchoFundEstParagraph($ref);
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), array($acct->oil_ref, $acct->cnh_ref), $ref->GetForexRefArray()), $acct->IsAdmin());
    $acct->EchoCommonParagraphs();
    if ($group = $acct->EchoTransaction()) 
    {
        $acct->EchoMoneyParagraph($group, $ref->GetCnyRef());
	}
	
    $acct->EchoDebugParagraph();
    $acct->EchoLinks('qdii', 'GetQdiiLinks');
}

function GetQdiiLinks($sym)
{
   	global $acct;
   	
	$strSymbol = $sym->GetSymbol();
   	
   	$ref = $acct->GetRef();
   	
   	if ($realtime_ref = $ref->GetRealtimeRef())		$strRealtimeSymbol = $realtime_ref->GetSymbol();
   	else											$strRealtimeSymbol = false;

	$str = GetJisiluQdiiLink();
	
	if (in_arrayOilQdii($strSymbol))
	{
		$str .= ' '.GetUscfLink();
	}
	
	if (in_arrayQqqQdii($strSymbol))
	{
		$str .= ' '.GetInvescoOfficialLink('QQQ').' '.GetProsharesOfficialLink('TQQQ');
	}
	
	if (in_arraySpyQdii($strSymbol))
	{
		$str .= ' '.GetSpdrOfficialLink('SPY');
	}
	
	if (in_arrayXopQdii($strSymbol))
	{
		$str .= ' '.GetSpdrOfficialLink('XOP').' '.GetSpindicesOfficialLink('SPSIOP');
	}
	
	if (in_arrayXbiQdii($strSymbol))
	{
		$str .= ' '.GetSpdrOfficialLink('XBI').' '.GetSpindicesOfficialLink('SPSIBI');
	}
	
	if ($strCmeUrl = GetCmeUrl($strRealtimeSymbol))				$str .= ' '.GetExternalLink($strCmeUrl, '芝商所');

	$str .= GetStockCategoryLinks($strSymbol);
	return $str.GetQdiiRelated($sym->GetDigitA());
}

   	$acct = new QdiiAccount();
   	$acct->Create();
?>
