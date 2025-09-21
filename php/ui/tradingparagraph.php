<?php
require_once('stocktable.php');

define('TRADING_QUOTE_NUM', 5);

function _getTradingTableColumn()
{
	return array(new TableColumn('交易', 50),
				  new TableColumnPrice(),
				  new TableColumnQuantity());
}

function _echoTradingTableItem($strColor, $strAskBid, $strPrice, $strQuantity, $ref, $fEstPrice, $fEstPrice2, $fEstPrice3, $callback)
{
	if ($strQuantity == '0')	return;
	
    $ar = array($strAskBid);
    $ar[] = $ref->GetPriceDisplay(floatval($strPrice), floatval($ref->GetPrevPrice()));
    $ar[] = $strQuantity;
    
	if ($fEstPrice)		$ar[] = $ref->GetPercentageDisplay($fEstPrice, $strPrice);
	if ($fEstPrice2)	$ar[] = $ref->GetPercentageDisplay($fEstPrice2, $strPrice);
	if ($fEstPrice3)	$ar[] = $ref->GetPercentageDisplay($fEstPrice3, $strPrice);
    if ($callback && (empty($strPrice) == false))
    {
    	$ar[] = call_user_func($callback, $strPrice);
    }
    
    EchoTableColumn($ar, $strColor);
}

function _getTradingColor($i)
{
	return ($i == 0) ? 'yellow' : false;
}

function _getTradingIndex($i)
{
	return strval($i + 1);
}

function _echoTradingTableData($ref, $fEstPrice, $fEstPrice2, $fEstPrice3, $callback)
{
   	$fPrice = floatval($ref->IsStockMarketTrading(GetNowYMD(), false) ? $ref->GetPrevPrice() : $ref->GetPrice());
   	$iPrecision = $ref->GetPrecision();
   	$strColor = 'orange';
	_echoTradingTableItem($strColor, '涨停', number_format($fPrice * 1.1, $iPrecision, '.', ''), '', $ref, $fEstPrice, $fEstPrice2, $fEstPrice3, $callback);
    
    for ($i = TRADING_QUOTE_NUM - 1; $i >= 0; $i --)
    {
    	if ($strQuantity = $ref->GetAskQuantity($i))	_echoTradingTableItem(_getTradingColor($i), '卖'._getTradingIndex($i), $ref->GetAskPrice($i), $strQuantity, $ref, $fEstPrice, $fEstPrice2, $fEstPrice3, $callback);
    }

    for ($i = 0; $i < TRADING_QUOTE_NUM; $i ++)
    {
    	if ($strQuantity = $ref->GetBidQuantity($i))	_echoTradingTableItem(_getTradingColor($i), '买'._getTradingIndex($i), $ref->GetBidPrice($i), $strQuantity, $ref, $fEstPrice, $fEstPrice2, $fEstPrice3, $callback);
    }

	_echoTradingTableItem($strColor, '跌停', number_format($fPrice * 0.9, $iPrecision, '.', ''), '', $ref, $fEstPrice, $fEstPrice2, $fEstPrice3, $callback);
}

function _checkTradingQuantity($ref)
{
    for ($i = 0; $i < TRADING_QUOTE_NUM; $i ++)
    {
    	if ($strQuantity = $ref->GetAskQuantity($i))
    	{
    		if ($strQuantity != '0')	return false;
    	}

    	if ($strQuantity = $ref->GetBidQuantity($i))
    	{
    		if ($strQuantity != '0')	return false;
    	}
    }
    return true;
}

function _echoTradingParagraph($str, $arColumn, $ref, $fEstPrice = false, $fEstPrice2 = false, $fEstPrice3 = false, $callback = false)
{
	if (_checkTradingQuantity($ref))	return;

	EchoTableParagraphBegin($arColumn, 'trading', $str);
    _echoTradingTableData($ref, $fEstPrice, $fEstPrice2, $fEstPrice3, $callback);
    EchoTableParagraphEnd();
}

function _getTradingParagraphStr($ref, $arColumn)
{
    $strSymbol = GetXueqiuLink($ref);
	$strPrice = $arColumn[1]->GetDisplay();
	$str = "{$strSymbol}当前5档交易{$strPrice}";
    return $str;
}

function EchoFundTradingParagraph($fund, $callback = false)
{
   	$ref = GetStockRef($fund);
   	
    $arColumn = _getTradingTableColumn();
    $strPrice = _getTradingParagraphStr($ref, $arColumn);
	$fRealtime = false;
    if ($fOfficial = $fund->GetOfficialNetValue())
    {
    	$arColumn[] = new TableColumnPremium(STOCK_DISP_OFFICIAL);
    	$strPrev = $ref->GetPrevPrice();
    	$strEstPrice = $ref->GetPriceDisplay($fOfficial, floatval($strPrev));

    	if ($fFair = $fund->GetFairNetValue())
    	{
    		$arColumn[] = new TableColumnPremium(STOCK_DISP_FAIR);
    		$strEstPrice .= '|'.$ref->GetPriceDisplay($fFair, floatval($strPrev));
    	}

   		if ($fRealtime = $fund->GetRealtimeNetValue())
   		{
   			$arColumn[] = new TableColumnPremium(STOCK_DISP_REALTIME);
   			$strEstPrice .= '|'.$ref->GetPriceDisplay($fRealtime, floatval($strPrev));
    	}

    	$strEst = TableColumnGetEst();
    	$strPremium = TableColumnGetPremium();
    	$str = "{$strPrice}相对于{$strEst}{$strEstPrice}的{$strPremium}";
    }
    else
	{
		$str = $strPrice;
		$fFair = false;
	}
    if ($callback)
    {
    	$strText = call_user_func($callback);
    	$strNoTag = strip_tags($strText);
//    	DebugString(__FUNCTION__.': '.$strNoTag, true);
		$iLen = strlen($strNoTag)*11;
		if (!$_SESSION['mobile'])	$iLen = min($iLen, LayoutGetDisplayWidth() - TableColumnGetTotalWidth($arColumn));
    	$arColumn[] = new TableColumn($strText, $iLen);
    }
    
    $strSymbol = $ref->GetSymbol();
    if (in_arrayXopQdii($strSymbol))	$str .= ' '.GetRotationTradingLink($strSymbol);
	
    _echoTradingParagraph($str, $arColumn, $ref, $fOfficial, $fFair, $fRealtime, $callback); 
}

function EchoFundPairTradingParagraph($ref)
{
    $strPairSymbol = RefGetMyStockLink($ref->GetPairRef());

    $arColumn = _getTradingTableColumn();
    $col = new TableColumnPremium(STOCK_DISP_OFFICIAL);
    $arColumn[] = $col;
	$strPremium = $col->GetDisplay();

    $strPrice = _getTradingParagraphStr($ref, $arColumn);
    $str = "{$strPrice}相对于{$strPairSymbol}的{$strPremium}";
        
    _echoTradingParagraph($str, $arColumn, $ref, $ref->GetOfficialNetValue()); 
}

function EchoTradingParagraph($ref, $ah_ref = false, $adr_ref = false)
{
    $arColumn = _getTradingTableColumn();
    $str = _getTradingParagraphStr($ref, $arColumn);
    $fValH = false;
    $fValAdr = false;
    if ($ah_ref)
    {
    	$h_ref = $ah_ref->GetPairRef();
    	$str .= '相对于'.TableColumnGetStock($h_ref).'港币'.$h_ref->GetPriceDisplay();
    	$arColumn[] = new TableColumnStock($h_ref);
    	$fValH = $ah_ref->EstFromPair();
    	if ($adr_ref)
    	{
    		$str .= '和'.TableColumnGetStock($adr_ref).'美元'.$adr_ref->GetPriceDisplay();
    		$arColumn[] = new TableColumnStock($adr_ref);
    		$fValAdr = $ah_ref->EstFromPair($adr_ref->EstToPair());
    	}
    	$str .= '的'.TableColumnGetPremium();
    }
    _echoTradingParagraph($str, $arColumn, $ref, $fValH, $fValAdr); 
}

?>
