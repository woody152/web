<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');

function _echoExhaustiveHoldingsItem($ref, $i, $iTotal, $strStockId1, $strStockId2, $strDate, $strNav, $bAdmin)
{
	$str1 = strval($iTotal - $i);
	$ref->arHoldingsRatio[$strStockId1] = $str1;
	
	$str2 = strval($i);
	$ref->arHoldingsRatio[$strStockId2] = $str2;
	
	$fEst = $ref->_estNav($strDate);
	$strEst = strval($fEst);
	if ($bAdmin && $ref->GetPercentageString($strNav, $strEst) == '0')
	{
		$strHoldings = SqlGetStockSymbol($strStockId1).'*'.$str1.';'.SqlGetStockSymbol($strStockId2).'*'.$str2;
		$str1 = GetOnClickLink(PATH_STOCK.'submitholdings.php?symbol='.$ref->GetSymbol().'&date='.$strDate.'&holdings='.$strHoldings, '确认更新持仓：'.$strDate.' '.$strHoldings.'？', $str1);
	}

	if (abs($ref->GetPercentage($strNav, $strEst)) < 0.2)
	{
		$ar = array($str1, $str2);
		$ar[] = strval_round($fEst, 3);
		$ar[] = $ref->GetPercentageDisplay($strNav, $strEst);
		EchoTableColumn($ar);
	}
}

function EchoAll()
{
	global $acct;
	
	$bAdmin = $acct->IsAdmin();
    if ($ref = $acct->EchoStockGroup())
    {
   		$strSymbol = $ref->GetSymbol();
   		$ref = new HoldingsReference($strSymbol);
    	if ($strDate = $ref->GetHoldingsDate())
    	{
    		$arHoldingRef = $ref->GetHoldingRefArray();
    		if (count($arHoldingRef) == 2)
    		{
    			$strNav = $ref->strNav;
    			$str = $strDate.' '.$strNav;
    			$nav_sql = GetNavHistorySql();
    			if ($record = $nav_sql->GetRecordPrev($ref->GetStockId(), $strDate))
    			{
    				$ref->strHoldingsDate = $record['date'];
    				$ref->strNav = rtrim0($record['close']);
    				$str .= ' <== '.$ref->strHoldingsDate.' '.$ref->strNav;
    			
    				$ref1 = $arHoldingRef[0];
    				$ref2 = $arHoldingRef[1];
    				$strStockId1 = $ref1->GetStockId();
    				$strStockId2 = $ref2->GetStockId();
    				$arRatio = $ref->GetHoldingsRatioArray();
    				$iTotal = intval(round(floatval($arRatio[$strStockId1]) + floatval($arRatio[$strStockId2])));

    				EchoTableParagraphBegin(array(new TableColumnStock($ref1),
										   new TableColumnStock($ref2),
										   new TableColumnEst(),
										   new TableColumnError()
										   ), 'exhaustiveholdings', $str);
					for ($i = 1; $i < $iTotal; $i ++)
					{
						_echoExhaustiveHoldingsItem($ref, $i, $iTotal, $strStockId1, $strStockId2, $strDate, $strNav, $bAdmin);
					}
					EchoTableParagraphEnd();
				}
			}
		}
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().EXHAUSTIVE_HOLDINGS_DISPLAY;
    $str .= '。仅用于只有2个持仓用来估值的美股QDII基金，使用穷举法来计算2个持仓最可能的实际比例。';
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
