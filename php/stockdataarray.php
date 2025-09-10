<?php
require_once('stock.php');

function _addIndexArray(&$ar, $strIndex, $strEtf, $strDate, $cal_sql)
{
	if (!isset($ar[$strEtf]))
	{
		$arData = array();
		
		$strEtfId = SqlGetStockId($strEtf);
		$arData['calibration'] = $cal_sql->GetCloseFrom($strEtfId, $strDate);
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
				$strStockId = $ref->GetStockId();
				$cal_sql = GetCalibrationSql();
				if ($record = $cal_sql->GetRecordNow($strStockId))
				{
					$arData['calibration'] = $record['close'];
					$strDate = $record['date'];
					$arData['netvalue'] = SqlGetNetValueByDate($strStockId, $strDate);
				
					$cny_ref = $fund_ref->GetForexRef();
					if ($est_ref = $fund_ref->GetEstRef())
					{
						$strIndex = $est_ref->GetSymbol();
						if ($strEtf = GetLeverageHedgeSymbol($strSymbol))
						{
							_addIndexArray($ar, $strIndex, $strEtf, $strDate, $cal_sql);
							$strIndex = $strEtf;
						}
					}
					$arData['symbol_hedge'] = $strIndex;
				}
				else
				{
					$cny_ref = $fund_ref->GetCnyRef();
					$arData['netvalue'] = $fund_ref->GetNetValue();
					$strDate = $fund_ref->GetHoldingsDate();
					$arData['CNYholdings'] = $cny_ref->GetClose($strDate);

					$arSymbolHedge = array();
					$sql = GetStockSql();
					$his_sql = GetStockHistorySql();
					foreach ($fund_ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
					{	
						$arHolding = array();
						$arHolding['ratio'] = $strRatio;
						$arHolding['price'] = $his_sql->GetClose($strHoldingId, $strDate);
						$strHoldingSymbol = $sql->GetStockSymbol($strHoldingId);
						$arSymbolHedge[$strHoldingSymbol] = $arHolding;
					}
					if (count($arSymbolHedge) > 0)	$arData['symbol_hedge'] = $arSymbolHedge;
				}
				$arData['CNY'] = $cny_ref->GetPrice();
				$arData['position'] = strval($fund_ref->GetPosition());
			}
		}
		$ar[$strSymbol] = $arData;
    }
    DebugPrint($ar);
    return $ar;
}

?>
