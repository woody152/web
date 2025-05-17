<?php
require_once('sqlval.php');

class IntSql extends ValSql
{
    public function __construct($strTableName, $strIntName = 'num')
    {
        parent::__construct($strTableName, $strIntName);
    }

    function CreateIntTable($strExtra = '')
    {
    	$str = $this->ComposePrimaryIdStr().','
         	  . $this->ComposeIntStr($this->GetValName())
         	  . $strExtra;
    	return $this->CreateTable($str);
    }
    
    public function Create()
    {
    	return $this->CreateIntTable();
    }
    
    function WriteString($strId, $str)
    {
    	return $this->WriteVal($strId, $str, true);
    }
    
    function ReadString($strId)
    {
    	return $this->ReadVal($strId, true);
    }
    
    public function WriteInt($strId, $iInt)
    {
    	return $this->WriteString($strId, strval($iInt));
    }
    
    public function ReadInt($strId)
    {
    	if ($str = $this->ReadString($strId))	return intval($str);
    	return false;
    }
    
    function Add($strId, $iInt)
    {
    	$iVal = $this->ReadInt($strId);
    	if ($iVal === false)		$iVal = 0;
    	
    	return $this->WriteInt($strId, $iVal + $iInt);
    }
    
    function Inc($strId)
    {
    	return $this->Add($strId, 1);
    }
}

/*
class StockQuoteSql extends IntSql
{
	var $strQuoteName = 'quote';

    public function __construct($strTableName = 'stockquote')
    {
        parent::__construct($strTableName, 'tick');
    }

    public function Create()
    {
    	return $this->CreateIntTable(','.$this->ComposeVarcharStr($this->strQuoteName));
    }
    
    function WriteQuote($strId, $iTick, $strQuote)
    {
    	$strTick = strval($iTick);
    	$ar = array($this->strValName => $strTick, $this->strQuoteName => $strQuote);
   		if ($record = $this->GetRecordById($strId))
   		{
   			if ($strQuote == $record[$this->strQuoteName])	unset($ar[$this->strQuoteName]);
   			if ($strTick == $record[$this->strValName])		return false;
   			return $this->UpdateById($ar, $strId);
   		}
    	return $this->InsertArray(array('id' => $strId) + $ar);
    }
}                                                                                                                                              
*/
?>
