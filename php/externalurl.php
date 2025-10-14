<?php

function GetIpInfoUrl()
{
	return 'http://ipinfo.io/';
}

function GetYahooStockUrl($strYahooSymbol)
{
	return 'https://finance.yahoo.com/quote/'.$strYahooSymbol;
}

function GetYahooStockHistoryUrl($strYahooSymbol)
{
	return GetYahooStockUrl($strYahooSymbol).'/history';
}

function GetYahooDataUrl($strVersion = '6')
{
	return 'https://query1.finance.yahoo.com/v'.$strVersion.'/finance';
}

/* Sina data
nf_IC2006. nf_AU0,nf_AG2512,nf_AG0
http://hq.sinajs.cn/list=s_sh000001 上证指数
http://hq.sinajs.cn/list=s_sz399001 深证成指
http://hq.sinajs.cn/list=int_hangseng 恒生指数
http://hq.sinajs.cn/list=s_sz399300 沪生300
http://hq.sinajs.cn/list=int_dji 道琼斯
http://hq.sinajs.cn/list=int_nasdaq 纳斯达克
http://hq.sinajs.cn/list=int_sp500 标普500
http://hq.sinajs.cn/list=int_ftse 英金融时报指数
http://blog.sina.com.cn/s/blog_7ed3ed3d0101gphj.html
http://hq.sinajs.cn/list=sh600151,sz000830,s_sh000001,s_sz399001,s_sz399106,s_sz399107,s_sz399108
期货 http://hq.sinajs.cn/rn=1318986550609&amp;list=hf_CL,hf_GC,hf_SI,hf_CAD,hf_ZSD,hf_S,hf_C,hf_W,hf_XAU
http://hq.sinajs.cn/rn=1318986628214&amp;list=fx_susdcny,USDHKD,EURCNY,GBPCNY,USDJPY,EURUSD,GBPUSD,
http://hq.sinajs.cn/list=gb_dji
https://hq.sinajs.cn/list=rt_hkHSTECH,hkHSTECH_i,rt_hkCSCSHQ,market_status_hk
https://w.sinajs.cn/rn=9037858664&list=hkHSI_i,hkHSCEI_i,hkHSTECH_i,hkHSCCI_i
https://w.sinajs.cn/rn=5130947756&list=rt_hkHSI_preipo,rt_hkHSCEI_preipo,rt_hkHSTECH_preipo,rt_hkHSCCI_preipo
*/
function GetSinaDataUrl($strSinaSymbols)
{
	return 'http://hq.sinajs.cn/list='.$strSinaSymbols;
//	return 'https://w.sinajs.cn/list='.$strSinaSymbols;
}	

function GetSinaFinanceUrl()
{
	return 'https://finance.sina.com.cn';
}

function GetSinaStockUrl()
{
	return 'https://stock.finance.sina.com.cn';
}

function GetSinaVipStockUrl()
{
	return 'https://vip.stock.finance.sina.com.cn';
}

function GetSinaChinaStockListUrl($strNode = 'hs_a')
{
	return GetSinaVipStockUrl().'/mkt/#'.$strNode;
}

function GetSinaUsStockListUrl()
{
	return GetSinaVipStockUrl().'/usstock/ustotal.php';
}

function GetEastMoneyFundUrl()
{
	return 'http://fund.eastmoney.com/';
}

function GetAastocksUrl()
{
	return 'http://www.aastocks.com/tc/';
}

// http://www.aastocks.com/tc/usq/quote/adr.aspx?sort=0&order=1&type=0
function GetAastocksAdrUrl()
{
	return GetAastocksUrl().'usq/quote/adr.aspx?sort=0&order=1&type=0';
}

// http://www.aastocks.com/tc/stocks/market/second-listing.aspx
function GetAastocksSecondListingUrl()
{
	return GetAastocksUrl().'stocks/market/second-listing.aspx';
}

// http://www.aastocks.com/tc/stocks/market/ah.aspx
function GetAastocksAhUrl()
{
	return GetAastocksUrl().'stocks/market/ah.aspx';
}

function GetXueqiuUrl()
{
	return 'https://xueqiu.com/';
}

function GetXueqiuWoodyUrl()
{
	return GetXueqiuUrl().'2244868365/';
}

function GetJisiluDataUrl()
{
	return 'https://www.jisilu.cn/data/';
}

// https://csi-web-dev.oss-cn-shanghai-finance-1-pub.aliyuncs.com/static/html/csindex/public/uploads/file/autofile/closeweight/H30533closeweight.xls
// https://csi-web-dev.oss-cn-shanghai-finance-1-pub.aliyuncs.com/static/html/csindex/public/uploads/file/autofile/closeweight/H11136closeweight.xls
// https://www.csindex.com.cn/#/indices/family/detail?indexCode=H11136
function GetCsindexUrl($strSymbol)
{
	return 'https://www.csindex.com.cn/#/indices/family/detail?indexCode='.$strSymbol;
}

function GetSzseUrl($strSubDomain = 'www')
{
	return 'https://'.$strSubDomain.'.szse.cn/';
}

// https://reportdocs.static.szse.cn/files/text/etf/ETF15960520220315.txt?random=0.12210692394619271
function GetSzseEtfFileName($strDigitA, $strDate)
{
    if ($strDate)	return 'ETF'.$strDigitA.str_replace('-', '', $strDate).'.txt';
    return '';
}

function GetSzseHoldingsUrl($strFileName)
{
	return GetSzseUrl('reportdocs.static').'files/text/etf/'.$strFileName.'?random='.strval(1.0 * rand() / getrandmax());
}

function GetSseUrl($strSubDomain = 'www')
{
	return 'https://'.$strSubDomain.'.sse.com.cn/';
}

// https://www.sse.com.cn/disclosure/fund/etflist/
function GetSseDisclosureUrl()
{
	return GetSseUrl().'disclosure/fund/etflist/';
}

function GetSseEtfType($strSymbol)
{
	$ar = array('SH513050' => '087', 'SH513090' => '254', 'SH513220' => '509', 'SH513230' => '459', 'SH513350' => '634', 'SH513360' => '395', 'SH513750' => '607', 'SH513850' => '577', 'SH513990' => '244');
	if (isset($ar[$strSymbol]))		return $ar[$strSymbol];
	return false;
}

// https://www.sse.com.cn/disclosure/fund/etflist/detail.shtml?type=087&fundid=513050&etfClass=33
function GetSseDisclosureDetailUrl($sym, $strEtfType)
{
	return GetSseDisclosureUrl().'detail.shtml?type='.$strEtfType.'&fundid='.$sym->GetDigitA().'&etfClass=33';
}

function GetProsharesUrl()
{
	return 'https://www.proshares.com/';
}

// https://kraneshares.com/kweb/
// https://kraneshares.com/csv/06_22_2021_kweb_holdings.csv
// https://kraneshares.com/product-json/?pid=477&type=premium-discount&start=2025-08-18&end=2025-08-19
function GetKraneUrl()
{
	return 'https://kraneshares.com/';
}

// https://www.cmegroup.com/markets/equities/sp/e-mini-sandp500.quotes.html
// https://www.cmegroup.com/markets/equities/nasdaq/e-mini-nasdaq-100.quotes.html
// https://www.cmegroup.com/markets/fx/cross-rates/usd-cnh.html
// https://www.cmegroup.com/markets/equities/international-indices/nikkei-225-yen.html
function GetCmeUrl($strSymbol)
{
	$str = 'https://www.cmegroup.com/markets/';
	switch ($strSymbol)
	{
	case 'hf_ES':
		return $str.'equities/sp/e-mini-sandp500.quotes.html';
		
	case 'hf_NQ':
		return $str.'equities/nasdaq/e-mini-nasdaq-100.quotes.html';
		
	case 'fx_susdcnh':
		return $str.'fx/cross-rates/usd-cnh.html';
		
	case 'NIY':
		return $str.'equities/international-indices/nikkei-225-yen.html';
	}
	
	return false;
}

// https://www.ishares.com/us/products/239517/ishares-us-oil-gas-exploration-production-etf/
function GetIsharesEtfUrl($strSymbol)
{
	$str = 'https://www.ishares.com/us/products/';
	switch ($strSymbol)
	{
	case 'GSG':
		$str .= '239757/GSG';
		break;

	case 'IEO':
		$str .= '239517/ishares-us-oil-gas-exploration-production-etf';
		break;
		
	case 'IXC':
		$str .= '239741/ishares-global-energy-etf';
		break;
	}
	return $str;
}

// https://www.ishares.com/us/products/239517/ishares-us-oil-gas-exploration-production-etf/1521942788811.ajax?fileType=xls&fileName=iShares-US-Oil--Gas-Exploration--Production-ETF_fund&dataType=fund
function _getIsharesXlsUrl($strSymbol)
{
	$str = GetIsharesEtfUrl($strSymbol).'/';
	switch ($strSymbol)
	{
	case 'GSG':
		break;

	case 'IEO':
		$str .= '1521942788811.ajax?fileType=xls&fileName=iShares-US-Oil--Gas-Exploration--Production-ETF_fund&dataType=fund';
		return $str;
		
	case 'IXC':
		break;
	}
	return false;
}

// https://www.ssga.com/us/en/individual/etfs/spdr-sp-500-etf-trust-spy
function GetSpdrUrl()
{
	return 'https://www.ssga.com/';
}

function GetSpdrEtfUrl()
{
	return GetSpdrUrl().'us/en/individual/etfs/';
}

function GetSpdrOfficialUrl($strSymbol)
{
	$str = GetSpdrEtfUrl();
	switch ($strSymbol)
	{
	case 'SPY':
		$str .= 'spdr-sp-500-etf-trust-spy';
		break;

	case 'XBI':
		$str .= 'spdr-sp-biotech-etf-xbi';
		break;

	case 'XLE':
		$str .= 'the-energy-select-sector-spdr-fund-xle';
		break;

	case 'XLY':
		$str .= 'the-consumer-discretionary-select-sector-spdr-fund-xly';
		break;

	case 'XOP':
		$str .= 'spdr-sp-oil-gas-exploration-production-etf-xop';
		break;
		
	default:
		return false;
	}
	return $str;
}

// https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/navhist-us-en-xop.xlsx
function GetSpdrNetValueUrl($strSymbol)
{
	return GetSpdrEtfUrl().'library-content/products/fund-data/etfs/us/navhist-us-en-'.strtolower($strSymbol).'.xlsx';
}

// https://dng-api.invesco.com/cache/v1/accounts/en_US/shareclasses/46090E103/prices?idType=cusip&variationType=priceListing&productType=ETF
// https://www.invesco.com/us/en/financial-products/etfs/invesco-qqq-trust-series-1.html
// https://www.invesco.com/us/financial-products/etfs/product-detail?audienceType=Investor&productId=ETF-QQQ
function GetInvescoUrl()
{
	return 'https://www.invesco.com/';
}

function GetInvescoEtfUrl()
{
	return GetInvescoUrl().'us/financial-products/etfs/product-detail';
}

function GetInvescoOfficialUrl($strSymbol)
{
	switch ($strSymbol)
	{
	case 'QQQ':
		return GetInvescoEtfUrl().'?audienceType=Investor&productId=ETF-'.$strSymbol;
	}
	return false;
}

// https://www.invesco.com/us/financial-products/etfs/product-detail/main/sidebar/0?audienceType=Investor&action=download&ticker=QQQ
function GetInvescoNetValueUrl($strSymbol)
{
	return GetInvescoEtfUrl().'/main/sidebar/0?audienceType=Investor&action=download&ticker='.$strSymbol;
}

function GetEtfNetValueUrl($strSymbol)
{
	if (GetSpdrOfficialUrl($strSymbol))			return GetSpdrNetValueUrl($strSymbol);
	else if (GetInvescoOfficialUrl($strSymbol))	return GetInvescoNetValueUrl($strSymbol);
	else if ($strName = SqlGetStockName($strSymbol))
	{
		if (stripos($strName, 'ishares') !== false)
		{
			return _getIsharesXlsUrl($strSymbol);
		}
	}
	return false;
}

function GetChinaMoneyUrl()
{
	return 'https://www.chinamoney.com.cn/';
}

function GetChinaMoneyJsonUrl()
{
	return GetChinaMoneyUrl().'r/cms/www/chinamoney/data/fx/ccpr.json';
}


?>
