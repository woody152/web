<?php
require_once('externalurl.php');
require_once('stocklink.php');
require_once('sql/sqlipaddress.php');
require_once('sql/sqlbotvisitor.php');
require_once('ui/commentparagraph.php');

function _getIpInfoIpLookUpUrl($strIp)
{
    return GetIpInfoUrl().$strIp.'/json';
}

function strstr_array($strHaystack, $arNeedle)
{
	foreach ($arNeedle as $strNeedle)
	{
		if (stripos($strHaystack, $strNeedle) !== false)		return true;
	}
	return false;
}

function _ipLookupMemberTable($strIp, $strNewLine, $bChinese)
{
    $str = '';
    if ($result = SqlGetMemberByIp($strIp)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
            $strLink = GetMemberLink($record['id'], $bChinese);
            $str .= $strNewLine.$strLink.($bChinese ? '登录于' : ' login on ').$record['login'];
        }
        mysqli_free_result($result);
    }
    return $str;
}

class IpLookupAccount extends CommentAccount
{
    function _ipInfoLookUp($strIp)
    { 
    	if ($str = url_get_contents(_getIpInfoIpLookUpUrl($strIp)))
    	{
    		DebugString($str);
    		$ar = json_decode($str, true);
    		if (isset($ar['hostname']))
    		{
    			$strHostName = $ar['hostname'];
    			if ($strHostName == 'No Hostname')		unset($ar['hostname']);
    			else
    			{
    				if (strstr_array($strHostName, array('bot', 'crawl', 'proxy', 'spider')))
    				{
    					if ($this->SetCrawler($strIp))	DebugString('自动标注爬虫:'.$strHostName);
    				}
    			}
    		}
    		return $ar;
    	}
    	return false;
    }

    function _pageCommentLookup($strIp, $bChinese)
    {
		$comment_sql = $this->GetCommentSql();
    	$strWhere = $this->BuildWhereByIp($strIp);
	    $iTotal = $this->CountComments($strWhere);
	    if ($iTotal == 0)   return '';
        
		$strNewLine = GetHtmlNewLine();
	    $str = $strNewLine;
	    if ($result = $comment_sql->GetAll($strWhere, 0, MAX_COMMENT_DISPLAY)) 
	    {
	    	while ($record = mysqli_fetch_assoc($result)) 
	    	{
	    		$str .= $strNewLine.$this->GetCommentDescription($record, $strWhere, $bChinese);
	    	}
	    	mysqli_free_result($result);
	    }
	    $str .= $strNewLine.strval($iTotal).' '.GetAllCommentLink('ip='.$strIp, $bChinese).$strNewLine;
	    return $str;
	}

	function _visitorLookup($strIp, $bChinese)
	{
		$strNewLine = GetHtmlNewLine();
		$str = '';
		$visitor_sql = $this->GetVisitorSql();
		$iVisit = $visitor_sql->CountBySrc(GetIpId($strIp));
		if ($iStored = $this->GetVisit($strIp))		$iVisit += $iStored;
		if ($iVisit > 0)							$str .= $strNewLine.($bChinese ? '普通网页总访问次数' : 'Total normal page visit').': '.strval($iVisit);
		if ($iLogin = $this->GetLogin($strIp))		$str .= $strNewLine.($bChinese ? '总登录次数' : 'Total login').': '.strval($iLogin);
	    if ($this->IsMalicious($strIp))				$str .= $strNewLine.GetFontElement($bChinese ? '已标注恶意IP' : 'Marked malicious IP');
		if ($this->IsCrawler($strIp))				$str .= $strNewLine.GetRemarkElement($bChinese ? '已标注爬虫' : 'Marked crawler');
		return $str;
	}

    function IpLookupString($strIp, $bChinese)
    {
    	$fStart = microtime(true);
    	$str = GetVisitorLink($strIp, $bChinese).' '.GetAllVisitorLink(TABLE_VISITOR, $bChinese);
    	if ($this->IsAdmin())		$str .= ' '.GetAllVisitorLink(TABLE_TELEGRAM_BOT, $bChinese).' '.GetAllVisitorLink(TABLE_WECHAT_BOT, $bChinese);
		$strNewLine = GetHtmlNewLine();
    	$str .= $strNewLine.GetExternalLink(_getIpInfoIpLookUpUrl($strIp), 'ipinfo.io').': ';
    	if ($arInfo = $this->_ipInfoLookUp($strIp))
    	{
    		if (isset($arInfo['error']) == false)
    		{
    			$str .= $arInfo['country'].' '.$arInfo['region'].' '.$arInfo['city'].' ['.$arInfo['loc'].'] '.$arInfo['org'];
    			if (isset($arInfo['postal']))	$str .= ' '.$arInfo['postal'];
    			if (isset($arInfo['hostname']))	$str .= ' '.$arInfo['hostname'];
    		}
    	}
    	$str .= DebugGetStopWatchDisplay($fStart);
    
    	$str .= _ipLookupMemberTable($strIp, $strNewLine, $bChinese);		// Search member login
    	$str .= $this->_pageCommentLookup($strIp, $bChinese);  		// Search blog comment
    	$str .= $this->_visitorLookup($strIp, $bChinese);
    	return $str;
    }
}

?>
