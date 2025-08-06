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

function GetSinaDataUrl($strSinaSymbols)
{
	return 'http://hq.sinajs.cn/list='.$strSinaSymbols;
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

function GetSzseUrl()
{
	return 'https://www.szse.cn/';
}

function GetSseUrl()
{
	return 'http://www.sse.com.cn/';
}

function GetProsharesUrl()
{
	return 'https://www.proshares.com/';
}

// https://kraneshares.com/kweb/
// https://kraneshares.com/csv/06_22_2021_kweb_holdings.csv
// https://kraneshares.com/product-json/?pid=477&type=premium-discount&start=2024-03-15&end=2024-03-15
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

?>
