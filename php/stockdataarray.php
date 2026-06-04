<?php
require_once('stock.php');

function _addIndexArray(&$ar, $strIndex, $strSymbol, $strDate, $sql, $cal_sql, $net_sql, $pos_sql)
{
	if (!isset($ar[$strSymbol]))
	{
		$strStockId = $sql->GetId($strSymbol);
    	$record = $cal_sql->GetRecordFromDate($strStockId, $strDate);
		$strDate = $record['date'];

		$arData = [];
		$arData['position'] = strval($pos_sql->ReadPos($strStockId));
		$arData['calibration'] = rtrim0($record['close']);
		$arData['date'] = $strDate;
		$arData['netvalue'] = rtrim0($net_sql->GetClose($strStockId, $strDate));
		$arData['symbol_hedge'] = $strIndex;
		$ar[$strSymbol] = $arData;
	}
}

function _addFundPairArray(&$ar, $strSymbol, $sql, $cal_sql, $last_sql, $pair_sql, $pos_sql)
{
	if (!isset($ar[$strSymbol]))
	{
		$strStockId = $sql->GetId($strSymbol);
       	if ($strPairId = $pair_sql->ReadPair($strStockId))	
        {
			$arData = [];
			$arData['position'] = strval($pos_sql->ReadPos($strStockId));
			$arData['calibration'] = rtrim0($cal_sql->GetCloseNow($strStockId));
			$arData['date'] = $cal_sql->GetDateNow($strStockId);
			$arData['netvalue'] = strval($last_sql->ReadVal($strStockId));
			$arData['symbol_hedge'] = $sql->GetStockSymbol($strPairId);
			$ar[$strSymbol] = $arData;
		}
	}
}

function GetStockDataArray($strSymbols, $arRange = false)
{
	DebugString(__FUNCTION__.' '.$strSymbols);
	InitGlobalStockSql();
    $arSymbol = GetInputSymbolArray(SqlCleanString($strSymbols));
	if ($arRange)	$arSymbol = array_intersect($arRange, $arSymbol);
    StockPrefetchArrayExtendedData($arSymbol);
	
	$sql = GetStockSql();
	$cal_sql = GetCalibrationSql();
	$last_sql = new LastCalibrationSql();
	$net_sql = GetNetValueHistorySql();
	$pair_sql = GetFundPairSql();
	$pos_sql = GetPositionSql();
	$ar = [];
	foreach ($arSymbol as $strSymbol)
	{
		$arData = [];
		$ref = StockGetReference($strSymbol);
		if ($ref->IsSymbolA())
		{
			if ($ref->IsFundA())
			{
				$fund_ref = StockGetFundReference($strSymbol);
				$arData['position'] = strval($fund_ref->GetPosition());
				$strOfficialDate = $fund_ref->GetOfficialDate();
				$arData['est_date'] = $strOfficialDate;
				if ($cny_ref = $fund_ref->GetCnyRef())
				{
					$arData['CNYest'] = rtrim0($cny_ref->GetClose($strOfficialDate));
					if ($fund_ref->IsLofA())	$arData['CNY'] = $cny_ref->GetPrice();
				}
				
				$strStockId = $ref->GetStockId();
				if ($record = $cal_sql->GetRecordNow($strStockId))
				{
					$arData['calibration'] = rtrim0($record['close']);
					$strDate = $record['date'];
					$arData['date'] = $strDate;
					$arData['netvalue'] = rtrim0($net_sql->GetClose($strStockId, $strDate));
				
					$est_ref = false;
					if (method_exists($fund_ref, 'GetEstRef'))
					{
						if ($est_ref = $fund_ref->GetEstRef())
						{
							$strIndex = $est_ref->GetSymbol();
							if ($strEtf = GetLeverageHedgeSymbol($strSymbol))
							{
								_addIndexArray($ar, $strIndex, $strEtf, $strDate, $sql, $cal_sql, $net_sql, $pos_sql);
								_addFundPairArray($ar, $strIndex, $sql, $cal_sql, $last_sql, $pair_sql, $pos_sql);
								$strIndex = $strEtf;
							}
						}
					}
					else
					{
						if ($est_ref = $fund_ref->GetPairRef())
						{
							$strIndex = $est_ref->GetSymbol();
						}
					}
					if ($est_ref)
					{
						if ($fVal = $est_ref->GetNetValue($strOfficialDate))	$arData['est_netvalue'] = strval($fVal);
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

					$arSymbolHedge = [];
					$his_sql = GetStockHistorySql();
					foreach ($fund_ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
					{	
						$arHolding = [];
						$arHolding['ratio'] = $strRatio;
						$arHolding['price'] = rtrim0($his_sql->GetClose($strHoldingId, $strDate));
						if ($strOfficial = $his_sql->GetAdjClose($strHoldingId, $strOfficialDate))	$arHolding['est_price'] = rtrim0($strOfficial);
						$strHoldingSymbol = $sql->GetStockSymbol($strHoldingId);
						$arSymbolHedge[$strHoldingSymbol] = $arHolding;
						if (str_starts_with($strHoldingSymbol, YAHOO_INDEX_CHAR) === false)
						{
							_addFundPairArray($ar, $strHoldingSymbol, $sql, $cal_sql, $last_sql, $pair_sql, $pos_sql);
						}
					}
					if (count($arSymbolHedge) > 0)	$arData['symbol_hedge'] = $arSymbolHedge;
				}
			}
		}
		$ar[$strSymbol] = $arData;
    }
	//DebugPrint($ar);
    return $ar;
}

?>
