<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('php/_updateholdings.php');
require_once('../../php/ui/editinputform.php');
require_once('../../php/ui/netvaluehistoryparagraph.php');
require_once('../../php/tutorial/cramersrule.php');
require_once('../../php/tutorial/gaussianelimination.php');

function _echoCurrentHoldingsItem($ref)
{
	$ar = array();
	foreach ($ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
	{
		$ar[] = number_format(floatval($strRatio), 0);
	}
	$ar[] = number_format($ref->GetPosition(), 2);
	EchoMatchTableColumn($ar);
}

function __getHoldingRatioDisplay($fVal, $strRatio, &$bMatch)
{
	$str = number_format($fVal, 0);
	if ($str != number_format(floatval($strRatio), 0))	$bMatch = false;
	return str_starts_with($str, '-') ? GetFontElement($str) : $str;
}

function __getPosDisplay($ref, $fPos, $arRatio, &$bMatch, $bAdmin)
{
	$strPos = number_format($fPos, 2);
	if ($strPos != number_format($ref->GetPosition(), 2))	$bMatch = false;
	if ($fPos > 0.25 && $fPos < 1.25)
	{
		if ($bAdmin && $bMatch === false)
		{
			$arJson = array();
			foreach ($arRatio as $strHoldingId => $fRatio)
			{
				$strHolding = SqlGetStockSymbol($strHoldingId);
				$arJson[$strHolding] = number_format($fRatio * $fPos, 2);
			}
			$str = DebugEncode($arJson);
			return GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&holdings='.urlencode($str), '确认更新持仓'.$str.'和仓位'.$strPos.'？', $strPos);
		}
		return $strPos;
	}
	return GetFontElement($strPos);
}

function _echoQuarterHoldingsItem($ref, $strDate, $strClose, $arExtraHoldings, $bAdmin)
{
	$bMatch = false;
	$ar = array($strDate);
	$arRatio = array();
	if ($fPos = StockOptionDecodeHoldings($strClose, $arRatio))
	{
		$arExtraHoldings += $ref->GetHoldingsRatioArray();
		$bMatch = true;
		foreach ($arExtraHoldings as $strHoldingId => $strRatio)
		{
			if (isset($arRatio[$strHoldingId]))
			{
				$ar[] = __getHoldingRatioDisplay($arRatio[$strHoldingId], $strRatio, $bMatch);
			}
			else
			{
				$bMatch = false;
				$ar[] = '';
			}	
		}
		$ar[] = __getPosDisplay($ref, $fPos, $arRatio, $bMatch, $bAdmin);
	}

	EchoMatchTableColumn($ar, $bMatch);
}

function _echoExhaustiveHoldingsItem($ref, $iCount, $fInput, $strDate, $fNetValue, $strPrevDate, $bAdmin)
{
	static $arEq = array();

	$ar = array($strDate, $ref->GetNetValueDisplay($fNetValue));

	$strStockId = $ref->GetStockId();
	$net_sql = GetNetValueHistorySql();
	$strPrevNetValue = $net_sql->GetClose($strStockId, $strPrevDate);
	$ar[] = $ref->GetPercentageDisplay(floatval($strPrevNetValue), $fNetValue);

	$bMatch = false;
	$fPercent = $net_sql->GetProportion($strStockId, $strDate, $strPrevDate) - 1.0;			// f
	if (abs($fPercent) > $fInput && $arPro = $ref->GetProportionArray($strDate, $strPrevDate))
	{
		$ref->SetHoldingsDate($strPrevDate);
		$ref->SetNetValueString($strPrevNetValue);
		$ar[] = $ref->GetPercentageDisplay($fNetValue, $ref->_estNetValue($strDate));

		$cny_ref = $ref->GetCnyRef();
		$fCny = $net_sql->GetProportion($cny_ref->GetStockId(), $strDate, $strPrevDate);	// n
		$fEnd = end($arPro);
		// x + y = 1; ax + by - (f/n)z = 1/n 			 ==> (a - b)x -            (f/n)z = 1/n - b
		// x + y + z = 1; ax + by + cz - (f/n)w = 1/n	 ==> (a - c)x + (b - c)y - (f/n)w = 1/n - c
		$arLine = array();
		for ($i = 0; $i < $iCount - 1; $i ++)	$arLine[] = $arPro[$i] - $fEnd;
		$arLine[] = -$fPercent / $fCny;
		$arLine[] = 1.0 / $fCny - $fEnd;
		$arEq[] = $arLine;
		if (count($arEq) >= $iCount)
		{
			try
			{
				$arXY = SolveOverdetermined($arEq);
				$iIndex = 0;
				$bMatch = true;
				$arNewRatio = array();
				$fTotal = 0.0;
				foreach ($ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
				{
					if ($iIndex == $iCount - 1)
					{
						$fVal = 1.0 - $fTotal;
					}
					else
					{
						$fVal = $arXY[$iIndex];
						$fTotal += $fVal;
					}
					$fVal *= 100.0;
					$ar[] = __getHoldingRatioDisplay($fVal, $strRatio, $bMatch);
					$arNewRatio[$strHoldingId] = $fVal;
					$iIndex ++;
				}
				$ar[] = __getPosDisplay($ref, 1.0 / $arXY[$iIndex - 1], $arNewRatio, $bMatch, $bAdmin);
			}
			catch (Exception $e) 
			{
				DebugString($e->getFile().' '.$e->getLine().' '.$e->getMessage());	// $e->getTraceAsString()
			}
		}	
	}

	EchoMatchTableColumn($ar, $bMatch);
}

function _echoVerifyHoldingsItem($ref, $iCount, $fInput, $strDate, $fNetValue, $strPrevDate, $bAdmin)
{
	static $arEq = array();

	$strStockId = $ref->GetStockId();
	$net_sql = GetNetValueHistorySql();
	$ar = array($strDate, $ref->GetNetValueDisplay($fNetValue));
	$ar[] = $ref->GetPercentageDisplay(floatval($net_sql->GetClose($strStockId, $strPrevDate)), $fNetValue);

	$bReturn = false;
	$bMatch = false;
	$fPercent = $net_sql->GetProportion($strStockId, $strDate, $strPrevDate) - 1.0;
	if (abs($fPercent) > $fInput && $arPro = $ref->GetProportionArray($strDate, $strPrevDate))
	{
		$cny_ref = $ref->GetCnyRef();
		$fCny = $net_sql->GetProportion($cny_ref->GetStockId(), $strDate, $strPrevDate);	// n
		// x + y + z = 1; ax + by + cz = ((e - 1)/p + 1) / n
		$arPro[] = -$fPercent / $fCny;
		$arPro[] = 1.0 / $fCny;
		$arEq[] = $arPro;
		if (count($arEq) == $iCount)
		{
			$bReturn = true;
			$arLine = array();
			for ($i = 0; $i < $iCount; $i ++)	$arLine[] = 1.0;
			$arLine[] = 0.0;
			$arLine[] = 1.0;
			$arEq[] = $arLine;
			try
			{
				$arXY = CramersRule($arEq);
				$iIndex = 0;
				$bMatch = true;
				$arNewRatio = array();
				foreach ($ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
				{
					$fVal = $arXY[$iIndex] * 100.0;
					$ar[] = __getHoldingRatioDisplay($fVal, $strRatio, $bMatch);
					$arNewRatio[$strHoldingId] = $fVal;
					$iIndex ++;
				}
				$ar[] = __getPosDisplay($ref, 1.0 / $arXY[$iIndex], $arNewRatio, $bMatch, $bAdmin);
			}
			catch (Exception $e) 
			{
				DebugString(__FUNCTION__.' '.$e->getMessage());
			}
		}
	}

	EchoMatchTableColumn($ar, $bMatch);
	return $bReturn;
}

function _echoQuarterHoldingsData($ref, $quarter_sql, $arExtraHoldings, $bAdmin)
{
    if ($result = $quarter_sql->GetAll($ref->GetStockId())) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
			_echoQuarterHoldingsItem($ref, $record['date'], $record['close'], $arExtraHoldings, $bAdmin);
		}
        mysqli_free_result($result);
    }
}

function _echoExhaustiveHoldingsData($ref, $iCount, $fInput, $iNum, $bAdmin)
{
   	$strStockId = $ref->GetStockId();
	$net_sql = GetNetValueHistorySql();
	$arDate = $net_sql->GetSwitchDates($strStockId);
	if (count($arDate) == 0)		return;

 	$iIndex = 0;
	$iTotal = 0;
    if ($result = $net_sql->GetAll($strStockId)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $record['date'];
       		if ($strDate == $arDate[$iIndex])
       		{
   				$iIndex ++;
   				if (isset($arDate[$iIndex]))
				{
					_echoExhaustiveHoldingsItem($ref, $iCount, $fInput, $strDate, floatval($record['close']), $arDate[$iIndex], $bAdmin);
					$iTotal ++;
					if ($iTotal == $iNum)	break;
				}
   				else
   				{
   					break;
       			}
       		}
        }
        mysqli_free_result($result);
    }
}

function _echoVerifyHoldingsData($ref, $iCount, $fInput, $bAdmin)
{
   	$strStockId = $ref->GetStockId();
	$net_sql = GetNetValueHistorySql();
	$arDate = $net_sql->GetSwitchDates($strStockId);
	if (count($arDate) == 0)		return;

 	$iIndex = 0;
    if ($result = $net_sql->GetAll($strStockId)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $record['date'];
       		if ($strDate == $arDate[$iIndex])
       		{
   				$iIndex ++;
   				if (isset($arDate[$iIndex]))
				{
					if (_echoVerifyHoldingsItem($ref, $iCount, $fInput, $strDate, floatval($record['close']), $arDate[$iIndex], $bAdmin))
					{
						break;
					}
				}
   				else
   				{
   					break;
       			}
       		}
        }
        mysqli_free_result($result);
    }
}

function _getQuarterHoldingsExtra($ref, $quarter_sql)
{
	$arExtra = array();
    if ($result = $quarter_sql->GetAll($ref->GetStockId())) 
    {
		$arHoldingsRatio = $ref->GetHoldingsRatioArray();
        while ($record = mysqli_fetch_assoc($result)) 
        {
			$arRatio = array();
			if (StockOptionDecodeHoldings($record['close'], $arRatio))
			{
				$arExtra += array_diff_key($arRatio, $arHoldingsRatio + $arExtra);
			}
		}
        mysqli_free_result($result);
    }
	return $arExtra;
}

function _echoExhaustiveHoldingsParagraph($strPage, $strSymbol, $fInput, $iNum, $bAdmin)
{
	$ref = new HoldingsReference($strSymbol);
    if ($ref->GetHoldingsDate())
    {
		$ar = array();
    	$arHoldingRef = $ref->GetHoldingsRefArray();
		foreach ($arHoldingRef as $holding_ref)	$ar[] = new TableColumnStock($holding_ref);
		$ar[] = new TableColumnPosition();

		if (EchoTableParagraphBegin($ar, 'current', '当前使用的持仓数据'))
		{
    		_echoCurrentHoldingsItem($ref);
			EchoTableParagraphEnd();
		}

		$quarter_sql = new QuarterReportSql();
		$arExtraHoldings = _getQuarterHoldingsExtra($ref, $quarter_sql);
		$arExtraColumn = array();
		foreach ($arExtraHoldings as $strExtraId => $strExtraData)
		{
			$arExtraColumn[] = new TableColumn(SqlGetStockSymbol($strExtraId));
		}	

		if (EchoTableParagraphBegin(array_merge(array(new TableColumnDate()), $arExtraColumn, $ar), 'quarter', '季报持仓'))
		{
    		_echoQuarterHoldingsData($ref, $quarter_sql, $arExtraHoldings, $bAdmin);
			EchoTableParagraphEnd();
		}

		$iCount = count($arHoldingRef);
    	if ($iCount <= 4)
    	{
			if (EchoTableParagraphBegin(array_merge(GetNetValueTableColumn(), array(new TableColumnError()), $ar), $strPage, GetFundLinks($strSymbol)))
			{
	    		_echoExhaustiveHoldingsData($ref, $iCount, $fInput, $iNum, $bAdmin);
				EchoTableParagraphEnd();
			}

			if (EchoTableParagraphBegin(array_merge(GetNetValueTableColumn(), $ar), 'verify', '验证最激进数据'))
			{
	    		_echoVerifyHoldingsData($ref, $iCount, $fInput, $bAdmin);
				EchoTableParagraphEnd();
			}
		}
	}
}

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
    	if (($strInput = GetEditInput()) === false)		$strInput = strval(HOLDINGS_NETVALUE_DIFF);
    	EchoEditInputForm('进行'.EXHAUSTIVE_HOLDINGS_DISPLAY.'计算的'.TableColumnGetNetValue().'涨跌%阈值', $strInput);
    	if ($strInput != '')
    	{
			_echoExhaustiveHoldingsParagraph($acct->GetPage(), $ref->GetSymbol(), floatval($strInput) / 100.0, $acct->GetNum(), $acct->IsAdmin());
		}
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().EXHAUSTIVE_HOLDINGS_DISPLAY;
    $str .= '。仅用于不超过4个持仓用来'.STOCK_DISP_EST.'的QDII基金，通过穷举求解超定线性方程组来计算这些持仓最可能的实际比例以及总体'.STOCK_DISP_POSITION.'。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetSymbolDisplay().EXHAUSTIVE_HOLDINGS_DISPLAY;
}

    $acct = new SymbolAccount();

require('../../php/ui/_dispcn.php');
?>
