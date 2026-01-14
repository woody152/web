import array
import json
import math
import requests
import time

from nyc_time import GetBeijingTimeDisplay

from _tgprivate import TG_TOKEN
from _tgprivate import WECHAT_KEY
from _tgprivate import WECHAT_SH501018_KEY
from _tgprivate import WECHAT_SH513350_KEY
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
from _tgprivate import WECHAT_SZ164701_KEY
from _tgprivate import WECHAT_SZ164906_KEY
from _tgprivate import WECHAT_SZ165513_KEY

def convert_fake_symbol(symbol):
    if symbol.startswith('^'):
        symbol = symbol[1:]
        if '-' in symbol:
            symbol = symbol.split('-')[0]
    return symbol

def _get_hedge(arData):
    return float(arData['calibration'])/float(arData['position'])

def _get_hedge2(arData, arHedge):
    return _get_hedge(arData) / _get_hedge(arHedge)

def _get_floor_quantity(fQuantity):
    fQuantity /= 100.0
    return math.floor(fQuantity) * 100.0

def _get_hedge_quantity(strType, arSymData, fHedge):
    f_quantity = _get_floor_quantity(float(arSymData[strType + '_size']))
    f_floor = math.floor(f_quantity / fHedge)
    return int(f_floor)

def fund_adjust_position(f_position, f_val, f_old_val):
    return f_position * f_val + (1.0 - f_position) * f_old_val;

def fund_reverse_adjust_position(f_position, f_val, f_old_val):
    return f_val / f_position - f_old_val * (1.0 / f_position - 1.0)

def qdii_get_peer_val(f_qdii, f_cny, f_calibration):
    return f_qdii * f_calibration / f_cny

def _ref_get_peer_val(strType, arData):
    f_qdii = fund_reverse_adjust_position(float(arData['position']), float(arData[strType + '_price']), float(arData['netvalue']))
    return qdii_get_peer_val(f_qdii, float(arData['CNY']), float(arData['calibration']))

def _ref_get_peer_val2(strType, arData, arHedge):
    f_index = _ref_get_peer_val(strType, arData)
    f_index /= float(arHedge['calibration'])
    return fund_adjust_position(float(arHedge['position']), f_index, float(arHedge['netvalue']))

def GetSendMsgArray(strKey):
    ar = {'key': strKey,
          'count': 6,
          'timer': 0,
          'msg': '',
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
        self.arSym = {}
        self.iTimer = 0
        self.bNewSinaData = False
        self.arSendMsg = {}
        self.arSendMsg['telegram'] = GetSendMsgArray(WECHAT_KEY)
        self.arSendMsg['SH501018'] = GetSendMsgArray(WECHAT_SH501018_KEY)
        self.arSendMsg['SH513350'] = GetSendMsgArray(WECHAT_SH513350_KEY)
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
        self.arSendMsg['SZ164701'] = GetSendMsgArray(WECHAT_SZ164701_KEY)
        self.arSendMsg['SZ164906'] = GetSendMsgArray(WECHAT_SZ164906_KEY)
        self.arSendMsg['SZ165513'] = GetSendMsgArray(WECHAT_SZ165513_KEY)
        self.arAG0 = GetMktDataArray('nf_AG0')

    def GetAG0(self):
        return self.arAG0

    def _getTelegramChatId(self):
        return 992671436

    def _fetchSinaData(self, strSymbols):
        strUrl = f'http://hq.sinajs.cn/list=nf_AG0,{strSymbols.lower()}'
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
                        if strSymbol == 'nf_AG0':
                            self.arAG0['BUY_price'] = float(arItem[6])
                            self.arAG0['SELL_price'] = float(arItem[7])
                            self.arAG0['BUY_size'] = int(arItem[11])
                            self.arAG0['SELL_size'] = int(arItem[12])
                        else:
                            strSymbol = strSymbol.upper()
                            if strSymbol in self.arSym:
                                arSymData = self.arSym[strSymbol]
                                arSymData['BUY_price'] = arItem[6]
                                arSymData['SELL_price'] = arItem[7]
                                arSymData['BUY_size'] = int(arItem[10])
                                arSymData['SELL_size'] = int(arItem[20])
            else:
                print('Failed to send request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('_fetchSinaData error:', e)

    def _fetchPalmmicroData(self, strSymbols):
        iChatId = self._getTelegramChatId()
        arMsg = {
            'update_id': 886050244,
            'message': {
                'message_id': 6620,
                'from': {
                    'id': iChatId,
                    'is_bot': False,
                    'first_name': 'ny152',
                    'username': 'sz152',
                    'language_code': 'zh-hans'
                        },
                'chat': {
                    'id': iChatId,
                    'first_name': 'ny152',
                    'username': 'sz152',
                    'type': 'private'
                        },
                'date': 0,
                'text': ''
                       }
                    }
        arMessage = arMsg['message']
        arMessage['date'] = int(time.time())
        arMessage['text'] = f"@{strSymbols}"
        strUrl = 'https://palmmicro.com/php/telegram.php?token=' + TG_TOKEN
        try:
            response = requests.post(strUrl, json=arMsg, headers={'Content-Type': 'application/json'})
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                response_data = response.json()  # Parse the JSON response data
                print('Response data:', response_data)
                #self.arSym.clear()
                self.arSym = response_data['text']
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('_fetchPalmmicroData error:', e)

    def FetchData(self, arSymbol):
        iCur = int(time.time())
        if iCur - self.iTimer >= 19:
            self.iTimer = iCur
            strSymbols = ','.join(arSymbol)
            if not self.arSym:
                self._fetchPalmmicroData(strSymbols)
            self._fetchSinaData(strSymbols)
        return self.arSym

    def _getSymDebugString(self, strSymbol, iSize, strType, strPeerType):
        arSymData = self.arSym[strSymbol]
        return ' | ' + strPeerType + ' ' + strSymbol + ' ' + str(iSize) + ' @' + arSymData[strType + '_price']

    def _getMktDebugString(self, strHedgeSymbol, iSize, fPrice):
        return ' ' + strHedgeSymbol + ' ' + str(iSize) + ' @' + str(fPrice)
            
    def CalcCalibrationArbitrage(self, arMktData, strMktSymbol, strMktType, strSymbol, strType):
        fMktPrice = arMktData[strMktType + '_price']
        iMktSize = arMktData[strMktType + '_size'] 
        arSymData = self.arSym[strSymbol]
        strSizeIndex = strType + '_size'
        if strSizeIndex in arSymData:
            if arSymData[strSizeIndex] > 0 and fMktPrice > 0.001:
                if strMktSymbol in self.arSym:
                    arLevSymData = self.arSym[strMktSymbol]
                    fHedge = _get_hedge2(arSymData, arLevSymData)
                    fHedgePos = float(arLevSymData['position'])
                    fHedgePrice = _ref_get_peer_val2(strType, arSymData, arLevSymData)
                else:
                    fHedgePos = False
                    fHedge = _get_hedge(arSymData)
                    if strSymbol == 'SZ161226':
                        fHedge *= 1.0
                    fHedgePrice = _ref_get_peer_val(strType, arSymData)
                iHedgeSize = _get_hedge_quantity(strType, arSymData, fHedge)
                fRatio = fMktPrice / fHedgePrice - 1.0
                if fHedgePos:
                    fRatio /= fHedgePos
                fRatio *= float(arSymData['position'])
                arSymData['discount'] = fRatio
                iMktSize = min(iMktSize, iHedgeSize)
                arSymData['quantity'] = iMktSize;
                strDebug = strType + self._getMktDebugString(strMktSymbol, iMktSize, fMktPrice)
                iSize = int((float(iMktSize) * fHedge + 50.0) / 100.0) * 100
                strDebug += self._getSymDebugString(strSymbol, iSize, strType, strMktType)
                return strDebug
        return False

    def CalcHoldingsArbitrage(self, arMkt, strMktSymbol, strMktType, iMktSize, strSymbol, strType):
        arSymData = self.arSym[strSymbol]
        fQuantity = _get_floor_quantity(float(arSymData[strType + '_size']))
        fNetValue = float(arSymData['netvalue'])
        fCNYholdings = float(arSymData['CNYholdings'])
        fAmount = fQuantity * fNetValue / fCNYholdings
        if strSymbol != 'SZ164701':
            fMktAmount = float(arSymData['symbol_hedge'][strMktSymbol]['price']) * float(iMktSize)
            if fMktAmount < fAmount:
                fAmount = fMktAmount
                fQuantity = fAmount * fCNYholdings / fNetValue
                fQuantity = _get_floor_quantity(fQuantity)
                fAmount = fQuantity * fNetValue / fCNYholdings
        strDebug = strType
        iTotalQuantity = 0
        bDisplayTotalQuantity = False
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
                        strDebug += self._getMktDebugString(strHoldingSymbol, iHoldingQuantity, fCurPrice) + ' and'
                    else:
                        return False
                    break
        strDebug = strDebug.rstrip(' and')
        if bDisplayTotalQuantity == True:
            strDebug += ' total ' + str(iTotalQuantity)
        strDebug += self._getSymDebugString(strSymbol, int(fQuantity), strType, strMktType)
        fTotal *= float(arSymData['CNY']) / fCNYholdings
        fTotal -= 1.0
        fTotal *= float(arSymData['position'])
        fTotal += 1.0
        fTotal *= fNetValue
        fPrice = float(arSymData[strType + '_price'])
        if (fPrice > 0.001):
            arSymData['discount'] = fTotal / fPrice - 1.0
            arSymData['quantity'] = iTotalQuantity
            return strDebug
        return False
    
    def IsFree(self, group):
        iCur = int(time.time())
        if iCur - self.arSendMsg[group]['timer'] < self.arSendMsg[group]['count']:
            return False
        self.arSendMsg[group]['timer'] = iCur
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
        arWechatMsg = {
            'msgtype': strType,  
            strType: {
                'content': ''
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
        if len(self.arSendMsg[group]['array_msg']) == 0:
            str = ''
        else:
            unique = set(self.arSendMsg[group]['array_msg'])
            str = '\n\n'.join(unique)
        return GetBeijingTimeDisplay() + ' | ' + str

    def __send_msg(self, group):
        str = self.__convert_array_msg(group)
        self.SendWechatMsg(str, group)
        #if group == 'telegram':
            #self.SendTelegramMsg(str)
        self.arSendMsg[group]['array_msg'].clear()

    def SendMsg(self, strMsg, group='telegram'):
        if self.arSendMsg[group]['msg'] != strMsg:
            if len(self.__convert_array_msg(group)) + len(strMsg) < 2046:
                self.arSendMsg[group]['msg'] = strMsg
                self.arSendMsg[group]['array_msg'].append(strMsg)
                if self.IsFree(group):
                    self.__send_msg(group)
            else:
                print('too many message in group: ', group)

    def SendSymbolMsg(self, strMsg, strSymbol):
        if strSymbol in self.arSendMsg:
            self.SendMsg(strMsg.replace(' ' + strSymbol.rstrip('ETF'), ''), strSymbol)

    def SendOldMsg(self):
        for group, value in self.arSendMsg.items():
            if self.IsFree(group):
                if len(value['array_msg']) > 0:
                    self.__send_msg(group)

    def CheckNewSinaData(self):
        if self.bNewSinaData == True:
            self.bNewSinaData = False
            return True
        return False


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
