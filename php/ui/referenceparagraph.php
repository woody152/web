<?php
require_once('stocktable.php');

function GetStockReferenceArray($ref)
{
	$ar = array();
	
    if ($ref->HasData())
    {
    	$ar[] = $ref->GetPriceDisplay();
    	$ar[] = $ref->GetPercentageDisplay();
    	$ar[] = $ref->GetDate();
    	$ar[] = $ref->GetTimeHM();
    }
    else
    {
    	$ar[] = '';
    	$ar[] = '';
    	$ar[] = '';
    	$ar[] = '';
    }
    $ar[] = SqlGetStockName($ref->GetSymbol());
    
    return $ar;
}

// $ref from StockReference
function _echoReferenceTableItem($ref, $strDescription, $bAdmin)
{
	$ar = array_merge(array($ref->GetExternalLink()), GetStockReferenceArray($ref));
	$strFirstHint = false;
	if ($strDescription)
	{
		$strFirstHint = array_pop($ar);
		$ar[] = $strDescription;
	}
	if ($bAdmin)	$ar[] = $ref->DebugLink();
    EchoTableColumn($ar, false, $strFirstHint);
}

function _echoReferenceTableData($arRef, $bAdmin)
{
    foreach ($arRef as $ref)
    {
    	if ($ref)
    	{
    		_echoReferenceTableItem($ref, false, $bAdmin);
   			if ($ref->extended_ref)	_echoReferenceTableItem($ref->extended_ref, GetHtmlElement(GetQuoteElement($ref->extended_ref->GetMarketSession()), 'i'), false);
    	}
    }
}

function GetTimeDisplay()
{
    date_default_timezone_set('PRC');
	$ymd = GetNowYMD();
	$strTick = strval($ymd->GetTick() * 1000);
	
	echo <<< END
	
	<script>
		var now = new Date($strTick); 
		function UpdateTime() 
		{ 
			now.setTime(now.getTime() + 250); 
			document.getElementById("time").innerHTML = now.toLocaleTimeString(); 
		} 
		setInterval("UpdateTime()", 250);
	</script>
END;

	return '<span id="time"></span>';
}

function GetStockReferenceColumn()
{
	return array(new TableColumnPrice(),
				   new TableColumnChange(),
				   new TableColumnDate(),
				   new TableColumnTime(),
				   new TableColumnName());
}

function EchoReferenceParagraph($arRef, $bAdmin = false)
{
	if ($_SESSION['mobile'])	$bAdmin = false;
	
	$str = '参考数据 '.GetTimeDisplay();
	$ar = array_merge(array(new TableColumnSymbol()), GetStockReferenceColumn());
	array_pop($ar);
	$ar[] = new TableColumnName(false, 270);
	if ($bAdmin)	$ar[] = new TableColumn('调试数据', TableColumnGetLastWidth($ar));
	EchoTableParagraphBegin($ar, 'reference', $str);
	_echoReferenceTableData($arRef, $bAdmin);
    EchoTableParagraphEnd();
}

?>
