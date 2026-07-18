<?php
/* https://fund.eastmoney.com/pingzhongdata/162411.js
var Data_netWorthTrend = [
{"x":1317225600000,"y":1.0,"equityReturn":0,"unitMoney":""},
{"x":1317312000000,"y":1.0,"equityReturn":0.0,"unitMoney":""},
...
];*/
function UpdateEastMoneyNetValueHistory($ref)
{
	$strStockId = $ref->GetStockId();
	$strCurDate = $ref->GetDate();
   	$date_sql = new NetValueHistoryDateSql();
   	if ($strCurDate == $date_sql->ReadDate($strStockId))
    {
        DebugString(__FUNCTION__.' already updated', true);
		return false;
	}

	$strDigit = $ref->GetDigitA();
	$strUrl = GetEastMoneyFundUrl()."pingzhongdata/{$strDigit}.js";
   	if ($str = StockSaveDebugFile(DebugGetEastMoneyFileName("netvalue{$strDigit}"), $strUrl))
   	{
		$pattern = '/Data_netWorthTrend\s*=\s*(\[.*?\]);/s';
		if (!preg_match($pattern, $str, $matches))
		{
			DebugString(__FILE__.' 未找到Data_netWorthTrend数据');
			return false;
		}
	    $data = json_decode($matches[1], true);
    	if (json_last_error() !== JSON_ERROR_NONE) 
		{
        	DebugString(__FILE__.' JSON解析失败: '.json_last_error_msg());
			return false;
    	}
        $net_sql = GetNetValueHistorySql();
        $oldest_ymd = new OldestYMD();
        $iTotal = 0;
        $iModified = 0;
		foreach ($data as $arResult)
		{
			$ymd = new TickYMD($arResult['x'] / 1000);
    		$strDate = $ymd->GetYMD();
    		if ($oldest_ymd->IsTooOld($strDate))	continue;
    		$strClose = mysql_round($arResult['y']);
	        if ($oldest_ymd->IsInvalid($strDate) == false)
	        {
	        	$iTotal ++;
	        	if ($net_sql->WriteDaily($strStockId, $strDate, $strClose))
	        	{
	        		$iModified ++;
	        	}
	        }
		}
		DebugVal($iTotal, 'Total');
		DebugVal($iModified, 'Modified');
		$date_sql->WriteDate($strStockId, $strCurDate);
		return true;
   	}
    return false;
}
