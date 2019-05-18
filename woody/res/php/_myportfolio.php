<?php
require_once('_stock.php');
require_once('/php/stockhis.php');
//require_once('/php/stockgroup.php');
require_once('/php/ui/referenceparagraph.php');

class _MyPortfolio extends StockGroup
{
    var $arStockGroup = array();
    
    function _MyPortfolio() 
    {
        parent::StockGroup();
    }
}

function _echoReference($arRef)
{
	$arA = array();
	$arH = array();
	$arUS = array();
	
    StockHistoryUpdate($arRef);    
    $arRef = RefSortBySymbol($arRef);
    foreach ($arRef as $ref)
    {
    	RefSetExternalLinkMyStock($ref);
    	$sym = $ref->GetSym();
    	if ($sym->IsSymbolA())		$arA[] = $ref;
    	else if ($sym->IsSymbolH())	$arH[] = $ref;
    	else							$arUS[] = $ref;
    }
    EchoReferenceParagraph(array_merge($arA, $arH, $arUS));
}

function _echoPortfolio($portfolio, $sql)
{
    $arRef = array();
    _EchoPortfolioParagraphBegin('个股盈亏');    
	if ($result = $sql->GetAll()) 
	{
		while ($record = mysql_fetch_assoc($result)) 
		{
		    $group = new MyStockGroup($record['id'], array());
		    if ($group->GetTotalRecords() > 0)
		    {
		        $portfolio->arStockGroup[] = $group;
		        foreach ($group->arStockTransaction as $trans)
		        {
		            if ($trans->iTotalRecords > 0)
		            {
		                _EchoPortfolioItem($record['id'], $trans);
		                $portfolio->OnStockTransaction($trans);
		                if (!in_array($trans->ref, $arRef))    $arRef[] = $trans->ref;
		            }
		        }
		    }
		}
		@mysql_free_result($result);
	}
    EchoTableParagraphEnd();    

    _echoReference($arRef);
}

function _echoMoneyParagraph($portfolio)
{
    $strUSDCNY = SqlGetUSCNY();
    $strHKDCNY = SqlGetHKCNY();    

    _EchoMoneyParagraphBegin();
    foreach ($portfolio->arStockGroup as $group)
    {
        _EchoMoneyGroupData($group, GetStockGroupLink($group->GetGroupId()), $strUSDCNY, $strHKDCNY);
    }
    _EchoMoneyGroupData($portfolio, '全部', $strUSDCNY, $strHKDCNY);
    EchoTableParagraphEnd();
}

function _onPrefetch($sql) 
{
	if ($result = $sql->GetAll()) 
	{
	    $arSymbol = array();
		while ($record = mysql_fetch_assoc($result)) 
		{
		    $arSymbol = array_merge($arSymbol, SqlGetStocksArray($record['id'], true));
		}
		@mysql_free_result($result);
	}
    StockPrefetchArrayData($arSymbol);
}

function EchoMyFortfolio($bChinese = true)
{
	$sql = new StockGroupSql(AcctGetMemberId());
    _onPrefetch($sql);

    $portfolio = new _MyPortfolio();
    _echoPortfolio($portfolio, $sql);
    _echoMoneyParagraph($portfolio);
    
    EchoPromotionHead('portfolio');
}

    AcctEmailAuth();

?>

