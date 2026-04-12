<?php

function UpdateStockOptionHoldings($strStockId, $strDate, $strVal)
{
	$sql = GetStockSql();
	$holdings_sql = GetHoldingsSql();
	$date_sql = new HoldingsDateSql();
	
	$date_sql->WriteDate($strStockId, $strDate);
	$holdings_sql->DeleteAll($strStockId);

	if ($ar = json_decode('{'.$strVal.'}', true))
	{
		foreach ($ar as $strHolding => $strRatio)
		{
			if ($strRatio != '0')
			{
				$strHolding = StockGetSymbol($strHolding);
				$holdings_sql->InsertHoldingId($strStockId, $sql->GetId($strHolding), $strRatio);
			}
		}
	}
}

?>
