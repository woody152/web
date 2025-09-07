<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');

function _echoRotationTradingItem($rotation_ref, $iQuantity, $bRotationSell)
{
	$ar = array();
	
	$ar[] = $rotation_ref->GetStockLink();
	
    $stock_ref = GetStockRef($rotation_ref);
   	if ($strQuantity = $stock_ref->GetAvailableQuantity($bRotationSell))
   	{
   		$strPrice = $stock_ref->GetAvailablePrice($bRotationSell);
   		$ar[] = $strPrice;
   		$ar[] = $strQuantity;
   	}

	EchoTableColumn($ar);
}

function _echoRotationTradingParagraph($arRotationRef, $iQuantity, $bSell)
{
	$bRotationSell = $bSell ? false : true;
	$strPrefix = '可'.($bRotationSell ? '卖' : '买');
	$strHint = '建议';
	$ar = array(new TableColumnSymbol(), new TableColumnPrice($strPrefix), new TableColumnQuantity($strPrefix), new TableColumnQuantity($strHint));
	EchoTableParagraphBegin($ar, 'rotationtrading');
	foreach ($arRotationRef as $rotation_ref)	_echoRotationTradingItem($rotation_ref, $iQuantity, $bRotationSell);
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

   			if ($strInput = GetEditInput())		$iInput = intval($strInput);
   			else								$iInput = -1000000;
   			$bSell = ($iInput < 0) ? true : false;
			
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
   				EchoEditInputForm(GetXueqiuLink($ref).'目前可'.$strOp.'价格'.$strPrice.'、可'.$strOp.'数量'.$strQuantity.'，轮动数量：', strval($iInput));
   				_echoRotationTradingParagraph($arRotationRef, abs($iInput), $bSell);
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
