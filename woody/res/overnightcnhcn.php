<?php
require_once('php/_stock.php');
require_once('../../php/ui/editinputform.php');

function _buildHedgeString($fQuantity, $strSymbol)
{
	return strval($fQuantity).'股'.GetMyStockLink($strSymbol);
}

function _futureCnhInput($ref, $future_ref, $strInput, $fMultiple)
{
	$strSymbol = $ref->GetSymbol();
	$cny_ref = ($strSymbol != 'SZ161226') ? $ref->GetCnyRef() : new CnyReference('USCNY');
	$fCnh = $future_ref->GetVal() * $cny_ref->GetVal();
	if ($fRealtime = $ref->GetRealtimeNetValue())
	{
		$fPremium = $ref->GetVal() / $fRealtime;
//		DebugVal($fPremium, $strSymbol, true);
//		$fCnh *= $fPremium;
		$fCnh *= 1.0 + ($fPremium - 1.0) * $ref->GetPosition();
	}
	$fCnh *= $fMultiple * floatval(substr($strInput, 0, strlen($strInput) - 3));
	return $fCnh;
}

function _fundCnhInput($ref, $stock_ref, $strInput)
{
	$strSymbol = $ref->GetSymbol();
	$fFeeRatio = StockGetFundFeeRatio($strSymbol);

	$fPremium = floatval($stock_ref->GetAvailablePrice(false)) / $ref->GetOfficialNetValue();
//	DebugVal($fPremium, $strSymbol, true);

	$fCnh = floatval(substr($strInput, 0, strlen($strInput) - 1));
	$fCnh /= 1 + $fFeeRatio;
//	$fCnh *= 1.0 + ($fPremium - 1.0) * $ref->GetPosition();
	$fCnh *= $fPremium;
	$fCnh *= $ref->GetPosition();
	return $fCnh;
}

function _convertCnhInput($ref, $stock_ref, $strInput)
{
	if (str_ends_with($strInput, 'F'))
	{
		$fCnh = _fundCnhInput($ref, $stock_ref, $strInput);
	}
	else if (str_ends_with($strInput, 'MCL'))
	{
		$fCnh = _futureCnhInput($ref, new MyStockReference('hf_CL'), $strInput, 100.0);
	}
	else if (str_ends_with($strInput, 'MGC'))
	{
		$fCnh = _futureCnhInput($ref, new MyStockReference('hf_GC'), $strInput, 10.0);
	}
	else
	{
		$fCnh = floatval($strInput);
	}
	return abs($fCnh);
}

function _echoOverNightCnhItem($strSymbol, $strInput, $bSell)
{
	$ref = StockGetFundReference($strSymbol);
   	if (method_exists($ref, 'GetStockRef'))
   	{
   		$stock_ref = $ref->GetStockRef();
   		$est_ref = $ref->GetEstRef();
   	}
   	else
   	{
   		$stock_ref = $ref;
		$est_ref = false;
   	}
	$fCnh = _convertCnhInput($ref, $stock_ref, $strInput);
   	
	$ar = array();
	$ar[] = $ref->GetStockLink();
   	if ($strQuantity = $stock_ref->GetAvailableQuantity($bSell))
   	{
   		$strPrice = $stock_ref->GetAvailablePrice($bSell);
   		$ar[] = $strPrice;
   		$ar[] = $strQuantity;

		$strStockId = $ref->GetStockId();
   		$fPos = $ref->GetPosition();
		$fHintQuantity = ($fCnh / $fPos) / floatval($strPrice);
		// DebugVal($fHintQuantity, $strSymbol, true);
		$strHedge = '';
		$strMemo = '';

   		if ($est_ref)
   		{
			if ($fHedge = GetStockHedge($strSymbol, $strStockId))
			{
//				$fHedgeQuantity = floor($fHintQuantity / $fHedge);
				$fHedgeQuantity = round($fHintQuantity / $fHedge);
				$fHintQuantity = $fHedgeQuantity * $fHedge;
				$strHedge = number_format($fHedge);
				$strMemo = _buildHedgeString($fHedgeQuantity, ($strEtf = GetLeverageHedgeSymbol($strSymbol)) ? $strEtf : $est_ref->GetSymbol());
			}
   		}
   		else if (method_exists($ref, 'GetHoldingsDate'))
   		{
   			$strDate = $ref->GetHoldingsDate();
			$fCny = floatval($ref->GetNetValueString()) * $fHintQuantity * $fPos;
//			$fCny = floatval(SqlGetNetValueByDate($ref->GetStockId(), $strDate)) * $fHintQuantity * $fPos;
			$sql = GetStockSql();
			$his_sql = GetStockHistorySql();
			$cny_ref = $ref->GetCnyRef();
			$fUsd = $fCny / $cny_ref->GetVal($strDate);
			$fTotalQuantity = 0.0;
			foreach ($ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
			{
				if ($strClose = $his_sql->GetClose($strHoldingId, $strDate))
   				{
					$fHoldingQuantity = round($fUsd * floatval($strRatio) / 100.0 / floatval($strClose), 1);
					$fTotalQuantity += $fHoldingQuantity;
					$strMemo .= _buildHedgeString($fHoldingQuantity, $sql->GetStockSymbol($strHoldingId)).'、';
				}
			}
			$strMemo = rtrim($strMemo, '、');
			if ($strSymbol != 'SZ160216' && $strSymbol != 'SZ161815' && $strSymbol != 'SZ164701')	$strMemo .= '，共'.strval(round($fTotalQuantity)).'股。';
   		}
		$fHintQuantity = round($fHintQuantity / 100.0) * 100.0;
		$strHintQuantity = strval($fHintQuantity);
		if ($fHintQuantity > floatval($strQuantity))	$strHintQuantity = GetFontElement($strHintQuantity);
		$ar[] = $strHintQuantity;
		$ar[] = $strHedge;
		if ($strMemo != '')		$ar[] = ($bSell ? '买入' : '卖出').$strMemo;
   	}
	EchoTableColumn($ar);
}

// function _echoOverNightCnhParagraph($arSymbol, $fCnh)
function _echoOverNightCnhParagraph($strPage, $arSymbol, $strInput)
{
	$bSell = (substr($strInput, 0, 1) == '-') ? true : false;
//	$bSell = ($fCnh < 0.0) ? true : false;
//	$fCnh = abs($fCnh);
	$strPrefix = '可'.($bSell ? '卖' : '买');
	$strHint = '建议';
	
	$ar = array(new TableColumnSymbol(),
				new TableColumnPrice($strPrefix),
				new TableColumnQuantity($strPrefix),
				new TableColumnQuantity($strHint),
				new TableColumnHedge());
	$ar[] = new TableColumn($strHint.'对冲操作', TableColumnGetLastWidth($ar));
	EchoTableParagraphBegin($ar, $strPage);
	foreach ($arSymbol as $strSymbol)	_echoOverNightCnhItem($strSymbol, $strInput, $bSell);
	EchoTableParagraphEnd();
}               

function _copyFutureLink($strQuery, $strFuture)
{
	return ' '.CopyPhpLink($strQuery.'1'.$strFuture, $strFuture.'对冲');
}

function EchoAll()
{
	global $acct;
	
	if (($strInput = GetEditInput()) === false)
	{
		if (($strInput = $acct->GetQuery()) === false)		$strInput = '100000';
    }
	
	$str = '需要平衡的离岸人民币CNH';
	$strPage = $acct->GetPage();
	$strQuery = $strPage.'=';
	$str .= _copyFutureLink($strQuery, 'MCL');
	$str .= _copyFutureLink($strQuery, 'MGC');
	$str .= ' '.CopyPhpLink($strQuery.'100000F', '基金申购');
   	EchoEditInputForm($str, $strInput);
   	if ($strInput != '')
   	{
		$arSymbol = GetOverNightSymbolArray();
   		StockPrefetchArrayExtendedData($arSymbol);
   		_echoOverNightCnhParagraph($strPage, $arSymbol, $strInput);
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
    $str = GetTitle().'。自动计算为了平衡对冲汇率策略下，义工群覆盖的当前各个QDII基金应该买入或者卖出的数量。同时顺便显示对冲值等信息。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	return OVERNIGHT_CNH_DISPLAY;
}

	$acct = new StockAccount();

require('../../php/ui/_dispcn.php');
?>
