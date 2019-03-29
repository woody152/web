<?php
require_once('_stock.php');
require_once('_fundgroup.php');

class _GoldEtfGroup extends _StockGroup
{
    // constructor 
    function _GoldEtfGroup($strSymbol) 
    {
        StockPrefetchData($strSymbol);
        GetChinaMoney();

        $this->cny_ref = new CnyReference('USCNY');
        $this->ref = new GoldFundReference($strSymbol);
        
        parent::_StockGroup(array($this->ref->stock_ref));
    }
} 

function _echoTestParagraph($group)
{
    $str = _GetEtfAdjustString($group->ref->stock_ref, $group->ref->est_ref);
    EchoParagraph($str);
}

function EchoAll($bChinese = true)
{
    global $group;
    $fund = $group->ref;
    EchoFundEstParagraph($fund);
    EchoReferenceParagraph(array($fund->est_ref, $fund->future_ref, $group->cny_ref, $fund->stock_ref));
    EchoFundTradingParagraph($fund);    
    EchoFundHistoryParagraph($fund);

    if ($group->strGroupId) 
    {
        _EchoTransactionParagraph($group);
	}
    
    EchoPromotionHead('goldetf');
    if (AcctIsAdmin())
    {
        _echoTestParagraph($group);
    }
}

function EchoMetaDescription($bChinese = true)
{
    global $group;

    $fund = $group->ref;
    $strDescription = _GetStockDisplay($fund->stock_ref);
    $strEst = _GetStockDisplay($fund->est_ref);
    $strFuture = _GetStockDisplay($fund->future_ref);
    $strCNY = _GetStockDisplay($group->cny_ref);
    $str = '根据'.$strEst.', '.$strFuture.'和'.$strCNY.'等因素计算'.$strDescription.'净值的网页工具.';
    EchoMetaDescriptionText($str);
}

    AcctNoAuth();
    $group = new _GoldEtfGroup(StockGetSymbolByUrl());

?>
