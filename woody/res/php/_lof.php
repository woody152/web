<?php
require_once('_stock.php');
require_once('_lofgroup.php');

class _LofUsGroup extends _LofGroup
{
    var $etf_netvalue_ref;
    var $usd_future_ref;

    // constructor 
    function _LofUsGroup($strSymbol) 
    {
        $strFutureUSD = 'DXF'; 
        $strEtfSymbol = LofGetEtfSymbol($strSymbol);
        $arPrefetch = LofGetAllSymbolArray($strSymbol);
        $arPrefetch[] = FutureGetSinaSymbol($strFutureUSD); 
        $arPrefetch[] = GetYahooNetValueSymbol($strEtfSymbol);
        PrefetchStockData($arPrefetch);
        
        $this->cny_ref = new CNYReference('USCNY');
        ForexUpdateHistory($this->cny_ref);
        
        $this->ref = new MyLofReference($strSymbol);
        $this->usd_future_ref = new MyFutureReference($strFutureUSD);
        $this->etf_netvalue_ref = new YahooNetValueReference($strEtfSymbol);
        parent::_LofGroup();
        $this->arDisplayRef = array($this->ref->index_ref, $this->ref->etf_ref, $this->etf_netvalue_ref, $this->ref->future_ref, $this->usd_future_ref, $this->cny_ref, $this->ref->stock_ref, $this->ref);
    }
    
    function OnData()
    {
        if ($this->ref->index_ref)
        {
            if ($this->ref->index_ref->AdjustEtfFactor($this->etf_netvalue_ref) == false)
            {
                $this->ref->index_ref->AdjustEtfFactor($this->ref->etf_ref);
            }
        }
    }
} 

function _onSmaUserDefinedVal($fVal, $bChinese)
{
    global $group;
    
    $fund = $group->ref;
    $strAmount = FUND_PURCHASE_AMOUNT;
    if ($group->strGroupId) 
    {
    	SqlCreateFundPurchaseTable();
    	if ($str = SqlGetFundPurchaseAmount(AcctIsLogin(), $fund->stock_ref->strSqlId))
    	{
    		$strAmount = $str;
    	}
    }
	$fAmount = floatval($strAmount);
    $iQuantity = intval($fAmount / $fund->fCNY / $fVal);
    $strQuantity = strval($iQuantity);
    if ($group->strGroupId) 
    {
        $etf_ref = $fund->etf_ref;
        $strQuery = sprintf('groupid=%s&fundid=%s&amount=%.2f&netvalue=%.3f&arbitrageid=%s&quantity=%s&price=%.2f', $group->strGroupId, $fund->stock_ref->strSqlId, $fAmount, $fund->fPrice, $etf_ref->strSqlId, $strQuantity, $etf_ref->fPrice);
        return UrlGetOnClickLink(STOCK_PHP_PATH.'_submitfundpurchase.php?'.$strQuery, $bChinese ? '确认添加对冲申购记录?' : 'Confirm to add arbitrage fund purchase record?', $strQuantity);
    }
    return $strQuantity;
}

function _getArbitrageQuantityName($bEditLink, $bChinese)
{
    global $group;

    if ($bChinese)
    {
    	$str = '申购对冲';
    	$strDisplay = '数量';
    }
    else
    {
    	$str = 'Arbitrage ';
    	$strDisplay = 'Quantity';
    }
    
    if ($group->strGroupId && $bEditLink) 
    {
    	$str .= UrlGetPhpLink(STOCK_PATH.'editfundamount', 'symbol='.$group->ref->GetStockSymbol(), $strDisplay, $bChinese);
    }
    else
    {
    	$str .= $strDisplay;
    }
    return $str;
}

function _onSmaUserDefined($fVal, $fNext, $bChinese)
{
    if ($fVal)
    {
        return _onSmaUserDefinedVal($fVal, $bChinese).'/'._onSmaUserDefinedVal($fNext, $bChinese);
    }
    return _getArbitrageQuantityName(false, $bChinese);
}

function _onTradingUserDefinedVal($fVal, $bChinese)
{
    global $group;
    
    $fund = $group->ref;
    $fEtf = $fund->EstEtf($fVal);
    return _onSmaUserDefinedVal($fEtf, $bChinese).'@'.$fund->etf_ref->GetPriceDisplay($fEtf);
}

function _onTradingUserDefined($fVal, $bChinese)
{
    if ($fVal)
    {
        return _onTradingUserDefinedVal($fVal, $bChinese);
    }
    return _getArbitrageQuantityName(true, $bChinese);
}

function EchoAll($bChinese)
{
    global $group;
    $fund = $group->ref;
    
    EchoFundEstParagraph($fund, $bChinese);
    EchoReferenceParagraph($group->arDisplayRef, $bChinese);
    EchoFundTradingParagraph($fund, _onTradingUserDefined, $bChinese);    
    EchoSmaParagraph($group->etf_his, $fund->stock_ref, EtfEstLof, _onSmaUserDefined, $bChinese);
    EchoFundHistoryParagraph($fund, $bChinese);
    
    if ($group->strGroupId) 
    {
        _EchoTransactionParagraph($group, $bChinese);
        if ($group->GetTotalRecords() > 0)
        {
            EchoMoneyParagraph($group, $group->cny_ref->fPrice, false, $bChinese);
            $group->EchoArbitrageParagraph($bChinese);
        }
	}
	    
    EchoPromotionHead('', $bChinese);
    $group->EchoAdminTestParagraph($bChinese);
}

    AcctNoAuth();
    $group = new _LofUsGroup(StockGetSymbolByUrl());
    $group->OnData();

?>
