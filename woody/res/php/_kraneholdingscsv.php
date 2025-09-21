<?php
require_once('_holdingscsvfile.php');

class _KraneHoldingsCsvFile extends _HoldingsCsvFile
{
	var $bUse;
	
    public function __construct($strFileName, $strStockId, $strDate) 
    {
        parent::__construct($strFileName, $strStockId);
        $this->SetDate($strDate);
        
        $this->bUse = false;
    }
    
    public function OnLineArray($arWord)
    {
    	if (count($arWord) != 7)	return true;
    	
    	$strName = $arWord[1];
//    	DebugString($strName);
    	if (($strName == 'HONG KONG DOLLAR') || ($strName == 'Cash'))	return true;
    	
    	$strRatio = $arWord[2];
    	if ($arWord[0] == 'Rank')
    	{
    		$this->DeleteAllHoldings();
    		$this->bUse = true;
    	}
    	else if (floatval($strRatio) < 0.01)		$this->bUse = false;
    	else if ($this->bUse)
    	{
    		$strHolding = $arWord[3];
    		if (is_numeric($strHolding))	$strHolding = BuildHongkongStockSymbol($strHolding);
    		if ($strHolding == 'YY')		$strHolding = 'JOYY';
    		if ($this->InsertHolding($strHolding, $strName, $strRatio))		$this->AddSum(floatval(str_replace(',', '', $arWord[6])));
    	}
    	return true;
    }
}

function ReadKraneHoldingsCsvFile($strSymbol, $strStockId, $strDate, $strNetValue)
{
	$arYMD = explode('-', $strDate);
	$strFileName = $arYMD[1].'_'.$arYMD[2].'_'.$arYMD[0].'_'.strtolower($strSymbol).'_holdings.csv';
	$strUrl = GetKraneUrl().'csv/'.$strFileName;
	if (StockSaveDebugCsv($strFileName, $strUrl))
	{
		$csv = new _KraneHoldingsCsvFile($strFileName, $strStockId, $strDate);
		$csv->Read();
		$fMarketValue = $csv->GetSum();
		DebugVal($fMarketValue, __FUNCTION__);
		if ($fMarketValue > MIN_FLOAT_VAL)
		{
			if ($csv->UpdateHoldingsDate())
			{
				$shares_sql = new SharesHistorySql();
				$strShares = number_format($fMarketValue / floatval($strNetValue) / 10000.0, 2, '.', ''); 
				DebugString(__FUNCTION__.' shares on '.$strDate.': '.$strShares);
				$shares_sql->WriteDaily($strStockId, $strDate, $strShares);
			}
		}
		else
		{
			DebugString(__FUNCTION__.' failed');
		}
	}
}

?>
