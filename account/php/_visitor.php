<?php
require_once('_account.php');
require_once('../php/iplookup.php');
require_once('../php/ui/table.php');
require_once('../php/sql/sqlbotvisitor.php');

define('MAX_VISITOR_CONTENTS', 35);
function _getVisitorContentsDisplay($strContents)
{
    if (strlen($strContents) > MAX_VISITOR_CONTENTS)
    {
        $iLen = MAX_VISITOR_CONTENTS - 3;
        return substr($strContents, 0, $iLen).'...';
    }
    return $strContents;
}

function _echoVisitorData($strId, $visitor_sql, $contents_sql, $iStart, $iNum, $bChinese)
{
	global $acct;
	
    $arBlogId = array();
    $arId = array();
    $strType = $contents_sql->GetTableName();

    if ($result = $visitor_sql->GetDataBySrc($strId, $iStart, $iNum)) 
    {
   		$strDstIndex = $visitor_sql->GetDstKeyIndex();
   		$strSrcIndex = $visitor_sql->GetSrcKeyIndex();
        while ($record = mysqli_fetch_assoc($result)) 
        {
			$ar = array($record['date'], GetHM($record['time']));

			$strDstId = $record[$strDstIndex];
			if ($strType == TABLE_BOT_MSG)
			{
				$ar[] = _getVisitorContentsDisplay($contents_sql->GetText($strDstId));
			}
			else
			{
				$strUri = $contents_sql->GetUri($strDstId);
				$strUriLink = ltrim($strUri, '/');
				$strUriLink = _getVisitorContentsDisplay($strUriLink);
				$ar[] = SelectColumnItem($strUriLink, GetInternalLink($strUri, $strUriLink), $strDstId, $arBlogId);
			}
            
			$strColor = false;
            if ($strId == false)
            {
            	$strSrcId = $record[$strSrcIndex];
				$strIp = GetIp($strSrcId);
				if ($acct->IsMalicious($strIp))		$strColor = 'red';
				else if ($acct->IsCrawler($strIp))	$strColor = 'yellow';
				$ar[] = SelectColumnItem($strIp, GetVisitorLink($strIp, $bChinese), $strSrcId, $arId);
            }
            
		    EchoTableColumn($ar, $strColor);
        }
        mysqli_free_result($result);
    }
}

function _echoVisitorParagraph($strIp, $strId, $visitor_sql, $contents_sql, $iStart, $iNum, $bAdmin, $bChinese)
{
	$ar = array(new TableColumnDate(false, $bChinese), new TableColumnTime($bChinese), new TableColumn(($bChinese ? '内容' : 'Contents'), MAX_VISITOR_CONTENTS * 10));
    
	$str = ' ';
    if ($strIp)
    {
        $strQuery = 'ip='.$strIp;
        $iTotal = $visitor_sql->CountBySrc($strId);
        
    	$str .= GetIpLink($strIp, $bChinese);
        if ($bAdmin)
        {
            $str .= ' '.GetDeleteLink('/php/_submitdelete.php?'.$strQuery, '访问记录', 'Visitor Record', $bChinese);
            $str .= ' '.GetInternalLink('/php/_submitoperation.php?'.$strQuery, '标注爬虫');
            $str .= ' '.GetInternalLink('/php/_submitoperation.php?'.'malicious'.$strQuery, '标注恶意IP');
        }
    }
    else
    {
    	$strTableName = $visitor_sql->GetTableName();
    	$strQuery = ($strTableName == TABLE_VISITOR) ? false : 'type='.$strTableName;
        $iTotal = $visitor_sql->CountData();
    	$ar[] = new TableColumnIP();
    }
    
    $strMenuLink = GetMenuLink($strQuery, $iTotal, $iStart, $iNum, $bChinese);
	EchoTableParagraphBegin($ar, $visitor_sql->GetTableName(), $strMenuLink.$str);
    _echoVisitorData($strId, $visitor_sql, $contents_sql, $iStart, $iNum, $bChinese);
    EchoTableParagraphEnd($strMenuLink);
}

function EchoAll($bChinese = true)
{
    global $acct;
    
    $strIp = $acct->GetQuery();
	if (filter_valid_ip($strIp) == false)
	{
		$strIp = false;
	}

    $visitor_sql = $acct->GetVisitorSql();
    $contents_sql = $acct->GetPageSql();
	if ($strType = UrlGetQueryValue('type'))
	{
		if ($strType == TABLE_TELEGRAM_BOT || $strType == TABLE_WECHAT_BOT)	
		{
			$visitor_sql = new BotVisitorSql($strType);
			$contents_sql = new BotMsgSql();
		}
	}
	
    if ($strIp)
    {
        $str = $acct->IpLookupString($strIp, $bChinese);
        $strId = GetIpId($strIp);
        $iPageCount = $visitor_sql->CountUniqueDst($strId);
        if ($iPageCount > 0)		$str .= '<br />'.($bChinese ? '保存的不同页面数量' : 'Saved unique page number').': '.strval($iPageCount);
    }
    else
    {
        $strId = false;
        $iCount = $visitor_sql->CountToday();
        $str = '今日访问: '.strval($iCount);
    }
    EchoHtmlElement($str);
    
    _echoVisitorParagraph($strIp, $strId, $visitor_sql, $contents_sql, $acct->GetStart(), $acct->GetNum(), $acct->IsAdmin(), $bChinese);
    
	$str = GetAccountToolLinks($bChinese);
	if ($bChinese)	$str .= GetHtmlNewLine().GetStockMenuLinks();
	EchoHtmlElement($str);
}

function GetTitle($bChinese = true)
{
    global $acct;
    
	$str = '';
	if ($strType = UrlGetQueryValue('type'))		$str .= GetBotDisplay($strType, $bChinese);
	if ($strIp = $acct->GetQuery())
	{
		$str .= $strIp;
		if (!$bChinese)	$str .= ' ';
	}

	$str .= $bChinese ? '用户访问数据' : 'Visitor Data';
	return $str;
}

function GetMetaDescription($bChinese = true)
{
	$str = GetTitle($bChinese);
    if ($bChinese)
    {
    	$str .= '页面。用于观察IP攻击的异常状况，用户登录后会自动清除该IP之前的记录，具体的用户统计工作还是由Google Analytics和Google Adsense完成。';
    }
    else
    {
    	$str .= ' page used to view IP attacks. The detailed user information is still using Google Analytics and Google Adsense.';
    }
    return CheckMetaDescription($str);
}

   	$acct = new IpLookupAccount('ip', true);	// Auth to  restrict robot ip lookup
?>
