<?php

function SqlDeleteNetValueHistory($strStockId)
{
	$net_sql = GetNetValueHistorySql();
	$iTotal = $net_sql->Count($strStockId);
	if ($iTotal > 0)
	{
		DebugVal($iTotal, 'Net value history existed');
		$net_sql->DeleteAll($strStockId);
	}
}

function SqlDeleteStock($strStockId)
{
	$sql = GetStockSql();
	$sql->DeleteById($strStockId);
}

function DeleteStock($strStockId)
{
	SqlDeleteStockEma($strStockId);
	SqlDeleteStockHistory($strStockId);
	SqlDeleteNetValueHistory($strStockId);
	SqlDeleteStock($strStockId);
}

?>
