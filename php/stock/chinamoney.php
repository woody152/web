<?php

function _chinaMoneyNeedData($strDate)
{
	$net_sql = GetNetValueHistorySql();
    if ($net_sql->GetRecord(SqlGetStockId('USCNY'), $strDate))
    {
//    	DebugString('Database entry existed');
    	return false;
    }
    return $strDate;
}

function _chinaMoneyInsertData($strMoney, $strDate, $strPrice)
{
	DebugString('Insert '.$strMoney);
	$net_sql = GetNetValueHistorySql();
	$net_sql->InsertDaily(SqlGetStockId($strMoney), $strDate, $strPrice);
}

function GetChinaMoney($ref)
{
    if (_chinaMoneyNeedData($ref->GetDate()) == false)			return;
	if ($ref->GetHourMinute() < 915)									return;	// Data not updated until 9:15
    
//    date_default_timezone_set('PRC');
	$ref->SetTimeZone();
	if ($ar = StockDebugJson(DebugGetChinaMoneyFile(), GetChinaMoneyJsonUrl()))
	{
		$arData = $ar['data'];
    	if ($strDate = _chinaMoneyNeedData(substr($arData['lastDate'], 0, 10)))		// 2018-04-12 9:15
		{
			if (isset($ar['records']))
			{
			    foreach ($ar['records'] as $arPair)
    			{
    				$strPair = $arPair['vrtEName'];
    				$strPrice = $arPair['price'];
    				DebugString($strPair.' '.$strPrice);
    				switch ($strPair)
    				{
    				case 'USD/CNY':
    					_chinaMoneyInsertData('USCNY', $strDate, $strPrice);
    					break;
    		
    				case 'EUR/CNY':
    					_chinaMoneyInsertData('EUCNY', $strDate, $strPrice);
    					break;
    		
    				case '100JPY/CNY':
    					_chinaMoneyInsertData('JPCNY', $strDate, $strPrice);
    					break;
    		
    				case 'HKD/CNY':
    					_chinaMoneyInsertData('HKCNY', $strDate, $strPrice);
    					break;
					}
				}
    		}
			else	DebugString(__FUNCTION__.' no records');
		}
	}
}

?>
