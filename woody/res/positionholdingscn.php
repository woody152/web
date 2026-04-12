<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');
require_once('../../php/ui/netvaluehistoryparagraph.php');
require_once('../../php/tutorial/gaussianelimination.php');

function _echoPositionHoldingsItem($ref, $iCount, $fInput, $strDate, $fNetValue, $strPrevDate, $bAdmin)
{
	static $arEq = array();

	$strStockId = $ref->GetStockId();
	$net_sql = GetNetValueHistorySql();
	$ar = array($strDate, $ref->GetNetValueDisplay($fNetValue));
	$ar[] = $ref->GetPercentageDisplay(floatval($net_sql->GetClose($strStockId, $strPrevDate)), $fNetValue);

	$bMatch = false;
	$fPercent = $net_sql->GetProportion($strStockId, $strDate, $strPrevDate) - 1.0;			// f
	if (abs($fPercent) > $fInput && $arPro = $ref->GetProportionArray($strDate, $strPrevDate))
	{
		$cny_ref = $ref->GetCnyRef();
		$fCny = $net_sql->GetProportion($cny_ref->GetStockId(), $strDate, $strPrevDate);	// n
		$fEnd = end($arPro);
		// x + y = 1; ax + by - (f/n)z = 1/n 			 ==> (a - b)x -            (f/n)z = 1/n - b
		// x + y + z = 1; ax + by + cz - (f/n)w = 1/n	 ==> (a - c)x + (b - c)y - (f/n)w = 1/n - c
		$arLine = array();
		for ($i = 0; $i < $iCount - 1; $i ++)	$arLine[] = $arPro[$i] - $fEnd;
		$arLine[] = -$fPercent / $fCny;
		$arLine[] = 1.0/$fCny - $fEnd;
		$arEq[] = $arLine;
		if (count($arEq) >= $iCount)
		{
			try
			{
				$arXY = SolveOverdetermined($arEq);
				$iIndex = 0;
				$bMatch = true;
				$bNegative = false;
				$arDisplay = array();
				$arJson = array();
				$arHoldingsRatio = $ref->GetHoldingsRatioArray();
				$fTotal = 0.0;
				foreach ($arHoldingsRatio as $strHoldingId => $strRatio)
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
					if ($fVal < 0.0)
					{
						$bNegative = true;
						$bMatch = false;
						break;
					}	 
					$str = number_format($fVal * 100.0, 0);
					if ($str != $strRatio)	$bMatch = false;
					$arDisplay[] = $str;
					$strHolding = SqlGetStockSymbol($strHoldingId);
					$arJson[$strHolding] = $str;
					$iIndex ++;
				}
				if ($bNegative === false)
				{
					$strPos = number_format(1.0 / $arXY[$iIndex - 1], 2);
					if ($strPos != strval($ref->GetPosition()))	$bMatch = false;
					$arDisplay[] = $strPos;
					if ($bAdmin && $bMatch === false)
					{
						$str = DebugEncode($arJson);
						$arDisplay[0] = GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&holdings='.urlencode($str).'&fundposition='.$strPos, '确认更新持仓'.$str.'和仓位'.$strPos.'？', $arDisplay[0]);
					}
					$ar = array_merge($ar, $arDisplay);
				}
			}
			catch (Exception $e) 
			{
				DebugString($e->getFile().' '.$e->getLine().' '.$e->getMessage());	// $e->getTraceAsString()
			}
		}	
	}

	EchoMatchTableColumn($ar, $bMatch);
}

function _echoPositionHoldingsData($ref, $iCount, $fInput, $iNum, $bAdmin)
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
					_echoPositionHoldingsItem($ref, $iCount, $fInput, $strDate, floatval($record['close']), $arDate[$iIndex], $bAdmin);
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

function _echoPositionHoldingsParagraph($strPage, $strSymbol, $fInput, $iNum, $bAdmin)
{
	$ref = new HoldingsReference($strSymbol);
    if ($ref->GetHoldingsDate())
    {
    	$arHoldingRef = $ref->GetHoldingsRefArray();
		$ar = GetNetValueTableColumn();
		foreach ($arHoldingRef as $holding_ref)	$ar[] = new TableColumnStock($holding_ref);
		$ar[] = new TableColumnPosition();

   		EchoTableParagraphBegin($ar, $strPage, GetFundLinks($strSymbol));
   		_echoPositionHoldingsData($ref, count($arHoldingRef), $fInput, $iNum, $bAdmin);
		EchoTableParagraphEnd();
	}
}

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
    	if (($strInput = GetEditInput()) === false)		$strInput = '0.99';	// strval(NETVALUE_DIFF);
    	EchoEditInputForm('进行'.POSITION_HOLDINGS_DISPLAY.'计算的'.TableColumnGetNetValue().'涨跌%阈值', $strInput);
    	if ($strInput != '')
    	{
			_echoPositionHoldingsParagraph($acct->GetPage(), $ref->GetSymbol(), floatval($strInput) / 100.0, $acct->GetNum(), $acct->IsAdmin());
		}
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().POSITION_HOLDINGS_DISPLAY;
    $str .= '。仅用于只有2个持仓用来'.STOCK_DISP_EST.'的美股QDII基金，使用解多元一次方程组的方法来计算这些持仓最可能的实际比例以及总体'.STOCK_DISP_POSITION.'。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetSymbolDisplay().POSITION_HOLDINGS_DISPLAY;
}

    $acct = new SymbolAccount();

require('../../php/ui/_dispcn.php');
?>
