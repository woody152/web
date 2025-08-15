<?php
require_once('sqldailyclose.php');

class DailyTimeSql extends DailyCloseSql
{
    public function __construct($strTableName, $strKeyPrefix = 'stock') 
    {
        parent::__construct($strTableName, $strKeyPrefix);
    }

    public function Create()
    {
        return $this->CreateDailyCloseTable($this->ComposeCloseStr().','.$this->ComposeTimeStr());
    }

    public function InsertDaily($strKeyId, $strDate, $strClose)
    {
        if ($this->GetRecord($strKeyId, $strDate))			return false;
        
        $ar = $this->MakeFieldArray($strKeyId, $strDate, $strClose);
   		$ar['time'] = DebugGetTime();
    	return $this->InsertArray($ar);
    }

    public function UpdateDaily($strId, $strClose)
    {
        $strTime = DebugGetTime();
		return $this->UpdateById(array('close' => $strClose, 'time' => $strTime), $strId);
    }
/*
    function GetTimeNow($strKeyId = false)
    {
    	if ($record = $this->GetRecordNow($strKeyId))
    	{
    		return $record['time'];
    	}
    	return false;
    }
*/    
}

class CalibrationSql extends DailyTimeSql
{
    public function __construct() 
    {
        parent::__construct('calibrationhistory');
    }
    
    public function Create()
    {
        return $this->CreateDailyCloseTable($this->ComposeCloseStr().','.$this->ComposeTimeStr().','.$this->ComposeIntStr('num'));
    }

    public function InsertDaily($strKeyId, $strDate, $strClose)
    {
        if ($this->GetRecord($strKeyId, $strDate))			return false;
        
        $ar = $this->MakeFieldArray($strKeyId, $strDate, $strClose);
   		$ar['time'] = DebugGetTime();
   		$ar['num'] = '1';
    	return $this->InsertArray($ar);
    }

    public function UpdateDaily($strId, $strClose)
    {
		return $this->UpdateDailyAverage($strId, $strClose);
    }
    
    function UpdateDailyAverage($strId, $strClose, $iNum = 1)
    {
        $strTime = DebugGetTime();
        $strNum = strval($iNum);
		return $this->UpdateById(array('close' => $strClose, 'time' => $strTime, 'num' => $strNum), $strId);
    }
    
    function WriteDailyAverage($strKeyId, $strDate, $strClose)
    {
    	if ($record = $this->GetRecord($strKeyId, $strDate))
    	{
    		$fOldAvg = floatval($record['close']);
    		$fVal = floatval($strClose);
    		if (abs($fOldAvg - $fVal) > MIN_FLOAT_VAL)
    		{
//    			DebugString($record['close'].' '.$strClose); 
//    			return $this->UpdateDaily($record['id'], $strClose);
				$iNum = intval($record['num']);
				$fTotal = $iNum * $fOldAvg + $fVal;
				$iNum ++;
				return $this->UpdateDailyAverage($record['id'], strval($fTotal/$iNum), $iNum);
    		}
    	}
    	else
    	{
//    		$ymd = new StringYMD($strDate);
//    		if ($ymd->IsWeekend())     			return false;   // hf_CHA50CFD calibration may in Sunday, which is Monday for SH000300
    		
    		return $this->InsertDaily($strKeyId, $strDate, $strClose);
    	}
    	return false;
    }
}

?>
