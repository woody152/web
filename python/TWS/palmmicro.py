import dtale
import requests
#import threading
#import time

from palmmicroapi import PalmmicroAPI, PalmmicroDataFrame
from palmmicrostock import PalmmicroTask, PalmmicroStock, SinaStock, TdxStock

from nyc_time import GetBeijingTimeDisplay

#from _tgprivate import TG_TOKEN
from _tgprivate import WECHAT_KEY
from _tgprivate import WECHAT_QMT_KEY
from _tgprivate import arSymbolKey
        
def get_display(strType):
    if strType == 'SELL':
        return '卖出'
    elif strType == 'BUY':
        return '买入'
    return ''

class Palmmicro:
    d_column_formats = {'Percent': {'fmt': '0.00%'}, 'SymbolPrice': {'fmt': '0.000'}}

    def __init__(self):
        self.iTimer = 0
        #self.usdcny_stock = None
        #self.ag0_stock = None
        self.arSendMsg = {}
        self.arSendMsg['telegram'] = self.GetSendMsgArray('telegram', WECHAT_KEY)
        for strSymbol, strKey in arSymbolKey.items():
            self.arSendMsg[strSymbol] = self.GetSendMsgArray(strSymbol, strKey)
        self.arSinaStock = SinaStock.TaskInit()
        self.arStock = TdxStock.TqInit()
        self.api = PalmmicroAPI(PalmmicroAPI.FetchData(PalmmicroStock.JoinSymbols(arSymbolKey), WECHAT_QMT_KEY))
        self.pdf = PalmmicroDataFrame(self.api)
        self.d = dtale.show(self.pdf.GetDataFrame(), host = '127.0.0.1', port = 40007, column_formats = self.d_column_formats, reaper_on = False)
        self.d.open_browser()
 
    def GetSendMsgArray(self, group, strKey):
        ar = {'key': strKey,
              #'count': 4,
              #'timer': 0,
              'msg': {}
             }
        task = PalmmicroTask(group + 'Msg', self.SendGroupMsg, 4, (group, ))
        task.start()
        return ar

    """
    def _fetchData(self):
        iCur = int(time.time())
        if iCur - self.iTimer >= 19:
            self.iTimer = iCur
            arLine = SinaStock.FetchData('fx_susdcny,nf_AG0')
            if arLine:
                self.usdcny_stock = SinaStock.UpdateStock(self.usdcny_stock, arLine[0])
                self.ag0_stock = SinaStock.UpdateStock(self.ag0_stock, arLine[1])
    """

    def SendWechatMsg(self, strMsg, group, strType = 'text'):
        url = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' + self.arSendMsg[group]['key']
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

    def __send_msg(self, group):
        strMsg = self.__convert_array_msg(group)
        self.SendWechatMsg(strMsg, group)
        if group == 'telegram':
            pass
            #self.api.SendMsg(strMsg[:20], TG_TOKEN)
        self.arSendMsg[group]['msg'].clear()

    """
    def __isFree(self, group):
        ar = self.arSendMsg[group]
        iCur = int(time.time())
        if iCur - ar['timer'] < ar['count']:
            return False
        ar['timer'] = iCur
        return True

    def _sendMsg(self):
        for group, value in self.arSendMsg.items():
            if self.__isFree(group):
                if len(value['msg']) > 0:
                    #self.__send_msg(group)
                    # 直接开线程发送，不等待
                    t = threading.Thread(target=self.__send_msg, args=(group,), daemon=True)
                    t.start()
    """
    
    def SendGroupMsg(self, group):
        if len(self.arSendMsg[group]['msg']) > 0:
            self.__send_msg(group)

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
    
    def _postMsg(self, strMsg, strType, group = 'telegram'):
        ar = self.arSendMsg[group]['msg']
        if group == 'telegram':
            ar[strType] = strMsg
        elif group in self.arSendMsg:
            ar[strType] = strMsg.replace(' ' + group, '')

    def _processPriceAndSize(self, mkt_stock, stock, strType, strSymbol, usdcny_stock = None, arMktList = []):
        strMktSymbol = mkt_stock.GetSymbol()
        strMktType = stock.GetPeerType(strType)
        if mkt_stock.HasData(strMktType):
            if stock.IsUpdated(strType) or mkt_stock.IsUpdated(strMktType):
                if self.pdf.ProcessPriceAndSize(arMktList, mkt_stock, stock, strType, usdcny_stock, GetBeijingTimeDisplay()):
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
                    if (fRatio < -0.001 and strType == 'SELL') or (fRatio > 0.001 and strType == 'BUY'):
                        print(f"{strDebug} | 对冲值:{iSize / iMktSize:.0f}")
                    if iMktSize >= 100 and ((fRatio < -0.01 and strType == 'SELL') or (fRatio > 0.005 and strType == 'BUY')):
                        self._postMsg(strDebug, strMsgType)
                    self._postMsg(strDebug, strMsgType, strSymbol)
                    return True
        else:
            print(strMktSymbol + '无' + get_display(strMktType) + '数据')
        return False
       
    def HandleData(self, arMkt):
        #self._fetchData()
        usdcny_stock = self.arSinaStock.get('CNY')
        ag0_stock = self.arSinaStock.get('nf_AG0')
        bChanged = False
        arMktList = list(arMkt.values())
        for strSymbol in self.api.get_config().keys():
            stock = self.arStock.get(strSymbol)
            if stock:
                for strType in stock.GetTypeList():
                    if stock.HasData(strType):
                        for mkt_stock in arMkt.values():
                            bChanged |= self._processPriceAndSize(mkt_stock, stock, strType, strSymbol, usdcny_stock, arMktList)
                        if ag0_stock is not None:
                            bChanged |= self._processPriceAndSize(ag0_stock, stock, strType, strSymbol)
                        stock.SetUpdated(strType, False)
        for strMktType in PalmmicroStock.GetTypeList():
            for mkt_stock in arMkt.values():
                mkt_stock.SetUpdated(strMktType, False)
            if ag0_stock is not None:
                ag0_stock.SetUpdated(strMktType, False)
        if bChanged:
            self.d.data = self.pdf.GetDataFrame()
            self.d.update_settings(column_formats = self.d_column_formats)
        #self._sendMsg()
        