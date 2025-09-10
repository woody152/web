<?php

function GetForeignMarketCloseTick($strDate, $strType)
{
	switch ($strType)
	{
	case 'EU':
		$strTimezone = 'Europe/Berlin';
		$strCloseTime = '17:30:00';
		break;
	
	case 'JP':
		$strTimezone = 'Asia/Tokyo';
		$strCloseTime = '15:00:00';
		break;
	
	case 'HK':
		$strTimezone = 'Asia/Hong_Kong';
		$strCloseTime = '16:08:00';
		break;
	}
	
	$strOldTimezone = date_default_timezone_get();
//	DebugString(__FUNCTION__.' '.$strOldTimezone, true);
	date_default_timezone_set($strTimezone);
	$iTick = strtotime($strDate.' '.$strCloseTime);
	date_default_timezone_set($strOldTimezone);
	return $iTick;
}

class MyStockReference extends MysqlReference
{
    public function __construct($strSymbol) 
    {
        parent::__construct($strSymbol);
        
    	if ($strStockId = $this->GetStockId())
   		{
   			if ($this->HasData())
   			{	
   				$this->SetTimeZone();
   				$now_ymd = GetNowYMD();
   				$strDate = $this->GetDate();
   				if ($now_ymd->GetYMD() == $strDate)
   				{
   					$this->_updateStockHistory($strStockId, $strDate);
   					if ($now_ymd->IsStockTradingHourEnd())	$this->_updateStockEma($strStockId, $strDate);
   				}
   			}
   			else
   			{
				if ($this->LoadDailySqlData(GetStockHistorySql()))
				{
					$tick_sql = new StockTickSql();
					$ymd = new TickYMD($tick_sql->ReadInt($strStockId));
					if ($ymd->GetYMD() == $this->GetDate())
					{
						$this->SetTime($ymd->GetHMS());
						$this->SetExternalLink($strSymbol);
						$this->SetHasData();
					}
				}
   			}
   			
       		if ($strSymbol == 'GLD' || $strSymbol == 'USO')		$this->BuildForeignMarketData($strSymbol);
        	else if ($strSymbol == 'hf_CL')
        	{
        		$cal_sql = GetCalibrationSql();
        		$fFactor = floatval($cal_sql->GetCloseNow(SqlGetStockId('USO')));
//        		DebugVal($fFactor, __CLASS__.'->'.__FUNCTION__, true);

        		$this->BuildForeignMarketData('USO', 'JP', $fFactor);
        		$this->BuildForeignMarketData('USO', 'HK', $fFactor);
        	}
   		}
    }
    
    public function LoadData()
    {
    	if ($this->IsSinaFuture())	        $this->LoadSinaFutureData();
    	else if ($this->IsSinaForex())		$this->LoadSinaForexData();
    	else								$this->LoadSinaData();
        $this->bConvertGB2312 = true;     // Sina name is GB2312 coded
    }
    
    function _invalidHistoryData($str)
    {
        if (empty($str))    return true;
        if ($str == 'N/A')   return true;
        return false;
    }
    
    function _updateStockHistory($strStockId, $strDate)
    {
        if ($this->_invalidHistoryData($this->strOpen))  return;
        if ($this->_invalidHistoryData($this->strHigh))  return;
        if ($this->_invalidHistoryData($this->strLow))  return;
        $strClose = $this->GetPrice();
        if ($this->_invalidHistoryData($strClose))  return;
        
        $his_sql = GetStockHistorySql();
        return $his_sql->WriteHistory($strStockId, $strDate, $strClose, $this->GetVolume());
    }
    
    // En = k * X0 + (1 - k) * Em; 其中m = n - 1; k = 2 / (n + 1)
	function CalculateEMA($fPrice, $fPrev, $iDays)
	{
		$f = 2.0 / ($iDays + 1);
		return $f * $fPrice + (1.0 - $f) * $fPrev;
	}
    
	function _updateStockEmaDays($strStockId, $strDate, $iDays)
	{
		$sql = GetStockEmaSql($iDays);
		if ($strPrev = $sql->GetClosePrev($strStockId, $strDate))
		{
			$fCur = $this->CalculateEMA(floatval($this->GetPrice()), floatval($strPrev), $iDays);
			$sql->WriteDaily($strStockId, $strDate, strval($fCur));
		}
	}
	
    function _updateStockEma($strStockId, $strDate)
    {
        $this->_updateStockEmaDays($strStockId, $strDate, 50);
        $this->_updateStockEmaDays($strStockId, $strDate, 200);
    }
    
    function BuildForeignMarketData($strSymbol, $strType = 'EU', $fCalibration = false)
    {
    	$strSymbol = BuildYahooNetValueSymbol($strSymbol, $strType);
    	$strDate = $this->GetDate();
    	$iCloseTick = GetForeignMarketCloseTick($strDate, $strType);
    	$iCurTick = $this->ConvertTick();
		
    	$tick_sql = new StockTickSql();
    	$strStockId = SqlGetStockId($strSymbol);
    	$iTick = $tick_sql->ReadInt($strStockId);
    	if (($iTick === false) || (abs($iCurTick - $iCloseTick) < abs($iTick - $iCloseTick)))
    	{
    		$his_sql = GetStockHistorySql();
    		if ($record = $his_sql->GetRecord($this->GetStockId(), $strDate))
    		{
    			$strClose = $record['close'];
    			if ($fCalibration)
    			{
    				$fClose = floatval($strClose) / $fCalibration;
    				$strClose = strval(round($fClose, 2));
    			}
    			if ($his_sql->WriteHistory($strStockId, $strDate, $strClose, $record['volume']))
    			{
    				$tick_sql->WriteInt($strStockId, $iCurTick);
    				DebugString(__CLASS__.'->'.__FUNCTION__.': '.$strSymbol.' updated history on '.$strDate.': '.$strClose);
    			}
    		}
    	}
    }
}
// 6543.25，6534
?>
