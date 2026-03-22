<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');

function _echoRotationTradingItem($rotation_ref, $fEstQuantity, $bRotationSell)
{
	$ar = array();
	
	$ar[] = $rotation_ref->GetStockLink();
	
    $stock_ref = GetStockRef($rotation_ref);
   	if ($strQuantity = $stock_ref->GetAvailableQuantity($bRotationSell))
   	{
   		$strPrice = $stock_ref->GetAvailablePrice($bRotationSell);
   		$ar[] = $strPrice;
   		$ar[] = $strQuantity;
		
		$fHedge = GetStockHedge($stock_ref->GetSymbol(), $stock_ref->GetStockId());
		$fHintQuantity = abs(round($fHedge * $fEstQuantity / 100.0) * 100.0);
		$strHintQuantity = strval($fHintQuantity);
		if ($fHintQuantity > floatval($strQuantity))	$strHintQuantity = GetFontElement($strHintQuantity);
		$ar[] = $strHintQuantity;
		$ar[] = number_format($fHedge);
		$ar[] = number_format($fEstQuantity);
   	}

	EchoTableColumn($ar);
}

function _echoRotationTradingParagraph($strPage, $arRotationRef, $fEstQuantity, $bSell)
{
	$bRotationSell = $bSell ? false : true;
	$strPrefix = '可'.($bRotationSell ? '卖' : '买');
	$strHint = '建议';
	$pair_ref = $arRotationRef[0]->GetEstRef();
	$ar = array(new TableColumnSymbol(),
				new TableColumnPrice($strPrefix),
				new TableColumnQuantity($strPrefix),
				new TableColumnQuantity($strHint),
				new TableColumnHedge(),
				new TableColumnQuantity($pair_ref->GetSymbol()));
	EchoTableParagraphBegin($ar, $strPage);
	foreach ($arRotationRef as $rotation_ref)	_echoRotationTradingItem($rotation_ref, $fEstQuantity, $bRotationSell);
	EchoTableParagraphEnd();
}

function _getRotationSymbolArray($strSymbol)
{
    if (in_arrayXopQdii($strSymbol))	return QdiiGetXopSymbolArray();
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
   				EchoEditInputForm(GetXueqiuLink($ref).'目前可'.$strOp.'价格'.$strPrice.'、可'.$strOp.'数量'.$strQuantity.'，轮动数量：', strval($fInput));
   				_echoRotationTradingParagraph($acct->GetPage(), $arRotationRef, $fEstQuantity, $bSell);
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
