<?php
require_once('stock.php');

function _addIndexArray(&$ar, $strIndex, $strEtf, $strDate, $cal_sql, $pos_sql)
{
	if (!isset($ar[$strEtf]))
	{
		$strEtfId = SqlGetStockId($strEtf);

		$arData = [];
		$arData['calibration'] = rtrim0($cal_sql->GetCloseFrom($strEtfId, $strDate));
		$strDate = $cal_sql->GetDateFrom($strEtfId, $strDate);
		$arData['date'] = $strDate;
		$arData['netvalue'] = rtrim0(SqlGetNetValueByDate($strEtfId, $strDate));
		$arData['position'] = strval($pos_sql->ReadPos($strEtfId));

		$arData['symbol_hedge'] = $strIndex;
		$ar[$strEtf] = $arData;
	}
}

function _addFundPairArray(&$ar, $strIndex, $cal_sql, $pos_sql, $last_sql)
{
	if (!isset($ar[$strIndex]))
	{
		$pair_sql = GetFundPairSql();
       	if ($strPair = $pair_sql->GetPairSymbol($strIndex))	
        {
			$strIndexId = SqlGetStockId($strIndex);

			$arData = [];
			$arData['calibration'] = rtrim0($cal_sql->GetCloseNow($strIndexId));
			$arData['date'] = $cal_sql->GetDateNow($strIndexId);
			$arData['netvalue'] = strval($last_sql->ReadVal($strIndexId));
			$arData['position'] = strval($pos_sql->ReadPos($strIndexId));

			$arData['symbol_hedge'] = $strPair;
			$ar[$strIndex] = $arData;
		}
	}
}

function GetStockDataArray($strSymbols, $arRange = false)
{
	DebugString(__FUNCTION__.' '.$strSymbols);

	InitGlobalStockSql();
    $arSymbol = GetInputSymbolArray(SqlCleanString($strSymbols));
	if ($arRange)
	{
		$arSymbol = array_intersect($arRange, $arSymbol);
	}
    StockPrefetchArrayExtendedData($arSymbol);
	
	$cal_sql = GetCalibrationSql();
	$pos_sql = GetPositionSql();
	$last_sql = new LastCalibrationSql();
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
				$cny_ref = $fund_ref->IsEtfA() ? false : $fund_ref->GetCnyRef();
				$strStockId = $ref->GetStockId();
				if ($record = $cal_sql->GetRecordNow($strStockId))
				{
					$arData['calibration'] = rtrim0($record['close']);
					$strDate = $record['date'];
					$arData['date'] = $strDate;
					$arData['netvalue'] = rtrim0(SqlGetNetValueByDate($strStockId, $strDate));
				
					if (method_exists($fund_ref, 'GetEstRef'))
					{
						if ($est_ref = $fund_ref->GetEstRef())
						{
							$strIndex = $est_ref->GetSymbol();
							if ($strEtf = GetLeverageHedgeSymbol($strSymbol))
							{
								_addIndexArray($ar, $strIndex, $strEtf, $strDate, $cal_sql, $pos_sql);
								_addFundPairArray($ar, $strIndex, $cal_sql, $pos_sql, $last_sql);
								$strIndex = $strEtf;
							}
						}
					}
					else
					{
						if ($pair_ref = $fund_ref->GetPairRef())
						{
							$strIndex = $pair_ref->GetSymbol();
						}
					}
					$arData['symbol_hedge'] = $strIndex;
					$arData['hedge'] = strval(round(GetStockHedge($strSymbol, $strStockId), FLOAT_PRECISION));
				}
				else
				{
					$strDate = $fund_ref->GetHoldingsDate();
					$arData['date'] = $strDate;
					$arData['netvalue'] = rtrim0($fund_ref->GetNetValueString());
					$arData['CNYholdings'] = rtrim0($cny_ref->GetClose($strDate));

					$arSymbolHedge = array();
					$sql = GetStockSql();
					$his_sql = GetStockHistorySql();
					foreach ($fund_ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
					{	
						$arHolding = array();
						$arHolding['ratio'] = $strRatio;
						$arHolding['price'] = rtrim0($his_sql->GetClose($strHoldingId, $strDate));
						$strHoldingSymbol = $sql->GetStockSymbol($strHoldingId);
						$arSymbolHedge[$strHoldingSymbol] = $arHolding;
						if (str_starts_with($strHoldingSymbol, YAHOO_INDEX_CHAR) === false)	_addFundPairArray($ar, $strHoldingSymbol, $cal_sql, $pos_sql, $last_sql);
					}
					if (count($arSymbolHedge) > 0)	$arData['symbol_hedge'] = $arSymbolHedge;
				}
//				$arData['CNY'] = $cny_ref ? $cny_ref->GetPrice() : '1.0';
				if ($cny_ref)	$arData['CNY'] = $cny_ref->GetPrice();
				$arData['position'] = strval($fund_ref->GetPosition());
			}
		}
		$ar[$strSymbol] = $arData;
    }
//    DebugPrint($ar);
    return $ar;
}

?>
