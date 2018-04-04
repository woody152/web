<?php
require_once('weixin.php');
require_once('url.php');
require_once('debug.php');
require_once('stock.php');
require_once('email.php');
require_once('sql.php');
require_once('mystock.php');

require_once('ui/stocktext.php');

require_once('sql/sqlstock.php');
require_once('sql/sqlvisitor.php');
require_once('sql/sqlspider.php');
require_once('sql/sqlweixin.php');

define('WX_DEBUG_VER', '版本745');

define('WX_DEFAULT_SYMBOL', 'SZ162411');

// ****************************** Wexin support functions *******************************************************

function _getMarketMatch($strKey)
{
    $str = $strKey;
    if (IsChineseStockDigit($strKey))
    {
        $str = StockBuildChineseSymbol($strKey);
    }
    else if (substr($strKey, 0, 5) == 'NYSE:')
    {
        $str = substr($strKey, 5);
    }
    else if (substr($strKey, 0, 7) == 'NASDAQ:')
    {
        $str = substr($strKey, 7);
    }
    else if (substr($strKey, 0, 3) == 'HK:')
    {
        $str = substr($strKey, 3);
        if (strlen($str) == 4)
        {
            $str = '0'.$str;
        }
    }
    else if (substr($strKey, 0, 3) == 'SH:')
    {
        $strDigit = substr($strKey, 3);
        if (IsChineseStockDigit($strDigit))
        {
            $str = SHANGHAI_PREFIX.$strDigit;
        }
    }
    else if (substr($strKey, 0, 3) == 'SZ:')
    {
        $strDigit = substr($strKey, 3);
        if (IsChineseStockDigit($strDigit))
        {
            $str = SHENZHEN_PREFIX.$strDigit;
        }
    }
    return $str;
}

function _getExactMatch($strKey)
{
    $strSymbol = _getMarketMatch($strKey);
    if (SqlGetStock($strSymbol))
    {
        return $strSymbol; 
    }
    return false;
}

function _getStockReferenceText($ref)
{
    $ref->strExternalLink = $ref->GetStockSymbol();
    $str = TextFromStockReference($ref);
    return $str;
}

function _getFundReferenceText($ref)
{
    $ref->stock_ref->strExternalLink = GetCommonToolLink($ref->GetStockSymbol(), true);
    $str = TextFromFundReference($ref); 
    return $str;
}

function _wxGetStockText($strSymbol)
{
    $sym = new StockSymbol($strSymbol);
    if ($sym->IsForex())
    {
        return false;
    }
    return $strSymbol;
}

// Try to stock stock information directly from stock data sources
function _wxGetUnknownStockText($strContents)
{
    if (preg_match('/^[A-Z0-9:^.]+$/', $strContents))
    {
        $strSymbol = _getMarketMatch($strContents);
        if ($str = _wxGetStockText($strSymbol))
        {
            return $str.WX_EOL.WX_EOL;
        }
    }
    return false;
}

function _wxGetDefaultText()
{
//    $str = _wxGetStockArrayText(LofGetAllSymbolArray(WX_DEFAULT_SYMBOL));
//    return '最热门:'.WX_EOL.$str;
    return _wxGetStockArrayText(array(WX_DEFAULT_SYMBOL));
}

function _wxEmailDebug($strUserName, $strText, $strSubject)
{   
    EmailDebug("<font color=blue>用户:</font>$strUserName.<br />".$strText, $strSubject);
}

function _wxUnknownMessage($strContents, $strUserName)
{
    _wxEmailDebug($strUserName, "<font color=green>内容:</font>".$strContents, 'Wechat message');
    $str = $strContents.WX_EOL;
    $str .= '本公众号目前只提供股票交易和净值估算自动查询. 因为没有匹配到信息, 此消息内容已经发往support@palmmicro.com邮箱, palmmicro会尽可能用电子邮件回复.'.WX_EOL;
    return $str._wxGetDefaultText();
}

function _wxGetStockArrayText($arSymbol)
{
    MyStockPrefetchData($arSymbol);
    $str = '';
    foreach ($arSymbol as $strSymbol)
    {
        if ($strText = _wxGetStockText($strSymbol))
        {
            if (strlen($str.$strText.WX_EOL.WX_DEBUG_VER) < MAX_WX_MSG_LEN)
            {
                $str .= $strText.WX_EOL;
            }
            else
            {
                break;
            }
        }
    }
    return $str;
}

function _updateWeixinTables($strText, $strUserName)
{
    SqlCreateVisitorTable(WEIXIN_VISITOR_TABLE);
    if ($strDstId = MustGetSpiderParameterId($strText))
    {
        $strSrcId = MustGetWeixinId($strUserName);
        SqlInsertVisitor(WEIXIN_VISITOR_TABLE, $strDstId, $strSrcId);
    }
}

// ****************************** Functions used in weixin.php *******************************************************

function WxOnText($strText, $strUserName)
{
    _updateWeixinTables($strText, $strUserName);
    
    $strContents = strtoupper($strText);
//    $arSymbol = _wxGetStockArray($strContents);
    if (count($arSymbol))
    {
        $str = _wxGetStockArrayText($arSymbol);
    }
    else
    {
        if (($str = _wxGetUnknownStockText($strContents)) == false)
        {
            $str = _wxUnknownMessage($strText, $strUserName);
        }
    }
    return $str;
}

function WxOnVoice($strContents, $strUserName)
{
    if (strlen($strContents) > 0)
    {
        $str = WxOnText($strContents, $strUserName);
    }
    else
    {
        $str = _wxUnknownMessage('未知语音信息', $strUserName);
    }
    return $str;
}

function WxOnEvent($strContents, $strUserName)
{
    if ($strContents == 'subscribe')
    {
        $str = '欢迎订阅, 本账号为自动回复, 请用语音或者键盘输入要查找的内容. 想聊天的请加QQ群204836363'.WX_EOL;
        $str .= _wxGetDefaultText();
    }
    else if ($strContents == 'unsubscribe')
    {
        $str = '再见';
    }
    else if ($strContents == 'MASSSENDJOBFINISH')
    {   // Mass send job finish
        $str = '收到群发完毕';
    }
    _wxEmailDebug($strUserName, $str, 'Wechat '.$strContents);
    return $str;
}

function WxOnEventMenu($strMenu, $strUserName)
{
    $str = _wxUnknownMessage('未知自定义菜单点击事件', $strUserName);
    return $str;
}

function WxOnImage($strUrl, $strUserName)
{
    $strContents = '未知图像信息';
    
    SqlCreateWeixinImageTable();
    $strOpenId = MustGetWeixinId($strUserName);
    SqlInsertWeixinImage($strOpenId);
    if ($str = SqlGetWeixinImageNow($strOpenId))
    {
        $img = url_get_contents($strUrl);    
        $size = strlen($img);
        $strFileName = DebugGetImageName($str); 
        $fp = @fopen($strFileName, 'w');  
        fwrite($fp, $img);  
        fclose($fp);  
//      unset($img, $url);

        $strLink = UrlGetLink($strFileName, $strFileName);
        $strContents .= "(已经保存到{$strLink})";
    }
    
    $str = _wxUnknownMessage($strContents, $strUserName);
    return $str;
}

function WxOnShortVideo($strContents, $strUserName)
{
    $str = _wxUnknownMessage('未知小视频信息', $strUserName);
    return $str;
}

function WxOnLocation($strContents, $strUserName)
{
    $str = _wxUnknownMessage('未知位置信息', $strUserName);
    return $str;
}

function WxOnLink($strContents, $strUserName)
{
    $str = _wxUnknownMessage('未知链接信息', $strUserName);
    return $str;
}

function WxOnUnknownType($strType, $strUserName)
{
    $str = _wxUnknownMessage('未知信息类型'.$strType, $strUserName);
    return $str;
}

function _main()
{
    SqlConnectDatabase();

    $wechatObj = new wechatCallbackapiTest();
    $wechatObj->valid();
}

    _main();
    
?>
