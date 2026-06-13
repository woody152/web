<?php
require('_tgprivate.php');
require_once('stockbot.php');
require_once('stockdataarray.php');
require_once('tutorial/iprules.php');

// 电报公共模板, 返回输入信息
const TG_DEBUG_VER = '版本051';
const BOT_EOL = "\r\n";
const MAX_BOT_MSG_LEN = 2048;

const TG_API_URL = 'https://api.telegram.org/bot'.TG_TOKEN.'/';
const TG_ADMIN_CHAT_ID = '992671436';		// @sz152
const TG_CAST_CHAT_ID = '-1001346320717';	// @palmmicrochan

function _inBlackList($strIp)
{
	$ar = ['36.143.159.189', '66.90.98.35', '203.10.99.42'];
	foreach ($ar as $str)
	{
		if (isIpInSubnetAuto($strIp, $str))		return true;
	}
	return false;
}

class TelegramCallback
{
    function GetVersion()
    {
    	return TG_DEBUG_VER;
    }
    
	function SetCallback()
	{
		$strUrl = TG_API_URL.'setWebhook?url='.UrlGetServer().'/php/telegram.php';
		if ($str = url_get_contents($strUrl))
		{
			echo $str;
		}
	}

	function DirectReply($method, $parameters) 
	{
		if (!is_string($method))			return false; 
		if (!$parameters) 		    		$parameters = [];
		else if (!is_array($parameters))	return false;

		$parameters['method'] = $method;
		$payload = json_encode($parameters);
		header('Content-Type: application/json');
		header('Content-Length: '.strlen($payload));
		echo $payload;
		return true;
	}

	function ReplyText($text, $strMessageId, $strChatId) 
	{
		$this->DirectReply('sendMessage', ['chat_id' => $strChatId, 'reply_to_message_id' => $strMessageId, 'text' => $text]);
	}
	
	private function _sendText($strText, $strChatId) 
	{
        url_get_contents(TG_API_URL.'sendMessage?text='.urlencode($strText).'&chat_id='.$strChatId);        //valid signature , option
	}

	function Debug($strDebug)
	{
		$this->_sendText($strDebug, TG_ADMIN_CHAT_ID);
		// $this->_sendText($strDebug, TG_CAST_CHAT_ID);
	}
	
    public function OnText($strText, $strMessageId, $strChatId)
    {
		$this->_sendText($strText, $strChatId);
    }

	private function _processMessage($message) 
	{	// process incoming message
		if (isset($message['message_id']))	$strMessageId = $message['message_id'];
		else								return;

		if (isset($message['chat']))		$strChatId = $message['chat']['id'];
		else								return;

		if (isset($message['text'])) 
		{	// incoming text message
			$strText = $message['text'];
			$strIp = LogBotVisit(TABLE_TELEGRAM_BOT, $strText, $strChatId);
			if ($strToken = UrlGetQueryValue('token'))
			{
				if (_inBlackList($strIp))
				{
		        	$this->ReplyText($strIp.' API访问太频繁', $strMessageId, $strChatId);
					return;
				}
				if ($strToken == TG_TOKEN || $strToken == WECHAT_QMT_KEY)
				{
					$this->ReplyText(GetStockDataArray($strText), $strMessageId, $strChatId);
				}
				else if ($strToken == WECHAT_ROT_KEY)
				{
					$this->ReplyText(GetStockDataArray($strText, [...QdiiGetQqqMatchArray(),
																  ...QdiiGetSpyMatchArray(),
																  ...QdiiGetXopSymbolArray(),
																  ...QdiiGetXbiSymbolArray()]), $strMessageId, $strChatId);
				}
				else
				{
					$str = "无效token: $strToken";
					$this->Debug($str);
					DebugString(__CLASS__.__FUNCTION__.$str);
				}
				return;
			}
			else if (str_starts_with($strText, '/'))
			{
				$strText = trim(ltrim($strText, '/'));
				switch ($strText)
				{
				case 'start':
					// apiRequestJson("sendMessage", array('chat_id' => $strChatId, "text" => 'Hello', 'reply_markup' => array('keyboard' => array(array('Hello', 'Hi')), 'one_time_keyboard' => true, 'resize_keyboard' => true)));
					return;
				
				case 'stop':	// stop now
					return;
				}
			} 
			if ($strIp != '91.108.5.6')
			{
				$str = "未授权IP: $strIp";
        		$this->Debug($str);
				DebugString(__CLASS__.__FUNCTION__.$str);
				return;
			}
			$this->OnText($strText, $strMessageId, $strChatId);
		}
		else 
		{
			// apiRequest("sendMessage", array('chat_id' => $strChatId, "text" => 'I understand only text messages'));
			// $this->_sendText('只能回复文本消息', $strChatId);
		}
	}		

	public function Run()
    {
    	$content = file_get_contents('php://input');
    	if ($update = json_decode($content, true))
    	{
			// DebugPrint($update);
    		if (isset($update['message'])) 
    		{
    			$this->_processMessage($update['message']);
    		}
    	}
    }
}

class TelegramStock extends TelegramCallback
{
    public function __construct() 
    {
    	SqlConnectDatabase();
    }

    public function OnText($strText, $strMessageId, $strChatId)
    {
    	$strVersion = $this->GetVersion();
        if ($str = StockBotGetStr($strText, $strVersion))
        {
			$str .= $strVersion; 
        	$this->ReplyText($str, $strMessageId, $strChatId);
        }
        else
        {
        	$this->Debug("未知查询: $strText");
        }
    }
}

    $acct = new TelegramStock();
    $acct->Run();
	// $acct->SetCallback();
