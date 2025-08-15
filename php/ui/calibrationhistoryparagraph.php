<?php
require_once('stocktable.php');

function _echoCalibrationHistoryItem($fPosition, $net_sql, $strStockId, $record)
{
	$fCalibration = floatval($record['close']);
	$strDate = $record['date'];
	$ar = array($strDate, strval_round($fCalibration, 4), GetHM($record['time']), $record['num']);
	if ($fPosition)
	{
		$ar[] = $net_sql->GetClose($strStockId, $strDate);
		$ar[] = strval(StockCalcHedge($fCalibration, $fPosition));
	}
	EchoTableColumn($ar);
}

function EchoCalibrationHistoryParagraph($ref, $iStart = 0, $iNum = TABLE_COMMON_DISPLAY)
{
	$strSymbol = $ref->GetSymbol();
	$strStockId = $ref->GetStockId();
   	$cal_sql = GetCalibrationSql();
   	if (IsTableCommonDisplay($iStart, $iNum))
   	{
   		$strMenuLink = '';
   		$strLink = GetCalibrationHistoryLink($strSymbol);
   	}
   	else
   	{
   		$strMenuLink = StockGetMenuLink($strSymbol, $cal_sql->Count($strStockId), $iStart, $iNum);
   		$strLink = GetFundListLink().' '.GetFundLinks($strSymbol).'<br />'.$strMenuLink;
   	}
    
   	$ar = array(new TableColumnDate(), new TableColumnCalibration(), new TableColumnTime(), new TableColumn('次数', 50));
   	if ($ref->IsFundA())
   	{
    	$fPosition = $ref->GetPosition();
    	$net_sql = GetNetValueHistorySql();
    	$ar[] = new TableColumnNetValue();
    	$ar[] = new TableColumnHedge();
   	}
   	else
	{
		$fPosition = false;
		$net_sql = false;
	}

	EchoTableParagraphBegin($ar, $strSymbol.'calibrationhistory', $strLink);
    if ($result = $cal_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
			_echoCalibrationHistoryItem($fPosition, $net_sql, $strStockId, $record);
        }
        mysqli_free_result($result);
    }
    EchoTableParagraphEnd($strMenuLink);
}

?>
