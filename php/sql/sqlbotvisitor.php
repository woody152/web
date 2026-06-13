<?php
require_once('sqlkeyname.php');
require_once('sqlvisitor.php');

const TABLE_BOT_MSG = 'botmsg';
const TABLE_BOT_SRC = 'botsrc';

class BotMsgSql extends KeyNameSql
{
    public function __construct()
    {
        parent::__construct(TABLE_BOT_MSG, 'text', 2048, true);
    }

    function GetText($strTextId)
    {
    	return $this->GetKey($strTextId);
	}
	
	function InsertText($strText)
	{
		return $this->InsertKey($strText);
	}
}

class BotSrcSql extends KeyNameSql
{
    public function __construct()
    {
        parent::__construct(TABLE_BOT_SRC, 'src', 128, true);	// can NOT use 'from' as a SQL field
    }

    function GetSrc($strSrcId)
    {
    	return $this->GetKey($strSrcId);
	}

    function InsertSrc($strSrc)
    {
    	return $this->InsertKey($strSrc);
	}
}

class BotVisitorSql extends VisitorSql
{
	var $strFromKey;
	
    public function __construct($strTableName)
    {
    	$this->strFromKey = $this->Add_id(TABLE_BOT_SRC);
        parent::__construct($strTableName, TABLE_BOT_MSG);
    }

    public function Create()
    {
    	$str = $this->ComposeIntStr($this->strFromKey).','
    		 . $this->ComposeForeignStr($this->strFromKey).',';
    	return $this->CreateVisitorTable($str);
    }
    
    function InsertBotVisitor($strMsgId, $strIpId, $strFromId, $strDate = false, $strTime = false)
    {
    	$ar = $this->MakeVisitorInsertArray($strMsgId, $strIpId, $strDate, $strTime);
    	$ar[$this->strFromKey] = $strFromId;
    	return $this->InsertArray($ar);
    }

    function BuildWhereByFrom($strFromId)
    {
    	return _SqlBuildWhere($this->strFromKey, $strFromId);
    }
}
