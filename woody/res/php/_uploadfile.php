<?php
require_once('_stock.php');
require_once('_emptygroup.php');
require_once('_yahoohistorycsv.php');

class _UploadFileAccount extends SymbolAccount
{
	function _handleFile()
	{
    	if ($_FILES['file']['error'] > 0)
    	{
    		DebugString('Return Code: '.$_FILES['file']['error']);
    		return false;
    	}

   		DebugString('Upload: '.$_FILES['file']['name']);
    	DebugString('Type: '.$_FILES['file']['type']);
    	DebugString('Size: '.($_FILES['file']['size'] / 1024).' Kb');
    	DebugString('Temp file: '.$_FILES['file']['tmp_name']);

    	$strFileName = DebugGetPath('csv').'/'.$_FILES['file']['name'];
    	if (file_exists($strFileName))
    	{
    		DebugString($_FILES['file']['name'].' already exists.');
    	}
    	else
    	{
    		move_uploaded_file($_FILES['file']['tmp_name'], $strFileName);
    		DebugString('Stored in: '.$strFileName);
    	}
    	return $strFileName;
	}
	
	function _updateStockHistory($ref, $strFileName)
	{
		$strStockId = $ref->GetStockId();
		$csv = new _YahooHistoryCsvFile($strFileName, $strStockId);
		$csv->Read();

		DebugVal($csv->iTotal, 'Total');
		DebugVal($csv->iModified, 'Modified');
        $his_sql = GetStockHistorySql();
		$his_sql->DeleteByZeroVolume($strStockId);
		unlinkConfigFile($ref->GetSymbol());
	}
	
    public function Process($strLoginId)
    {
	    if ($ref = $this->GetSymbolRef())
	    {
	        if ($strFileName = $this->_handleFile())
	        {
	        	if (isset($_POST['submit']))
	        	{
	        		switch ($_POST['submit'])
	        		{
	        		case STOCK_HISTORY_UPDATE:
	        			$this->_updateStockHistory($ref, $strFileName);
	        			break;
	        			
	        		default:
	  					DebugString('Unknown file operation: '.$_POST['submit']);
	        			break;
	        		}
	        		unset($_POST['submit']);
	        	}
	        }
	    }
	}
}
