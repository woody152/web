import requests
import time

from palmmicroapi import convert_symbol
from palmmicroapi import PalmmicroAPI

from nyc_time import GetBeijingTimeDisplay

from _tgprivate import TG_TOKEN
from _tgprivate import WECHAT_KEY
from _tgprivate import WECHAT_SH501018_KEY
from _tgprivate import WECHAT_SH513350_KEY
from _tgprivate import WECHAT_SZ159502_KEY
from _tgprivate import WECHAT_SZ159518_KEY
from _tgprivate import WECHAT_SZ160719_KEY
from _tgprivate import WECHAT_SZ160723_KEY
from _tgprivate import WECHAT_SZ161116_KEY
from _tgprivate import WECHAT_SZ161125_KEY
from _tgprivate import WECHAT_SZ161127_KEY
from _tgprivate import WECHAT_SZ161129_KEY
from _tgprivate import WECHAT_SZ161130_KEY
from _tgprivate import WECHAT_SZ161226_KEY
from _tgprivate import WECHAT_SZ162411_KEY
from _tgprivate import WECHAT_SZ162415_KEY
from _tgprivate import WECHAT_SZ163208_KEY
from _tgprivate import WECHAT_SZ164701_KEY
from _tgprivate import WECHAT_SZ164824_KEY
from _tgprivate import WECHAT_SZ164906_KEY
from _tgprivate import WECHAT_SZ165513_KEY

def get_display(strType):
    if strType == 'SELL':
        return '卖出'
    elif strType == 'BUY':
        return '买入'
    return ''

def get_separate_display(strType):
    return ' | ' + get_display(strType) + ' '
"""
def convert_fake_symbol(symbol):
    if symbol.startswith('^'):
        symbol = symbol[1:]
        if '-' in symbol:
            symbol = symbol.split('-')[0]
    return symbol

def _get_floor_quantity(fQuantity):
    fQuantity /= 100.0
    return math.floor(fQuantity) * 100.0
"""
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
        self.fUSDCNY = 1.0;
        self.arDebug = {}
        self.arSendMsg = {}
        self.arSendMsg['telegram'] = GetSendMsgArray(WECHAT_KEY)
        self.arSendMsg['SH501018'] = GetSendMsgArray(WECHAT_SH501018_KEY)
        self.arSendMsg['SH513350'] = GetSendMsgArray(WECHAT_SH513350_KEY)
        self.arSendMsg['SZ159502'] = GetSendMsgArray(WECHAT_SZ159502_KEY)
        self.arSendMsg['SZ159518'] = GetSendMsgArray(WECHAT_SZ159518_KEY)
        self.arSendMsg['SZ160719'] = GetSendMsgArray(WECHAT_SZ160719_KEY)
        self.arSendMsg['SZ160723'] = GetSendMsgArray(WECHAT_SZ160723_KEY)
        self.arSendMsg['SZ161116'] = GetSendMsgArray(WECHAT_SZ161116_KEY)
        self.arSendMsg['SZ161125'] = GetSendMsgArray(WECHAT_SZ161125_KEY)
        self.arSendMsg['SZ161127'] = GetSendMsgArray(WECHAT_SZ161127_KEY)
        self.arSendMsg['SZ161129'] = GetSendMsgArray(WECHAT_SZ161129_KEY)
        self.arSendMsg['SZ161130'] = GetSendMsgArray(WECHAT_SZ161130_KEY)
        self.arSendMsg['SZ161226'] = GetSendMsgArray(WECHAT_SZ161226_KEY)
        self.arSendMsg['SZ162411'] = GetSendMsgArray(WECHAT_SZ162411_KEY)
        self.arSendMsg['SZ162415'] = GetSendMsgArray(WECHAT_SZ162415_KEY)
        self.arSendMsg['SZ163208'] = GetSendMsgArray(WECHAT_SZ163208_KEY)
        self.arSendMsg['SZ164701'] = GetSendMsgArray(WECHAT_SZ164701_KEY)
        self.arSendMsg['SZ164824'] = GetSendMsgArray(WECHAT_SZ164824_KEY)
        self.arSendMsg['SZ164906'] = GetSendMsgArray(WECHAT_SZ164906_KEY)
        self.arSendMsg['SZ165513'] = GetSendMsgArray(WECHAT_SZ165513_KEY)
        self.arAG0 = GetMktDataArray('nf_AG0')

    def _fetchSinaData(self, strSymbols):
        strUrl = f'http://hq.sinajs.cn/list=fx_susdcny,nf_AG0,{strSymbols.lower()}'
        try:
            response = requests.get(strUrl, headers={'Referer': 'https://finance.sina.com.cn'})
            if response.status_code == 200:
                self.bNewSinaData = True
                arLine = response.text.split("\n")
                iLen = len('var hq_str_')
                for strLine in arLine:
                    if len(strLine) > iLen + len('="";'):
                        arItem = strLine.split(',')
                        strSymbol = strLine[iLen:].split('"')[0]
                        strSymbol = strSymbol.rstrip('=')
                        if strSymbol == 'fx_susdcny':
                            self.fUSDCNY = float(arItem[8])
                        elif strSymbol == 'nf_AG0':
                            self.arAG0['BUY_price'] = float(arItem[6])
                            self.arAG0['SELL_price'] = float(arItem[7])
                            self.arAG0['BUY_size'] = int(arItem[11])
                            self.arAG0['SELL_size'] = int(arItem[12])
                        else:
                            strSymbol = strSymbol.upper()
                            arSymData = self.api.get_param(strSymbol)
                            if arSymData != None:
                                arSymData['BUY_price'] = float(arItem[6])
                                arSymData['SELL_price'] = float(arItem[7])
                                arSymData['BUY_size'] = int(arItem[10])
                                arSymData['SELL_size'] = int(arItem[20])
            else:
                print('Failed to send request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('_fetchSinaData error:', e)

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

    def _fetchData(self, arSymbol):
        iCur = int(time.time())
        if iCur - self.iTimer >= 19:
            self.iTimer = iCur
            strSymbols = ','.join(arSymbol)
            if self.api is None:
                self._fetchPalmmicroData(strSymbols)
            self._fetchSinaData(strSymbols)
        
    def _getMktDebugString(self, strHedgeSymbol, iSize, fPrice):
        return strHedgeSymbol + ' ' + str(iSize) + '@' + str(fPrice)
    
    def _getSymDebugString(self, strSymbol, iSize, strType, strMktType):
        arSymData = self.api.get_param(strSymbol)
        return get_display(strMktType) + ' ' + self._getMktDebugString(strSymbol, iSize, float(arSymData[strType + '_price']))
    """
    def _getCNY(self, arSymData):
        if 'CNY' in arSymData:
            return float(arSymData['CNY'])
        return self.fUSDCNY
    """        
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
        arMsg = self.arSendMsg[group]['array_msg']
        if len(arMsg) >= 0:
            #unique = set(arMsg)
            #str = '\n\n'.join(unique)
            str = '\n\n'.join(arMsg)
            return GetBeijingTimeDisplay() + ' | ' + str
        return ''

    def __send_msg(self, group):
        str = self.__convert_array_msg(group)
        self.SendWechatMsg(str, group)
        #if group == 'telegram':
            #self.SendTelegramMsg(str)
        self.arSendMsg[group]['array_msg'].clear()

    def _sendMsg(self, strMsg, strType, group='telegram'):
        if len(self.__convert_array_msg(group).encode('utf-8')) + len(strMsg.encode('utf-8')) < 2046:
            self.arSendMsg[group]['array_msg'].append(strMsg)
            if self.IsFree(group):
                self.__send_msg(group)
        else:
            print('too many message in group: ', group)

    def _sendSymbolMsg(self, strMsg, strType, strSymbol):
        if strSymbol in self.arSendMsg:
            ar = self.arSendMsg[strSymbol]
            ar['array_msg'].clear()
            ar['msg_' + strType] = strMsg.replace(' ' + strSymbol.rstrip('ETF'), '')
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

    def _debugPriceAndSize(self, arSymData, strSymbol, strType, strDebug):
        iSize = arSymData['quantity']
        if iSize >= 1:
            fRatio = arSymData['discount']
            strDebug = str(round(fRatio * 100.0, 2)) + '% | ' + strDebug
            strSymbolType = strSymbol + strType
            if strSymbolType not in self.arDebug or self.arDebug[strSymbolType] != strDebug:
                self.arDebug[strSymbolType] = strDebug
                if (fRatio < -0.001 and strType == 'SELL') or (fRatio > 0.001 and strType == 'BUY'):
                    print(strDebug)
                if iSize >= 100 and ((fRatio < -0.01 and strType == 'SELL') or (fRatio > 0.005 and strType == 'BUY')):
                    self._sendMsg(strDebug, strType)
                self._sendSymbolMsg(strDebug, strType, strSymbol)

    def _calcCalibrationArbitrage(self, arMktData, strMktSymbol, strMktType, arSymData, strSymbol, strType):
        fMktPrice = arMktData[strMktType + '_price']
        arSrc = {strMktSymbol: fMktPrice}
        if self.api.IsLOF(strSymbol) == False:
            arSrc |= {'CNY': self.fUSDCNY}
        arQuantity = self.api.CalcQuantity(strSymbol, {strSymbol: arSymData[strType + '_size'], strMktSymbol: arMktData[strMktType + '_size']})
        iSize = arQuantity[strSymbol]
        if iSize > 0:
            arSymData['discount'] = float(arSymData[strType + '_price']) / self.api.EstNetValue(strSymbol, arSrc) - 1.0
            iMktSize = arQuantity[strMktSymbol]
            arSymData['quantity'] = iMktSize
            strDebug = self._getSymDebugString(strSymbol, iSize, strType, strMktType)
            strDebug += get_separate_display(strType) + self._getMktDebugString(strMktSymbol, iMktSize, fMktPrice)
            return strDebug
        return False
    """    
    def _calcHoldingsArbitrage(self, arMkt, strMktSymbol, strMktType, iMktSize, arSymData, strSymbol, strType):
        fQuantity = _get_floor_quantity(float(arSymData[strType + '_size']))
        fNetValue = float(arSymData['netvalue'])
        fCNYholdings = float(arSymData['CNYholdings'])
        fPosition = float(arSymData['position'])
        fAmount = fQuantity * fNetValue / fCNYholdings
        if strSymbol != 'SZ164701':
            fMktAmount = float(arSymData['symbol_hedge'][strMktSymbol]['price']) * float(iMktSize)
            if fMktAmount < fAmount:
                fAmount = fMktAmount
                fQuantity = fAmount * fCNYholdings / fNetValue
                fQuantity = _get_floor_quantity(fQuantity)
                fAmount = fQuantity * fNetValue / fCNYholdings
        strDebug = get_separate_display(strType)
        iTotalQuantity = 0
        bDisplayTotalQuantity = False
        strAnd = ' 和 '
        fTotal = 0.0
        for strHoldingSymbol, arHoldingData in arSymData['symbol_hedge'].items():
            strRealSymbol = convert_fake_symbol(strHoldingSymbol)
            if strRealSymbol != strHoldingSymbol:
                bDisplayTotalQuantity = True
            for reqId, arMktData in arMkt.items():
                if arMktData['symbol'] == strRealSymbol:
                    fCurPrice = arMktData[strMktType + '_price']
                    if fCurPrice != None:
                        fRatio = float(arHoldingData['ratio']) / 100.0
                        fPrice = float(arHoldingData['price'])
                        iHoldingQuantity = int(round(fAmount * fRatio / fPrice))
                        iTotalQuantity += iHoldingQuantity
                        fTotal += fRatio * (fCurPrice / fPrice)
                        strDebug += self._getMktDebugString(strHoldingSymbol, iHoldingQuantity, fCurPrice) + strAnd
                    else:
                        return False
                    break
        strDebug = strDebug.rstrip(strAnd)
        if bDisplayTotalQuantity == True:
            strDebug += ' 共' + str(iTotalQuantity)
        strDebug = self._getSymDebugString(strSymbol, int(fQuantity), strType, strMktType) + strDebug
        fTotal *= self._getCNY(arSymData) / fCNYholdings
        fTotal -= 1.0
        fTotal *= fPosition
        fTotal += 1.0
        fTotal *= fNetValue
        fPrice = float(arSymData[strType + '_price'])
        if (fPrice > 0.001):
            arSymData['discount'] = fPrice / fTotal - 1.0
            arSymData['quantity'] = iTotalQuantity
            return strDebug
        return False
    """

    def _calcHoldingArbitrage(self, arMkt, arMktData, strMktSymbol, strMktType, arSymData, strSymbol, strType):
        strMktPriceType = strMktType + '_price'
        strMktSizeType = strMktType + '_size'
        arSrcPrice = {strMktSymbol: arMktData[strMktPriceType]}
        arSrcQuantity = {strMktSymbol: arMktData[strMktSizeType]}
        for arOtherMktData in arMkt.values():
            strOtherSymbol = arOtherMktData['symbol'] 
            if strOtherSymbol != strMktSymbol and self.api.is_holding_symbol(arSymData, strOtherSymbol):
                if all(arOtherMktData[attr] is not None for attr in [strMktPriceType, strMktSizeType]):
                    arSrcPrice |= {strOtherSymbol: arOtherMktData[strMktPriceType]}
                    arSrcQuantity |= {strOtherSymbol: arOtherMktData[strMktSizeType]}
        arQuantity = self.api.CalcQuantity(strSymbol, {strSymbol: arSymData[strType + '_size']} | arSrcQuantity)
        iSize = arQuantity[strSymbol]
        if iSize > 0:
            arSymData['discount'] = float(arSymData[strType + '_price']) / self.api.EstNetValue(strSymbol, arSrcPrice) - 1.0
            strDebug = get_separate_display(strType)
            iTotalSize = 0
            bDisplayTotalQuantity = False
            strAnd = ' 和 '
            for strHoldingSymbol in arSymData['symbol_hedge']:
                strRealSymbol = convert_symbol(strHoldingSymbol)
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
            arSymData['quantity'] = iTotalSize
            return self._getSymDebugString(strSymbol, iSize, strType, strMktType) + strDebug
        return False

    def _sendMktData(self, strType, strMktSymbol, strMktType, arMktData, arMkt):
        for strSymbol, arSymData in self.api.get_config().items():
            strDebug = False
            if strType + '_size' in arSymData:
                if self.api.is_single(arSymData):
                    if self.api.get_next_symbol(arSymData) == strMktSymbol:
                        strDebug = self._calcCalibrationArbitrage(arMktData, strMktSymbol, strMktType, arSymData, strSymbol, strType)
                else:
                    if self.api.is_holding_symbol(arSymData, strMktSymbol):
                        #strDebug = self._calcHoldingsArbitrage(arMkt, strMktSymbol, strMktType, arMktData[strMktType + '_size'], arSymData, strSymbol, strType)
                        strDebug = self._calcHoldingArbitrage(arMkt, arMktData, strMktSymbol, strMktType, arSymData, strSymbol, strType)
            if strDebug:
                self._debugPriceAndSize(arSymData, strSymbol, strType, strDebug)

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
                
    def CheckPriceAndSize(self, arSrc, arMktData, arMkt):
        self._fetchData(arSrc)
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
