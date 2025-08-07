<?php
require_once('_fundgroup.php');
require_once('_kraneholdingscsv.php');
require_once('_sseholdings.php');
require_once('_szseholdings.php');
require_once('../../php/stock/kraneshares.php');

// SH501225 全球芯片LOF SOXX*75%+SH516640*15%
// SH501312 海外科技LOF ARKW*19.56;ARKK*19.66;ARKF*16.75;ARKG*11.86;ARKQ*5.37;QQQ*8.88;SOXX*7.44;XLK*5.2
// SZ160644 NVDA*9.48;MSFT*7.33;TSM*9.21;PDD*7.27;GOOGL*6.38;00700*6.26;09988*4.85;META*4.85;AMZN*5.8;03690*3.62

class _QdiiMixAccount extends FundGroupAccount
{
    var $us_ref;
    var $pair_ref;
    var $cnh_ref;

    function Create()
    {
        $strCNH = 'fx_susdcnh';
        $strSymbol = $this->GetName();
        StockPrefetchExtendedData($strSymbol, $strCNH);

        $this->cnh_ref = new MyStockReference($strCNH);
        $this->ref = new HoldingsReference($strSymbol);
        switch ($strSymbol)
        {
        case 'SH501225':
        	$this->us_ref = new MyStockReference('SMH');
        	break;
        	
        case 'SZ164906':
        	$strSymbolUs = 'KWEB';
        	$this->us_ref = new HoldingsReference($strSymbolUs);
        	if ($strDate = NeedOfficialNetValue($this->us_ref))
        	{
        		if ($strNetValue = GetKraneNetValue($this->us_ref))
        		{
        			$netvalue_sql = GetNetValueHistorySql();
        			$strStockIdUs = $this->us_ref->GetStockId();
					if ($netvalue_sql->ModifyDaily($strStockIdUs, $strDate, $strNetValue))		ReadKraneHoldingsCsvFile($strSymbolUs, $strStockIdUs, $strDate, $strNetValue);
        		}
        	}
        	$this->pair_ref = new FundPairReference($strSymbol);
        	break;
        	
		default:
        	$this->us_ref = false;
        	$this->pair_ref = false;
        	break;
        }

        $this->_updateStockHoldings($strSymbol);
		if ($this->pair_ref)	$this->pair_ref->DailyCalibration();

        $arRef = array($this->ref);
        if ($this->us_ref)	$arRef[] = $this->us_ref; 
        if ($this->ref->UseRealtimeEst())
        {
        	$ar_realtime_ref = $this->ref->GetRealtimeRefArray();
        	$arRef = array_merge($arRef, $ar_realtime_ref);
        	foreach ($ar_realtime_ref as $ref)	$arRef[] = $ref->GetPairRef();
        }

        GetChinaMoney($this->ref);
        SzseGetLofShares($this->ref);
        $this->CreateGroup($arRef);
    }
    
    private function _updateStockHoldings($strSymbol)
    {
    	$ref = $this->ref;
    	$strStockId = $ref->GetStockId();
    	$netvalue_sql = GetNetValueHistorySql();
    	$strNetValueDate = $netvalue_sql->GetDateNow($strStockId); 
    	
    	$date_sql = new HoldingsDateSql();
    	$strHoldingsDate = $date_sql->ReadDate($strStockId);
		if ($strNetValueDate == $strHoldingsDate)											return;	// Already up to date
    	if ($strHoldingsDate == $ref->GetOfficialDate())									return;
    	
    	$bUpdated = false;
    	switch ($strSymbol)
    	{
		case 'SZ160644':
		case 'SZ164701':
		case 'SH501225':
		case 'SH501312':
        	if ($strNetValueDate != $strHoldingsDate)		
        	{
        		if ($date_sql->WriteDate($strStockId, $strNetValueDate))		$bUpdated = true;
        	}
        	break;
		
		case 'SZ164906':
			$us_ref = $this->us_ref;
			$strUsId = $us_ref->GetStockId();
			if ($strHoldingsDate != $date_sql->ReadDate($strUsId))		$bUpdated = CopyHoldings($date_sql, $strUsId, $strStockId);
			break;
			
		default:
    		$fund_est_sql = GetFundEstSql();
    		$strEstDate = $fund_est_sql->GetDateNow($strStockId);
    		if ($strEstDate == $strNetValueDate)													return;	//
    		$strDate = $ref->GetDate();
    		if (!in_arrayHkMix($strSymbol))
    		{
    			if ($strEstDate == $strDate)													return;	// A day too early
    		}
    		
    		$iHourMinute = $ref->GetHourMinute();
    		if ($iHourMinute < 930)															return;	// Data not updated until 9:30
			else if ($iHourMinute > 1455)													return;	// Stop autoupdate after market close. 美股休市后第2天的盘前，有可能会有数据看上去像休市日数据，导致5分钟一次频繁下载老文件。这里有意错过每天美股盘前时间，并且考虑了夏令时的不同最坏情况。

    		$strSymbol = $ref->GetSymbol();
    		if ($ref->IsShangHaiEtf())		$bUpdated = ReadSseHoldingsFile($strSymbol, $strStockId);
    		else if ($ref->IsShenZhenEtf())	$bUpdated = ReadSzseHoldingsFile($strSymbol, $strStockId, $strDate);
    		break;
    	}
    	
    	if ($bUpdated)
    	{
    		unset($this->ref);
    		$this->ref = new HoldingsReference($strSymbol);
//    		DebugString('Holdings updated');
    	}
    }
    
    function GetUsRef()
    {
    	return $this->us_ref;
    }
    
    function GetPairRef()
    {
    	return $this->pair_ref;
    }
}

function _callbackQdiiMixSma($ref, $strEst = false)
{
	if ($strEst)	return strval_round($ref->EstFromPair(floatval($strEst)));
	return $ref;
}

function _callbackQdiiMixTrading($strVal = false)
{
	global $acct;
    
	$ref = $acct->GetPairRef();
	$us_ref = $ref->GetPairRef();
    if ($strVal)
    {
    	if ($strVal == '0')	return '';
    	else		    	return $us_ref->GetPriceDisplay(strval($ref->EstToPair(floatval($strVal))));
    }
   	return TableColumnGetStock($us_ref).TableColumnGetPrice();
}

function _callbackFundList($fRatio, $fFactor)
{
   	return strval(round($fFactor / $fRatio));
}

function EchoAll()
{
    global $acct;
    
    $ref = $acct->GetRef();
    $us_ref = $acct->GetUsRef();
    $uscny_ref = $ref->GetCnyRef();
    $hkcny_ref = $ref->GetHkcnyRef();
    $arForex = array($acct->cnh_ref, $uscny_ref);
    if ($hkcny_ref !== false)	$arForex[] = $hkcny_ref;
    
	EchoHoldingsEstParagraph($ref);
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), 
    									//$ref->GetHoldingRefArray(), 
    									$arForex), $acct->IsAdmin());
    
	if ($ref->GetSymbol() == 'SZ164906')
	{
		EchoFundTradingParagraph($ref, '_callbackQdiiMixTrading');
		EchoHoldingsEstParagraph($us_ref);
		$pair_ref = $acct->GetPairRef();
		EchoFundListParagraph(array($pair_ref), '_callbackFundList');
		EchoSmaParagraph($us_ref, false, $pair_ref, '_callbackQdiiMixSma');
	}
	else	
	{
		EchoFundTradingParagraph($ref);
		if ($us_ref)	EchoSmaParagraph($us_ref);
        if ($ref->UseRealtimeEst())		EchoFundListParagraph($ref->GetRealtimeRefArray());
	}

    EchoFundHistoryParagraph($ref);
   	EchoFundShareParagraph($ref);
	if ($us_ref)	EchoNetValueCloseParagraph($us_ref);

    if ($group = $acct->EchoTransaction()) 
    {
    	$acct->EchoMoneyParagraph($group, $uscny_ref, $hkcny_ref);
	}

    $acct->EchoLinks('qdiimix', 'GetQdiiMixLinks');
}

function GetQdiiMixLinks($sym)
{
	$str = GetSpySoftwareLinks();
	$str .= GetHSharesSoftwareLinks();
	$str .= GetChinaInternetSoftwareLinks();
	return $str.GetQdiiMixRelated($sym->GetDigitA());
}

function GetMetaDescription()
{
    global $acct;

    $strDescription = $acct->GetStockDisplay();
    $str = "根据美元人民币汇率中间价和港币人民币汇率中间价以及成分股持仓涨跌比例估算{$strDescription}净值的网页工具.";
    return CheckMetaDescription($str);
}

   	$acct = new _QdiiMixAccount();
   	$acct->Create();
?>
