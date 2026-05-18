<?php

function _getCalibrationFromBase($strBase)
{
	$cal_sql = GetCalibrationSql();
	if ($strFactor = $cal_sql->GetCloseNow(SqlGetStockId($strBase)))
	{
		return floatval($strFactor);
	}
	return false;
}

function GetForeignMarketCloseTick($strDate, $strType)
{
	switch ($strType)
	{
	case 'EU':
		$strCheck = 'znb_DAX';
		$strTimezone = 'Europe/Berlin';
		$strCloseTime = '17:30:00';
		break;
	
	case 'JP':
		$strCheck = 'znb_NKY';
		$strTimezone = 'Asia/Tokyo';
		$strCloseTime = '15:30:00';
		break;
	
	case 'HK':
		$strCheck = '^HSI';
		$strTimezone = 'Asia/Hong_Kong';
		$strCloseTime = '16:08:00';
		break;
	}
	if (SqlGetHistoryByDate(SqlGetStockId($strCheck), $strDate) === false)
	{
		DebugString(__FUNCTION__.' no data of '.$strCheck.' on '.$strDate);
		return false;
	}

	$strOldTimezone = date_default_timezone_get();
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
   					$iHourMinute = $now_ymd->GetHourMinute();
//   					DebugVal($iHourMinute, __FUNCTION__.' '.$strSymbol, true);
   					if ($iHourMinute < 2100)
   					{
//   						DebugString(__FUNCTION__.' update history '.$strSymbol.' on '.$strDate, true);
   						$this->_updateStockHistory($strStockId, $strDate);
   					}
   					if ($now_ymd->IsStockTradingHourEnd())	$this->_updateStockEma($strStockId, $strDate);
   				}
   			}
   			else
   			{
				if ($this->LoadDailySqlData(GetStockHistorySql()))
				{
					$tick_sql = new StockTickSql();
					$ymd = new TickYMD($tick_sql->ReadInt($strStockId));
					//if ($ymd->GetYMD() == $this->GetDate())
					//{
						$this->SetTime($ymd->GetHMS());
						$this->SetExternalLink($strSymbol);
						$this->SetHasData();
					//}
				}
   			}
   			
       		if ($strSymbol == 'GLD' || $strSymbol == 'USO' || $strSymbol == 'INDA')		$this->_buildForeignMarketData($strSymbol);
        	else if ($strSymbol == 'hf_GC')
        	{
        		$strBase = 'GLD';
				$fFactor = _getCalibrationFromBase($strBase);
        		$this->_buildForeignMarketData($strBase, 'JP', $fFactor);
        	}
        	else if ($strSymbol == 'hf_CL')
        	{
        		$strBase = 'USO';
				$fFactor = _getCalibrationFromBase($strBase);
        		$this->_buildForeignMarketData($strBase, 'JP', $fFactor);
        		$this->_buildForeignMarketData($strBase, 'HK', $fFactor);
        	}
        	else if ($strSymbol == 'znb_SENSEX')
        	{
        		$strBase = 'INDA';
				if ($fFactor = _getCalibrationFromBase($strBase))
				{
					$his_sql = GetStockHistorySql();
					if ($strUSDINR = $his_sql->GetCloseNow(SqlGetStockId('fx_susdinr')))
					{
						$fFactor *= floatval($strUSDINR);
	        			$this->_buildForeignMarketData($strBase, 'JP', $fFactor);
    	    			$this->_buildForeignMarketData($strBase, 'HK', $fFactor);
					}
				}	
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
        return $his_sql->WriteHistory($strStockId, $strDate, $strClose, $this->GetVolume(), $this->strSettlePrice);
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
    
    function _buildForeignMarketData($strSymbol, $strType = 'EU', $fFactor = false)
    {
    	$strDate = $this->GetDate();
    	$iCloseTick = GetForeignMarketCloseTick($strDate, $strType);
		if ($iCloseTick === false)	return;

    	$iCurTick = $this->ConvertTick();
    	$tick_sql = new StockTickSql();
    	$strStockId = SqlGetStockId(BuildYahooNetValueSymbol($strSymbol, $strType));
    	$iTick = $tick_sql->ReadInt($strStockId);
    	if (($iTick === false) || (abs($iCurTick - $iCloseTick) < abs($iTick - $iCloseTick)))
    	{
    		$his_sql = GetStockHistorySql();
    		if ($record = $his_sql->GetRecord($this->GetStockId(), $strDate))
    		{
    			$strVolume = $record['volume'];
    			$strClose = $record['close'];
    			if ($fFactor)
    			{
    				$strClose = strval(round(floatval($strClose) / $fFactor, 2));
    				$strVolume = '100';
    			}
    			if ($his_sql->WriteHistory($strStockId, $strDate, $strClose, $strVolume))
    			{
    				$tick_sql->WriteInt($strStockId, $iCurTick);
    			}
    		}
    	}
    }
}

?>
