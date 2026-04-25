<?php

function StockOptionDecodeHoldings($strVal, &$arRatio)
{
	if ($ar = DebugDecode($strVal))
	{
		$sql = GetStockSql();
		$fPos = 0.0;
		foreach ($ar as $strHolding => $strRatio)
		{
			if ($strRatio != '0')
			{
				$strHolding = StockGetSymbol($strHolding);
				$strHoldingId = $sql->GetId($strHolding);
				$fRatio = floatval($strRatio);
				$arRatio[$strHoldingId] = $fRatio;
				$fPos += $fRatio;
			}
		}

		$fPos /= 100.0;
		foreach ($arRatio as $strHoldingId => $fRatio)
		{
			$arRatio[$strHoldingId] = $fRatio / $fPos;
		}
		return $fPos;
	}
	return false;
}

function UpdateStockOptionHoldings($strStockId, $strDate, $strVal)
{
	$arRatio = array();
	if ($fPos = StockOptionDecodeHoldings($strVal, $arRatio))
	{
		$pos_sql = GetPositionSql();
		$pos_sql->WritePos($strStockId, $fPos);
		
		$date_sql = new HoldingsDateSql();
		$date_sql->WriteDate($strStockId, $strDate);

		$holdings_sql = GetHoldingsSql();
		$holdings_sql->DeleteAll($strStockId);
		foreach ($arRatio as $strHoldingId => $fRatio)
		{
			$holdings_sql->InsertHoldingId($strStockId, $strHoldingId, strval($fRatio));
		}
	}
}

/*
function UpdateStockOptionHoldings($strStockId, $strDate, $strVal)
{
	$sql = GetStockSql();
	$holdings_sql = GetHoldingsSql();
	$date_sql = new HoldingsDateSql();
	
	$date_sql->WriteDate($strStockId, $strDate);
	$holdings_sql->DeleteAll($strStockId);

	$fPos = 0.0;
	$arRatio = array();
	if ($ar = DebugDecode($strVal))
	{
		foreach ($ar as $strHolding => $strRatio)
		{
			if ($strRatio != '0')
			{
				$strHolding = StockGetSymbol($strHolding);
				$strHoldingId = $sql->GetId($strHolding);
				$fRatio = floatval($strRatio);
				$arRatio[$strHoldingId] = $fRatio;
				$fPos += $fRatio;
				//$holdings_sql->InsertHoldingId($strStockId, $sql->GetId($strHolding), $strRatio);
			}
		}

		foreach ($arRatio as $strHoldingId => $fRatio)
		{
			$holdings_sql->InsertHoldingId($strStockId, $strHoldingId, strval(100.0*$fRatio/$fPos));
		}
		$pos_sql = GetPositionSql();
		$pos_sql->WritePos($strStockId, $fPos/100.0);
	}
}
*/

?>
