<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');
require_once('../../php/ui/netvaluehistoryparagraph.php');
require_once('../../php/tutorial/cramersrule.php');

function _echoExhaustiveHoldingsItem($ref, $iCount, $fInput, $strDate, $fNetValue, $strPrevDate, $bAdmin)
{
	static $a = 1.0;
	static $b = 1.0;
	static $c = 1.0;
	static $d = 1.0;

	$strStockId = $ref->GetStockId();
	$net_sql = GetNetValueHistorySql();
	$ar = array($strDate, $ref->GetNetValueDisplay($fNetValue));
	$ar[] = $ref->GetPercentageDisplay(floatval($net_sql->GetClose($strStockId, $strPrevDate)), $fNetValue);

	$bMatch = false;
	$fPercent = $net_sql->GetProportion($strStockId, $strDate, $strPrevDate) - 1.0;
	if (abs($fPercent) > $fInput && $arPro = $ref->GetProportionArray($strDate, $strPrevDate))
	{
		$fPos = $ref->GetPosition();														// p
		$cny_ref = $ref->GetCnyRef();
		$fCny = $net_sql->GetProportion($cny_ref->GetStockId(), $strDate, $strPrevDate);	// n
		$fVal = ($fPercent / $fPos + 1) / $fCny;
		if ($iCount == 2)
		{	// x + y = 1; ax + by = ((e - 1)/p + 1) / n
			$arEq = [[1.0, 1.0, 1.0], [$arPro[0], $arPro[1], $fVal]];
		}
		else if ($iCount == 3)
		{	// x + y + z = 1; ax + by + cz = ((e - 1)/p + 1) / n
			$arEq = [[1.0, 1.0, 1.0, 1.0], [$arPro[0], $arPro[1], $arPro[2], $fVal], [$a, $b, $c, $d]];
			$a = $arPro[0];
			$b = $arPro[1];
			$c = $arPro[2];
			$d = $fVal;
		}
		try
		{
			$arXY = CramersRule($arEq);
			$iIndex = 0;
			$bMatch = true;
			$arDisplay = array();
			$arJson = array();
			foreach ($ref->GetHoldingsRatioArray() as $strHoldingId => $strRatio)
			{
				$str = number_format($arXY[$iIndex] * 100.0, 0);
				if ($str != $strRatio)	$bMatch = false;
				$arDisplay[] = $str;
				$strHolding = SqlGetStockSymbol($strHoldingId);
				$arJson[$strHolding] = $str;
				$iIndex ++;
			}
			if ($bAdmin && $bMatch === false)
			{
				$str = DebugEncode($arJson);
				$arDisplay[0] = GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&holdings='.urlencode($str), '确认更新持仓'.$str.'？', $arDisplay[0]);
			}
			$ar = array_merge($ar, $arDisplay);
		}
		catch (Exception $e) 
		{
//			DebugString(__FUNCTION__.' '.$e->getMessage());
		}
	}

	EchoMatchTableColumn($ar, $bMatch);
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

function _echoExhaustiveHoldingsParagraph($strPage, $strSymbol, $fInput, $iNum, $bAdmin)
{
	$ref = new HoldingsReference($strSymbol);
    if ($ref->GetHoldingsDate())
    {
    	$arHoldingRef = $ref->GetHoldingsRefArray();
    	$iCount = count($arHoldingRef);
    	if ($iCount <= 3)
    	{
			$ar = GetNetValueTableColumn();
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
			_echoExhaustiveHoldingsParagraph($acct->GetPage(), $ref->GetSymbol(), floatval($strInput) / 100.0, $acct->GetNum(), $acct->IsAdmin());
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
