<?php
require_once('../../php/class/PHPExcel/IOFactory.php');

function _readXlsFile($bIshares, $strPathName, $net_sql, $shares_sql, $strStockId)
{
//	date_default_timezone_set('America/New_York');
	try 
	{	// 读取excel文件
		$inputFileType = PHPExcel_IOFactory::identify($strPathName);
		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
		$objPHPExcel = $objReader->load($strPathName);
	} 
	catch(Exception $e) 
	{
		DebugString('Load excel file error: "'.pathinfo($strPathName, PATHINFO_BASENAME).'": '.$e->getMessage());
	}
	
	if ($bIshares)
	{
		$iSheet = 1;
		$iNetValueIndex = 2;
		$iSharesIndex = 4;
	}
	else
	{
		$iSheet = 0;
		$iNetValueIndex = 1;
		$iSharesIndex = 2;
	}

	// 确定要读取的sheet，什么是sheet，看excel的右下角
	$sheet = $objPHPExcel->getSheet($iSheet);
	$highestRow = $sheet->getHighestRow();
	$highestColumn = $sheet->getHighestColumn();

	$oldest_ymd = new OldestYMD();
   	$cal_sql = GetCalibrationSql();
	
	// 获取一行的数据
	$iCount = 0;
	$iSharesCount = 0;
	for ($row = 1; $row <= $highestRow; $row++)
	{
		// Read a row of data into an array
		$rowData = $sheet->rangeToArray('A'.$row.':'.$highestColumn.$row, null, true, false);
		//这里得到的rowData都是一行的数据，得到数据后自行处理，我们这里只打出来看看效果
		$ar = $rowData[0];
//		DebugPrint($ar);
		if ($iTick = strtotime($ar[0]))
		{
    		$ymd = new TickYMD($iTick);
    		$strDate = $ymd->GetYMD();
			if ($oldest_ymd->IsTooOld($strDate))	break;
   			if ($oldest_ymd->IsInvalid($strDate) === false)
   			{
  				if ($net_sql->WriteDaily($strStockId, $strDate, $ar[$iNetValueIndex]))
  				{
  					$iCount ++;
  					if ($cal_sql->GetClose($strStockId, $strDate))
  					{
  						DebugString('Delete calibaration on '.$strDate);
  						$cal_sql->DeleteByDate($strStockId, $strDate);
  					}
  				}
  				if ($shares_sql->WriteDaily($strStockId, $strDate, strval(floatval($ar[$iSharesIndex]) / 10000.0)))
  				{
  					$iSharesCount ++;
  				}
   			}
		}
	}
	return '更新'.strval($iCount).'条净值和'.strval($iSharesCount).'条流通股数';
}

function GetNetValueXlsStr($sym, $bAutoCheck = false)
{
	$strSymbol = $sym->GetSymbol();	
   	if ($strUrl = GetEtfNetValueUrl($strSymbol))
	{
		$bIshares = (stripos($strUrl, 'ishares') !== false) ? true : false;
		$strFileName = DebugGetPathName('netvalue_'.$strSymbol.'.xls');
		
		if ($bAutoCheck)	
		{
			if ($bIshares)													return '目前不对ISHARES的ETF做自动更新';
			if (StockNeedFile($strFileName, SECONDS_IN_HOUR) == false)		return '避免频繁自动更新文件';   // update on every hour
		}
		
		if ($str = url_get_contents($strUrl))
		{
			file_put_contents($strFileName, $str);
			$sym->SetTimeZone();
			$strStockId = SqlGetStockId($strSymbol);
			$net_sql = GetNetValueHistorySql();
			$shares_sql = new SharesHistorySql();
			return _readXlsFile($bIshares, $strFileName, $net_sql, $shares_sql, $strStockId);
		}
		else
		{
			file_put_failed($strFileName);
			return '没读到数据';
		}
	}
	return $strSymbol.'不是SPDR或者ISHARES的ETF';
}

function DebugNetValueXlsStr($sym, $bAutoCheck = false)
{
	$str = GetNetValueXlsStr($sym, $bAutoCheck);
    DebugString($str);
}

?>
