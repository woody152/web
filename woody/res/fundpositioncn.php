<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/dateimagefile.php');
require_once('../../php/ui/editinputform.php');

function _echoFundPositionItem($csv, $ref, $cny_ref, $est_ref, $strDate, $strNetValue, $strPrevDate, $net_sql, $strStockId, $est_sql, $strEstId, $strInput, $bAdmin)
{
	$bWritten = false;
	$ar = array($strDate);
	$ar[] = number_format(floatval($strNetValue), 4);
	
   	$strPrev = $net_sql->GetClose($strStockId, $strPrevDate);
	$ar[] = $ref->GetPercentageDisplay($strPrev, $strNetValue);

	$strCny = $cny_ref->GetClose($strDate);
	$ar[] = $strCny;
	if ($strCnyPrev = $cny_ref->GetClose($strPrevDate))		$ar[] = $cny_ref->GetPercentageDisplay($strCnyPrev, $strCny);
	else															$ar[] = '';
		
	if ($strEst = $est_sql->GetClose($strEstId, $strDate))
	{
		$ar[] = number_format(floatval($strEst), 2);
		if ($strEstPrev = $est_sql->GetClose($strEstId, $strPrevDate))
		{
			$ar[] = $est_ref->GetPercentageDisplay($strEstPrev, $strEst);
			if ($strPosition = QdiiGetStockPosition($strEstPrev, $strEst, $strPrev, $strNetValue, $strCnyPrev, $strCny, $strInput))
			{
				$bWritten = true;
				$csv->Write($strDate, $strNetValue, $strPosition);
				if ($bAdmin)	$strPosition = GetOnClickLink('/php/_submitoperation.php?stockid='.$strStockId.'&fundposition='.$strPosition, "确认使用{$strPosition}作为估值仓位？", $strPosition);
				$ar[] = $strPosition;
			}
		}
	}

	if ($bWritten == false)		$csv->Write($strDate, $strNetValue);
	EchoTableColumn($ar);
}

function _getSwitchDateArray($net_sql, $strStockId, $est_sql, $strEstId)
{
	$arDate = array();
	$bFirst = true;
    if ($result = $net_sql->GetAll($strStockId)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $record['date'];
       		if ($strEst = $est_sql->GetClose($strEstId, $strDate))
       		{
       			$fCur = floatval($strEst);
       			if ($bFirst)
       			{
       				$arDate[] = $strDate;
       				$bSecond = true;
       				$bFirst = false;
       			}
       			else
       			{
       				if ($bSecond)
       				{
       					$bUp = ($fOld > $fCur) ? true : false;
       					$bSecond = false;
       				}
       				else
       				{
       					if ($bUp)
       					{
       						if ($fOld < $fCur)
       						{
       							$bUp = false;
       							$arDate[] = $strOldDate;
       						}
       					}
       					else
       					{
       						if ($fOld > $fCur)
       						{
       							$bUp = true;
       							$arDate[] = $strOldDate;
       						}
       					}
       				}
       			}
   				$fOld = $fCur;
   				$strOldDate = $strDate;
       		}
        }
        mysqli_free_result($result);
    }
    return $arDate;
}
	
function _echoFundPositionData($csv, $ref, $cny_ref, $est_ref, $strInput, $bAdmin)
{
   	$strStockId = $ref->GetStockId();
	$strEstId = $est_ref->GetStockId();
	$net_sql = GetNetValueHistorySql();
	$est_sql = ($est_ref->CountNetValue() > 0) ? $net_sql : GetStockHistorySql(); 

	$arDate = _getSwitchDateArray($net_sql, $strStockId, $est_sql, $strEstId);
	if (count($arDate) == 0)		return;
 
 	$iIndex = 0;
    if ($result = $net_sql->GetAll($strStockId)) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
       		$strDate = $record['date'];
       		$strNetValue = $record['close'];
       		if ($strDate == $arDate[$iIndex])
       		{
   				$iIndex ++;
   				if (isset($arDate[$iIndex]))	_echoFundPositionItem($csv, $ref, $cny_ref, $est_ref, $strDate, $strNetValue, $arDate[$iIndex], $net_sql, $strStockId, $est_sql, $strEstId, $strInput, $bAdmin);
   				else
   				{
   					$csv->Write($strDate, $strNetValue);
   					break;
       			}
       		}
       		else	$csv->Write($strDate, $strNetValue);
        }
        mysqli_free_result($result);
    }
}

function _echoFundPositionParagraph($ref, $cny_ref, $est_ref, $strSymbol, $strInput, $bAdmin)
{
 	$str = GetFundLinks($strSymbol);
	$change_col = new TableColumnChange();
	$position_col = new TableColumnPosition();
	EchoTableParagraphBegin(array(new TableColumnDate(),
								   new TableColumnNetValue(),
								   $change_col,
								   new TableColumnStock($cny_ref),
								   $change_col,
								   RefGetTableColumnNetValue($est_ref),
								   $change_col,
								   $position_col
								   ), 'fundposition', $str);
	
	$csv = new PageCsvFile();
	_echoFundPositionData($csv, $ref, $cny_ref, $est_ref, $strInput, $bAdmin);
	$csv->Close();
	
	if ($csv->HasFile())
	{
		$jpg = new DateImageFile();
		$strNewLine = GetHtmlNewLine();
		
		$str = $strNewLine.$csv->GetLink();
		if ($jpg->Draw($csv->ReadColumn(2), $csv->ReadColumn(1)))	$str .= $strNewLine.$jpg->GetAll($position_col->GetDisplay(), $strSymbol);
		EchoTableParagraphEnd($str);
   	}
}

function EchoAll()
{
	global $acct;
	
    if ($ref = $acct->EchoStockGroup())
    {
    	if (($strInput = GetEditInput()) === false)		$strInput = POSITION_EST_LEVEL;
    	EchoEditInputForm('进行估算的涨跌阈值', $strInput);
    	if ($strInput != '')
    	{
    		$fund = false;
    		$strSymbol = $ref->GetSymbol();
    		if ($fund = StockGetQdiiReference($strSymbol))
    		{
    			$cny_ref = $fund->GetCnyRef();
    			$est_ref = $fund->GetEstRef();
    		}
    		if ($fund)		_echoFundPositionParagraph($fund, $cny_ref, $est_ref, $strSymbol, $strInput, $acct->IsAdmin());
    	}
    }
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetStockDisplay().FUND_POSITION_DISPLAY;
    $str .= '。仅用于美股QDII基金，寻找对应美股ETF净值连续几天累计涨跌超过4%的机会测算A股基金的实际持仓仓位。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetSymbolDisplay().FUND_POSITION_DISPLAY;
}

    $acct = new SymbolAccount();

require('../../php/ui/_dispcn.php');
?>
