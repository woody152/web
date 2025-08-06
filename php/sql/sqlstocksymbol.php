<?php
//require_once('sqlint.php');
require_once('sqlkeyname.php');
require_once('sqlkeytable.php');
require_once('sqldailyclose.php');
require_once('sqldailytime.php');
require_once('sqlholdings.php');

class StockHistorySql extends DailyCloseSql
{
    public function __construct() 
    {
        parent::__construct('stockhistory');
    }

    public function Create()
    {
    	$str = ' `open` DOUBLE(10,3) NOT NULL ,'
         	  . ' `high` DOUBLE(10,3) NOT NULL ,'
         	  . ' `low` DOUBLE(10,3) NOT NULL ,'
         	  . ' `close` DOUBLE(10,3) NOT NULL ,'
         	  . ' `volume` BIGINT UNSIGNED NOT NULL ,'
         	  . ' `adjclose` DOUBLE(13,6) NOT NULL';
        return $this->CreateDailyCloseTable($str);
    }

    function WriteHistory($strStockId, $strDate, $strClose, $strOpen = '', $strHigh = '', $strLow = '', $strVolume = '', $strAdjClose = false)
    {
    	if ($strAdjClose == false)	$strAdjClose = $strClose;
    	
    	$ar = array('date' => $strDate,
    				   'open' => $strOpen,
    				   'high' => $strHigh,
    				   'low' => $strLow,
    				   'close' => $strClose,
    				   'volume' => $strVolume,
    				   'adjclose' => $strAdjClose);
    	
    	if ($record = $this->GetRecord($strStockId, $strDate))
    	{
    		unset($ar['date']);
    		if (abs(floatval($record['open']) - floatval($strOpen)) < 0.0005)					unset($ar['open']);
    		if (abs(floatval($record['high']) - floatval($strHigh)) < 0.0005)					unset($ar['high']);
    		if (abs(floatval($record['low']) - floatval($strLow)) < 0.0005)						unset($ar['low']);
    		if (abs(floatval($record['close']) - floatval($strClose)) < 0.0005)					unset($ar['close']);
    		if ($record['volume'] == $strVolume)												unset($ar['volume']);
    		if (abs(floatval($record['adjclose']) - floatval($strAdjClose)) < MIN_FLOAT_VAL)	unset($ar['adjclose']);
    		
    		if (count($ar) > 0)	return $this->UpdateById($ar, $record['id']);
    	}
    	else	return $this->InsertArrays($this->MakeFieldKeyId($strStockId), $ar);
    	return false;
    }
    
    function UpdateClose($strId, $strClose)
    {
		return $this->UpdateById(array('close' => $strClose, 'adjclose' => $strClose), $strId);
    }

    function UpdateAdjClose($strId, $strAdjClose)
    {
		return $this->UpdateById(array('adjclose' => $strAdjClose), $strId);
    }

    function DeleteByZeroVolume($strStockId)
    {
    	return $this->DeleteData("volume = '0' AND ".$this->BuildWhere_key($strStockId));
    }

    function GetVolume($strStockId, $strDate)
    {
    	if ($record = $this->GetRecord($strStockId, $strDate))
    	{
    		return $record['volume'];
    	}
    	return '0';
    }

    function _getAdjCloseString($callback, $strStockId, $strDate = false)
    {
    	if ($record = $this->$callback($strStockId, $strDate))
    	{
    		return rtrim0($record['adjclose']);
    	}
    	return false;
    }
    
    function GetAdjClose($strStockId, $strDate, $bStrict = false)
    {
    	$str = $this->_getAdjCloseString('GetRecord', $strStockId, $strDate);
    	if ($bStrict === false)
    	{
    		if ($str === false)		$str = $this->_getAdjCloseString('GetRecordPrev', $strStockId, $strDate);	// when hongkong market on holiday
    	}
		return $str;
    }
}

class StockSql extends KeyNameSql
{
    var $calibration_sql;
    var $ema50_sql;
    var $ema200_sql;
	var $fund_est_sql;
    var $his_sql;
	var $holdings_sql;
    var $netvalue_sql;
    
    public function __construct()
    {
        parent::__construct('stock', 'symbol');
        
       	$this->calibration_sql = new CalibrationSql();
       	$this->ema50_sql = new StockEmaSql(50);
       	$this->ema200_sql = new StockEmaSql(200);
       	$this->fund_est_sql = new FundEstSql();
       	$this->his_sql = new StockHistorySql();
        $this->holdings_sql = new HoldingsSql();
       	$this->netvalue_sql = new DailyCloseSql('netvaluehistory');
    }

    public function Create()
    {
    	$str = $this->ComposeVarcharStr('symbol', 32, false).','
         	. $this->ComposeVarcharStr('name', 128).','
         	. ' UNIQUE ( `symbol` )';
    	return $this->CreateIdTable($str);
    }

    function WriteSymbol($strSymbol, $strName)
    {
    	$strName = SqlCleanString($strName);
    	$ar = array('symbol' => $strSymbol,
    				  'name' => $strName);
    	
    	if ($record = $this->GetRecord($strSymbol))
    	{	
    		unset($ar['symbol']);
    		if ($strName != $record['name'])	return $this->UpdateById($ar, $record['id']);
    	}
    	else
    	{
  			DebugString('新增:'.$strSymbol.'-'.$strName);
    		return $this->InsertArray($ar);
    	}
    	return false;
    }
    
    function InsertSymbol($strSymbol, $strName)
    {
    	if ($this->GetRecord($strSymbol) == false)
    	{
    		return $this->WriteSymbol($strSymbol, $strName);
    	}
    	return false;
    }
    
    function GetStockName($strSymbol)
    {
    	if ($record = $this->GetRecord($strSymbol))
    	{	
    		return $record['name'];
    	}
    	return false;
    }

    function GetStockSymbol($strStockId)
    {
    	return $this->GetKey($strStockId);
	}
}

global $g_stock_sql;

function InitGlobalStockSql()
{
	global $g_stock_sql;
    $g_stock_sql = new StockSql();
}

function GetStockSql()
{
	global $g_stock_sql;
	return $g_stock_sql;
}

function SqlGetStockName($strSymbol)
{
	$sql = GetStockSql();
	return $sql->GetStockName($strSymbol);
}

function SqlGetStockId($strSymbol)
{
	$sql = GetStockSql();
	return $sql->GetId($strSymbol);
}

function SqlGetStockSymbol($strStockId)
{
	$sql = GetStockSql();
	return $sql->GetStockSymbol($strStockId);
}

function SqlDeleteStock($strStockId)
{
	$sql = GetStockSql();
	$sql->DeleteById($strStockId);
}

function SqlGetStockSymbolAndId($strWhere, $strLimit = false)
{
	$sql = GetStockSql();
    $ar = array();
    
   	if ($result = $sql->GetData($strWhere, 'symbol ASC', $strLimit)) 
   	{
   		while ($record = mysqli_fetch_assoc($result)) 
   		{
   			$ar[$record['symbol']] = $record['id'];
   		}
   		mysqli_free_result($result);
    }
    return $ar;
}

function GetCalibrationSql()
{
	global $g_stock_sql;
   	return $g_stock_sql->calibration_sql;
}

function GetFundEstSql()
{
	global $g_stock_sql;
   	return $g_stock_sql->fund_est_sql;
}

function GetStockHistorySql()
{
	global $g_stock_sql;
   	return $g_stock_sql->his_sql;
}

function SqlDeleteStockHistory($strStockId)
{
	$his_sql = GetStockHistorySql();
	if ($strStockId)
	{
		$iTotal = $his_sql->Count($strStockId);
		if ($iTotal > 0)
		{
			DebugVal($iTotal, 'Stock history existed');
			$his_sql->DeleteAll($strStockId);
		}
	}
}
/*
function SqlGetHisByDate($strStockId, $strDate)
{
	$his_sql = GetStockHistorySql();
	return $his_sql->GetClose($strStockId, $strDate);
}
*/
function GetStockEmaSql($iDays)
{
	global $g_stock_sql;
	if ($iDays == 50)		return $g_stock_sql->ema50_sql;
	return $g_stock_sql->ema200_sql;
}

function SqlDeleteStockEma($strStockId)
{
	$ar = array(50, 200);
	
	foreach ($ar as $iDays)
	{
		$ema_sql = GetStockEmaSql($iDays);
		$iTotal = $ema_sql->Count($strStockId);
		if ($iTotal > 0)
		{
			DebugVal($iTotal, strval($iDays).' EMA existed');
			$ema_sql->DeleteAll($strStockId);
		}
	}
}

function GetNetValueHistorySql()
{
	global $g_stock_sql;
   	return $g_stock_sql->netvalue_sql;
}

function SqlDeleteNetValueHistory($strStockId)
{
	$netvalue_sql = GetNetValueHistorySql();
	$iTotal = $netvalue_sql->Count($strStockId);
	if ($iTotal > 0)
	{
		DebugVal($iTotal, 'Net value history existed');
		$netvalue_sql->DeleteAll($strStockId);
	}
}

function SqlSetNetValue($strStockId, $strDate, $strNetValue)
{
	$netvalue_sql = GetNetValueHistorySql();
	return $netvalue_sql->InsertDaily($strStockId, $strDate, $strNetValue);
}

function SqlGetNetValueByDate($strStockId, $strDate)
{
	$netvalue_sql = GetNetValueHistorySql();
	return $netvalue_sql->GetClose($strStockId, $strDate);
}

function SqlGetNetValue($strStockId)
{
	$netvalue_sql = GetNetValueHistorySql();
	return $netvalue_sql->GetCloseNow($strStockId);
}

function SqlGetUscny()
{
	return floatval(SqlGetNetValue(SqlGetStockId('USCNY')));
}

function SqlGetHkcny()
{
	return floatval(SqlGetNetValue(SqlGetStockId('HKCNY')));
}

function SqlGetUshkd()
{
	return SqlGetUscny() / SqlGetHkcny(); 
}

function GetHoldingsSql()
{
	global $g_stock_sql;
   	return $g_stock_sql->holdings_sql;
}

function SqlCountHoldings($strSymbol)
{
	$holdings_sql = GetHoldingsSql();
	return $holdings_sql->Count(SqlGetStockId($strSymbol));
}

?>
