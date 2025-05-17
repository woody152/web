<?php
require_once('stocktable.php');

function _echoCalibrationHistoryItem($fPosition, $nav_sql, $strStockId, $record)
{
	$fCalibration = floatval($record['close']);
	$strDate = $record['date'];
	$ar = array($strDate, strval_round($fCalibration, 4), GetHM($record['time']), $record['num']);
	if ($fPosition)
	{
		$ar[] = $nav_sql->GetClose($strStockId, $strDate);
		$ar[] = strval(round($fCalibration / $fPosition));
	}
	EchoTableColumn($ar);
}

function EchoCalibrationHistoryParagraph($ref, $iStart = 0, $iNum = TABLE_COMMON_DISPLAY)
{
	$strSymbol = $ref->GetSymbol();
	$strStockId = $ref->GetStockId();
   	$calibration_sql = GetCalibrationSql();
   	if (IsTableCommonDisplay($iStart, $iNum))
   	{
   		$strMenuLink = '';
   		$strLink = GetCalibrationHistoryLink($strSymbol);
   	}
   	else
   	{
   		$strMenuLink = StockGetMenuLink($strSymbol, $calibration_sql->Count($strStockId), $iStart, $iNum);
   		$strLink = GetFundListLink().' '.GetFundLinks($strSymbol).'<br />'.$strMenuLink;
   	}
    
   	$ar = array(new TableColumnDate(), new TableColumnCalibration(), new TableColumnTime(), new TableColumn('次数', 50));
   	if ($ref->IsFundA())
   	{
    	$fPosition = RefGetPosition($ref);
    	$nav_sql = GetNavHistorySql();
    	$ar[] = new TableColumnNav();
    	$ar[] = new TableColumnConvert();
   	}
   	else
	{
		$fPosition = false;
		$nav_sql = false;
	}

	EchoTableParagraphBegin($ar, $strSymbol.'calibrationhistory', $strLink);
    if ($result = $calibration_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
			_echoCalibrationHistoryItem($fPosition, $nav_sql, $strStockId, $record);
        }
        mysqli_free_result($result);
    }
    EchoTableParagraphEnd($strMenuLink);
}

?>
