<?php
require_once('_qdiigroup.php');
require_once('_kraneholdingscsv.php');
require_once('../../php/stock/kraneshares.php');

class QdiiAccount extends QdiiGroupAccount
{
    var $oil_ref = false;
    var $cnh_ref;

    function Create() 
    {
//        $strUSD = 'DINIW';
        $strCNH = 'fx_susdcnh';
        $strSymbol = $this->GetName();
        $strEstSymbol = QdiiGetEstSymbol($strSymbol);
        $arLev = $this->GetLeverageSymbols($strEstSymbol);
		$ar = array($strSymbol, $strCNH);
/*		if (in_arrayOilQdii($strSymbol))
		{
			$strOil = 'hf_OIL';
			$ar[] = $strOil; 
		}
		else*/ if (in_arrayXopQdii($strSymbol))
		{
			$strOil = 'hf_CL';
			$ar[] = $strOil; 
		}
		else
		{
			$strOil = false;
		}
        StockPrefetchArrayExtendedData(array_merge($arLev, $ar));
        
        $this->ref = new QdiiReference($strSymbol);
        $this->cnh_ref = new MyStockReference($strCNH);
        if ($strOil)	$this->oil_ref = new MyStockReference($strOil);
        
        if ($strEstSymbol == 'KWEB')
        {
        	$est_ref = $this->ref->GetEstRef();
        	if ($strDate = NeedOfficialWebData($est_ref))
        	{
        		$strEstId = $est_ref->GetStockId();
				if ($strNetValue = GetKraneNetValue($est_ref, $strDate))
				{
					$net_sql = GetNetValueHistorySql();
					$net_sql->ModifyDaily($strEstId, $strDate, $strNetValue);
				}
				else
				{
					$strNetValue = $est_ref->GetNetValueString();
				}
				ReadKraneHoldingsCsvFile($strEstSymbol, $strEstId, $strDate, $strNetValue);
        	}
        }

		$this->QdiiCreateGroup($arLev);
    }
} 

function EchoAll()
{
   	global $acct;
   	$ref = $acct->GetRef();
   	$est_ref = $ref->GetEstRef();
    
    EchoFundEstParagraph($ref);
    if (method_exists($est_ref, 'GetHoldingsDate'))		EchoHoldingsEstParagraph($est_ref);
    
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

   	$str = '';
   	if ($acct->IsAdmin())	$str .= ' '.GetJisiluQdiiLink();
/*	
	if (in_arrayOilQdii($strSymbol))
	{
		$str .= ' '.GetUscfLink();
	}
*/	
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
