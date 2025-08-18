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

class StockTickSql extends IntSql
{
    public function __construct()
    {
        parent::__construct('stocktick', 'tick');
    }
}


?>
