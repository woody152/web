<?php
require_once('stock.php');

function _addIndexArray(&$ar, $strIndex, $strEtf, $strDate, $cal_sql)
{
	if (!isset($ar[$strEtf]))
	{
		$arData = array();
		
		$strEtfId = SqlGetStockId($strEtf);
		$arData['calibration'] = $cal_sql->GetClose($strEtfId, $strDate);
		$arData['netvalue'] = SqlGetNetValueByDate($strEtfId, $strDate);

		$pos_sql = GetPositionSql();
		$arData['position'] = strval($pos_sql->ReadVal($strEtfId));

		$arData['symbol_hedge'] = $strIndex;
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
				$cny_ref = $fund_ref->GetCnyRef();
				
				$strStockId = $ref->GetStockId();
				$cal_sql = GetCalibrationSql();
				if ($record = $cal_sql->GetRecordNow($strStockId))
				{
					$arData['calibration'] = $record['close'];
					$strDate = $record['date'];
					$arData['netvalue'] = SqlGetNetValueByDate($strStockId, $strDate);
				
					if (method_exists($fund_ref, 'GetEstRef'))
					{	
						if ($est_ref = $fund_ref->GetEstRef())
						{
							$strIndex = $est_ref->GetSymbol();
							if ($strEtf = GetLeverageHedgeSymbol($strSymbol))
							{
								_addIndexArray($ar, $strIndex, $strEtf, $strDate, $cal_sql);
								$strIndex = $strEtf;
							}
						}
					}
					else if ($strSymbol == 'SZ164906')			$strIndex = 'KWEB';
				}
				else if ($strSymbol == 'SZ164701')
				{
					$strIndex = 'GLD';
					$net_sql = GetNetValueHistorySql();
					if ($record = $net_sql->GetRecordNow($strStockId))
					{
						$strNetValue = $record['close'];
						$strDate = $record['date'];
						$arData['calibration'] = strval(round(floatval(SqlGetHistoryByDate(SqlGetStockId($strIndex), $strDate)) * floatval($cny_ref->GetPrice()) / floatval($strNetValue), 6));
						$arData['netvalue'] = $strNetValue;
					}
				}
				$arData['CNY'] = $cny_ref->GetPrice();
				$arData['position'] = strval($fund_ref->GetPosition());
				$arData['symbol_hedge'] = $strIndex;
			}
		}
		$ar[$strSymbol] = $arData;
    }
    DebugPrint($ar);
    return $ar;
}

?>
