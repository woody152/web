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
          'msg': {}
         }
    return ar


class Palmmicro:
    def __init__(self):
        self.api = None
        self.iTimer = 0
        self.bNewSinaData = False
        self.usdcny_stock = None
        self.ag0_stock = None
        self.arStock = {}
        self.arDebug = {}
        self.arSendMsg = {}
        self.arSendMsg['telegram'] = GetSendMsgArray(WECHAT_KEY)
        for strSymbol, strKey in arSymbolKey.items():
            self.arSendMsg[strSymbol] = GetSendMsgArray(strKey)

    @staticmethod
    def __updateSina(stock, strLine):
        if stock != None:
            stock.Update(strLine)
        else:
            stock = SinaStock(strLine)
        return stock

    def _fetchSinaData(self, strSymbols):
        arLine = SinaStock.FetchData('fx_susdcny,nf_AG0,' + strSymbols.lower())
        if arLine:
            self.bNewSinaData = True
            self.usdcny_stock = self.__updateSina(self.usdcny_stock, arLine[0])
            self.ag0_stock = self.__updateSina(self.ag0_stock, arLine[1])
            arSymbol = strSymbols.split(',')
            iIndex = 2
            for strSymbol in arSymbol:
                self.arStock[strSymbol] = self.__updateSina(self.arStock.get(strSymbol), arLine[iIndex])
                iIndex += 1
                        
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
        
    def _getMktDebugString(self, strSymbol, stock, iSize, strType):
        (strRealSymbol, fPrice), = stock.GetSymbolPrice(strType).items()
        strDebug = strSymbol + ' ' + str(iSize)
        if strRealSymbol == strSymbol:
            strDebug += '@' + str(fPrice)
        return strDebug
    
    def _getSymDebugString(self, stock, iSize, strType, strMktType):
        return get_display(strMktType) + ' ' + self._getMktDebugString(stock.GetSymbol(), stock, iSize, strType) + ' | ' + get_display(strType) + ' '

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
            #if group == 'telegram':
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

    def _checkNewSinaData(self):
        if self.bNewSinaData == True:
            self.bNewSinaData = False
            return True
        return False

    def _debugPriceAndSize(self, strMktSymbol, stock, strType, strDebug, iSize, iMktSize, fEst):
        (strSymbol, fPrice), = stock.GetSymbolPrice(strType).items()
        fRatio = fPrice / fEst - 1.0
        if iMktSize > 0:
            strDebug = str(round(fRatio * 100.0, 2)) + '% | ' + strDebug
            strMsgType = strSymbol + strMktSymbol + strType
            if strMsgType not in self.arDebug or self.arDebug[strMsgType] != strDebug:
                self.arDebug[strMsgType] = strDebug
                if (fRatio < -0.001 and strType == 'SELL') or (fRatio > 0.001 and strType == 'BUY'):
                    print(f"{strDebug} | 对冲值:{iSize / iMktSize:.0f}")
                if iMktSize >= 100 and ((fRatio < -0.01 and strType == 'SELL') or (fRatio > 0.005 and strType == 'BUY')):
                    self._sendMsg(strDebug, strMsgType)
                self._sendMsg(strDebug, strMsgType, strSymbol)

    def _calcCalibrationArbitrage(self, mkt_stock, strMktSymbol, strMktType, strSymbol, strType):
        stock = self.arStock.get(strSymbol)
        if stock:
            arQuantity = self.api.CalcQuantity(strSymbol, stock.GetSymbolSize(strType) | mkt_stock.GetSymbolSize(strMktType))
            iSize = arQuantity[strSymbol]
            if iSize > 0:
                iMktSize = arQuantity[strMktSymbol]
                strDebug = self._getSymDebugString(stock, iSize, strType, strMktType) + self._getMktDebugString(strMktSymbol, mkt_stock, iMktSize, strMktType)
                arSrc = mkt_stock.GetSymbolPrice(strMktType)
                if PalmmicroStock.IsLOF(strSymbol) == False:
                    if self.usdcny_stock:
                        arSrc |= self.usdcny_stock.GetSymbolPrice('LAST')
                self._debugPriceAndSize(strMktSymbol, stock, strType, strDebug, iSize, iMktSize, self.api.EstNetValue(strSymbol, arSrc))
        
    def _calcHoldingArbitrage(self, arMkt, mkt_stock, strMktSymbol, strMktType, strSymbol, strType):
        stock = self.arStock.get(strSymbol)
        if stock:
            arSrcPrice = mkt_stock.GetSymbolPrice(strMktType)
            arSrcQuantity = mkt_stock.GetSymbolSize(strMktType)
            for other_stock in arMkt.values():
                strOtherSymbol = other_stock.GetSymbol()
                if strOtherSymbol != strMktSymbol and self.api.IsHoldingSymbol(strSymbol, strOtherSymbol):
                    if other_stock.HasData(strMktType):
                        arSrcPrice |= other_stock.GetSymbolPrice(strMktType)
                        arSrcQuantity |= other_stock.GetSymbolSize(strMktType)
            arQuantity = self.api.CalcQuantity(strSymbol, stock.GetSymbolSize(strType) | arSrcQuantity)
            iSize = arQuantity[strSymbol]
            if iSize > 0:
                iTotalSize = 0
                bDisplayTotalQuantity = False
                strAnd = ' 和 '
                strDebug = self._getSymDebugString(stock, iSize, strType, strMktType)
                for strHoldingSymbol in self.api.GetHoldingSymbols(strSymbol):
                    strRealSymbol = PalmmicroStock.ConvertYahooNetValueSymbol(strHoldingSymbol)
                    if strRealSymbol != strHoldingSymbol:
                        bDisplayTotalQuantity = True
                    for all_stock in arMkt.values():
                        if all_stock.GetSymbol() == strRealSymbol:
                            iHoldingSize = arQuantity[strHoldingSymbol]
                            if iHoldingSize > 0:
                                iTotalSize += iHoldingSize
                                strDebug += self._getMktDebugString(strHoldingSymbol, all_stock, iHoldingSize, strMktType) + strAnd
                            break
                strDebug = strDebug.rstrip(strAnd)
                if bDisplayTotalQuantity == True:
                    strDebug += ' 共' + str(iTotalSize)
                self._debugPriceAndSize(strMktSymbol, stock, strType, strDebug, iSize, iTotalSize, self.api.EstNetValue(strSymbol, arSrcPrice))

    def _sendMktData(self, strType, strMktSymbol, strMktType, mkt_stock, arMkt):
        for strSymbol in self.api.get_config().keys():
            stock = self.arStock.get(strSymbol)
            if stock and stock.HasData(strType):
                strNextSymbol = self.api.GetNextSymbol(strSymbol)
                if strNextSymbol != False:
                    if strNextSymbol == strMktSymbol or strNextSymbol == self.api.GetNextSymbol(strMktSymbol) or strMktSymbol == self.api.GetNextSymbol(self.api.GetNextSymbol(strNextSymbol)):
                        self._calcCalibrationArbitrage(mkt_stock, strMktSymbol, strMktType, strSymbol, strType)
                else:
                    if self.api.IsHoldingSymbol(strSymbol, strMktSymbol):
                        self._calcHoldingArbitrage(arMkt, mkt_stock, strMktSymbol, strMktType, strSymbol, strType)
                        
    def _processPriceAndSize(self, mkt_stock, arMkt):
        strMktSymbol = mkt_stock.GetSymbol()
        for strType in ['SELL', 'BUY']:
            if strType == 'SELL':
                strMktType = 'BUY'
            else:
                strMktType = 'SELL'
            if mkt_stock.HasData(strMktType):
                self._sendMktData(strType, strMktSymbol, strMktType, mkt_stock, arMkt)
            else:
                print(strMktSymbol + '无' + get_display(strMktType) + '数据')
                
    def CheckPriceAndSize(self, mkt_stock, arMkt):
        self._fetchData()
        self._processPriceAndSize(mkt_stock, arMkt)
        if self._checkNewSinaData() == True:
            for other_stock in arMkt.values():
                if other_stock.GetSymbol() != mkt_stock.GetSymbol():
                    self._processPriceAndSize(other_stock, arMkt)
            self._processPriceAndSize(self.ag0_stock, arMkt)
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
