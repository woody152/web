<?php
require_once('stock.php');

function _addIndexArray(&$ar, $strIndex, $strEtf)
{
	if (!isset($ar[$strEtf]))
	{
		$arData = array();
		//$arData['CNY'] = '1.0';
		$arData['symbol_hedge'] = $strIndex;
		
		$strEtfId = SqlGetStockId($strEtf);
		$pos_sql = new FundPositionSql();
		$arData['position'] = strval($pos_sql->ReadVal($strEtfId));
		
		$calibration_sql = GetCalibrationSql();
		$arData['calibration'] = $calibration_sql->GetCloseNow($strEtfId);
		$strDate = $calibration_sql->GetDateNow($strEtfId);
		$arData['netvalue'] = SqlGetNetValueByDate($strEtfId, $strDate);

		$ar[$strEtf] = $arData;
	}
}

function GetStockDataArray($strSymbols)
{
	InitGlobalStockSql();
    $arSymbol = GetInputSymbolArray(SqlCleanString($strSymbols));
    StockPrefetchArrayExtendedData($arSymbol);
	
	$ar = array();
	foreach ($arSymbol as $strSymbol)
	{
		$arData = array();
		$ref = StockGetReference($strSymbol);
		if ($ref->IsSymbolA())
		{
			if ($ref->IsFundA())
			{
				$fund_ref = StockGetFundReference($strSymbol);
				if (method_exists($fund_ref, 'GetEstRef'))
				{	
					if ($est_ref = $fund_ref->GetEstRef())
					{
						$strIndex = $est_ref->GetSymbol();
						if ($est_ref->IsIndex())
						{
							switch ($strIndex)
							{
							case '^GSPC':
								$strEtf  = 'SPY';
								break;
							
							case '^NDX':
								$strEtf = 'QQQ';
								break;
							}
							_addIndexArray($ar, $strIndex, $strEtf);
							$strIndex = $strEtf;
						}
					}
				}
				else if ($strSymbol == 'SZ164906')			$strIndex = 'KWEB';

				$cny_ref = $fund_ref->GetCnyRef();
				$arData['CNY'] = $cny_ref->GetPrice();
				$arData['symbol_hedge'] = $strIndex;
				$arData['position'] = strval(RefGetPosition($fund_ref));

				$strStockId = $ref->GetStockId();
				$calibration_sql = GetCalibrationSql();
				$arData['calibration'] = $calibration_sql->GetCloseNow($strStockId);
				$strDate = $calibration_sql->GetDateNow($strStockId);
				$arData['netvalue'] = SqlGetNetValueByDate($strStockId, $strDate);
			}
		}
		$ar[$strSymbol] = $arData;
    }
    
    return $ar;
}

?>
