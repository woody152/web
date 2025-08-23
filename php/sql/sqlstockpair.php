<?php
require_once('sqlpair.php');

class StockPairSql extends PairSql
{
    function GetSymbolArray($strPairSymbol = false)
    {
		$sql = GetStockSql();
		$arSymbol = array();
		if ($strPairSymbol)
		{
			if ($strPairId = $sql->GetId($strPairSymbol))		$ar = $this->GetIdArray($strPairId);
			else	return $arSymbol; 
		}
		else														$ar = $this->GetIdArray();
		
		if (count($ar) > 0)
		{
			foreach ($ar as $strStockId)	$arSymbol[] = $sql->GetStockSymbol($strStockId);
			sort($arSymbol);
		}
		return $arSymbol;
	}
	
	function GetSymbol($strPairSymbol)
	{
		$sql = GetStockSql();
		if ($strPairId = $sql->GetId($strPairSymbol))
		{
			if ($strStockId = $this->GetId($strPairId))
			{
				return $sql->GetStockSymbol($strStockId);
			}
		}
		return false;
	}
	
	function GetPairSymbol($strSymbol)
	{
		$sql = GetStockSql();
		if ($strStockId = $sql->GetId($strSymbol))
		{
			if ($strPairId = $this->ReadPair($strStockId))
			{
				return $sql->GetStockSymbol($strPairId);
			}
		}
		return false;
	}

	function WritePairSymbol($strSymbol, $strPairSymbol)
	{
		$sql = GetStockSql();
		if ($strStockId = $sql->GetId($strSymbol))
		{
			if ($strPairId = $sql->GetId($strPairSymbol))
			{
				return $this->WritePair($strStockId, $strPairId);
			}
		}
		return false;
	}
	
	function DeleteBySymbol($strSymbol)
	{
		if ($strStockId = SqlGetStockId($strSymbol))
		{
			return $this->DeleteById($strStockId);
		}
		return false;
	}
	
	function DeleteByPairSymbol($strPairSymbol)
	{
		if ($strPairId = SqlGetStockId($strPairSymbol))
		{
			return $this->Delete($strPairId);
		}
		return false;
	}
}

?>
