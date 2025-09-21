<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');

function _echoEtfDividendData($sql, $his_sql, $ref)
{
	$strStockId = $ref->GetStockId();
    if ($result = $sql->GetAll($strStockId)) 
    {
        while ($arDividend = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $arDividend['date'];
       		if ($strClose = $his_sql->GetClose($strStockId, $strDate))
       		{
       			$strDividend = rtrim0($arDividend['close']);
       			$ar = array($strDate, $strDividend, $strClose);
       			$ar[] = number_format(floatval($strDividend) / floatval($strClose) * 100.0, 2);
       			EchoTableColumn($ar);
       		}
        }
        mysqli_free_result($result);
    }
}

function _EchoEtfDividendHistoryParagraph($ref, $bAdmin = false)
{
    if ($ref->CountNetValue() == 0)	return;

    $strSymbol = $ref->GetSymbol();
   	$str = GetFundLinks($strSymbol).' '.GetStockDividendLink($ref);
   	if ($bAdmin)	$str .= ' '.GetOnClickLink(PATH_STOCK.'submitdividend.php?symbol='.$strSymbol, '确认更新'.$strSymbol.ETF_DIVIDEND_DISPLAY.'？', '更新'.ETF_DIVIDEND_DISPLAY);
	EchoTableParagraphBegin(array(new TableColumnDate(),
								  new TableColumn(STOCK_OPTION_DIVIDEND),
								   new TableColumnPrice(),
								   new TableColumnPercentage()
								   ), $strSymbol.'etfdividend', $str);
    _echoEtfDividendData(new StockDividendSql(), GetStockHistorySql(), $ref);
    EchoTableParagraphEnd();
}                                               

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
   		_EchoEtfDividendHistoryParagraph($ref, $acct->IsAdmin());
    }
    $acct->EchoLinks();
}    

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().ETF_DIVIDEND_DISPLAY;
    $str .= '历史页面。通过历史数据为夜盘跨市场套利工具估算美股ETF分红除权当日对应的QDII基金的假溢价幅度。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetSymbolDisplay().ETF_DIVIDEND_DISPLAY;
}

    $acct = new SymbolAccount();

require('../../php/ui/_dispcn.php');
?>
