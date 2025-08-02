<?php

function _updateStockOptionHoldings($strSymbol, $strStockId, $strDate, $strVal)
{
	$sql = GetStockSql();
	$holdings_sql = GetHoldingsSql();
	$date_sql = new HoldingsDateSql();
	
	$date_sql->WriteDate($strStockId, $strDate);
	$holdings_sql->DeleteAll($strStockId);
	
	$ar = explode(';', $strVal);
	foreach ($ar as $str)
	{
		$arHolding = explode('*', $str);
		if (count($arHolding) == 2)
		{
			$strHolding = StockGetSymbol($arHolding[0]);
			$strRatio = $arHolding[1];
			if ($strRatio == '0')
			{	// delete
			}
			else
			{
				$holdings_sql->InsertHolding($strStockId, $sql->GetId($strHolding), $strRatio);
			}
		}
	}
}

?>
