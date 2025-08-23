<?php
require_once('php/_stock.php');
require_once('../../php/ui/fundlistparagraph.php');
require_once('../../php/ui/referenceparagraph.php');

function EchoAll()
{
	global $acct;

	$pair_sql = GetFundPairSql();
    $ar = $pair_sql->GetSymbolArray();
    StockPrefetchArrayExtendedData($ar);
    
    $arRef = array();
    foreach ($ar as $strSymbol)
    {
    	$arRef[] = new FundPairReference($strSymbol);
    }
    
   	EchoReferenceParagraph($arRef);
    EchoFundListParagraph($arRef);
    $acct->EchoLinks();
}

function GetMetaDescription()
{
  	$str = '各个估值页面中用到的基金和指数对照表，包括杠杆倍数('.STOCK_DISP_POSITION.')和校准值快照，同时提供链接查看具体校准情况。有些指数不容易拿到数据，就用1倍ETF代替指数给其它杠杆ETF做对照。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	return FUND_LIST_DISPLAY;
}

	$acct = new StockAccount();

require('../../php/ui/_dispcn.php');
?>
