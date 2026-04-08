<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/editinputform.php');
require_once('../../php/tutorial/math.php');

function _echoPositionHoldingsItem($ref, $iCount, $fPercent, $strDate, $strNetValue, $strPrevDate, $bAdmin)
{
	static $a = 1.0;
	static $b = 1.0;
	static $c = 0.0;
	static $d = 1.0;

	$ar = array($strDate);
	
	$fNetValue = floatval($strNetValue);
	$ar[] = $ref->GetNetValueDisplay($fNetValue);
	
	$fPrev = $ref->GetNetValue($strPrevDate);
	$ar[] = $ref->GetPercentageDisplay($fPrev, $fNetValue);

	$arRatio = $ref->GetHoldingsRatioArray();
	$bMatch = false;
	$his_sql = GetStockHistorySql();
	if ($arHolding = $his_sql->GetDailyCloseProportionArray($arRatio, $strDate, $strPrevDate))
	{
		$fPercent /= 100.0;
		//$fPos = $ref->GetPosition();
		$cny_ref = $ref->GetCnyRef();
		$fCny = $cny_ref->GetVal($strDate) / $cny_ref->GetVal($strPrevDate);
		//$fVal = ($fPercent / $fPos + 1) / $fCny;
		if ($iCount == 2)
		{
			$a1 = $arHolding[0];
			$b1 = $arHolding[1];
			$c1 = -$fPercent/$fCny;
			$d1 = 1.0/$fCny;
			$arXY = CramersRule3(1.0, 1.0, 0.0, 1.0, $a1, $b1, $c1, $d1, $a, $b, $c, $d);
			$a = $a1;
			$b = $b1;
			$c = $c1;
			$d = $d1;
		}
		if ($arXY['status'] == 'unique')
		{
			$strHoldings = '';
			$iIndex = 0;
			$bMatch = true;
			$arDisplay = array();
			foreach ($arRatio as $strHoldingId => $strRatio)
			{
				$str = number_format($arXY['solution'][$iIndex] * 100.0, 0);
				if ($str != $strRatio)	$bMatch = false;
				$arDisplay[] = $str;
				$strHoldings .= SqlGetStockSymbol($strHoldingId).'*'.$str.';';
				$iIndex ++;
			}
			$arDisplay[] = number_format(1.0 / $arXY['solution'][$iIndex], 2);
			if ($bAdmin && $bMatch === false)
			{
				$strHoldings = rtrim($strHoldings, ';');
				$arDisplay[0] = GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&holdings='.$strHoldings, '确认更新持仓'.$strHoldings.'？', $arDisplay[0]);
			}
			$ar = array_merge($ar, $arDisplay);
		}
		//else	DebugString(__FUNCTION__.$arXY['message']);
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
					$strPrevDate = $arDate[$iIndex];
					$fPercent = $ref->GetNetValuePercent($strDate, $strPrevDate);
					if (abs($fPercent) > $fInput)
					{
						_echoPositionHoldingsItem($ref, $iCount, $fPercent, $strDate, $record['close'], $strPrevDate, $bAdmin);
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

function _echoPositionHoldingsParagraph($strPage, $strSymbol, $fInput, $iNum, $bAdmin)
{
	$ref = new HoldingsReference($strSymbol);
    if ($ref->GetHoldingsDate())
    {
    	$arHoldingRef = $ref->GetHoldingsRefArray();
    	$iCount = count($arHoldingRef);
    	if ($iCount <= 2)
    	{
			$ar = array(new TableColumnDate(), new TableColumnNetValue(), new TableColumnChange());
			foreach ($arHoldingRef as $holding_ref)	$ar[] = new TableColumnStock($holding_ref);
			$ar[] = new TableColumnPosition();
    		EchoTableParagraphBegin($ar, $strPage, GetFundLinks($strSymbol));
    		_echoPositionHoldingsData($ref, $iCount, $fInput, $iNum, $bAdmin);
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
    	EchoEditInputForm('进行'.POSITION_HOLDINGS_DISPLAY.'计算的'.TableColumnGetNetValue().'涨跌%阈值', $strInput);
    	if ($strInput != '')
    	{
			_echoPositionHoldingsParagraph($acct->GetPage(), $ref->GetSymbol(), floatval($strInput), $acct->GetNum(), $acct->IsAdmin());
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
