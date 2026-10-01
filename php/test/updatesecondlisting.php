<?php
require_once('_commonupdatestock.php');

/*在港第二上市股票 名稱 港股比美股價格3 代號 股價1 升跌(%)2 1個月升跌(%)2 3個月升跌(%)2 
和黃醫藥 高1.01% 00013.HK 港元 27.300 (美股折合 27.028) -2.151% -17.89% -3.70% HCM.US 美元 17.360 (港股折合 17.535) -1.810% -18.42% -2.96% 
華住集團－Ｓ1 低1.86% 01179.HK 港元 27.350 (美股折合 27.869) -1.619% -5.36% +20.75% HTHT.US 美元 35.800 (港股折合 35.134) -0.334% -5.04% +22.94% 
騰訊音樂－ＳＷ 低3.18% 01698.HK 港元 43.450 (美股折合 44.878) -3.013% -5.54% +6.89% TME.US 美元 11.530 (港股折合 11.163) +0.087% -2.29% +12.49%
*/

class _SecondListingAccount extends Account
{
    public function AdminProcess()
    {
    	$strUrl = GetAastocksSecondListingUrl();
    	$iPage = 1;
    	do
    	{
    		if ($str = url_get_contents($strUrl.'?page='.strval($iPage)))
    		{
    			$iPage ++;
    			$str = strip_tags($str);
    			$str = str_replace('－', '', $str);
    			$arMatch = array();
    			$strPattern = '#\b(\w+)\s+(高|低)\d+\.\d+%?\s+([0-9]{5})\.HK.*?\b.*?港元 (\d+\.\d+).*?\((?:美股折合 )?([\d.]+)\).*?\b([A-Z]+)\.US.*?\b#u';
    			preg_match_all($strPattern, $str, $arMatch, PREG_SET_ORDER);
    	
    			$iCount = count($arMatch);
    			DebugVal($iCount, '找到的匹配项如下：');
    			foreach ($arMatch as $arItem) 
    			{
    				$iStart = 2;
    				DebugString('HK股票代码：'.$arItem[$iStart + 1].' 港元价格：'.$arItem[$iStart + 2].' 美股折合价格：'.$arItem[$iStart + 3].' US股票代码：'.$arItem[$iStart + 4].' '.preg_replace('/\d+$/', '', $arItem[1]).' '.$arItem[2]);
    			}
    		}
    		else	$iCount = 0;
    	} while ($iCount == 20);
/*
    	$iCount = 0;
    	$sql = GetStockSql();
    	foreach ($arMatch as $arItem)
    	{
    		$ar = explode(',', $arItem[1]);
    		$strSymbol = reset($ar);
    		$strName = end($ar);
    		if ($sql->WriteSymbol($strSymbol, $strName))
    		{
    			DebugString($strSymbol.' '.$strName);
    			$iCount ++;
    		}
    	}
    	DebugVal($iCount, 'US stock updated');*/
    }
}

   	$acct = new _SecondListingAccount();
	$acct->AdminRun();
