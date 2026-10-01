<?php
require_once('_stock.php');
require_once('_editstockoptionform.php');

function _getEditStockOptionSubmit($strPage)
{
    $ar = GetStockOptionArray();
	return $ar[$strPage];
}

function EchoAll()
{
	global $acct;
    /** @var SymbolEditAccount $acct */
	
    if ($acct->EchoStockGroup())
    {
      	$strPage = UrlGetPage();
       	$acct->StockOptionEditForm(_getEditStockOptionSubmit($strPage));
    }
}

function GetMetaDescription()
{
	global $acct;
	
	$strPage = UrlGetPage();
    $str = '本页面导入和查看原始数据，以及在用户登录和有相应权限的情况下关闭只读和提供编辑功能，对'.$acct->GetStockDisplay();
    $str .= _getEditStockOptionSubmit($strPage);
    return CheckMetaDescription($str);
}

function GetTitle()
{
	global $acct;
	return $acct->GetSymbolDisplay()._getEditStockOptionSubmit(UrlGetPage());
}

    $acct = new SymbolEditAccount();
