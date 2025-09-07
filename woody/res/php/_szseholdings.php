<?php
require_once('_etfholdings.php');

class _SzseHoldingsFile extends _EtfHoldingsFile
{
	var $bUse;
	
    public function __construct($strFileName, $strStockId) 
    {
        parent::__construct($strFileName, $strStockId);
        $this->SetSeparator('?');

        $this->bUse = false;
    }
    
    public function SetDate($strDate)
    {
    	parent::SetDate($strDate);
    	$this->CalcCurrency($strDate);
    }
    
    public function OnLineArray($arWord)
    {
   		$ar = explode(':', str_replace('：', ':', GbToUtf8($arWord[0])));		// explode函数只能用单字符分割字符串
   		$iCount = count($ar); 
   		if ($iCount == 2)
   		{
   			$str = trim($ar[1]);
			$fVal = floatval(rtrim($str, '元')); 
    		switch (trim($ar[0]))
    		{
    		case '现金差额':
    			$this->SubCash($fVal);
    			break;
    			
    		case '最小申购、赎回单位资产净值':
    			$this->AddCash($fVal);
    			break;
    		}
    	}
    	else if ($iCount == 1)
    	{
    		$ar = explode(' ', $ar[0]);
			if ($this->bUse)
			{
				if (count($ar) > 7)
				{
					$ar = array_filter($ar);
					$ar = array_values($ar);
					$strHolding = $ar[0];
					if ($strHolding != '159900')
					{
						if (is_numeric($strHolding))	$strHolding = BuildHongkongStockSymbol($strHolding);
						
						$iQuantity = intval(str_replace(',', '', $ar[2]));
						if ($iQuantity == 0)		$fVal = floatval(str_replace(',', '', $ar[5]));
						else						$fVal = $this->GetMarketVal($strHolding, $iQuantity);
						$this->AddHolding($strHolding, $ar[1], $fVal);
					}
				}
//				DebugPrint($ar);
			}
			else
			{
				if ($ar[0] == '证券代码')
				{
					$this->bUse = true;
					$this->DeleteAllHoldings();
				}
				else if ($this->GetDate() == false)
				{
					if (count($ar) == 2)
					{
						if ($ar[1] == '信息内容')		$this->SetDate(rtrim($ar[0], '日'));
					}
				}
			}
    	}
    	return true;
    }
}

// https://www.szse.cn/modules/report/views/eft_download_new.html?path=%2Ffiles%2Ftext%2FETFDown%2F&filename=pcf_159605_20250901%3B159605ETF20250901&opencode=ETF15960520250901.txt
function ReadSzseHoldingsFile($strSymbol, $strStockId, $strDate)
{
    $strDigit = substr($strSymbol, 2);
    $strFileName = GetSzseEtfFileName($strDigit, $strDate);
	$strUrl = GetSzseHoldingsUrl($strFileName);
	if (StockSaveDebugCsv($strFileName, $strUrl))
	{
		$csv = new _SzseHoldingsFile($strFileName, $strStockId);
		$csv->Read();
		return $csv->Done();
	}
	return false;
}

?>
