<?php
require_once('_fundgroup.php');

//https://81.futsseapi.eastmoney.com/sse/113_ag2602_qt

class _ChinaFutureAccount extends FundGroupAccount
{
    var $realtime_ref = false;
    var $cnh_ref = false;

    function Create() 
    {
        $strSymbol = $this->GetName();
		$ar = array($strSymbol);
        if ($strSymbol == 'SZ161226')
        {
        	$strRealtime = 'hf_SI';
        	$ar[] = $strRealtime;
        	$strCNH = 'fx_susdcnh';
        	$ar[] = $strCNH;
        	$callback = '_RealtimeCallback';
        }
        else
        {
        	$strRealtime = false;
        	$strCNH = false;
        	$callback = false;
        }
        
        StockPrefetchArrayExtendedData($ar);
        $this->ref = new FundPairReference($strSymbol, $callback);
        if ($strRealtime)	$this->realtime_ref = new MyStockReference($strRealtime);
        if ($strCNH)		$this->cnh_ref = new MyStockReference($strCNH);
		
        SzseGetLofShares($this->ref);
   		$this->ref->DailyCalibration();
   		
        $this->CreateGroup(array($this->ref->GetPairRef(), $this->ref));
    }

    function GetRealtimeRef()
    {
    	return $this->realtime_ref;
    }

    function GetCnhRef()
    {
    	return $this->cnh_ref;
    }
}

function _RealtimeCallback()
{
    global $acct;
    
    $realtime_ref = $acct->GetRealtimeRef();
    $cnh_ref = $acct->GetCnhRef();
    return 1000.0 * $realtime_ref->GetVal() * $cnh_ref->GetVal() / 31.1035;
}


function EchoAll()
{
    global $acct;

    $ref = $acct->GetRef();
    
	EchoFundEstParagraph($ref);
    EchoReferenceParagraph(array_merge($acct->GetStockRefArray(), array($acct->realtime_ref, $acct->cnh_ref)), $acct->IsAdmin());
    EchoFundListParagraph(array($ref));
    EchoFundPairTradingParagraph($ref);
    EchoFundPairSmaParagraph($ref);
    EchoFundHistoryParagraph($ref);
   	EchoFundShareParagraph($ref);

    if ($group = $acct->EchoTransaction()) 
    {
    	$acct->EchoMoneyParagraph($group);
	}
	
    $acct->EchoLinks('chinafuture', 'GetChinaFutureLinks');
}

function GetChinaFutureLinks($sym)
{
	$str = GetStockCategoryLinks($sym->GetSymbol());
	return $str.GetChinaFutureRelated($sym->GetDigitA());
}

function GetMetaDescription()
{
    global $acct;

    $ref = $acct->GetRef();
    
    $strDescription = RefGetStockDisplay($ref);
    $strEst = RefGetStockDisplay($ref->GetPairRef());
    $str = "用{$strEst}估算{$strDescription}净值，同时测算无风险套利的国内期货市场对冲比例等数据。";
    return CheckMetaDescription($str);
}

   	$acct = new _ChinaFutureAccount();
   	$acct->Create();
?>
