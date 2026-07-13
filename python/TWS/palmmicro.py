import dtale
import requests
import time

from palmmicroapi import PalmmicroAPI, PalmmicroDataFrame
from palmmicrostock import PalmmicroStock, SinaStock, TdxStock

from nyc_time import GetBeijingTimeDisplay

from _tgprivate import TG_TOKEN
from _tgprivate import WECHAT_KEY
from _tgprivate import arSymbolKey  # QQQ = ['SH513100', 'SH513110', 'SH513390', 'SH513870', 'SZ159501', 'SZ159513', 'SZ159632', 'SZ159659', 'SZ159660', 'SZ159696', 'SZ159941']
        
def get_display(strType):
    if strType == 'SELL':
        return '卖出'
    elif strType == 'BUY':
        return '买入'
    return ''

def GetSendMsgArray(strKey):
    ar = {'key': strKey,
          'count': 4,
          'timer': 0,
          'msg': {}
         }
    return ar


class Palmmicro:
    d_column_formats = {'Percent': {'fmt': '0.00%'}, 'SymbolPrice': {'fmt': '0.000'}}

    def __init__(self):
        self.api = None
        self.pdf = None
        self.iTimer = 0
        self.usdcny_stock = None
        self.ag0_stock = None
        self.arDebug = {}
        self.arSendMsg = {}
        self.arSendMsg['telegram'] = GetSendMsgArray(WECHAT_KEY)
        for strSymbol, strKey in arSymbolKey.items():
            self.arSendMsg[strSymbol] = GetSendMsgArray(strKey)
        self.arStock = TdxStock.Init()
        self._fetchPalmmicroData(','.join(arSymbolKey.keys()))
        self.d = dtale.show(self.pdf.GetDataFrame(), host = '127.0.0.1', port = 40005, column_formats = self.d_column_formats)
        self.d.open_browser()

    def _getTelegramChatId(self):
        return 992671436

    def _fetchPalmmicroData(self, strSymbols):
        iChatId = self._getTelegramChatId()
        ar = {'update_id': 886050244,
              'message': {'message_id': 6620,
                          'from': {'id': iChatId,
                                   'is_bot': False,
                                   'first_name': 'woody',
                                   'username': 'palmmicro',
                                   'language_code': 'zh-Hans'
                                  },
                          'chat': {'id': iChatId,
                                   'first_name': 'woody',
                                   'username': 'palmmicro',
                                   'type': 'private'
                                  },
                          'date': 0,
                          'text': ''
                         }
             }
        arMessage = ar['message']
        arMessage['date'] = int(time.time())
        arMessage['text'] = strSymbols
        strUrl = 'https://palmmicro.com/php/telegram.php?token=' + TG_TOKEN
        try:
            response = requests.post(strUrl, json=ar, headers={'Content-Type': 'application/json'})
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                response_data = response.json()  # Parse the JSON response data
                #print('Response data:', response_data)
                self.api = PalmmicroAPI(response_data['text'])
                self.pdf = PalmmicroDataFrame(self.api)
                #self.api.set_multiplier('hf_ES', 1)
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('_fetchPalmmicroData error:', e)

    def _fetchData(self):
        iCur = int(time.time())
        if iCur - self.iTimer >= 19:
            self.iTimer = iCur
            arLine = SinaStock.FetchData('fx_susdcny,nf_AG0')
            if arLine:
                self.usdcny_stock = SinaStock.UpdateStock(self.usdcny_stock, arLine[0])
                self.ag0_stock = SinaStock.UpdateStock(self.ag0_stock, arLine[1])
        
    def IsFree(self, group):
        ar = self.arSendMsg[group]
        iCur = int(time.time())
        if iCur - ar['timer'] < ar['count']:
            return False
        ar['timer'] = iCur
        return True

    def SendTelegramMsg(self, strMsg):
        url = 'https://api.telegram.org/bot' + TG_TOKEN + '/sendMessage?text=' + strMsg + '&chat_id=-1001346320717'
        try:
            response = requests.get(url)
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                data = response.json()  # Assuming the response is in JSON format
                #print(data)
            else:
                print('Failed to retrieve data. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('SendTelegramMsg Error occurred:', e)

    def SendWechatMsg(self, strMsg, group, strType = 'text'):
        url = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' + self.arSendMsg[group]['key']
        arWechatMsg = {'msgtype': strType,  
                       strType: {'content': ''
                                }
                      }
        arText = arWechatMsg[strType]
        arText['content'] = strMsg
        try:
            response = requests.post(url, json=arWechatMsg, headers={'Content-Type': 'application/json'})
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                response_data = response.json()  # Parse the JSON response data
                #print('Response data:', response_data)
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('SendWechatMsg Error occurred:', e)

    def __convert_array_msg(self, group):
        arAll = []
        strAll = GetBeijingTimeDisplay() + ' | '
        iTotal = len(strAll.encode('utf-8'))
        for strMsg in self.arSendMsg[group]['msg'].values():
            iLen = len(strMsg.encode('utf-8'))
            if iTotal + iLen > 2046:
                print('too many message in group: ', group)
                break
            elif strMsg not in arAll:
                arAll.append(strMsg)
                strAll += strMsg + '\n\n'
                iTotal += iLen + 2
        return strAll[:-2]
        
    def __send_msg(self, group):
        ar = self.arSendMsg[group]['msg']
        if len(ar) > 0:
            strMsg = self.__convert_array_msg(group)
            self.SendWechatMsg(strMsg, group)
            if group == 'telegram':
                pass
                #self.SendTelegramMsg(strMsg)
            ar.clear()

    def _sendMsg(self, strMsg, strType, group='telegram'):
        if group == 'telegram' or group in self.arSendMsg:
            self.arSendMsg[group]['msg'][strType] = strMsg.replace(' ' + group, '')
            if self.IsFree(group):
                self.__send_msg(group)

    def _sendOldMsg(self):
        for group, value in self.arSendMsg.items():
            if self.IsFree(group):
                if len(value['msg']) > 0:
                    self.__send_msg(group)

    def _processPriceAndSize(self, arMkt, mkt_stock, strMktType, stock, strType, strSymbol):
        strMktSymbol = mkt_stock.GetSymbol()
        if mkt_stock.HasData(strMktType):
            if stock.IsUpdated(strType) or mkt_stock.IsUpdated(strType):
                if self.pdf.ProcessPriceAndSize(arMkt, mkt_stock, strMktType, strMktSymbol, stock, strType, strSymbol, self.usdcny_stock, GetBeijingTimeDisplay()):
                    strMsgType = strSymbol + strMktSymbol + strType
                    ar = self.pdf.GetData(strSymbol, strMktSymbol, strType)
                    fRatio = ar['Percent']
                    strDebug = strDebug = str(round(fRatio * 100.0, 2)) + '% | '
                    iSize = ar['SymbolSize']
                    strDebug += get_display(strMktType) + ' ' + self.pdf.CombineSizeAndPrice(strSymbol, stock, iSize, strType) + ' '
                    iMktSize = ar['HedgeSize']
                    strDebug += get_display(strType) + ' ' + self.pdf.CombineSizeAndPrice(strMktSymbol, mkt_stock, iMktSize, strMktType)
                    if ar['Note'] != '':
                        strDebug += ' # ' + ar['Note']
                    #if (fRatio < -0.001 and strType == 'SELL') or (fRatio > 0.001 and strType == 'BUY'):
                        #print(f"{strDebug} | 对冲值:{iSize / iMktSize:.0f}")
                    if iMktSize >= 100 and ((fRatio < -0.01 and strType == 'SELL') or (fRatio > 0.005 and strType == 'BUY')):
                        self._sendMsg(strDebug, strMsgType)
                    self._sendMsg(strDebug, strMsgType, strSymbol)
                    return True
        else:
            print(strMktSymbol + '无' + get_display(strMktType) + '数据')
        return False
       
    """
    def CheckPriceAndSize(self, arMkt, mkt_stock, strMktType):
        self._fetchData()
        bChanged = False
        strType = mkt_stock.GetPeerType(strMktType)
        for strSymbol in self.api.get_config().keys():
            stock = self.arStock.get(strSymbol)
            if stock and stock.HasData(strType):
                bChanged |= self._processPriceAndSize(arMkt, mkt_stock, strMktType, stock, strType, strSymbol)
                if stock.IsUpdated(strType):
                    for other_stock in arMkt.values():
                        if other_stock.GetSymbol() != mkt_stock.GetSymbol():
                            bChanged |= self._processPriceAndSize(arMkt, other_stock, strMktType, stock, strType, strSymbol)
                if stock.IsUpdated(strType) or self.ag0_stock.IsUpdated(strMktType):
                    bChanged |= self._processPriceAndSize(arMkt, self.ag0_stock, strMktType, stock, strType, strSymbol)
                    self.ag0_stock.SetUpdated(strMktType, False)
                stock.SetUpdated(strType, False)
        if bChanged:
            self.d.data = self.pdf.GetDataFrame()
            self.d.update_settings(column_formats = self.d_column_formats)
        self._sendOldMsg()
    """

    def HandleData(self, arMkt):
        self._fetchData()
        bChanged = False
        for strSymbol in self.api.get_config().keys():
            stock = self.arStock.get(strSymbol)
            if stock:
                for strType in stock.GetTypeList():
                    if stock.HasData(strType):
                        strMktType = stock.GetPeerType(strType)
                        for mkt_stock in arMkt.values():
                            bChanged |= self._processPriceAndSize(arMkt, mkt_stock, strMktType, stock, strType, strSymbol)
                        bChanged |= self._processPriceAndSize(arMkt, self.ag0_stock, strMktType, stock, strType, strSymbol)
                        stock.SetUpdated(strType, False)
        for strMktType in PalmmicroStock.GetTypeList():
            for mkt_stock in arMkt.values():
                mkt_stock.SetUpdated(strMktType, False)
            self.ag0_stock.SetUpdated(strMktType, False)
        if bChanged:
            self.d.data = self.pdf.GetDataFrame()
            self.d.update_settings(column_formats = self.d_column_formats)
        self._sendOldMsg()
