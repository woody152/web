import dtale
import requests
#import threading
#import time

from palmmicroapi import PalmmicroAPI, PalmmicroDataFrame
from palmmicrostock import PalmmicroTask, PalmmicroStock, SinaStock, TdxStock

#from _tgprivate import TG_TOKEN
from _tgprivate import WECHAT_KEY
from _tgprivate import WECHAT_QMT_KEY
from _tgprivate import arSymbolKey
        
class Palmmicro:
    FENCE = ' | '
    d_column_formats = {'Percent': {'fmt': '0.00%'}, 'SymbolPrice': {'fmt': '0.000'}}

    def __init__(self):
        self.arStock = TdxStock.TqInit()
        self.arSinaStock = SinaStock.TaskInit()
        self.api = PalmmicroAPI(PalmmicroAPI.FetchData(PalmmicroStock.JoinSymbols(arSymbolKey), WECHAT_QMT_KEY))
        self.pdf = PalmmicroDataFrame(self.api)
        self.d = dtale.show(self.pdf.GetDataFrame(), host = '127.0.0.1', port = 40007, column_formats = self.d_column_formats, reaper_on = False)
        self.d.open_browser()
        self.arSendMsg = {}
        self.arSendMsg['telegram'] = self.GetSendMsgArray('telegram', WECHAT_KEY)
        for strSymbol, strKey in arSymbolKey.items():
            self.arSendMsg[strSymbol] = self.GetSendMsgArray(strSymbol, strKey)
 
    def GetSendMsgArray(self, group, strKey):
        ar = {'key': strKey,
              'last': '',
              'msg': {}
             }
        task = PalmmicroTask(group + 'Msg', self.SendGroupMsg, 4, (group, ))
        task.start(2)
        return ar

    @staticmethod
    def SendWechatMsg(strMsg, strKey, strType = 'text'):
        url = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' + strKey
        arWechatMsg = {'msgtype': strType,  
                       strType: {'content': strMsg}
                      }
        try:
            response = requests.post(url, json=arWechatMsg, headers={'Content-Type': 'application/json'})
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                ...
                #response_data = response.json()  # Parse the JSON response data
                #print('Response data:', response_data)
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('SendWechatMsg Error occurred:', e)

    def SendGroupMsg(self, group):
        ar = self.arSendMsg[group]
        arMsg = ar['msg']
        if len(arMsg) == 0:
            return
        strTime = self.pdf.GetBeijingTime() + self.FENCE
        iTotal = len(strTime.encode('utf-8'))
        strAll = ''
        arAll = []
        for strMsg in arMsg.values():
            iLen = len(strMsg.encode('utf-8'))
            if iTotal + iLen > 2046:
                print('too many message in group: ', group)
                break
            elif strMsg not in arAll:
                arAll.append(strMsg)
                strAll += strMsg + '\n\n'
                iTotal += iLen + 2
        arMsg.clear()
        strMsg = strAll[:-2]
        if strMsg != ar['last']:
            ar['last'] = strMsg
            self.SendWechatMsg(strTime + strMsg, ar['key'])
            """
            if group == 'telegram':
                self.api.SendMsg(strMsg[:20], TG_TOKEN)
            """

    def _postMsg(self, strMsg, strType, group = 'telegram'):
        arMsg = self.arSendMsg[group].get('msg', {})
        if group == 'telegram':
            arMsg[strType] = strMsg
        elif group in self.arSendMsg:
            arMsg[strType] = strMsg.replace(' ' + group, '')

    def _processPriceAndSize(self, stock, mkt_stock, strType, strSymbol, usdcny_stock = None, arMktList = []):
        strMktType = stock.GetPeerType(strType)
        if stock.IsUpdated(strType) or mkt_stock.IsUpdated(strMktType):
            if self.pdf.ProcessPriceAndSize(stock, mkt_stock, strType, usdcny_stock, arMktList):
                strMktSymbol = mkt_stock.GetSymbol()
                strMsgType = strSymbol + strMktSymbol + strType
                ar = self.pdf.GetData(strSymbol, strMktSymbol, strType)
                fRatio = ar['Percent']
                strDebug = str(round(fRatio * 100.0, 2)) + '%'
                iSize = ar['SymbolSize']
                strDebug += self.FENCE + stock.GetTypeDisplay(strMktType) + ' ' + self.pdf.CombineSizeAndPrice(strSymbol, stock, iSize, strType)
                iMktSize = ar['HedgeSize']
                strDebug += self.FENCE + mkt_stock.GetTypeDisplay(strType) + ' ' + self.pdf.CombineSizeAndPrice(strMktSymbol, mkt_stock, iMktSize, strMktType)
                if ar['Note'] != '':
                    strDebug += self.FENCE + ar['Note']
                if (fRatio < -0.001 and strType == 'SELL') or (fRatio > 0.001 and strType == 'BUY'):
                    print(f"{strDebug}{self.FENCE}对冲值:{iSize / iMktSize:.0f}")
                if iMktSize >= 100 and ((fRatio < -0.01 and strType == 'SELL') or (fRatio > 0.005 and strType == 'BUY')):
                    self._postMsg(strDebug, strMsgType)
                self._postMsg(strDebug, strMsgType, strSymbol)
                return True
        return False

    def _lockAndHandleData(self, arMktList, usdcny_stock):
        with PalmmicroStock._global_lock:
            bChanged = False
            for strSymbol in self.api.get_config().keys():
                stock = self.arStock.get(strSymbol)
                if stock is not None:
                    for strType in stock.GetTypeList():
                        for mkt_stock in arMktList:
                            bChanged |= self._processPriceAndSize(stock, mkt_stock, strType, strSymbol, usdcny_stock, arMktList)
                        #if ag0_stock is not None:
                            #bChanged |= self._processPriceAndSize(stock, ag0_stock, strType, strSymbol)
                        stock.SetUpdated(strType, False)
            for strMktType in PalmmicroStock.GetTypeList():
                for mkt_stock in arMktList:
                    mkt_stock.SetUpdated(strMktType, False)
                #if ag0_stock is not None:
                    #ag0_stock.SetUpdated(strMktType, False)
            return bChanged
       
    def HandleData(self, arMkt):
        usdcny_stock = self.arSinaStock.get('CNY')
        ag0_stock = self.arSinaStock.get('nf_AG0')
        arMktList = list(arMkt.values())
        if ag0_stock is not None:
            arMktList.append(ag0_stock)
        if self._lockAndHandleData(arMktList, usdcny_stock):
            self.d.data = self.pdf.GetDataFrame()
            self.d.update_settings(column_formats = self.d_column_formats)
        