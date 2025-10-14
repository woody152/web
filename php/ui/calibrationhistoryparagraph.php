<?php
require_once('stocktable.php');

function _echoCalibrationHistoryItem($fPosition, $ref, $record, $iMultiplier)
{
	$fCalibration = floatval($record['close']);
	$strDate = $record['date'];
	$ar = array($strDate, number_format($fCalibration, NETVALUE_PRECISION), GetHM($record['time']), $record['num']);
	if ($fPosition)
	{
		$ar[] = $ref->GetNetValueDisplay($ref->GetNetValue($strDate));
		$ar[] = number_format(StockCalcHedge($fCalibration, $fPosition) * $iMultiplier);
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
    	$ar[] = new TableColumnNetValue();
    	$ar[] = new TableColumnHedge();
   	}
   	else
	{
		$fPosition = false;
	}
	
	switch ($strSymbol)
	{
	case 'SH518800':
	case 'SH518880':
	case 'SZ159934':
	case 'SZ159937':
		$iMultiplier = 1000;
		break;
	
	case 'SZ159985':
		$iMultiplier = 10;
		break;
	
	case 'SZ161226':
		$iMultiplier = 15;
		break;
		
	default:
		$iMultiplier = 1;
		break;
	}

	EchoTableParagraphBegin($ar, $strSymbol.'calibrationhistory', $strLink);
    if ($result = $cal_sql->GetAll($strStockId, $iStart, $iNum)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
			_echoCalibrationHistoryItem($fPosition, $ref, $record, $iMultiplier);
        }
        mysqli_free_result($result);
    }
    EchoTableParagraphEnd($strMenuLink);
}

?>
