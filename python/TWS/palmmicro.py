import requests
import time

from palmmicroapi import PalmmicroAPI
from palmmicrostock import PalmmicroStock, SinaStock

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
          'count': 6,
          'timer': 0,
          'msg_SELL': '',
          'msg_BUY': '',
          'array_msg': []
         }
    return ar

def GetMktDataArray(strSymbol):
    ar = {'symbol': strSymbol,
          'LAST_price': None,
          'VWAP_price': None,
          'BUY_price': None,
          'SELL_price': None,
          'BUY_size': None,
          'SELL_size': None
         }
    return ar


class Palmmicro:
    def __init__(self):
        self.api = None
        self.iTimer = 0
        self.bNewSinaData = False
        self.usdcny_stock = False
        self.arStock = {}
        self.arDebug = {}
        self.arSendMsg = {}
        self.arSendMsg['telegram'] = GetSendMsgArray(WECHAT_KEY)
        for strSymbol, strKey in arSymbolKey.items():
            self.arSendMsg[strSymbol] = GetSendMsgArray(strKey)
        self.arAG0 = GetMktDataArray('nf_AG0')

    def _fetchSinaData(self, strSymbols):
        arLine = SinaStock.FetchData('fx_susdcny,nf_AG0,' + strSymbols.lower())
        if arLine:
            self.bNewSinaData = True
            strLine = arLine[0]
            if self.usdcny_stock:
                self.usdcny_stock.Update(strLine)
            else:
                self.usdcny_stock = SinaStock(strLine)
            arSymbol = strSymbols.split(',')
            iIndex = 2
            for strSymbol in arSymbol:
                strLine = arLine[iIndex]
                stock = self.arStock.get(strSymbol)
                if stock:
                    stock.Update(strLine)
                else:
                    self.arStock[strSymbol] = SinaStock(strLine)
                iIndex += 1
            iLen = len('var hq_str_')
            for strLine in arLine:
                if len(strLine) > iLen + len('="";'):
                    arItem = strLine.split(',')
                    strSymbol = strLine[iLen:].split('"')[0]
                    strSymbol = strSymbol.rstrip('=')
                    if strSymbol == 'nf_AG0':
                        self.arAG0['BUY_price'] = float(arItem[6])
                        self.arAG0['SELL_price'] = float(arItem[7])
                        self.arAG0['BUY_size'] = int(arItem[11])
                        self.arAG0['SELL_size'] = int(arItem[12])
            
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
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('_fetchPalmmicroData error:', e)

    def _fetchData(self):
        iCur = int(time.time())
        if iCur - self.iTimer >= 19:
            self.iTimer = iCur
            strSymbols = ','.join(arSymbolKey.keys())
            if self.api is None:
                self._fetchPalmmicroData(strSymbols)
            self._fetchSinaData(strSymbols)
        
    def _getMktDebugString(self, strHedgeSymbol, iSize, fPrice):
        return strHedgeSymbol + ' ' + str(iSize) + '@' + str(fPrice)
    
    def _getSymDebugString(self, strSymbol, iSize, strType, strMktType):
        fPrice = self.arStock[strSymbol].get_value(strType + '_price')
        return get_display(strMktType) + ' ' + self._getMktDebugString(strSymbol, iSize, fPrice) + ' | ' + get_display(strType) + ' '

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
        str = GetBeijingTimeDisplay() + ' | '
        arMsg = self.arSendMsg[group]['array_msg']
        if len(arMsg) > 0:
            str += '\n\n'.join(arMsg)
        return str
        
    def __send_msg(self, group):
        str = self.__convert_array_msg(group)
        self.SendWechatMsg(str, group)
        #if group == 'telegram':
            #self.SendTelegramMsg(str)
        self.arSendMsg[group]['array_msg'].clear()

    def _sendMsg(self, strMsg, group='telegram'):
        arMsg = self.arSendMsg[group]['array_msg']
        if strMsg in arMsg:
            print('duplicated message in group: ', group)
        else:
            if len(self.__convert_array_msg(group).encode('utf-8')) + len(strMsg.encode('utf-8')) < 2046:
                arMsg.append(strMsg)
                if self.IsFree(group):
                    self.__send_msg(group)
            else:
                print('too many message in group: ', group)

    def _sendSymbolMsg(self, strMsg, strType, strSymbol):
        if strSymbol in self.arSendMsg:
            ar = self.arSendMsg[strSymbol]
            ar['array_msg'].clear()
            ar['msg_' + strType] = strMsg.replace(' ' + strSymbol, '')
            for strLoop in ['SELL', 'BUY']:
                str = ar['msg_' + strLoop]
                if str != '':
                    if strLoop != strType:
                        str += ' | 延迟'
                    ar['array_msg'].append(str)
            if self.IsFree(strSymbol):
                self.__send_msg(strSymbol)

    def _sendOldMsg(self):
        for group, value in self.arSendMsg.items():
            if self.IsFree(group):
                if len(value['array_msg']) > 0:
                    self.__send_msg(group)

    def _checkNewSinaData(self):
        if self.bNewSinaData == True:
            self.bNewSinaData = False
            return True
        return False

    def _debugPriceAndSize(self, strSymbol, strType, strDebug, iSize, iMktSize, fRatio):
        fRatio -= 1.0
        if iMktSize > 0:
            strDebug = str(round(fRatio * 100.0, 2)) + '% | ' + strDebug
            strSymbolType = strSymbol + strType
            if strSymbolType not in self.arDebug or self.arDebug[strSymbolType] != strDebug:
                self.arDebug[strSymbolType] = strDebug
                if (fRatio < -0.001 and strType == 'SELL') or (fRatio > 0.001 and strType == 'BUY'):
                    print(f"{strDebug} | 对冲值:{iSize / iMktSize:.0f}")
                if iMktSize >= 100 and ((fRatio < -0.01 and strType == 'SELL') or (fRatio > 0.005 and strType == 'BUY')):
                    self._sendMsg(strDebug)
                self._sendSymbolMsg(strDebug, strType, strSymbol)

    def _calcCalibrationArbitrage(self, arMktData, strMktSymbol, strMktType, strSymbol, strType):
        stock = self.arStock.get(strSymbol)
        if stock:
            arQuantity = self.api.CalcQuantity(strSymbol, stock.GetSize(strType) | {strMktSymbol: arMktData[strMktType + '_size']})
            iSize = arQuantity[strSymbol]
            if iSize > 0:
                iMktSize = arQuantity[strMktSymbol]
                fMktPrice = arMktData[strMktType + '_price']
                strDebug = self._getSymDebugString(strSymbol, iSize, strType, strMktType) + self._getMktDebugString(strMktSymbol, iMktSize, fMktPrice)
                arSrc = {strMktSymbol: fMktPrice}
                if PalmmicroStock.IsLOF(strSymbol) == False:
                    if self.usdcny_stock:
                        arSrc |= self.usdcny_stock.GetPrice()
                self._debugPriceAndSize(strSymbol, strType, strDebug, iSize, iMktSize, stock.get_value(strType + '_price') / self.api.EstNetValue(strSymbol, arSrc))
        
    def _calcHoldingArbitrage(self, arMkt, arMktData, strMktSymbol, strMktType, strSymbol, strType):
        stock = self.arStock.get(strSymbol)
        if stock:
            strMktPriceType = strMktType + '_price'
            strMktSizeType = strMktType + '_size'
            arSrcPrice = {strMktSymbol: arMktData[strMktPriceType]}
            arSrcQuantity = {strMktSymbol: arMktData[strMktSizeType]}
            for arOtherMktData in arMkt.values():
                strOtherSymbol = arOtherMktData['symbol'] 
                if strOtherSymbol != strMktSymbol and self.api.IsHoldingSymbol(strSymbol, strOtherSymbol):
                    if all(arOtherMktData[attr] is not None for attr in [strMktPriceType, strMktSizeType]):
                        arSrcPrice |= {strOtherSymbol: arOtherMktData[strMktPriceType]}
                        arSrcQuantity |= {strOtherSymbol: arOtherMktData[strMktSizeType]}
            arQuantity = self.api.CalcQuantity(strSymbol, stock.GetSize(strType) | arSrcQuantity)
            iSize = arQuantity[strSymbol]
            if iSize > 0:
                iTotalSize = 0
                bDisplayTotalQuantity = False
                strAnd = ' 和 '
                strDebug = self._getSymDebugString(strSymbol, iSize, strType, strMktType)
                for strHoldingSymbol in self.api.GetHoldingSymbols(strSymbol):
                    strRealSymbol = PalmmicroStock.ConvertSymbol(strHoldingSymbol)
                    if strRealSymbol != strHoldingSymbol:
                        bDisplayTotalQuantity = True
                    for arAllMktData in arMkt.values():
                        if arAllMktData['symbol'] == strRealSymbol:
                            iHoldingSize = arQuantity[strHoldingSymbol]
                            iTotalSize += iHoldingSize
                            strDebug += self._getMktDebugString(strHoldingSymbol, iHoldingSize, arAllMktData[strMktPriceType]) + strAnd
                            break
                strDebug = strDebug.rstrip(strAnd)
                if bDisplayTotalQuantity == True:
                    strDebug += ' 共' + str(iTotalSize)
                self._debugPriceAndSize(strSymbol, strType, strDebug, iSize, iTotalSize, stock.get_value(strType + '_price') / self.api.EstNetValue(strSymbol, arSrcPrice))

    def _sendMktData(self, strType, strMktSymbol, strMktType, arMktData, arMkt):
        for strSymbol in self.api.get_config().keys():
            stock = self.arStock.get(strSymbol)
            if stock and stock.get_value(strType + '_size') != None:
                strNextSymbol = self.api.GetNextSymbol(strSymbol)
                if strNextSymbol != False:
                    if strNextSymbol == strMktSymbol:
                        self._calcCalibrationArbitrage(arMktData, strMktSymbol, strMktType, strSymbol, strType)
                else:
                    if self.api.IsHoldingSymbol(strSymbol, strMktSymbol):
                        self._calcHoldingArbitrage(arMkt, arMktData, strMktSymbol, strMktType, strSymbol, strType)
                        
    def _processPriceAndSize(self, arMktData, arMkt):
        strMktSymbol = arMktData['symbol']
        for strType in ['SELL', 'BUY']:
            if strType == 'SELL':
                strMktType = 'BUY'
            else:
                strMktType = 'SELL'
            if all(arMktData[attr] is not None for attr in [strMktType + '_price', strMktType + '_size']):
                self._sendMktData(strType, strMktSymbol, strMktType, arMktData, arMkt)
            else:
                print(strMktSymbol + '无' + get_display(strMktType) + '数据')
                
    def CheckPriceAndSize(self, arMktData, arMkt):
        self._fetchData()
        self._processPriceAndSize(arMktData, arMkt)
        if self._checkNewSinaData() == True:
            for arOtherMktData in arMkt.values():
                if arOtherMktData['symbol'] != arMktData['symbol']:
                    self._processPriceAndSize(arOtherMktData, arMkt)
            self._processPriceAndSize(self.arAG0, arMkt)
        self._sendOldMsg()


class Calibration:
    def __init__(self, strDisplay):
        self.strDisplay = strDisplay
        self.fPrice = None
        self.Reset()

    def Reset(self):
        self.fTotal = 0.0
        self.iCount = 0

    def SetPrice(self, fPrice):
        self.fPrice = fPrice

    def Calc(self, fPeerPrice):
        if self.fPrice != None:
            fRatio = fPeerPrice/self.fPrice
            self.fTotal += fRatio
            self.iCount += 1
            if self.iCount > 100:
                fAvg = round(self.fTotal/self.iCount, 4)
                print(self.strDisplay, 'last', round(fRatio, 4), 'avg', fAvg)
                self.Reset()
                return fAvg
        return 0.0
