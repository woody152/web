<?php
require_once('php/_stock.php');
require_once('php/_emptygroup.php');
require_once('../../php/ui/stockhistoryparagraph.php');

function _getStockHistoryLinks($ref, $bAdmin)
{
	$strSymbol = $ref->GetSymbol();
	
	$str = $ref->IsFund() ? GetFundLinks($strSymbol) : '';
    $str .= ' '.GetExternalStockHistoryLink($ref);
    if ($ref->IsTradable())	$str .= ' '.GetStockDividendLink($ref);
    if ($bAdmin)	$str .= ' '.StockGetAllLink($strSymbol).' '.GetUpdateStockHistoryLink($ref, STOCK_HISTORY_UPDATE);
    return $str;
}

function _echoUploadFile()
{
    $strPassQuery = UrlPassQuery();
    $strSubmit = STOCK_HISTORY_UPDATE;
    
	echo <<< END
	<form action="uploadfile.php{$strPassQuery}" method="post" enctype="multipart/form-data">
        <div>
		<label for="file">文件名称：</label>
		<input type="file" name="file" id="file" /> 
		<br />
		<input type="submit" name="submit" value="$strSubmit" />
        </div>
	</form>
END;
}

function EchoAll()
{
	global $acct;
	
//    if ($ref = $acct->EchoStockGroup())
	if ($strSymbol = $acct->StockCheckSymbol())
    {
    	$arSymbol = array($strSymbol);
    	if ($strCompare = UrlGetQueryValue('compare'))	$arSymbol[] = $strCompare;
    	if ($strForex = UrlGetQueryValue('forex'))		$arSymbol[] = $strForex;
  		StockPrefetchArrayExtendedData($arSymbol);
  		
		$ref = StockGetReference($strSymbol);
		$compare_ref = $strCompare ? StockGetReference($strCompare) : false;
		$forex_ref = $strForex ? StockGetReference($strForex) : false;
		$acct->SetRef($ref);
		$acct->EchoStockGroup();

    	$bAdmin = $acct->IsAdmin();
   		$strLinks = _getStockHistoryLinks($ref, $bAdmin);
   		$csv = new PageCsvFile();
   		EchoStockHistoryParagraph($ref, $compare_ref, $forex_ref, $strLinks, $csv, $acct->GetStart(), $acct->GetNum(), $bAdmin);
   		$csv->Close();
   		
   		if ($acct->GetLoginId()) 
   		{
   			$strSymbol = $ref->GetSymbol();
   			switch ($strSymbol)
   			{
   			case 'hf_CHA50CFD';
   			case 'SH000016';
   				_echoUploadFile();
   				break;
   			}
   		}
   	}
    $acct->EchoLinks();
}

function GetMetaDescription()
{
	global $acct;
	
  	$str = $acct->GetMetaDisplay(STOCK_HISTORY_DISPLAY);
    $str .= '页面。用于查看计算SMA的原始数据，方便人工处理合股和拆股、分红除权等价格问题。附带简单的图形显示数据。';
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetTitleDisplay(STOCK_HISTORY_DISPLAY);
}

    $acct = new SymbolAccount();

require('../../php/ui/_dispcn.php');
?>
