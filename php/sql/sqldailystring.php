<?php
require_once('sqldailyclose.php');

class DailyStringSql extends DailyCloseSql
{
    public function __construct($strTableName, $strKeyPrefix = 'stock') 
    {
        parent::__construct($strTableName, $strKeyPrefix);
    }

    public function Create()
    {
        return $this->CreateDailyCloseTable($this->ComposeVarcharStr());
    }

    public function WriteDaily($strKeyId, $strDate, $strClose)
    {
    	if ($record = $this->GetRecord($strKeyId, $strDate))
    	{
    		if ($record['close'] != $strClose)
    		{
    			return $this->UpdateDaily($record['id'], $strClose);
    		}
    	}
    	else
    	{
    		return $this->InsertDaily($strKeyId, $strDate, $strClose);
    	}
    	return false;
    }
}

class QuarterReportSql extends DailyStringSql
{
    public function __construct() 
    {
        parent::__construct('quarterreport');
    }
}

?>
