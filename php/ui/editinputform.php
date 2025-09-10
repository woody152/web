<?php
define('EDIT_INPUT_NAME', 'input');

function GetEditInput()
{
    if (isset($_POST['clear']))
	{
		unset($_POST['clear']);
		//DebugString(__FUNCTION__.' unset clear');
		return '';
	}
	else if (isset($_POST['submit']) && isset($_POST[EDIT_INPUT_NAME]))
	{
		unset($_POST['submit']);
		//DebugString(__FUNCTION__.' unset submit');
		return SqlCleanString($_POST[EDIT_INPUT_NAME]);
	}
	return false;
}

function EchoEditInputForm($strPage, $strInput = '', $bChinese = true)
{
    $strSubmit =  $bChinese ? '提交' : 'Submit';
    $strClear =  $bChinese ? '清除' : 'Clear';
    $strName = EDIT_INPUT_NAME;
	$strCur = UrlGetCur();
	$strPage = GetFontElement($strPage, 'olive');
	echo <<< END
	<form id="inputForm" name="inputForm" method="post" action="$strCur">
        <div>
		<p>$strPage
	        <br /><input name="$strName" value="$strInput" type="text" style="width:630px;" maxlength="1024" class="textfield" id="$strName" />
	        <br /><input type="submit" name="submit" value="$strSubmit" /> <input type="submit" name="clear" value="$strClear" />
	    </p>
        </div>
    </form>
END;
}

?>
