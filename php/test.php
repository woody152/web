<?php
require_once('account.php');
require_once('stock.php');
//require_once('stocktrans.php');

//require_once('sql/sqlkeystring.php');

define('DEBUG_UTF8_BOM', "\xef\xbb\xbf");

// http://www.todayir.com/en/index.php HSFML25

/*
function SqlGetNetValue($strStockId)
{
	$net_sql = GetNetValueHistorySql();
	return $net_sql->GetCloseNow($strStockId);
}

function SqlGetUscny()
{
	return floatval(SqlGetNetValue(SqlGetStockId('USCNY')));
}

function SqlGetHkcny()
{
	return floatval(SqlGetNetValue(SqlGetStockId('HKCNY')));
}

function SqlGetUshkd()
{
	return SqlGetUscny() / SqlGetHkcny(); 
}

function TestModifyTransactions($strGroupId, $strSymbol, $strNewSymbol, $iRatio)
{
	$sql = new StockGroupItemSql($strGroupId);
	$strGroupItemId = $sql->GetId(SqlGetStockId($strSymbol));
	$strNewGroupItemId = $sql->GetId(SqlGetStockId($strNewSymbol));
	$fUshkd = SqlGetUshkd();
	DebugVal($fUshkd);
    if ($result = $sql->GetAllStockTransaction()) 
    {
        while ($record = mysqli_fetch_assoc($result)) 
        {
        	if ($strGroupItemId == $record['groupitem_id'])
        	{
//        		DebugPrint($record);
//        		$sql->trans_sql->Update($record['id'], $strNewGroupItemId, $record['quantity'], $record['price'], $record['fees'], $record['remark'].$strSymbol);
				$strQuantity = strval($iRatio * intval($record['quantity']));
				$strPrice = strval(floatval($record['price']) * $fUshkd / $iRatio);
				$strFees = strval(floatval($record['fees']) * $fUshkd);
        		$sql->trans_sql->Update($record['id'], $strNewGroupItemId, $strQuantity, $strPrice, $strFees, $record['remark'].$strSymbol);
        	}
        }
        mysqli_free_result($result);
    }
   	UpdateStockGroupItem($strGroupId, $strGroupItemId);
}
*/

function TestConvertTables()
{
	$amount_sql = new GroupItemAmountSql();
	$sql = new TableSql('fundpurchase');
   	if ($result = $sql->GetData())
   	{
   		while ($record = mysqli_fetch_assoc($result)) 
   		{
   			if ($strGroupItemId = SqlGetMyStockGroupItemId($record['member_id'], $record['stock_id']))
   			{
				$amount_sql->WriteString($strGroupItemId, $record['amount']);
			}
    	}
   		mysqli_free_result($result);
    }
}

function DebugLogFile()
{
    $strFileName = UrlGetRootDir().'logs/scripts.log';
    clearstatcache();
	if (file_exists($strFileName))
	{
		DebugString(file_get_contents($strFileName));
		unlink($strFileName);
	}
}

function DebugClearPath($strSection)
{
    $strPath = DebugGetPath($strSection);
    $hDir = opendir($strPath);
    while ($strFileName = readdir($hDir))
    {
    	if ($strFileName != '.' && $strFileName != '..')
    	{
    		$strPathName = $strPath.'/'.$strFileName;
    		if (!is_dir($strPathName)) 
    		{
    			unlink($strPathName);
    		}
    		else 
    		{
    			DebugString('Unexpected subdir: '.$strPathName); 
    		}
    	}
    }
	closedir($hDir);
}

	$acct = new Account();
	if ($acct->AllowCurl() == false)		die('Crawler not allowed on this page');

    echo GetContentType();

	file_put_contents(DebugGetFile(), DEBUG_UTF8_BOM.'Start debug:'.PHP_EOL);
//	DebugString($_SERVER['DOCUMENT_ROOT']);
	DebugString(UrlGetRootDir());
	DebugString('PHP version: '.phpversion());
	DebugLogFile();
	echo strval(rand()).' Hello, world!<br />';
	
	DebugClearPath('csv');
	DebugClearPath('image');

//	$sql = GetStockSql();
//	$sql->AlterTable('INDEX ( `name` )');
	
    $his_sql = GetStockHistorySql();
    $iCount = $his_sql->DeleteClose();
	if ($iCount > 0)	DebugVal($iCount, 'Zero close data');

//    $iCount = $his_sql->DeleteInvalidDate();		// this can be very slow!
//	if ($iCount > 0)	DebugVal($iCount, 'Invalid or older date'); 
	
//	TestModifyTransactions('1376', 'UWT', 'USO');
//	TestModifyTransactions('1831', 'CHU', '00762', 10);
//	TestModifyTransactions('160', 'SNP', '00386', 100);

//	TestConvertTables();
	
	phpinfo();
?>
