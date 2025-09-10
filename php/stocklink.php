<?php
require_once('ui/stocktable.php');
require_once('stock/stocksymbol.php');

define('BIOTECH_GROUP_DISPLAY', '生物科技');
define('CHINAINTERNET_GROUP_DISPLAY', '中丐互怜');
define('COMMODITY_GROUP_DISPLAY', '大宗商品和黄金');
define('HANGSENG_GROUP_DISPLAY', '恒生指数');
define('HSHARES_GROUP_DISPLAY', 'H股中国企业指数');
define('HSTECH_GROUP_DISPLAY', '恒生科技指数');
define('MSCIUS50_GROUP_DISPLAY', 'MSCI美国50');
define('OIL_GROUP_DISPLAY', '原油');
define('QQQ_GROUP_DISPLAY', '纳斯达克100');
define('SPY_GROUP_DISPLAY', '标普500');

define('LOF_ALL_DISPLAY', 'LOF基金');
define('OVERNIGHT_DISPLAY', '义工群对比');
define('OVERNIGHT_CNH_DISPLAY', '义工群对冲计算器');

define('CHINA_INDEX_DISPLAY', 'A股指数');
define('QDII_DISPLAY', '美股QDII');
define('QDII_MIX_DISPLAY', '混合QDII');
define('QDII_HK_DISPLAY', '港股QDII');
define('QDII_JP_DISPLAY', '日本QDII');
define('QDII_EU_DISPLAY', '欧洲QDII');

define('STOCK_OPTION_ADR', '修改H股对应ADR代码');
define('STOCK_OPTION_AH', '修改A股对应H股代码');
define('STOCK_OPTION_AMOUNT', '基金申购金额');
define('STOCK_OPTION_CALIBRATION', '手工校准');
define('STOCK_OPTION_CLOSE', '更新收盘价');
define('STOCK_OPTION_DIVIDEND', '分红');
define('STOCK_OPTION_EDIT', '修改股票说明');
define('STOCK_OPTION_EMA', '修改200/50日EMA');
define('STOCK_OPTION_FUND', '修改对应配对代码和仓位');
define('STOCK_OPTION_HA', '修改H股对应A股代码');
define('STOCK_OPTION_HOLDINGS', '修改基金持仓');
define('STOCK_OPTION_NETVALUE', '修改净值');
define('STOCK_OPTION_PREMIUM', '期货升水');
define('STOCK_OPTION_SHARE_DIFF', '场内新增(万)');
define('STOCK_OPTION_SPLIT', '拆股或合股');

function GetStockCategoryArray()
{
    return array('biotech' => BIOTECH_GROUP_DISPLAY,
                   'chinainternet' => CHINAINTERNET_GROUP_DISPLAY,
                   'commodity' => COMMODITY_GROUP_DISPLAY,
                   'hangseng' => HANGSENG_GROUP_DISPLAY,
                   'hshares' => HSHARES_GROUP_DISPLAY,
                   'hstech' => HSTECH_GROUP_DISPLAY,
                   'mscius50' => MSCIUS50_GROUP_DISPLAY,
                   'oilfund' => OIL_GROUP_DISPLAY,
                   'qqqfund' => QQQ_GROUP_DISPLAY,
                   'spyfund' => SPY_GROUP_DISPLAY,
                   );
}

function GetStockMenuArray()
{
    return array('chinaindex' => CHINA_INDEX_DISPLAY,
                   'qdii' => QDII_DISPLAY,
                   'qdiimix' => QDII_MIX_DISPLAY,
                   'qdiihk' => QDII_HK_DISPLAY,
                   'qdiijp' => QDII_JP_DISPLAY,
                   'qdiieu' => QDII_EU_DISPLAY);
}

function GetStockMenuLinks()
{
	return GetCategoryLinks(GetStockMenuArray());
}

function GetStockPhpLink($strPage, $strDisplay, $strQuery = false)
{
    return GetPhpLink(PATH_STOCK.$strPage, $strQuery, $strDisplay);
}

function GetStockCategoryLink($strItem)
{
    $ar = GetStockCategoryArray();
    return GetStockPhpLink($strItem, $ar[$strItem]);
}

function GetStockMenuLink($strItem)
{
    $ar = GetStockMenuArray();
    return GetStockPhpLink($strItem, $ar[$strItem]);
}

function GetStockPageLink($strPage, $strDisplay, $strQuery = false)
{
	return GetPageLink(PATH_STOCK, $strPage, $strQuery, $strDisplay);
}

function GetAllLofLink()
{
    return GetStockPageLink('lof', LOF_ALL_DISPLAY);
}

function GetOvernightLink()
{
    return GetStockPageLink('overnight', OVERNIGHT_DISPLAY);
}

function GetOvernightCnhLink($strDisplay = OVERNIGHT_CNH_DISPLAY, $strAmount = '95000')
{
	$strPage = 'overnightcnh';
	return GetStockPageLink($strPage, $strDisplay, $strPage.'='.$strAmount);
}

function GetStockSymbolLink($strPage, $strSymbol, $strDisplay = false, $strExtraQuery = false)
{
	$strQuery = 'symbol='.$strSymbol;
	if ($strExtraQuery)	$strQuery .= '&'.$strExtraQuery;
	return GetStockPageLink($strPage, ($strDisplay ? $strDisplay : $strSymbol), $strQuery);
}

define('ALL_STOCK_DISPLAY', '全部股票代码');
function GetMyStockLink($strSymbol = false, $strDisplay = false)
{
	$strPage = 'mystock';
	if ($strSymbol)		return GetStockSymbolLink($strPage, $strSymbol, $strDisplay);
	return GetStockPageLink($strPage, ALL_STOCK_DISPLAY);
}

define('CALIBRATION_HISTORY_DISPLAY', '校准记录');
function GetCalibrationHistoryLink($strSymbol, $strDisplay = CALIBRATION_HISTORY_DISPLAY)
{
    return GetStockSymbolLink('calibrationhistory', $strSymbol, ($strDisplay ? $strDisplay : $strSymbol));
}

function SymCalibrationHistoryLink($sym)
{
    return GetCalibrationHistoryLink($sym->GetSymbol(), $sym->GetDisplay());
}

define('HOLDINGS_DISPLAY', '基金持仓');
function GetHoldingsLink($strSymbol, $strDisplay = HOLDINGS_DISPLAY)
{
    return GetStockSymbolLink('holdings', $strSymbol, ($strDisplay ? $strDisplay : $strSymbol));
}

define('STOCK_HISTORY_DISPLAY', '历史价格');
function GetStockHistoryLink($strSymbol, $strDisplay = STOCK_HISTORY_DISPLAY)
{
    return GetStockSymbolLink('stockhistory', $strSymbol, ($strDisplay ? $strDisplay : $strSymbol));
}

define('STOCK_HISTORY_UPDATE', '更新'.STOCK_HISTORY_DISPLAY);
function GetUpdateStockHistoryLink($sym, $strDisplay = false)
{
	$strSymbol = $sym->GetSymbol();
	return GetOnClickLink(PATH_STOCK.'submithistory.php?symbol='.$strSymbol, '确认'.$strSymbol.STOCK_HISTORY_UPDATE.'？', ($strDisplay ? $strDisplay : $strSymbol));
}

define('FUND_HISTORY_DISPLAY', '基金溢价记录');
function GetFundHistoryLink($strSymbol)
{
    return GetStockSymbolLink('fundhistory', $strSymbol, FUND_HISTORY_DISPLAY);
}

define('NETVALUE_HISTORY_DISPLAY', '净值记录');
function GetNetValueHistoryLink($strSymbol, $strExtraQuery = false, $strExtraDisplay = false)
{
	$strDisplay = $strExtraQuery ? $strExtraDisplay : NETVALUE_HISTORY_DISPLAY;
    return GetStockSymbolLink('netvaluehistory', $strSymbol, $strDisplay, $strExtraQuery);
}

define('NETVALUE_CLOSE_DISPLAY', '净值价格比较');
function GetNetValueCloseLink($strSymbol)
{
	return GetStockSymbolLink('netvalueclose', $strSymbol, NETVALUE_CLOSE_DISPLAY);
}

define('ETF_DIVIDEND_DISPLAY', STOCK_OPTION_DIVIDEND.'数据');
function GetEtfDividendLink($strSymbol)
{
	return GetStockSymbolLink('etfdividend', $strSymbol, ETF_DIVIDEND_DISPLAY);
}

function GetFundLinks($strSymbol)
{
	$bChinaIndex = in_arrayChinaIndex($strSymbol);
    $bQdii = in_arrayQdii($strSymbol);
	$bQdiiHk = in_arrayQdiiHk($strSymbol);
	$bQdiiJp = in_arrayQdiiJp($strSymbol);
	$bQdiiEu = in_arrayQdiiEu($strSymbol);
	$bQdiiMix = in_arrayQdiiMix($strSymbol);

	$str = GetStockHistoryLink($strSymbol).' '.GetFundHistoryLink($strSymbol).' '.GetNetValueHistoryLink($strSymbol).' '.GetNetValueCloseLink($strSymbol).' '.GetFundShareLink($strSymbol);
	if ($bChinaIndex || $bQdii || $bQdiiHk || $bQdiiJp || $bQdiiEu || $bQdiiMix)
	{
		$str .= ' '.GetCalibrationHistoryLink($strSymbol);
		if ($bQdii || $bQdiiHk || $bQdiiJp || $bQdiiEu)		$str .= ' '.GetFundPositionLink($strSymbol);
		if ($bQdii)											$str .= ' '.GetFundAccountLink($strSymbol).' '.GetThanousParadoxLink($strSymbol);
	}
	else
	{
		$str .= ' '.GetEtfDividendLink($strSymbol);
	}
	return $str;
}

define('AH_HISTORY_DISPLAY', 'AH'.STOCK_HISTORY_DISPLAY.'比较');
function GetAhHistoryLink($strSymbol)
{
    return GetStockSymbolLink('ahhistory', $strSymbol, AH_HISTORY_DISPLAY);
}

define('THANOUS_PARADOX_DISPLAY', '小心愿佯谬');
function GetThanousParadoxLink($strSymbol)
{
    return GetStockSymbolLink('thanousparadox', $strSymbol, THANOUS_PARADOX_DISPLAY);
}

define('FUND_ACCOUNT_DISPLAY', '基金场内申购账户');
function GetFundAccountLink($strSymbol, $iNum = false)
{
    return GetStockSymbolLink('fundaccount', $strSymbol, ($iNum ? strval($iNum) : FUND_ACCOUNT_DISPLAY));
}

define('FUND_POSITION_DISPLAY', STOCK_DISP_POSITION.'估算');
function GetFundPositionLink($strSymbol, $bDisplaySymbol = false)
{
    return GetStockSymbolLink('fundposition', $strSymbol, ($bDisplaySymbol ? $strSymbol : FUND_POSITION_DISPLAY));
}

define('FUND_SHARE_DISPLAY', '基金场内份额');
function GetFundShareLink($strSymbol)
{
    return GetStockSymbolLink('fundshare', $strSymbol, FUND_SHARE_DISPLAY);
}

define('EXHAUSTIVE_HOLDINGS_DISPLAY', '穷举持仓比例');
function GetExhaustiveHoldingsLink($strSymbol)
{
    return GetStockSymbolLink('exhaustiveholdings', $strSymbol, EXHAUSTIVE_HOLDINGS_DISPLAY);
}

define('ROTATION_TRADING_DISPLAY', '轮动交易');
function GetRotationTradingLink($strSymbol)
{
    return GetStockSymbolLink('rotationtrading', $strSymbol, ROTATION_TRADING_DISPLAY);
}

function GetStockOptionArray()
{
    $ar = array(
                  'editcalibration' => STOCK_OPTION_CALIBRATION,
                  'editfund' => STOCK_OPTION_FUND,
                  'editnetvalue' => STOCK_OPTION_NETVALUE,
                  'editpremium' => STOCK_OPTION_PREMIUM,
                  'editsharesdiff' => STOCK_OPTION_SHARE_DIFF,
    			  'editstock' => STOCK_OPTION_EDIT,
                  'editstockadr' => STOCK_OPTION_ADR,
                  'editstockah' => STOCK_OPTION_AH,
                  'editstockamount' => STOCK_OPTION_AMOUNT,
                  'editstockclose' => STOCK_OPTION_CLOSE,
                  'editstockdividend' => STOCK_OPTION_DIVIDEND,
                  'editstockema' => STOCK_OPTION_EMA,
                  'editstockha' => STOCK_OPTION_HA,
                  'editstockholdings' => STOCK_OPTION_HOLDINGS,
                  'editstocksplit' => STOCK_OPTION_SPLIT,
                 );
	return $ar;
}

function GetStockOptionLink($strOption, $strSymbol)
{
    $ar = GetStockOptionArray();
    $strPage = array_search($strOption, $ar);
    return GetStockSymbolLink($strPage, $strSymbol, $strOption);
}

function GetStockEditDeleteLink($strSymbol, $bAdmin)
{
	$str = GetStockOptionLink(STOCK_OPTION_EDIT, $strSymbol);
	if ($bAdmin)	$str .= ' '.GetDeleteLink(PATH_STOCK.'deletesymbol.php?symbol='.$strSymbol, '股票'.$strSymbol);
	return $str;
}

define('AUTO_TRACTOR_DISPLAY', '拖拉机自动化');
function GetAutoTractorLink($strQuery = false)
{
    return GetStockPageLink('autotractor', AUTO_TRACTOR_DISPLAY, $strQuery);
}

define('MY_PORTFOLIO_DISPLAY', STOCK_DISP_HOLDING.STOCK_DISP_PROFIT);
function GetMyPortfolioLink($strQuery = false)
{
    return GetStockPageLink('myportfolio', MY_PORTFOLIO_DISPLAY, $strQuery);
}

define('AB_COMPARE_DISPLAY', 'A股和B股对比');
function GetAbCompareLink()
{
    return GetStockPageLink('abcompare', AB_COMPARE_DISPLAY);
}

define('AH_COMPARE_DISPLAY', 'A股和H股对比');
function GetAhCompareLink()
{
    return GetStockPageLink('ahcompare', AH_COMPARE_DISPLAY);
}

define('FUND_LIST_DISPLAY', '基金指数对照表');
function GetFundListLink()
{
    return GetStockPageLink('fundlist', FUND_LIST_DISPLAY);
}

define('ADRH_COMPARE_DISPLAY', 'ADR和H股对比');
function GetAdrhCompareLink()
{
    return GetStockPageLink('adrhcompare', ADRH_COMPARE_DISPLAY);
}

define('STOCK_GROUP_DISPLAY', '股票分组');
function GetMyStockGroupLink($strQuery = false, $strDisplay = false)
{
    if ($strDisplay == false)	$strDisplay = STOCK_GROUP_DISPLAY;
	return GetStockPageLink('mystockgroup', $strDisplay, $strQuery);
}

define('STOCK_TRANSACTION_DISPLAY', '交易记录');
function StockGetTransactionLink($strGroupId, $strSymbol, $strDisplay = false)
{
    $strQuery = 'groupid='.$strGroupId;
    if ($strSymbol)	$strQuery .= '&symbol='.$strSymbol;
    
    if ($strDisplay == false)
    {
    	$sym = new StockSymbol($strSymbol);
    	$strDisplay = $sym->GetDisplay();
    }
	return GetStockPageLink('mystocktransaction', $strDisplay, $strQuery);
}

function StockGetAllTransactionLink($strGroupId, $ref = false)
{
	if ($ref)
	{
		$strSymbol = $ref->GetSymbol();
	}
	else
	{
		$strSymbol = false;
	}
    return StockGetTransactionLink($strGroupId, $strSymbol, STOCK_TRANSACTION_DISPLAY);
}

function StockGetGroupTransactionLinks($strGroupId)
{
    $str = '';
	$sql = new StockGroupItemSql($strGroupId);
    if ($arGroupItemSymbol = SqlGetStockGroupItemSymbolArray($sql))
    {
    	foreach ($arGroupItemSymbol as $strGroupItemId => $strSymbol)
    	{
   			$str .= StockGetTransactionLink($strGroupId, $strSymbol).' ';
    	}
    }
    return rtrim($str, ' ');
}

// ****************************** Other internal link related functions *******************************************************
function GetGroupStockLink($strSymbol, $bFull = false)
{
    if (in_arrayAll($strSymbol))		return GetStockPageLink(strtolower($strSymbol), ($bFull ? SqlGetStockName($strSymbol).'('.$strSymbol.')' : $strSymbol));
    return false;
}

function StockGetMenuLink($strSymbol, $iTotal, $iStart, $iNum)
{
    return GetMenuLink('symbol='.$strSymbol, $iTotal, $iStart, $iNum);
}

function StockGetAllLink($strSymbol)
{
    return CopyPhpLink('symbol='.$strSymbol.'&start=0&num=0', '全部记录');
}

?>
