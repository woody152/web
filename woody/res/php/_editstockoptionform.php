<?php
require_once('_emptygroup.php');
require_once('../../php/stock/kraneshares.php');

function _getStockOptionDate($strSubmit, $ref, $strSymbol)
{
    $now_ymd = GetNowYMD();
	$strYMD = $now_ymd->GetYMD();
    
    $strStockId = $ref->GetStockId();
	$his_sql = GetStockHistorySql();
	switch ($strSubmit)
	{
	case STOCK_OPTION_DIVIDEND:
	case STOCK_OPTION_EMA:
	case STOCK_OPTION_SHARE_DIFF:
	case STOCK_OPTION_SPLIT:
		if ($strDate = $his_sql->GetDateNow($strStockId))		return $strDate;
	 	return $strYMD;

	case STOCK_OPTION_CLOSE:
		if ($record = $his_sql->GetRecordPrev($strStockId, $ref->GetDate()))	return $record['date'];
	 	return $strYMD;

	case STOCK_OPTION_CALIBRATION:
	case STOCK_OPTION_NETVALUE:
		if ($strSymbol == 'KWEB')
		{
			if ($strDate = $his_sql->GetDatePrev($strStockId, $ref->GetDate()))		return $strDate;
		}
		else
		{
			$net_sql = GetNetValueHistorySql();
			if ($strDate = $net_sql->GetDateNow($strStockId))		return $strDate;
			if ($strDate = $his_sql->GetDateNow($strStockId))		return $strDate;
		}
	 	return $strYMD;

	case STOCK_OPTION_HOLDINGS:
		$date_sql = new HoldingsDateSql();
		if ($strDate = $date_sql->ReadDate($strStockId))		return $strDate;
	 	return $strYMD;

	case STOCK_OPTION_PREMIUM:
		$premium_sql = new FuturePremiumSql();
		if ($strDate = $premium_sql->GetDateNow($strStockId))		return $strDate;
	 	return $strYMD;
	}

	return '';
}

function _getStockOptionNewName($ref, $strName)
{
	$str = '';
	$strChinese = $ref->GetChineseName();
	$strEnglish = $ref->GetEnglishName();
	if ($strChinese != $strName)									$str .= '-'.$strChinese;
    if ($strEnglish != $strName && $strEnglish != $strChinese)	$str .= '-'.$strEnglish;
    return $str;
}

function _getStockOptionName($ref, $strSymbol)
{
	$strName = SqlGetStockName($strSymbol);
	
    $str = $strName;
   	$str .= _getStockOptionNewName($ref, $strName);
    if ($ref->IsFundA())
    {
        $fund_ref = new FundReference($strSymbol);
        $str .= _getStockOptionNewName($fund_ref, $strName);
    }
    return $str;
}

function _getStockOptionAmount($strLoginId, $strStockId)
{
//   	if ($str = SqlGetFundPurchaseAmount($strLoginId, $strStockId))
	if ($strGroupItemId = SqlGetMyStockGroupItemId($strLoginId, $strStockId))
	{
		$amount_sql = new GroupItemAmountSql();
		return $amount_sql->ReadAmount($strGroupItemId);
    }
    return '出错了';
}

function _getStockOptionAh($strSymbol)
{
	if ($strH = SqlGetAhPair($strSymbol))
	{
		return $strH;
	}
	return 'H';
}

function _getStockOptionHa($strSymbol)
{
	if ($strA = SqlGetHaPair($strSymbol))
	{
		return $strA;
	}
	return 'A';
}

function _getStockOptionNetValue($ref, $strSymbol, $strStockId, $strDate)
{
	if ($strSymbol == 'KWEB')
	{
		if ($strNetValue = GetKraneNetValue($ref))		return $strNetValue;
	}
	return SqlGetNetValueByDate($strStockId, $strDate);
}

function _getStockOptionPremium($strStockId, $strDate)
{
	$premium_sql = new FuturePremiumSql();
	if ($strClose = $premium_sql->GetClose($strStockId, $strDate))		return $strClose;
	return '4.5';
}

function _getStockOptionAdr($strSymbol)
{
	$pair_sql = GetAdrPairSql();
	if ($strAdr = $pair_sql->GetSymbol($strSymbol))
	{
		$pos_sql = GetPositionSql();
		if ($fRatio = $pos_sql->ReadVal(SqlGetStockId($strAdr)))	return $strAdr.'/'.strval($fRatio);
		return $strAdr;
	}
	return 'ADR/100';
}

function _getStockOptionFund($strSymbol)
{
	$pos_sql = GetPositionSql();
	if ($fPos = $pos_sql->ReadVal(SqlGetStockId($strSymbol)))	$strPos = strval($fPos);
		
	$pair_sql = GetFundPairSql();
	if ($strIndex = $pair_sql->GetPairSymbol($strSymbol))
	{
		$pos_sql = GetPositionSql();
		if ($fPos)	return $strIndex.'*'.$strPos;
		return $strIndex;
	}
	if ($fPos)	return $strPos;
	return 'INDEX*1';
}

function _getStockOptionEmaDays($strStockId, $strDate, $iDays)
{
	$sql = GetStockEmaSql($iDays);
	return round($sql->GetClose($strStockId, $strDate), 2);
}

function _getStockOptionEma($strStockId, $strDate)
{
	$str200 = _getStockOptionEmaDays($strStockId, $strDate, 200);
	$str50 = _getStockOptionEmaDays($strStockId, $strDate, 50);
	if ($str200 && $str50)
	{
		return $str200.'/'.$str50;
	}
	return 'EMA200/50';
}

function _getStockOptionSharesDiff($strStockId, $strDate)
{
	$sql = new SharesDiffSql();
	if ($str = $sql->GetClose($strStockId, $strDate))		return $str;

	$shares_sql = new SharesHistorySql();
	if ($strClose = $shares_sql->GetClose($strStockId, $strDate))
	{
		if ($strClosePrev = $shares_sql->GetClosePrev($strStockId, $strDate))
		{
			return strval(floatval($strClose) - floatval($strClosePrev));
		}
	}

	return '';
}

function _getStockOptionDividend($strStockId, $strDate)
{
	$sql = new StockDividendSql();
	if ($strClose = $sql->GetClose($strStockId, $strDate))
	{
		return $strClose;
	}
	return '1.00';
}

function _getBestEstNetValue($ref, $strDate)
{
	$strEst = SqlGetNetValueByDate($ref->GetStockId(), $strDate);
	if ($strEst == false)
    {
    	$strEst = $ref->GetClose($strDate);
   		if ($strEst == false)	$strEst = $ref->GetPrevPrice();
   	}
   	return $strEst;
}

function _getStockOptionCalibration($strSymbol, $strDate)
{
	$est_ref = false;
	if ($ref = StockGetFundPairReference($strSymbol))
	{
		$est_ref = $ref->GetPairRef();
	}
	else if ($fund = StockGetQdiiReference($strSymbol))
    {
       	$est_ref = $fund->GetEstRef();
    }
	else if ($strSymbol == 'SZ164906')
	{
		$est_ref = new MyStockReference('KWEB');
	}
	else if ($strSymbol == 'INDA')
	{
		$est_ref = new MyStockReference('znb_SENSEX');
	}

	return $est_ref ? _getBestEstNetValue($est_ref, $strDate) : '对方净值';
}

function _getStockOptionHoldings($strStockId)
{
	$sql = GetHoldingsSql();
	$ar = $sql->GetHoldingsArray($strStockId);
	if (count($ar) == 0)			return 'STOCK1*10.1;STOCK2*20.2;STOCK3*30.3;STOCK4*39.4';

	$str = '';
	foreach ($ar as $strStockId => $strRatio)
	{
		$str .= SqlGetStockSymbol($strStockId).'*'.rtrim0($strRatio).';';
	}
	return rtrim($str, ';');
}

function _getStockOptionVal($strSubmit, $strLoginId, $ref, $strSymbol, $strDate)
{
	$strStockId = $ref->GetStockId();
	switch ($strSubmit)
	{
	case STOCK_OPTION_ADR:
		return _getStockOptionAdr($strSymbol);

	case STOCK_OPTION_AH:
		return _getStockOptionAh($strSymbol);

	case STOCK_OPTION_AMOUNT:
		return _getStockOptionAmount($strLoginId, $strStockId);
		
	case STOCK_OPTION_CALIBRATION:
		return _getStockOptionCalibration($strSymbol, $strDate); 
		
	case STOCK_OPTION_CLOSE:
		return $ref->GetPrevPrice();

	case STOCK_OPTION_DIVIDEND:
		return _getStockOptionDividend($strStockId, $strDate);

	case STOCK_OPTION_EDIT:
		return _getStockOptionName($ref, $strSymbol);
		
	case STOCK_OPTION_EMA:
		return _getStockOptionEma($strStockId, $strDate);

	case STOCK_OPTION_FUND:
		return _getStockOptionFund($strSymbol);

	case STOCK_OPTION_HA:
		return _getStockOptionHa($strSymbol);

	case STOCK_OPTION_HOLDINGS:
		return _getStockOptionHoldings($strStockId);

	case STOCK_OPTION_NETVALUE:
		return _getStockOptionNetValue($ref, $strSymbol, $strStockId, $strDate);

	case STOCK_OPTION_PREMIUM:
		return _getStockOptionPremium($strStockId, $strDate);
		
	case STOCK_OPTION_SHARE_DIFF:
		return _getStockOptionSharesDiff($strStockId, $strDate);

	case STOCK_OPTION_SPLIT:
		return '10:1';
	}
	return '';
}

function _getStockOptionMemo($strSubmit)
{
	switch ($strSubmit)
	{
	case STOCK_OPTION_ADR:
		return '输入SYMBOL/0删除对应ADR。';
		
	case STOCK_OPTION_AH:
		return '清空输入删除对应H股。';
		
	case STOCK_OPTION_CALIBRATION:
		return '清空输入删除对应日期校准值。';
		
	case STOCK_OPTION_DIVIDEND:
		return '清空输入删除对应分红。';
		
	case STOCK_OPTION_EMA:
		return '股票收盘后的第2天修改才会生效，同时删除以往全部EMA记录。';

	case STOCK_OPTION_FUND:
		return '输入INDEX*0删除对应关系和全部'.CALIBRATION_HISTORY_DISPLAY.'，输入0删除仓位。';

	case STOCK_OPTION_HA:
		return '清空输入删除对应A股。';

	case STOCK_OPTION_HOLDINGS:
		return '输入STOCK*0删除对应基金持仓，用;号间隔多个持仓品种。';
		
	case STOCK_OPTION_NETVALUE:
		return '清空输入删除对应日期净值。';

	case STOCK_OPTION_PREMIUM:
		return '期货升水年化百分比';
		
	case STOCK_OPTION_SHARE_DIFF:
		return '清空输入删除对应日期新增。';
		
	case STOCK_OPTION_SPLIT:
		return '输入1:10表示10股合1股，10:1表示1股拆10股，0:0删除对应日期数据。';
	}
	return '';
}

class SymbolEditAccount extends SymbolAccount
{
	function StockOptionEditForm($strSubmit)
	{
		$ref = $this->GetSymbolRef();
		$strReadonly = HtmlElementReadonly();
		$strEmail = $this->GetLoginEmail(); 
		$strEmailReadonly = $strReadonly;
		$strSymbol = $ref->GetSymbol();
		$strSymbolReadonly = $strReadonly;
	
		$strDateDisabled = '';
		if (($strDate = _getStockOptionDate($strSubmit, $ref, $strSymbol)) == '')
		{
			$strDateDisabled = HtmlElementDisabled();
		}
    
		$strVal = _getStockOptionVal($strSubmit, $this->GetLoginId(), $ref, $strSymbol, $strDate);
		$strMemo = GetInfoElement(_getStockOptionMemo($strSubmit));
		
		$strDateReadonly = '';
		$strValReadonly = '';
		if ($strSubmit != STOCK_OPTION_AMOUNT)
		{
			if ($this->IsAdmin() === false)
			{
				$strValReadonly = $strReadonly;
				if ($strDateDisabled == '')		$strDateReadonly = $strReadonly;
				$strSubmit = '返回';
			}
		}
	
		echo <<< END
	<script>
	    function OnLoad()
	    {
	    }
	</script>
	
	<form id="stockoptionForm" name="stockoptionForm" method="post" action="submitstockoptions.php">
        <div>
		<p>$strMemo
		<br /><input name="login" value="$strEmail" type="text" size="40" maxlength="128" class="textfield" id="login" $strEmailReadonly />
		<br /><input name="symbol" value="$strSymbol" type="text" size="20" maxlength="32" class="textfield" id="symbol" $strSymbolReadonly />
		<br /><input name="date" value="$strDate" type="text" size="10" maxlength="32" class="textfield" id="date" {$strDateReadonly}{$strDateDisabled} />
		<br /><textarea name="val" rows="8" cols="75" id="val" $strValReadonly>$strVal</textarea>
	    <br /><input type="submit" name="submit" value="$strSubmit" />
	    </p>
	    </div>
    </form>
END;
	}
}

?>
