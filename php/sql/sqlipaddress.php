<?php
require_once('sqlint.php');

function GetIp($strId)
{
	return long2ip($strId);
}
    
function GetIpId($strIp)
{
	return sprintf("%u", ip2long($strIp));
}

class IpAddressSql extends TableSql
{
    public function GetRecord($strIp)
    {
   		return $this->GetRecordById(GetIpId($strIp));
    }

    function InsertIp($strIp)
    {
       	if ($this->GetRecord($strIp) == false)
       	{
       		if ($strId = GetIpId($strIp))
       		{
       			return $this->InsertId($strId);
       		}
       	}
		return false;
    }
    
    function DeleteByIp($strIp)
    {
    	return $this->DeleteById(GetIpId($strIp));
    }
}

class IpIntSql extends IntSql
{
    public function WriteInt($strIp, $iTick)
    {
    	return parent::WriteInt(GetIpId($strIp), $iTick);
    }
    
    public function ReadInt($strIp)
    {
    	return parent::ReadInt(GetIpId($strIp));
    }
}
