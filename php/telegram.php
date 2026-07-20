<?php
require('_tgprivate.php');
require_once('stockbot.php');
require_once('stockdataarray.php');
require_once('tutorial/iprules.php');

// 电报公共模板, 返回输入信息
const TG_DEBUG_VER = '版本054';
const BOT_EOL = "\r\n";
const MAX_BOT_MSG_LEN = 2048;

const TG_API_URL = 'https://api.telegram.org/bot'.TG_TOKEN.'/';
// const TG_ADMIN_CHAT_ID = '992671436';		// @sz152
// const TG_CAST_CHAT_ID = '-1001346320717';	// @palmmicrochan

const CONTACT_EMAIL = ', 请联系: '.ADMIN_EMAIL;

function _inBlackList($strIp)
{
	$ar = ['66.90.98.35',
		   '203.10.99.42'];
	foreach ($ar as $str)
	{
		if (isIpInSubnetAuto($strIp, $str))		return true;
	}
	return false;
}

class TelegramCallback
{
	function SetCallback()
	{
		$strUrl = TG_API_URL.'setWebhook?url='.UrlGetServer().'/php/telegram.php';
		if ($str = url_get_contents($strUrl))
		{
			echo $str;
		}
	}

	private function _directReply($method, $parameters) 
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
		$this->_directReply('sendMessage', ['chat_id' => $strChatId, 'reply_to_message_id' => $strMessageId, 'text' => $text]);
	}
	
	private function _sendText($strText, $strChatId) 
	{
        url_get_contents(TG_API_URL.'sendMessage?text='.urlencode($strText).'&chat_id='.$strChatId);        //valid signature , option
	}

	public function OnText($strText, $strMessageId, $strChatId)
    {
		$this->_sendText($strText, $strChatId);
		// $this->_sendText($strText, TG_ADMIN_CHAT_ID);
		// $this->_sendText($strText, TG_CAST_CHAT_ID);
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
					$str = "$strIp API访问太频繁".CONTACT_EMAIL;
				}
				else if ($strToken == WECHAT_QMT_KEY)
				{
					$str = GetStockDataArray($strText);
				}
				else if ($strToken == WECHAT_ROT_KEY)
				{
					$str = GetStockDataArray($strText, [...QdiiGetQqqMatchArray(),
														...QdiiGetSpyMatchArray(),
														...QdiiGetXopSymbolArray(),
														...QdiiGetXbiSymbolArray()]);
				}
				else
				{
					$str = "无效token: $strToken";
					DebugString(__CLASS__.__FUNCTION__.$str);
					$str .= CONTACT_EMAIL;
				}
	        	$this->ReplyText($str, $strMessageId, $strChatId);
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
				$str = "未授权IP: $strIp, 请检查调用时是否带上了TOKEN参数。";
				DebugString(__CLASS__.__FUNCTION__.$str);
	        	$this->ReplyText($str.CONTACT_EMAIL, $strMessageId, $strChatId);
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
        if ($str = StockBotGetStr($strText, TG_DEBUG_VER))	$str .= TG_DEBUG_VER; 
        else												$str = "未知查询: $strText";
       	$this->ReplyText($str, $strMessageId, $strChatId);
    }
}

    $acct = new TelegramStock();
    $acct->Run();
	// $acct->SetCallback();
