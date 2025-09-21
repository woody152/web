<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/dateimagefile.php');
require_once('../../php/ui/editinputform.php');
require_once('../../php/ui/netvaluehistoryparagraph.php');

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
   				if (isset($arDate[$iIndex]))	EchoNetValueItem($csv, $ref, $cny_ref, $est_ref, $strDate, $strNetValue, $arDate[$iIndex], $strInput, $bAdmin);
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
	EchoTableParagraphBegin(GetNetValueTableColumn($est_ref, $cny_ref), 'fundposition', GetFundLinks($strSymbol));
	
	$csv = new PageCsvFile();
	_echoFundPositionData($csv, $ref, $cny_ref, $est_ref, $strInput, $bAdmin);
	$csv->Close();
	
 	$str = '';
	if ($csv->HasFile())
	{
		$jpg = new DateImageFile();
		$strNewLine = GetHtmlNewLine();
		
		$str = $strNewLine.$csv->GetLink();
		if ($jpg->Draw($csv->ReadColumn(2), $csv->ReadColumn(1)))	$str .= $strNewLine.$jpg->GetAll(TableColumnGetPosition(), $strSymbol);
   	}
	EchoTableParagraphEnd($str);
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
