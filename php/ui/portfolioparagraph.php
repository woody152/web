<?php
require_once('stocktable.php');

function _getPortfolioTestVal($iShares, $strSymbol)
{
	$iQuantity = match($strSymbol)
    			 {'KWEB' => 200,
				  'SH600104' => 12000,
				  'TLT' => 500,
				  default => 0
				 };
	return $iShares - $iQuantity;
}

function _getArbitrageTestStr($iShares, $strGroupId, $strStockId, $strSymbol)
{
	$iArbitrageQuantity = 0;
	$item_sql = new StockGroupItemSql($strGroupId);
	if ($result = $item_sql->GetAll()) 
	{   
		while ($record = mysqli_fetch_assoc($result)) 
		{
			if ($strStockId != $record['stock_id'])
			{
				$iArbitrageQuantity = intval($record['quantity']);
				if ($iArbitrageQuantity > 0)
				{
					break;
				}
			}
		}
        mysqli_free_result($result);
    }

    if ($record)
    {
    	$iQuantity = _getPortfolioTestVal($iShares, $strSymbol); 
    	return GetNumberDisplay($iArbitrageQuantity + $iQuantity * GetStockHedge(SqlGetStockSymbol($record['stock_id']), $record['stock_id']), 0);
    }
    return '';
}

function _echoPortfolioTableItem($trans)
{
	static $fCny = 0.0;
	$ar = [];
	
    $ref = $trans->ref;
    $strSymbol = $ref->GetSymbol();
    $strStockId = $ref->GetStockId();
    
    $strGroupId = $trans->GetGroupId();
    $ar[] = StockGetTransactionLink($strGroupId, $strSymbol);
    $ar[] = $trans->GetProfitDisplay();
    $iShares = $trans->GetTotalShares();
    if ($iShares != 0)
    {
    	$fVal = $trans->GetValue();
    	if (in_arrayQdiiMix($strSymbol) || in_arrayQdii($strSymbol))	$fCny += $fVal * $ref->GetPosition();
    	
        $ar[] = GetNumberDisplay($fVal);
        $ar[] = strval($iShares); 
        $ar[] = $trans->GetAvgCostDisplay();
       	$ar[] = ($trans->GetTotalCost() > 0.0) ? $ref->GetPercentageDisplay($trans->GetAvgCost()) : '';
        switch ($strSymbol)
        {
		case 'KWEB':
        	$ar[] = _getArbitrageTestStr($iShares, $strGroupId, $strStockId, $strSymbol);
        	break;

		case 'SH600104':
		case 'TLT':
        	$ar[] = strval(_getPortfolioTestVal($iShares, $strSymbol));
			break;

        case 'SZ161125':
        case 'SZ161127':
        case 'SZ161130':
		case 'SZ162411':
		case 'SZ162415':
        case 'SZ164906':
        	$ar[] = GetArbitrageQuantity($strSymbol, $strStockId, floatval($iShares));
			break;

		case 'hf_ES':
        	$ar[] = strval($iShares / 5);
			break;
			
		case 'fx_susdcnh':
			$fVal += $fCny;
			$ar[] = GetOvernightCnhLink('$'.number_format($fVal / $ref->GetVal() / 1000.0).'K', strval(round(0.0 - $fVal)));
			break;
   		}
    }

    RefEchoTableColumn($ref, $ar);
}

function EchoPortfolioParagraph($arTrans)
{
	$profit_col = new TableColumnProfit();
	if (EchoTableParagraphBegin([new TableColumnSymbol(),
								 $profit_col,
								 new TableColumnHolding(),
								 new TableColumnQuantity(),
								 new TableColumnPrice('平均'),
								 new TableColumnChange(),
								 new TableColumnTest()
								], 'myportfolio', '个股'.$profit_col->GetDisplay()))
	{
		foreach ($arTrans as $trans)
		{
			if ($trans->GetTotalRecords() > 0)	_echoPortfolioTableItem($trans);
		}
    	EchoTableParagraphEnd();
	}								 
}
