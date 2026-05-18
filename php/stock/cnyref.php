<?php

class CnyReference extends MysqlReference
{
    public function LoadData()
    {
    	$strSymbol = $this->GetSymbol();
    	
    	$this->strSqlId = SqlGetStockId($strSymbol);
       	$this->LoadSqlNetValueData();
   		$this->SetTime('09:15:00');
        $this->strFileName = DebugGetChinaMoneyFile();
        $this->SetExternalLink(GetReferenceRateForexLink($strSymbol));
    }
    
	public function GetClose($strDate)
	{
		if ($strDate == $this->GetDate())	return $this->GetPrice();
		return SqlGetNetValueByDate($this->strSqlId, $strDate);
	}
}

?>
