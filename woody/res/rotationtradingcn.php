<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');

function _echoRotationTradingItem($rotation_ref, $fEstQuantity, $bRotationSell)
{
	$ar = array();
    $stock_ref = GetStockRef($rotation_ref);
	
	// $ar[] = $rotation_ref->GetStockLink();
	$strSymbol = $stock_ref->GetSymbol();
	$ar[] = CopyPhpLink('symbol='.$strSymbol, $strSymbol);
   	if ($strQuantity = $stock_ref->GetAvailableQuantity($bRotationSell))
   	{
   		$strPrice = $stock_ref->GetAvailablePrice($bRotationSell);
   		$ar[] = $strPrice;
   		$ar[] = $strQuantity;
		
		$fHedge = GetStockHedge($strSymbol, $stock_ref->GetStockId());
		$fHintQuantity = abs(round($fHedge * $fEstQuantity / 100.0) * 100.0);
		$strHintQuantity = strval($fHintQuantity);
		if ($fHintQuantity > floatval($strQuantity))	$strHintQuantity = GetFontElement($strHintQuantity);
		$ar[] = $strHintQuantity;
		$ar[] = number_format($fHedge);
		$ar[] = number_format($fEstQuantity);
		if ($fEst = $rotation_ref->GetEstNetValue())
		{
			$ar[] = $stock_ref->GetPercentageDisplay($fEst, floatval($strPrice));
		}
   	}

	EchoTableColumn($ar);
}

function _echoRotationTradingParagraph($strPage, $arRotationRef, $fEstQuantity, $strHedgeSymbol, $bSell)
{
	$bRotationSell = $bSell ? false : true;
	$strPrefix = '可'.($bRotationSell ? '卖' : '买');
	$strHint = '建议';
	$ar = array(new TableColumnSymbol(),
				new TableColumnPrice($strPrefix),
				new TableColumnQuantity($strPrefix),
				new TableColumnQuantity($strHint),
				new TableColumnHedge(),
				new TableColumnQuantity($strHedgeSymbol),
				new TableColumnPremium());
	
	if (EchoTableParagraphBegin($ar, $strPage))
	{
		foreach ($arRotationRef as $rotation_ref)	_echoRotationTradingItem($rotation_ref, $fEstQuantity, $bRotationSell);
		EchoTableParagraphEnd();
	}
}

function _getRotationSymbolArray($strSymbol)
{
    if (in_arrayXopQdii($strSymbol))		return QdiiGetXopSymbolArray();
	else if (in_arrayXbiQdii($strSymbol))	return QdiiGetXbiSymbolArray();
	else if (in_arrayQqqMatch($strSymbol))	return QdiiGetQqqMatchArray();
	else if (in_arraySpyMatch($strSymbol))	return QdiiGetSpyMatchArray();
    return false;
}

function EchoAll()
{
	global $acct;
	
	if ($strSymbol = $acct->StockCheckSymbol())
    {
    	if ($arSymbol = _getRotationSymbolArray($strSymbol))
    	{
    		StockPrefetchArrayExtendedData($arSymbol);
    		$arRotationRef = array();
    		foreach ($arSymbol as $str)
    		{
    			if ($str != $strSymbol)		$arRotationRef[] = StockGetFundReference($str);
    		}

    		$fund_ref = StockGetFundReference($strSymbol);
    		$ref = GetStockRef($fund_ref);
   			$acct->SetRef($ref);
   			$acct->EchoStockGroup();

   			if ($strInput = GetEditInput())		$fInput = floatval($strInput);
   			else								$fInput = -1000000.0;
   			$bSell = ($fInput < 0.0) ? true : false;
			$fEstQuantity = $fInput / GetStockHedge($strSymbol, $ref->GetStockId());

			$strHedgeSymbol = GetLeverageHedgeSymbol($strSymbol);
			if ($strHedgeSymbol === false)
			{
				$est_ref = $fund_ref->GetEstRef();
				$strHedgeSymbol = $est_ref->GetSymbol();
			}
			
   			if ($strQuantity = $ref->GetAvailableQuantity($bSell))
   			{
   				if ($bSell)
   				{
   					$strPrice = $ref->GetBidPrice();
   					$strOp = '卖';
   				}
   				else
   				{
   					$strPrice = $ref->GetAskPrice();
   					$strOp = '买';
   				}

				if ($ref->IsShangHaiEtf())		$str = GetShangHaiEtfListLink($ref, false);
				else if ($ref->IsShenZhenEtf())	$str = GetShenZhenEtfListLink($ref, false);
				else							$str = GetXueqiuLink($ref);

				$str .= '目前可'.$strOp.'价格'.$strPrice;
				if ($fEst = $fund_ref->GetEstNetValue())
				{
					$str .= TableColumnGetPremium();
					$str .= $ref->GetPercentageDisplay($fEst, floatval($strPrice));
				}
				$str .= '、可'.$strOp.'数量'.$strQuantity.'，轮动数量：';
   				EchoEditInputForm($str, strval($fInput));
   				_echoRotationTradingParagraph($acct->GetPage(), $arRotationRef, $fEstQuantity, $strHedgeSymbol, $bSell);
   			}
   		}
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().ROTATION_TRADING_DISPLAY;
    $str .= '。计算把现有基金换成持有同样标的其它基金时对应的价格、折价溢价、数量和对冲值等数据，以及可以实际成交的数量和价格。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetSymbolDisplay().ROTATION_TRADING_DISPLAY;
}

    $acct = new SymbolAccount();

require('../../php/ui/_dispcn.php');
?>
