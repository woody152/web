<?php
require_once('stock.php');
require_once('sql/sqlbotvisitor.php');
require_once('ui/stocktext.php');

define('MAX_BOT_STOCK', 32);

function _getMatchString($strKey)
{
	$str = '%';
	$iLen = mb_strlen($strKey, 'UTF-8');

	// Separate multi-byte UTF-8 characters
	for ($i = 0; $i < $iLen; $i++) 
	{
		$char = mb_substr($strKey, $i, 1, 'UTF-8');
		$str .= $char;
		// Check if the character is multi-byte
		if (strlen($char) > 1)	$str .= '%'; 
    }
	if (substr($str, -1, 1) != '%')	$str .= '%';
//	DebugString(__FUNCTION__.' '.$str);
	return $str;
}

function _botGetStockArray($strKey)
{
//  if (!empty($strKey))     // "0" (0 as a string) is considered to be empty
	$iLen = strlen($strKey); 
    if ($iLen > 0)
    {
    	$strMatchKey = _getMatchString($strKey);
    	$strSymbolWhere = "symbol LIKE '$strMatchKey'";
    	$strNameWhere = "name LIKE '$strMatchKey'";
    	$strLimit = strval(MAX_BOT_STOCK);
    	if (is_numeric($strKey))
    	{
    		if ($iLen == 6)
    		{
    			$iKey = intval($strKey);
    			if ($iKey < 1000)		
    			{
    				$strWhere = "symbol LIKE '__$strKey'";
    				$strLimit = '2';	// SH000028 and SZ000028
    			}
    			else
    			{
    				$strKey = StockGetSymbol($strKey);
    				$strWhere = "symbol = '$strKey'";
    				$strLimit = '1';
    			}
    		}
    		else																	$strWhere = $strSymbolWhere;
    	}
    	else if (mb_detect_encoding($strKey, 'ASCII', true) == false)		$strWhere = $strNameWhere;
		else																		$strWhere = $strSymbolWhere.' OR '.$strNameWhere;

    	return SqlGetStockSymbolAndId($strWhere, $strLimit);
    }
    return false;
}

function _botGetStockText($strSymbol)
{
    $str = false;
	$ref = StockGetReference($strSymbol);
    if ($ref->IsFundA())
    {
   		$fund_ref = StockGetFundReference($strSymbol);
   		$str = TextFromFundReference($fund_ref);
   		if (method_exists($fund_ref, 'GetEstRef'))
   		{	
    		if ($est_ref = $fund_ref->GetEstRef())
    		{
//    			$str .= BOT_EOL.TextFromStockReference($fund_ref->GetCnyRef()); 
    			$str .= BOT_EOL.TextFromStockReference($est_ref); 
    			if ($realtime_ref = $fund_ref->GetRealtimeRef())	$str .= BOT_EOL.TextFromStockReference($realtime_ref);
    		}
	    }
    }
    else
    {
		if ($str = TextFromStockReference($ref))
		{
			list($ab_ref, $ah_ref, $adr_ref) = StockGetPairReferences($strSymbol);
			$str .= TextPairRatio($ab_ref, STOCK_DISP_ASHARES, STOCK_DISP_BSHARES, 'A/B');  
			$str .= TextPairRatio($ah_ref, STOCK_DISP_ASHARES, STOCK_DISP_HSHARES, 'A/H');  
			$str .= TextPairRatio($adr_ref, 'ADR', STOCK_DISP_HSHARES, 'ADR/H');
		}
    }
    
   	if ($str == false)
   	{
   		$str = "($strSymbol:无数据)";
   		DebugString($str);
		$str .= BOT_EOL;
   	}
   	
    return $str;
}

function _botGetStockArrayText($arSymbol, $str, $strVersion)
{
	$iMaxLen = MAX_BOT_MSG_LEN - strlen($strVersion);
	StockPrefetchArrayExtendedData($arSymbol);
		
	foreach ($arSymbol as $strSymbol)
	{
		if ($strText = _botGetStockText($strSymbol))
		{
			if (strlen($str.$strText.BOT_EOL) < $iMaxLen)
			{
				$str .= $strText.BOT_EOL;
			}
			else
			{
				break;
			}
		}
	}
	return $str;
}

function StockBotGetStr($strText, $strVersion)
{
	InitGlobalStockSql();
	
	$strText = str_replace(array('【', '】', '，', '。', '、', '“',  '”', '‘', '’', '：', '；', "\xC2\xA0"), '', $strText);	// &nbsp;
	$strText = trim($strText, " ,.:;~`{}[]'\"\n\r\t\v\0");
	$strText = SqlCleanString($strText);

//   	$fStart = microtime(true);
	if ($ar = _botGetStockArray($strText))
	{
//		DebugString($strText.':'.DebugGetStopWatchDisplay($fStart, 3));
		
		$arSymbol = array();
		foreach ($ar as $strSymbol => $strId)		$arSymbol[] = $strSymbol;
		if ($iCount = count($arSymbol))
		{
			if ($iCount > 1)
			{
				$str = '(至少发现'.strval($iCount).'个匹配：';
				foreach ($arSymbol as $strSymbol)		$str .= $strSymbol.' ';
				$str = rtrim($str, ' ');
				$str .= ')'.BOT_EOL.BOT_EOL;
			}
			else
			{
				$str = '';
			}
			return _botGetStockArrayText($arSymbol, $str, $strVersion);
		}
	}
	return false;
}

function LogBotVisit($strType, $strMsg, $strSrc)
{
    $visitor_sql = new BotVisitorSql($strType);
    $msg_sql = new BotMsgSql();
    $src_sql = new BotSrcSql();
	    
    $strIp = UrlGetIp();
	$msg_sql->InsertText($strMsg);
	$src_sql->InsertSrc($strSrc);
	$visitor_sql->InsertBotVisitor($msg_sql->GetId($strMsg), GetIpId($strIp), $src_sql->GetId($strSrc));
	
	return $strIp;
}

?>
