<?php
require_once('stocktable.php');

function _getPortfolioTestVal($iShares, $strSymbol)
{
	switch ($strSymbol)
    {
    case 'KWEB':
		$iQuantity = 600;
		break;
		
    case 'SH600104':
		$iQuantity = 16000;
		break;

    case 'TLT':
		$iQuantity = 400;
		break;
/*
    case 'XOP':
		$iQuantity = -400;
		break;

    case 'ASHR':
		$iQuantity = 601;
		break;
		
    case 'SH510300':
		$iQuantity = 30000;
		break;

    case 'SPY':
		$iQuantity = 100;
		break;
		
	case 'XBI':
		$iQuantity = 0;
		break;
		
    case 'SZ162411':
		$iQuantity = 91200;
		break;
*/		
	default:
		$iQuantity = 0;
		break;
	}
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
    	return strval($iArbitrageQuantity + $iQuantity * GetArbitrageRatio($record['stock_id']));
    }
    return '';
}

function _echoPortfolioTableItem($trans)
{
	static $fCny = 0.0;
	$ar = array();
	
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
    	if (in_arrayQdiiMix($strSymbol) || in_arrayQdii($strSymbol))	$fCny += $fVal * RefGetPosition($ref);
    	
        $ar[] = GetNumberDisplay($fVal);
        $ar[] = strval($iShares); 
        $ar[] = $trans->GetAvgCostDisplay();
       	$ar[] = ($trans->GetTotalCost() > 0.0) ? $ref->GetPercentageDisplay(strval($trans->GetAvgCost())) : '';
        switch ($strSymbol)
        {
		case 'KWEB':
//		case 'XBI':
//		case 'XOP':
        	$ar[] = _getArbitrageTestStr($iShares, $strGroupId, $strStockId, $strSymbol);
        	break;

//		case 'ASHR':
//		case 'SH510300':
		case 'SH600104':
//		case 'SPY':
		case 'TLT':
        	$ar[] = strval(_getPortfolioTestVal($iShares, $strSymbol));
			break;

//        case 'SZ161127':
		case 'SZ162411':
		case 'SZ162415':
        case 'SZ164906':
        	$ar[] = GetArbitrageQuantity($strStockId, floatval($iShares));
			break;

		case 'hf_ES':
        	$ar[] = strval($iShares / 5);
			break;
			
		case 'fx_susdcnh':
			$fVal += $fCny;
//			$ar[] = GetNumberDisplay($fVal).' $'.GetNumberDisplay($fVal / floatval($ref->GetPrice()));
			$strPage = 'overnightcnh';
			$ar[] = GetStockPhpLink($strPage, '$'.strval_round($fVal / floatval($ref->GetPrice()), 0), $strPage.'='.strval_round($fVal, 0));
			break;
   		}
    }

    RefEchoTableColumn($ref, $ar);
}

function EchoPortfolioParagraph($arTrans)
{
	$profit_col = new TableColumnProfit();
	EchoTableParagraphBegin(array(new TableColumnSymbol(),
								   $profit_col,
								   new TableColumnHolding(),
								   new TableColumnQuantity(),
								   new TableColumnPrice('平均'),
								   new TableColumnChange(),
								   new TableColumnTest()
								   ), 'myportfolio', '个股'.$profit_col->GetDisplay());

	foreach ($arTrans as $trans)
	{
		if ($trans->GetTotalRecords() > 0)	_echoPortfolioTableItem($trans);
	}
    EchoTableParagraphEnd();
}

?>
