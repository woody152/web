#import aiohttp
#import asyncio
import dtale
#import json
import requests
import threading
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
        self.iTimer = 0
        self.usdcny_stock = None
        self.ag0_stock = None
        self.arSendMsg = {}
        self.arSendMsg['telegram'] = GetSendMsgArray(WECHAT_KEY)
        for strSymbol, strKey in arSymbolKey.items():
            self.arSendMsg[strSymbol] = GetSendMsgArray(strKey)
        self.arStock = TdxStock.Init()
        self.api = PalmmicroAPI(PalmmicroAPI.FetchData(','.join(arSymbolKey.keys()), TG_TOKEN))
        self.pdf = PalmmicroDataFrame(self.api)
        self.d = dtale.show(self.pdf.GetDataFrame(), host = '127.0.0.1', port = 40007, column_formats = self.d_column_formats)
        self.d.open_browser()
  
    def _fetchData(self):
        iCur = int(time.time())
        if iCur - self.iTimer >= 19:
            self.iTimer = iCur
            arLine = SinaStock.FetchData('fx_susdcny,nf_AG0')
            if arLine:
                self.usdcny_stock = SinaStock.UpdateStock(self.usdcny_stock, arLine[0])
                self.ag0_stock = SinaStock.UpdateStock(self.ag0_stock, arLine[1])
    """
    async def SendWechatMsgAsync(self, strMsg, group, strType='text'):
        url = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' + self.arSendMsg[group]['key']
        arWechatMsg = {'msgtype': strType,
                       strType: {'content': strMsg}
                      }
        try:
            async with aiohttp.ClientSession() as session:
                async with session.post(url, json = arWechatMsg, headers = {'Content-Type': 'application/json'}) as response:
                    if response.status == 200:
                        response_data = await response.json()
                        return response_data
                    else:
                        print(f'Failed to send POST request. Status code: {response.status}')
                        return None
        except aiohttp.ClientError as e:
            print(f'SendWechatMsgAsync Error occurred: {e}')
            return None
    
    async def _sendMsgAsync(self):
        tasks = []
        for group, value in self.arSendMsg.items():
            if self.__isFree(group):
                if len(value['msg']) > 0:
                    strMsg = self.__convert_array_msg(group)
                    task = asyncio.create_task(self.SendWechatMsgAsync(strMsg, group))
                    tasks.append(task)
                    self.arSendMsg[group]['msg'].clear()
        if tasks:
            # 等待所有发送任务完成（可选）
            results = await asyncio.gather(*tasks, return_exceptions=True)
            # 可以检查results看哪些成功了哪些失败了
            for i, result in enumerate(results):
                if isinstance(result, Exception):
                    print(f'任务{i} 发送失败: {result}')
            return results
        return []
    
    def _sendMsg(self):
        #保持原接口不变，内部启动异步发送
        try:
            # 尝试获取当前事件循环
            loop = asyncio.get_running_loop()
            # 如果已经在异步环境中，直接创建任务
            asyncio.create_task(self._sendMsgAsync())
        except RuntimeError:
            # 没有运行中的事件循环，创建一个新的
            loop = asyncio.new_event_loop()
            asyncio.set_event_loop(loop)
            loop.run_until_complete(self._sendMsgAsync())
            loop.close()

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

    def _sendMsg(self):
        for group, value in self.arSendMsg.items():
            if self.__isFree(group):
                if len(value['msg']) > 0:
                    #self.__send_msg(group)
                    # 直接开线程发送，不等待
                    t = threading.Thread(target=self.__send_msg, args=(group,), daemon=True)
                    t.start()
    

    def __isFree(self, group):
        ar = self.arSendMsg[group]
        iCur = int(time.time())
        if iCur - ar['timer'] < ar['count']:
            return False
        ar['timer'] = iCur
        return True

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

    def _processPriceAndSize(self, arMktList, mkt_stock, stock, strType, strSymbol):
        strMktSymbol = mkt_stock.GetSymbol()
        strMktType = stock.GetPeerType(strType)
        if mkt_stock.HasData(strMktType):
            if stock.IsUpdated(strType) or mkt_stock.IsUpdated(strMktType):
                if self.pdf.ProcessPriceAndSize(arMktList, mkt_stock, stock, strType, self.usdcny_stock, GetBeijingTimeDisplay()):
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
        self._fetchData()
        bChanged = False
        arMktList = list(arMkt.values())
        for strSymbol in self.api.get_config().keys():
            stock = self.arStock.get(strSymbol)
            if stock:
                for strType in stock.GetTypeList():
                    if stock.HasData(strType):
                        for mkt_stock in arMkt.values():
                            bChanged |= self._processPriceAndSize(arMktList, mkt_stock, stock, strType, strSymbol)
                        bChanged |= self._processPriceAndSize(arMktList, self.ag0_stock, stock, strType, strSymbol)
                        stock.SetUpdated(strType, False)
        for strMktType in PalmmicroStock.GetTypeList():
            for mkt_stock in arMkt.values():
                mkt_stock.SetUpdated(strMktType, False)
            self.ag0_stock.SetUpdated(strMktType, False)
        if bChanged:
            self.d.data = self.pdf.GetDataFrame()
            self.d.update_settings(column_formats = self.d_column_formats)
        self._sendMsg()
        