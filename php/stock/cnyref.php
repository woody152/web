<?php

class CnyReference extends MysqlReference
{
    public function LoadData()
    {
    	$this->SetStockId();
       	$this->LoadSqlNetValueData();
   		$this->SetTime('09:15:00');
        $this->strFileName = DebugGetChinaMoneyFile();
        $this->SetExternalLink(GetReferenceRateForexLink($this->GetSymbol()));
    }
    
	public function GetClose($strDate)
	{
		if ($strDate == $this->GetDate())	return $this->GetPrice();
		return SqlGetNetValueByDate($this->GetStockId(), $strDate);
	}
}
