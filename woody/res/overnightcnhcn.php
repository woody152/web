<?php
require_once('php/_stock.php');
require_once('../../php/ui/editinputform.php');

function _getAvailableQuantity($stock_ref, $bSell)
{
	if ($bSell)
	{
		if ($strQuantity = $stock_ref->GetBidQuantity())	return $strQuantity;
	}
	else
	{
		if ($strQuantity = $stock_ref->GetAskQuantity())	return $strQuantity;
	}
	return false;
}

function _buildHedgeString($fQuantity, $strSymbol)
{
	return strval($fQuantity).'股'.GetMyStockLink($strSymbol);
}

function _echoOverNightCnhItem($strSymbol, $fCnh, $bSell)
{
	$ref = StockGetFundReference($strSymbol);
	$ar = array();
	
	$ar[] = $ref->GetStockLink();
   	if (method_exists($ref, 'GetStockRef'))
   	{
   		$stock_ref = $ref->GetStockRef();
   		$est_ref = $ref->GetEstRef();
   	}
   	else
   	{
   		$stock_ref = $ref;
   		if ($strSymbol == 'SZ164906')	$est_ref = new MyStockReference('KWEB');
   		else							$est_ref = false;
   	}
   	
   	if ($strQuantity = _getAvailableQuantity($stock_ref, $bSell))
   	{
   		$strPrice = $bSell ? $stock_ref->GetBidPrice() : $stock_ref->GetAskPrice();
   		$ar[] = $strPrice;
   		$ar[] = $strQuantity;

		$strStockId = $ref->GetStockId();
   		$fPos = $ref->GetPosition();
		$fHintQuantity = ($fCnh / $fPos) / floatval($strPrice);
		$strHedge = '';
		$strMemo = '';

   		if ($est_ref)
   		{
			$cal_sql = GetCalibrationSql();
			if ($record = $cal_sql->GetRecordNow($strStockId))
			{
				$fCal = floatval($record['close']);
				if ($strEtf = GetLeverageHedgeSymbol($strSymbol))
				{
					$strEtfId = SqlGetStockId($strEtf);
					if ($strFactor = $cal_sql->GetClose($strEtfId, $record['date']))
					{
						$pos_sql = GetPositionSql();
						$fHedge = StockCalcLeverageHedge($fCal, $fPos, floatval($strFactor), $pos_sql->ReadVal($strEtfId));
					}
					else
					{
						$fHedge = false;
					}
				}
				else
				{
					$fHedge = StockCalcHedge($fCal, $fPos);
					$strEtf = $est_ref->GetSymbol();
				}
				if ($fHedge)
				{
					$fHedgeQuantity = floor($fHintQuantity / $fHedge);
					$fHintQuantity = $fHedgeQuantity * $fHedge;
					$strHedge = strval($fHedge);
					$strMemo = _buildHedgeString($fHedgeQuantity, $strEtf);
				}
			}
   		}
   		else
   		{
   			$net_sql = GetNetValueHistorySql();
   			if ($record = $net_sql->GetRecordNow($strStockId))
   			{
   				$strDate = $record['date'];
   				$fCny = floatval($record['close']) * $fHintQuantity * $fPos;
   				
   				$sql = GetStockSql();
   				$his_sql = GetStockHistorySql();
   				$cny_ref = $ref->GetCnyRef();
   				$fUsd = $fCny / $cny_ref->GetVal($strDate);
   				$iTotalQuantity = 0;
   				foreach ($ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
   				{
   					$fHoldingQuantity = round($fUsd * floatval($strRatio) / 100.0 / floatval($his_sql->GetClose($strHoldingId, $strDate)));
   					$iTotalQuantity += intval($fHoldingQuantity);
   					$strMemo .= _buildHedgeString($fHoldingQuantity, $sql->GetStockSymbol($strHoldingId)).'、';
   				}
   				$strMemo = rtrim($strMemo, '、');
   				if ($strSymbol != 'SZ164701')	$strMemo .= '，共'.strval($iTotalQuantity).'股。';
   			}
   		}
		$fHintQuantity = round($fHintQuantity / 100.0) * 100.0;
		$strHintQuantity = strval($fHintQuantity);
		if ($fHintQuantity > floatval($strQuantity))	$strHintQuantity = GetFontElement($strHintQuantity);
		$ar[] = $strHintQuantity;
		$ar[] = $strHedge;
		$ar[] = ($bSell ? '买入' : '卖出').$strMemo;
   	}
	EchoTableColumn($ar);
}

function _echoOverNightCnhParagraph($arSymbol, $fCnh)
{
	$bSell = ($fCnh < 0.0) ? true : false;
	$fCnh = abs($fCnh);
	$strPrefix = '可'.($bSell ? '卖' : '买');
	$strHint = '建议';
	
	$ar = array(new TableColumnSymbol(), new TableColumnPrice($strPrefix), new TableColumnQuantity($strPrefix), new TableColumnQuantity($strHint), new TableColumnHedge());
	$ar[] = new TableColumn($strHint.'对冲操作', TableColumnGetLastWidth($ar));
	EchoTableParagraphBegin($ar, 'overnightcnh');
	foreach ($arSymbol as $strSymbol)	_echoOverNightCnhItem($strSymbol, $fCnh, $bSell);
	EchoTableParagraphEnd();
}               

function EchoAll()
{
	global $acct;
	
	if (($strInput = GetEditInput()) === false)
	{
		if (($strInput = $acct->GetQuery()) === false)		$strInput = '100000';
    }
   	EchoEditInputForm('需要平衡的离岸人民币CNH', $strInput);
   	if ($strInput != '')
   	{
		$arSymbol = GetOverNightSymbolArray();
   		StockPrefetchArrayExtendedData($arSymbol);
   		_echoOverNightCnhParagraph($arSymbol, floatval($strInput));
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
