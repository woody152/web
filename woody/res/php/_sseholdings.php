<?php
require_once('_etfholdings.php');

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
    		$strHolding = $csv->ConvertHolding(trim($holding_xml->InstrumentID));
    		$csv->AddHolding($strHolding, trim($holding_xml->InstrumentName), floatval(trim($holding_xml->SubstitutionCashAmount)));
		}
		$csv->TriggerReport();
		return $csv->Done();
	}
	return false;
}

?>
