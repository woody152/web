<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');
require_once('../../php/tutorial/math.php');

function __getHoldingsArray($arRatio, $strDate, $strPrevDate)
{
	$ar = array();
	foreach ($arRatio as $strHoldingId => $strRatio)
	{
		if ($str = SqlGetHistoryByDate($strHoldingId, $strDate))
		{
			if ($strPrev = SqlGetHistoryByDate($strHoldingId, $strPrevDate))
			{
				$ar[] = floatval($str) / floatval($strPrev);
			}
			else
			{
			 	return false;
			}
		}
		else
		{
		 	return false;
		}
	}
	// DebugPrint($ar);
	return $ar;
}

function _echoExhaustiveHoldingsItem($ref, $iCount, $fPercent, $strDate, $strNetValue, $strPrevDate, $bAdmin)
{
	static $a = 1.0;
	static $b = 1.0;
	static $c = 1.0;
	static $d = 1.0;

	$ar = array($strDate);
	
	$fNetValue = floatval($strNetValue);
	$ar[] = $ref->GetNetValueDisplay($fNetValue);
	
	$fPrev = $ref->GetNetValue($strPrevDate);
	$ar[] = $ref->GetPercentageDisplay($fPrev, $fNetValue);

	$arRatio = $ref->GetHoldingsRatioArray();
	$bMatch = false;
	if ($arHolding = __getHoldingsArray($arRatio, $strDate, $strPrevDate))
	{
		$fPercent /= 100.0;
		$fPos = $ref->GetPosition();
		$cny_ref = $ref->GetCnyRef();
		$fCny = $cny_ref->GetVal($strDate) / $cny_ref->GetVal($strPrevDate);
		$fVal = ($fPercent / $fPos + 1) / $fCny;
		if ($iCount == 2)
		{
			$arXY = CramersRule(1.0, 1.0, 1.0, $arHolding[0], $arHolding[1], $fVal);
		}
		else if ($iCount == 3)
		{
			$arXY = CramersRule3(1.0, 1.0, 1.0, 1.0, $arHolding[0], $arHolding[1], $arHolding[2], $fVal, $a, $b, $c, $d);
			$a = $arHolding[0];
			$b = $arHolding[1];
			$c = $arHolding[2];
			$d = $fVal;
		}
		if ($arXY['status'] == 'unique')
		{
			$strHoldings = '';
			$iIndex = 0;
			$bMatch = true;
			$arDisplay = array();
			foreach ($arRatio as $strHoldingId => $strRatio)
			{
				$str = number_format($arXY['solution'][$iIndex] * 100.0, 2);
				if ($str != $strRatio)	$bMatch = false;
				$arDisplay[] = $str;
				$strHoldings .= SqlGetStockSymbol($strHoldingId).'*'.$str.';';
				$iIndex ++;
			}
			if ($bAdmin && $bMatch === false)
			{
				$strHoldings = rtrim($strHoldings, ';');
				$arDisplay[0] = GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&holdings='.$strHoldings, '确认更新持仓'.$strHoldings.'？', $arDisplay[0]);
			}
			$ar = array_merge($ar, $arDisplay);
		}
		//else	DebugString(__FUNCTION__.$arXY['message']);
	}

	EchoTableColumn($ar, $bMatch ? 'yellow' : false);
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
					$strPrevDate = $arDate[$iIndex];
					$fPercent = $ref->GetNetValuePercent($strDate, $strPrevDate);
					if (abs($fPercent) > $fInput)
					{
						_echoExhaustiveHoldingsItem($ref, $iCount, $fPercent, $strDate, $record['close'], $strPrevDate, $bAdmin);
						$iTotal ++;
						if ($iTotal == $iNum)	break;
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

function _echoExhaustiveHoldingsParagraph($strPage, $strSymbol, $fInput, $iNum, $bAdmin)
{
	$ref = new HoldingsReference($strSymbol);
    if ($ref->GetHoldingsDate())
    {
    	$arHoldingRef = $ref->GetHoldingsRefArray();
    	$iCount = count($arHoldingRef);
    	if ($iCount <= 3)
    	{
			$ar = array();
			$ar[] = new TableColumnDate();
			$ar[] = new TableColumnNetValue();
			$ar[] = new TableColumnChange();
			foreach ($arHoldingRef as $holding_ref)	$ar[] = new TableColumnStock($holding_ref);
    		EchoTableParagraphBegin($ar, $strPage, GetFundLinks($strSymbol));
    		_echoExhaustiveHoldingsData($ref, $iCount, $fInput, $iNum, $bAdmin);
			EchoTableParagraphEnd();
		}
	}
}

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
    	if (($strInput = GetEditInput()) === false)		$strInput = strval(NETVALUE_DIFF);
    	EchoEditInputForm('进行'.EXHAUSTIVE_HOLDINGS_DISPLAY.'计算的'.TableColumnGetNetValue().'涨跌%阈值', $strInput);
    	if ($strInput != '')
    	{
			_echoExhaustiveHoldingsParagraph($acct->GetPage(), $ref->GetSymbol(), floatval($strInput), $acct->GetNum(), $acct->IsAdmin());
		}
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().EXHAUSTIVE_HOLDINGS_DISPLAY;
    $str .= '。仅用于只有2到3个持仓用来'.STOCK_DISP_EST.'的美股QDII基金，使用穷举法来计算这些持仓最可能的实际比例。';
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
