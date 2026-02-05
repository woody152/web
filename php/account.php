<?php
require_once('switch.php');
require_once('sql.php');
require_once('ui/table.php');

require_once('sql/sqlipaddress.php');
require_once('sql/sqlstocksymbol.php');
require_once('sql/sqlstockgroup.php');

define('DISP_ALL_US', 'All');
define('DISP_EDIT_US', 'Edit');
define('DISP_NEW_US', 'New');

define('DISP_ALL_CN', '全部');
define('DISP_EDIT_CN', '修改');
define('DISP_NEW_CN', '新建');

function GetAllDisplay($bChinese = true)
{
	return $bChinese ? DISP_ALL_CN : DISP_ALL_US;
}

class Account
{
    var $strMemberId = false;
    var $strPageId;
    
    var $strLoginEmail = false;

    var $ip_crawler_sql;
    var $ip_malicious_sql;
    var $ip_visit_sql;
    var $ip_login_sql;
    
    var $page_sql;
    var $visitor_sql;

    var $bAllowCurl = true;
    
    public function __construct() 
    {
    	session_start();
    	SqlConnectDatabase();

		$this->ip_crawler_sql = new IpAddressSql('ipcrawler');
		$this->ip_malicious_sql = new IpAddressSql('ipmalicious');
		$this->ip_visit_sql = new IpIntSql('ipvisit');
		$this->ip_login_sql = new IpIntSql('iplogin');
	    $tick_sql = new IpIntSql('iptick', 'tick');

	    $strIp = UrlGetIp();
   		$ymd = GetNowYMD();
    	
	    if ($this->IsMalicious($strIp))		die('403 Forbidden');
	    else if ($this->IsCrawler($strIp))
	    {
			if ($this->_allowCrawler($ymd, $tick_sql, $strIp))	$this->bAllowCurl = false;
			else																		SwitchToLink('/account/code429.php');
	    }

	    $strUri = UrlGetUri();
	    $this->page_sql = new PageSql();
   		$this->page_sql->InsertUri($strUri);
	    
	    $this->visitor_sql = new VisitorSql();
	    $strId = GetIpId($strIp);
	    if ($this->strPageId = $this->GetPageId($strUri))	$this->visitor_sql->InsertVisitor($this->strPageId, $strId);
    
	    $iCount = $this->visitor_sql->CountBySrc($strId);
	    if ($iCount >= 1000)
	    {
	    	$iPageCount = $this->visitor_sql->CountUniqueDst($strId);
	    	$strDebug = '访问次数: '.strval($iCount).'<br />不同页面数: '.strval($iPageCount).'<br />';
	    	if ($this->GetLoginId())						$strDebug .= 'logined!<br />';
	    	if ($this->bAllowCurl === false)
	    	{
	    		$strDebug .= '已标注的老爬虫';
	    		$tick_sql->WriteInt($strIp, $ymd->GetTick());
	    	}
	    	else
	    	{
	    		if ($iPageCount >= ($iCount / 100))		$strDebug .= '疑似爬虫';
	    		else
	    		{
	    			$strDebug .= '新标注爬虫';
	    			$this->SetCrawler($strIp);
	    		}
	    	}
			trigger_error($strDebug);
	    	$this->AddVisit($strIp, $iCount);
	    	$this->visitor_sql->DeleteBySrc($strId);        
	    }

	   	if ($strEmail = UrlGetQueryValue('email'))
	   	{
	   		if (filter_var_email($strEmail))		$this->strMemberId = SqlGetIdByEmail($strEmail);
	   	}
		InitGlobalStockSql();
    }

	function _allowCrawler($ymd, $tick_sql, $strIp)
	{
		if ($ymd->IsWeekDay())
		{
			$iHourMinute = $ymd->GetHourMinute();
			if ($iHourMinute > 900 && $iHourMinute < 1610)	return false;
		}
	    if ($iTick = $tick_sql->ReadInt($strIp))
	    {
			if ($ymd->GetTick() - $iTick < SECONDS_IN_DAY/2)	return false;
	    }
		return true;
	}

    function IsCrawler($strIp)
    {
    	return $this->ip_crawler_sql->GetRecord($strIp);
    }

    function IsMalicious($strIp)
    {
    	return $this->ip_malicious_sql->GetRecord($strIp);
    }

    function SetCrawler($strIp)
    {
    	return $this->ip_crawler_sql->InsertIp($strIp);
    }
    
    function SetMalicious($strIp)
    {
    	return $this->ip_malicious_sql->InsertIp($strIp);
    }
    
    function SetNormal($strIp)
    {
		if ($this->ip_crawler_sql->DeleteByIp($strIp))	return true;
    	return $this->ip_malicious_sql->DeleteByIp($strIp);
    }
    
    function IncLogin($strIp)
    {
		return $this->ip_login_sql->Inc($strIp);
    }

    function GetLogin($strIp)
    {
		return $this->ip_login_sql->ReadInt($strIp);
    }

    function AddVisit($strIp, $iCount)
    {
		return $this->ip_visit_sql->Add($strIp, $iCount);
    }

    function GetVisit($strIp)
    {
		return $this->ip_visit_sql->ReadInt($strIp);
    }

    function GetPageUri($strPageId)
    {
    	return $this->page_sql->GetUri($strPageId);
    }
    
    function GetPageId($strPageUri = false)
    {
    	return $strPageUri ? $this->page_sql->GetId($strPageUri) : $this->strPageId;
    }
    
    function GetPageSql()
    {
    	return $this->page_sql;
    }
    
    function GetVisitorSql()
    {
    	return $this->visitor_sql;
    }
    
    function Auth()
    {
    	if ($this->GetLoginId() == false) 
    	{
    		SwitchSetSess();
    		SwitchTo('/account/login');
    	}
    }
    
    function GetWhoseDisplay($strMemberId = false, $bChinese = true)
    {
    	if ($strMemberId == false)		$strMemberId = $this->GetMemberId();
    	
    	if ($strMemberId == $this->GetLoginId())
    	{
    		$str = $bChinese ? '我' : 'My';
    	}
    	else
    	{
    		if (($str = SqlGetNameByMemberId($strMemberId)) == false)
    		{
    			$str = SqlGetEmailById($strMemberId);
    		}
    	}
    	return $str.($bChinese ? '的' : ' ');
    }

    function GetWhoseAllDisplay($bChinese = true)
    {
     	$strAll = $bChinese ? DISP_ALL_CN : ' '.DISP_ALL_US.' ';
    	return $this->GetWhoseDisplay(false, $bChinese).$strAll;
    }
    
    function GetLoginId()
    {
    	// Check whether the session variable SESS_ID is present or not
    	$strMemberId = isset($_SESSION['SESS_ID']) ? $_SESSION['SESS_ID'] : false;
    	if ($strMemberId)
    	{
    		if (trim($strMemberId) == '')	$strMemberId = false;
    	}
    	return $strMemberId;	
    }
    
    function GetMemberId()
    {
    	if ($this->strMemberId)	return $this->strMemberId;
    	return $this->GetLoginId();
    }
    
    function GetLoginEmail()
    {
    	if (($strLoginId = $this->GetLoginId()) == false)	return false;
    	
    	if ($this->strLoginEmail == false)
    	{
    		$this->strLoginEmail = SqlGetEmailById($strLoginId);
    	}
    	return $this->strLoginEmail;
	}

    function IsReadOnly()
    {
    	if ($this->strMemberId)	return ($this->GetLoginId() == $this->strMemberId) ? false : true;
    	return false;
    }

    function AllowCurl()
    {
    	return $this->bAllowCurl;
    }
    
    function IsPalmmicro()
    {
    	$str = '@palmmicro.com';
    	$iLen = strlen($str);
    	if (substr($this->GetLoginEmail(), -$iLen, $iLen) == $str)
    	{
    		return true;
    	}
    	return false;
    }
    
    function IsAdmin()
    {
    	if ($this->GetLoginEmail() == ADMIN_EMAIL)
    	{
    		return true;
    	}
    	return false;
    }

    public function AdminProcess()
    {
    	DebugString('Empty Admin Process');
    }
    
    function AdminRun()
    {
    	if ($this->IsAdmin())
    	{
    		$fStart = microtime(true);
    		$this->AdminProcess();
    		DebugString(DebugGetStopWatchDisplay($fStart));
    	}
    	SwitchToSess();
    }

    public function Process($strLoginId)
    {
    	DebugString('Empty Process');
    }
    
    function Run()
    {
    	if ($strLoginId = $this->GetLoginId())
    	{
    		$this->Process($strLoginId);
    	}
    	SwitchToSess();
    }
}

class TitleAccount extends Account
{
	var $strPage;
	var $strQuery;
	
    var $iStart;
    var $iNum;
    
    public function __construct($strQueryItem = false, $arLoginTitle = false) 
    {
        parent::__construct();
    	$this->strPage = UrlGetPage();
    	if ($arLoginTitle)
    	{
    		if (($arLoginTitle === true) || in_array($this->strPage, $arLoginTitle))		$this->Auth();
    	}
   		
   		$this->iStart = UrlGetQueryInt('start');
   		$this->iNum = UrlGetQueryInt('num', DEFAULT_PAGE_NUM);
   		if (($this->iStart != 0) && ($this->iNum != 0))							  			$this->Auth();
   		
        $this->strQuery = UrlGetQueryValue($strQueryItem ? $strQueryItem : $this->strPage);
    }
    
    function GetPage()
    {
    	return $this->strPage;
    }
    
    function GetQuery()
    {
    	return $this->strQuery;
    }
    
    function GetStart()
    {
    	return $this->iStart;
    }
    
    function GetNum()
    {
    	return $this->iNum;
    }
    
    function GetStartNumDisplay($bChinese = true)
    {
   		if (($this->iStart == 0) && ($this->iNum == 0))	$str = GetAllDisplay($bChinese);
   		else 													$str = strval($this->iStart + 1).'-'.strval($this->iStart + $this->iNum); 
    	return "($str)";
    }
}

?>
