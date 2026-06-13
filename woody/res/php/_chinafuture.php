<?php
require_once('_fundgroup.php');

//https://81.futsseapi.eastmoney.com/sse/113_ag2602_qt

class _ChinaFutureAccount extends FundGroupAccount
{
    private $realtime_ref = false;
    private $cnh_ref = false;

    public function Create() 
    {
        $strSymbol = $this->GetName();
		$ar = [$strSymbol];
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
   		
        $this->CreateGroup([$this->ref->GetPairRef(), $this->ref]);
    }

    public function GetRealtimeRef()
    {
    	return $this->realtime_ref;
    }

    public function GetCnhRef()
    {
    	return $this->cnh_ref;
    }
}

function _RealtimeCallback()
{
    global $acct;
	/** @var _ChinaFutureAccount $acct */
    
    $realtime_ref = $acct->GetRealtimeRef();
    $cnh_ref = $acct->GetCnhRef();
    return 1000.0 * $realtime_ref->GetVal() * $cnh_ref->GetVal() / 31.1035;
}

function EchoAll()
{
    global $acct;
	/** @var _ChinaFutureAccount $acct */

    $ref = $acct->GetRef();
    
	EchoFundEstParagraph($ref);
    EchoReferenceParagraph([...$acct->GetStockRefArray(), $acct->GetRealtimeRef(), $acct->GetCnhRef()], $acct->IsAdmin());
    EchoFundListParagraph([$ref]);
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
    $str = "用{$strEst}估算{$strDescription}".STOCK_DISP_NETVALUE.'，同时测算无风险套利的国内期货市场对冲比例等数据。';
    return CheckMetaDescription($str);
}

   	$acct = new _ChinaFutureAccount();
   	$acct->Create();
