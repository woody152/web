<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');

function _echoExhaustiveHoldingsItem($ref, $i, $iTotal, $strStockId1, $strStockId2, $strDate, $strNav)
{
	$ar = array();
	
	$str1 = strval($iTotal - $i);
	$ref->arHoldingsRatio[$strStockId1] = $str1;
	
	$str2 = strval($i);
	$ref->arHoldingsRatio[$strStockId2] = $str2;
	
	$fEst = $ref->_estNav($strDate);
	
	$ar[] = $str1;
	$ar[] = $str2;
	$ar[] = strval_round($fEst, 3);
	$ar[] = $ref->GetPercentageDisplay($strNav, strval($fEst));
	EchoTableColumn($ar);
}

function EchoAll()
{
	global $acct;
	
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
						_echoExhaustiveHoldingsItem($ref, $i, $iTotal, $strStockId1, $strStockId2, $strDate, $strNav);
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
