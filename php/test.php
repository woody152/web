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

function extractStockData($text) {
    $results = [];
    
    // 更灵活的正则表达式，适应不同的格式变化
    $pattern = '/([^\d\s]+(?:\s+[^\d\s]+)*)\s+(?:高|低)[\d.%]+\s+(\d+)\.HK[^$]+?港元\s+([\d.]+)\s+\(美股折合\s+([\d.]+)\)[^$]+?([A-Z]+)\.US[^$]+?美元\s+([\d.]+)\s+\(港股折合\s+([\d.]+)\)/';
    
    preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $results[] = [
            'company' => trim($match[1]),
            'hk_code' => $match[2],
            'hk_price' => floatval($match[3]),
            'us_converted_price' => floatval($match[4]),
            'us_code' => $match[5],
            'us_price' => floatval($match[6]),
            'hk_converted_price' => floatval($match[7])
        ];
    }
    
    return $results;
}

// 示例数据
$text = "名稱 港股比美股價格3 代號 股價1 升跌(%)2 1個月升跌(%)2 3個月升跌(%)2      和黃醫藥 高0.94% 00013.HK  港元 23.700 (美股折合 23.480)  +1.369% -22.42% +7.97%    HCM.US  美元 15.060 (港股折合 15.201)  +1.551% -21.60% +8.66%    華住集團－Ｓ 高3.16% 01179.HK3月高  港元 29.640 (美股折合 28.733)  +5.330% +15.33% +5.11%    HTHT.US  美元 36.859 (港股折合 38.022)  -0.596% +13.94% +2.56%";

// 提取数据
$companiesData = extractStockData($text);

// 输出结果
if (!empty($companiesData)) {
    foreach ($companiesData as $data) {
        echo "公司名称: " . $data['company'] . "\n";
        echo "港股代码: " . $data['hk_code'] . ".HK\n";
        echo "港股价格: " . $data['hk_price'] . " 港元\n";
        echo "美股折算价格: " . $data['us_converted_price'] . " 港元\n";
        echo "美股代码: " . $data['us_code'] . ".US\n";
        echo "美股价格: " . $data['us_price'] . " 美元\n";
        echo "港股折算价格: " . $data['hk_converted_price'] . " 美元\n";
        
        // 计算价差百分比
        $hk_price_diff = abs($data['hk_price'] - $data['us_converted_price']) / $data['hk_price'] * 100;
        $us_price_diff = abs($data['us_price'] - $data['hk_converted_price']) / $data['us_price'] * 100;
        
        echo "港股与美股折算价差: " . number_format($hk_price_diff, 2) . "%\n";
        echo "美股与港股折算价差: " . number_format($us_price_diff, 2) . "%\n";
        
        // 计算隐含汇率
        $implied_rate_from_hk = $data['hk_price'] / $data['us_price'];
        
        echo "隐含汇率 (从港股计算): 1美元 = " . number_format($implied_rate_from_hk, 4) . " 港元\n";
        echo "----------------------------------------\n";
    }
} else {
    echo "未能从文本中提取数据。\n";
    echo "尝试使用更宽松的匹配模式...\n";
    
    // 尝试更宽松的匹配模式
    $fallback_pattern = '/([^\d\s]+(?:\s+[^\d\s]+)*)\s+[^$]+?(\d+)\.HK[^$]+?([\d.]+)[^$]+?美股折合\s+([\d.]+)[^$]+?([A-Z]+)\.US[^$]+?([\d.]+)[^$]+?港股折合\s+([\d.]+)/';
    
    preg_match_all($fallback_pattern, $text, $fallback_matches, PREG_SET_ORDER);
    
    if (!empty($fallback_matches)) {
        echo "使用备用模式找到 " . count($fallback_matches) . " 家公司:\n";
        
        foreach ($fallback_matches as $match) {
            echo "公司: " . trim($match[1]) . "\n";
            echo "港股代码: " . $match[2] . ".HK\n";
            echo "港股价格: " . $match[3] . "\n";
            echo "美股折算价格: " . $match[4] . "\n";
            echo "美股代码: " . $match[5] . ".US\n";
            echo "美股价格: " . $match[6] . "\n";
            echo "港股折算价格: " . $match[7] . "\n";
            echo "----------------------------------------\n";
        }
    } else {
        echo "备用模式也未能提取数据。\n";
    }
}
?>	