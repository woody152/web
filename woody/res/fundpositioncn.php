<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/dateimagefile.php');
require_once('../../php/ui/editinputform.php');
require_once('../../php/ui/netvaluehistoryparagraph.php');

function _echoFundPositionData($csv, $ref, $cny_ref, $est_ref, $fInput, $iNum, $bAdmin)
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
			$bWritten = false;
       		$strDate = $record['date'];
       		$strNetValue = $record['close'];
       		if ($strDate == $arDate[$iIndex])
       		{
   				$iIndex ++;
   				if (isset($arDate[$iIndex]))
				{
					$bWritten = EchoNetValueItem($csv, $ref, $cny_ref, $est_ref, $strDate, $strNetValue, $arDate[$iIndex], $fInput, $bAdmin);
					$iTotal ++;
					if ($iTotal == $iNum)	break;
				}
   				else
   				{
   					break;
       			}
       		}
			if ($bWritten === false)	$csv->Write($strDate, $strNetValue);
        }
        mysqli_free_result($result);
    }
}

function _echoFundPositionParagraph($strPage, $strLinks, $ref, $cny_ref, $est_ref, $strSymbol, $fInput, $iNum, $bAdmin)
{
	EchoTableParagraphBegin(GetNetValueTableColumn($est_ref, $cny_ref), $strPage, $strLinks);
	
	$csv = new PageCsvFile();
	_echoFundPositionData($csv, $ref, $cny_ref, $est_ref, $fInput, $iNum, $bAdmin);
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
    	if (($strInput = GetEditInput()) === false)		$strInput = strval(POS_NETVALUE_DIFF);
    	EchoEditInputForm('进行'.TableColumnGetPosition().'估算的'.TableColumnGetNetValue().'涨跌%阈值', $strInput);
    	if ($strInput != '')
    	{
    		$fund = false;
    		$strSymbol = $ref->GetSymbol();
    		if ($fund = StockGetQdiiReference($strSymbol))
    		{
    			$cny_ref = $fund->GetCnyRef();
    			$est_ref = $fund->GetEstRef();
    		}
			else if (in_arrayQdiiGoldOil($strSymbol))
			{
				$fund = new HoldingsReference($strSymbol);
				$cny_ref = $fund->GetCnyRef();
				$est_ref = false;				
			}
    		if ($fund)
			{
				$strLinks = GetFundLinks($strSymbol);
				if ($acct->GetLoginId())	$strLinks .= ' '.StockGetAllLink($strSymbol);
				_echoFundPositionParagraph($acct->GetPage(), $strLinks, $fund, $cny_ref, $est_ref, $strSymbol, floatval($strInput), $acct->GetNum(), $acct->IsAdmin());
    		}
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
