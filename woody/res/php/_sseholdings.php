<?php
require_once('_etfholdings.php');
/*
class _SseHoldingsFile extends _EtfHoldingsFile
{
    public function __construct($strFileName, $strStockId) 
    {
        parent::__construct($strFileName, $strStockId);
        $this->SetSeparator('|');
    }
    
    public function OnLineArray($arWord)
    {
    	if (count($arWord) == 1)
    	{
    		$ar = explode('=', $arWord[0]);
    		switch ($ar[0])
    		{
    		case 'EstimateCashComponent':
    			$this->SubCash(floatval($ar[1]));
    			break;
    			
			case 'PreTradingDay':
    			$this->SetDate(ConvertYMD($ar[1]));
				$this->DeleteAllHoldings();
    			break;
    		
    		case 'NAVperCU':
    			$this->AddCash(floatval($ar[1]));
    			break;
    		}
    	}
    	else
    	{
    		$strHolding = trim($arWord[0]);
    		if (is_numeric($strHolding))
    		{
    			if (strlen($strHolding) <= 5)	$strHolding = BuildHongkongStockSymbol($strHolding);
    			else								$strHolding = BuildChinaStockSymbol($strHolding);
    		}
    		$this->AddHolding($strHolding, GbToUtf8(trim($arWord[1])), floatval(trim($arWord[6])));
    	}
    	return true;
    }
}

// https://query.sse.com.cn/etfDownload/downloadETF2Bulletin.do?etfType=087
function ReadSseHoldingsFile($strSymbol, $strStockId)
{
	$strEtfType = GetSseEtfType($strSymbol);
	$strUrl = GetSseUrl('query').'etfDownload/downloadETF2Bulletin.do?etfType='.$strEtfType;
	$strFileName = $strSymbol.'.txt';
	
	if (StockSaveDebugCsv($strFileName, $strUrl))
	{
		$csv = new _SseHoldingsFile($strFileName, $strStockId);
		$csv->Read();
		return $csv->Done();
	}
	return false;
}
*/

// https://query.sse.com.cn/etfDownload/downloadETF2Bulletin.do?fundCode=513050
function ReadSseHoldingsFile($strSymbol, $strStockId)
{
	if ($xml = StockDebugXml(DebugGetPathName('holdings_'.$strSymbol.'.xml')
								 ,GetSseUrl('query').'etfDownload/downloadETF2Bulletin.do?fundCode='.substr($strSymbol, 2, 6))
	   )
	{
		$csv = new _EtfHoldingsFile('empty.txt', $strStockId);
		$csv->SubCash(floatval($xml->EstimateCashComponent));
    	$csv->SetDate(ConvertYMD($xml->PreTradingDay));
		$csv->DeleteAllHoldings();
		$csv->AddCash(floatval($xml->NAVperCU));
		foreach ($xml->ComponentList->Component as $holding_xml) 
		{
    		$strHolding = trim($holding_xml->InstrumentID);
    		if (is_numeric($strHolding))
    		{
    			if (strlen($strHolding) <= 5)	$strHolding = BuildHongkongStockSymbol($strHolding);
    			else									$strHolding = BuildChinaStockSymbol($strHolding);
    		}
    		$csv->AddHolding($strHolding, trim($holding_xml->InstrumentName), floatval(trim($holding_xml->SubstitutionCashAmount)));
		}
		$csv->TriggerReport();
		return $csv->Done();
	}
	return false;
}

?>
