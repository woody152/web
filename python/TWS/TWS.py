import threading
import time

# python -m pip install setuptools
# python setup.py install
from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract
from ibapi.order import Order

from palmmicro import Palmmicro
from palmmicrostock import PalmmicroWrapper, PalmmicroStock, IbkrStock

def IsChinaMarketOpen():
    iTime = PalmmicroStock.GetExchangeTime('SZSE')
    if iTime >= 915 and iTime < 1130:
        return True
    elif iTime >= 1300 and iTime < 1500:
        return True
    #return False
    return True

def IsMarketOpen():
    iTime = PalmmicroStock.GetExchangeTime()
    if iTime >= 930 and iTime < 1600:
        #pass
        return True
    return False

def GetOrderArray(arPrice = [], iSize = 1, iBuyPos = -1, iSellPos = -1, iAvgPos = -1):
    iLen = len(arPrice)
    if iSellPos >= iLen or iSellPos < -1:
        iSellPos = -1
    if iBuyPos  >= iLen or iBuyPos < -1:
        iBuyPos = -1
    ar = {'price': arPrice,
          'BUY_id': -1,
          'SELL_id': -1,
          'BUY_pos': iBuyPos,
          'SELL_pos': iSellPos,
          'BUY_org_pos': iBuyPos,
          'SELL_org_pos': iSellPos,
          'VWAP_pos': iAvgPos,
          'size': iSize
         }
    return ar

def AdjustPriceArray(arPrice, fAdjust):
    arNew = []
    for fPrice in arPrice:
        arNew.append(round(round(4.0*fPrice*fAdjust)/4.0, 2))
    return arNew

def AdjustOrderArray(arOrder, fAdjust, iBuyPos = -1, iSellPos = -1):
    return GetOrderArray(AdjustPriceArray(arOrder['price'], fAdjust), arOrder['size'], iBuyPos, iSellPos)


class MyEWrapper(EWrapper):
    def __init__(self, client):
        self.client = client
        self.palmmicro = None
        self.arMkt = {}
        self.strCurFuture = '202609'
        self.strNextFuture = '202612'
        self.arOrder = {}
        self.arOrder['SPY'] = GetOrderArray()
        if IsChinaMarketOpen():
            self.arOrder['KWEB'] = GetOrderArray()
            self.arOrder['GLD'] = GetOrderArray()
            self.arOrder['GUSH'] = GetOrderArray()
            #self.arOrder['IEO'] = GetOrderArray()
            self.arOrder['INDA'] = GetOrderArray()
            self.arOrder['QQQ'] = GetOrderArray()
            #self.arOrder['SLV'] = GetOrderArray()
            self.arOrder['USO'] = GetOrderArray()
            self.arOrder['XBI'] = GetOrderArray()
            self.arOrder['XLE'] = GetOrderArray()
            self.arOrder['XLY'] = GetOrderArray()
            self.arOrder['XOP'] = GetOrderArray()
            self.arOrder['MES' + self.strCurFuture] = GetOrderArray()
            self.arOrder['MNQ' + self.strCurFuture] = GetOrderArray()
            self.arOrder['MCL202609'] = GetOrderArray()
            self.arOrder['MGC202608'] = GetOrderArray()
        else:
            #self.arOrder['TLT'] = GetOrderArray([80.90, 84.19, 85.21, 86.40, 86.62, 86.72, 87.59, 89.76, 91.88], 100, 1, 8)
            self.arOrder['SPX'] = GetOrderArray([5177.26, 6293.95, 7182.92, 7353.96, 7477.28, 7491.32, 7516.62, 7628.67, 8071.89])
            self.arOrder['MES' + self.strCurFuture] = AdjustOrderArray(self.arOrder['SPX'], 1.0052, 4, 7)
            self.arOrder['MES' + self.strNextFuture] = AdjustOrderArray(self.arOrder['SPX'], 1.0182, -1, -1)
            
    def nextValidId(self, orderId: int):
        self.client.StartStreaming(orderId)
        self.spx_cal = {}
        for strSymbol in self.arOrder.keys():
            if strSymbol.startswith('MES'):
                self.spx_cal[strSymbol] = Calibration(strSymbol)
                iRequestId = self.client.FutureReqMktData('MES', strSymbol[3:])
            elif strSymbol.startswith('MNQ'):
                iRequestId = self.client.FutureReqMktData(strSymbol[:3], strSymbol[3:])
            elif strSymbol.startswith('MGC'):
                iRequestId = self.client.FutureReqMktData(strSymbol[:3], strSymbol[3:], 'COMEX')
            elif strSymbol.startswith('MCL'):
                iRequestId = self.client.FutureReqMktData(strSymbol[:3], strSymbol[3:], 'NYMEX')
            elif strSymbol == 'SPX':
                iRequestId = self.client.IndexReqMktData(strSymbol)
            else:
                iRequestId = self.client.StockReqMktData(strSymbol)
            self.arMkt[iRequestId] = IbkrStock(strSymbol)

    def __get_buy_symbol(self, strSymbol):
        if strSymbol.startswith('MES'):
            #return 'MES' + self.strNextFuture
            return 'MES' + self.strCurFuture
        else:
            return strSymbol

    def __get_sell_symbol(self, strSymbol):
        if strSymbol.startswith('MES'):
            #return 'MES' + self.strNextFuture
            return 'MES' + self.strCurFuture
        else:
            return strSymbol

    #def error(self, reqId, errorCode, errorString, advancedOrderRejectJson=""):
    def error(self, reqId, errorCode, errorString, *args):
        #print(f"IB Error {errorCode}: {errorString}")
        print('Error:', reqId, errorCode, errorString)
        if args:
            print(args[0])

    def connectionClosed(self):
        print("IB连接已关闭")

    def tickPrice(self, reqId, tickType, price, attrib):
        if price > 0.0:
            mkt_stock = self.arMkt[reqId]
            if tickType == 1:  # Bid price
                mkt_stock.SetPrice(price, 'BUY')
                self.BidPriceTrade(mkt_stock)
            elif tickType == 2:  # Ask price
                mkt_stock.SetPrice(price, 'SELL')
                self.AskPriceTrade(mkt_stock)
            elif tickType == 4: # Last price
                if IsMarketOpen():
                    mkt_stock.SetPrice(price)
                    self.LastPriceTrade(mkt_stock)
            else:
                if IsMarketOpen():
                    print(mkt_stock.GetName(), price, tickType)

    def tickSize(self, reqId, tickType, size):
        mkt_stock = self.arMkt[reqId]
        iSize = int(size)
        if tickType == 0:  # Bid size
            mkt_stock.SetSize(iSize, 'BUY')
        elif tickType == 3:  # Ask size
            mkt_stock.SetSize(iSize, 'SELL')
        
    def tickString(self, reqId, tickType, value):
        mkt_stock = self.arMkt[reqId]
        if tickType == 48:  # RT_VOLUME
            arParts = value.split(';')
            if len(arParts) >= 6 and arParts[4] != '': 
                fPrice = float(arParts[4])
                (strSymbol, fOld), = mkt_stock.GetNamePrice('VWAP').items()
                if strSymbol.startswith('MES') or strSymbol.startswith('MNQ'):
                    fPrice = round(4.0 * fPrice) / 4.0
                fPrice = round(fPrice, 2)
                if fOld is None or abs(fPrice - fOld) > 0.005:
                    mkt_stock.SetPrice(fPrice, 'VWAP')
                    print(strSymbol, 'VWAP', fPrice)
                    arOrder = self.arOrder[strSymbol]
                    arPrice = arOrder['price']
                    if arOrder['VWAP_pos'] != -1:
                        iSellPos = arOrder['SELL_pos']
                        if arOrder['SELL_id'] != -1 and arOrder['VWAP_pos'] == iSellPos:
                            if fPrice < arPrice[iSellPos] and fPrice > arPrice[iSellPos - 1]:
                                self.client.CallPlaceOrder(strSymbol, fPrice, arOrder['size'], 'SELL', arOrder['SELL_id'])
                
    def _debugUnexpectedStatus(self, strStatus, strType):
        if strStatus != 'Submitted' and strStatus != 'PreSubmitted':
            print('Unexpected ' + strType + ' status: ' + strStatus)

    def orderStatus(self, orderId, status, filled, remaining, avgFillPrice, permId, parentId, lastFillPrice, clientId, whyHeld, mktCapPrice):
        print('Order Status - OrderId:', orderId, '| Filled:', filled, '| Remaining:', remaining, '| AvgFillPrice:', avgFillPrice, '| Status:', status)
        for strSymbol in self.arOrder.keys():
            arOrder = self.arOrder[strSymbol]
            iLen = len(arOrder['price'])
            if arOrder['BUY_id'] == orderId:
                if status == 'Filled' and remaining == 0:
                    arOrder['BUY_id'] = -1
                    strSellSymbol = self.__get_sell_symbol(strSymbol)
                    arSellOrder = self.arOrder[strSellSymbol]
                    iOldSellPos = arSellOrder['SELL_pos']
                    self.IncSellPos(arSellOrder, arOrder['BUY_pos'], iLen)
                    iSellPos = arSellOrder['SELL_pos']
                    arSellOrder['SELL_org_pos'] = iSellPos
                    arOrder['BUY_pos'] -= 1
                    arOrder['BUY_org_pos'] = arOrder['BUY_pos']
                    if arSellOrder['SELL_id'] != -1 and iSellPos > -1 and iSellPos != iOldSellPos:
                        fPrice = arSellOrder['price'][iSellPos]
                        #if arSellOrder['VWAP_pos'] == iSellPos:
                        self.client.CallPlaceOrder(strSellSymbol, fPrice, arSellOrder['size'], 'SELL', arSellOrder['SELL_id'])
                elif status == 'Cancelled':
                    arOrder['BUY_id'] = -1
                    arOrder['BUY_pos'] = -1
                    arOrder['BUY_org_pos'] = -1
                    #print('BUY order cancelled ' + str(orderId))
                else:
                    self._debugUnexpectedStatus(status, 'BUY')
            elif arOrder['SELL_id'] == orderId:
                if status == 'Filled' and remaining == 0:
                    arOrder['SELL_id'] = -1
                    strBuySymbol = self.__get_buy_symbol(strSymbol)
                    arBuyOrder = self.arOrder[strBuySymbol]
                    iOldBuyPos = arBuyOrder['BUY_pos']
                    arBuyOrder['BUY_pos'] = arOrder['SELL_pos'] - 1
                    arBuyOrder['BUY_org_pos'] = arBuyOrder['BUY_pos']
                    self.IncSellPos(arOrder, arOrder['SELL_pos'], iLen)
                    arOrder['SELL_org_pos'] = arOrder['SELL_pos']
                    if arBuyOrder['BUY_id'] != -1 and arBuyOrder['BUY_pos'] > -1 and arBuyOrder['BUY_pos'] != iOldBuyPos:
                        self.client.CallPlaceOrder(strBuySymbol, arBuyOrder['price'][arBuyOrder['BUY_pos']], arBuyOrder['size'], 'BUY', arBuyOrder['BUY_id'])
                elif status == 'Cancelled':
                    arOrder['SELL_id'] = -1
                    arOrder['SELL_pos'] = -1
                    arOrder['SELL_org_pos'] = -1
                else:
                    self._debugUnexpectedStatus(status, 'SELL')

    def IncSellPos(self, arOrder, iFrom, iLen):
        arOrder['SELL_pos'] = iFrom + 1
        if arOrder['SELL_pos'] >= iLen:
            arOrder['SELL_pos'] = -1

    def LastPriceTrade(self, mkt_stock):
        (strSymbol, fLast), = mkt_stock.GetNamePrice().items()
        if strSymbol.startswith('MES'):
            fAdjust = self.spx_cal[strSymbol].Calc(fLast)
            if fAdjust > 1.0:
                arOrder = self.arOrder[strSymbol]
                arNew = AdjustPriceArray(self.arOrder['SPX']['price'], fAdjust)
                for iIndex in range(len(arNew)):
                    fNew = arNew[iIndex]
                    if abs(fNew - arOrder['price'][iIndex]) > 0.01:
                        arOrder['price'][iIndex] = fNew
                        if strSymbol == self.__get_buy_symbol(strSymbol):
                            if arOrder['BUY_id'] != -1 and arOrder['BUY_pos'] == iIndex:
                                self.client.CallPlaceOrder(strSymbol, fNew, arOrder['size'], 'BUY', arOrder['BUY_id'])
                        if strSymbol == self.__get_sell_symbol(strSymbol):
                            if arOrder['SELL_id'] != -1 and arOrder['SELL_pos'] == iIndex:
                                self.client.CallPlaceOrder(strSymbol, fNew, arOrder['size'], 'SELL', arOrder['SELL_id'])
                print(arOrder)
        elif strSymbol == 'SPX':
            for key in self.spx_cal:
                self.spx_cal[key].SetPrice(fLast)
    
    def AskPriceTrade(self, mkt_stock):
        (strSymbol, fSell), = mkt_stock.GetNamePrice('SELL').items()
        arOrder = self.arOrder[strSymbol]
        iPos = arOrder['BUY_org_pos']
        if iPos != -1:
            iSize = arOrder['size']
            arPrice = arOrder['price']
            fPrice = arPrice[iPos]
            if arOrder['BUY_id'] == -1:
                if fSell > fPrice:
                    arOrder['BUY_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'BUY')
                elif iPos >= 1:
                    iActPos = iPos - 1
                    if fSell > arPrice[iActPos]:
                        arOrder['BUY_id'] = self.client.CallPlaceOrder(strSymbol, arPrice[iActPos], iSize, 'BUY')
                        arOrder['BUY_pos'] = iActPos
            elif iPos != arOrder['BUY_pos']:
                if fSell > fPrice:
                    self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'BUY', arOrder['BUY_id'])
                    arOrder['BUY_pos'] = iPos

    def BidPriceTrade(self, mkt_stock):
        (strSymbol, fBuy), = mkt_stock.GetNamePrice('BUY').items()
        arOrder = self.arOrder[strSymbol]
        iPos = arOrder['SELL_org_pos']
        if iPos != -1:
            iSize = arOrder['size']
            arPrice = arOrder['price']
            fPrice = arPrice[iPos]
            if arOrder['SELL_id'] == -1:
                if fBuy < fPrice:
                    arOrder['SELL_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'SELL')
                elif iPos < len(arPrice) - 1 :
                    iActPos = iPos + 1
                    if fBuy < arPrice[iActPos]:
                        arOrder['SELL_id'] = self.client.CallPlaceOrder(strSymbol, arPrice[iActPos], iSize, 'SELL')
                        arOrder['SELL_pos'] = iActPos
            elif iPos != arOrder['SELL_pos']:
                if fBuy < fPrice:
                    self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'SELL', arOrder['SELL_id'])
                    arOrder['SELL_pos'] = iPos

    def PalmmicroInit(self):
        self.palmmicro = Palmmicro()

    def PalmmicroRun(self):
        if self.palmmicro is not None and IsChinaMarketOpen():
            self.palmmicro.HandleData(self.arMkt)

class MyEClient(EClient):
    """
    def __init__(self, wrapper):
        EClient.__init__(self, wrapper)
        #self.wrapper = wrapper
    """

    def StartStreaming(self, iOrderId):
        self.iOrderId = iOrderId
        self.iRequestId = 0
        self.arContract = {}

    def callReqMktData(self, strSymbol, contract, strYearMonth = ''):
        contract.symbol = strSymbol
        contract.currency = 'USD'
        self.arContract[strSymbol + strYearMonth] = contract
        self.iRequestId += 1
        self.reqMktData(self.iRequestId, contract, '233', False, False, [])
        return self.iRequestId

    def FutureReqMktData(self, strSymbol, strYearMonth, strExchange = 'CME'):
        contract = Contract()
        contract.secType = 'FUT'
        contract.exchange = strExchange
        contract.lastTradeDateOrContractMonth = strYearMonth
        return self.callReqMktData(strSymbol, contract, strYearMonth)

    def StockReqMktData(self, strSymbol):
        contract = Contract()
        contract.secType = 'STK'
        contract.exchange = PalmmicroWrapper.GetStockContractExchange()     # GetContractExchange()
        return self.callReqMktData(strSymbol, contract)

    def IndexReqMktData(self, strSymbol):
        contract = Contract()
        contract.secType = 'IND'
        contract.exchange = 'CBOE'
        return self.callReqMktData(strSymbol, contract)

    def CallPlaceOrder(self, strSymbol, price, iSize, strAction, iOrderId = -1):
        contract = self.arContract[strSymbol]
        order = Order()
        order.action = strAction
        order.totalQuantity = iSize
        order.orderType = 'LMT'
        order.lmtPrice = price
        if strSymbol == 'KWEB' or strSymbol == 'XBI' or strSymbol == 'XOP':
            if contract.exchange != 'OVERNIGHT':
                order.outsideRth = True
        else:
            if IsMarketOpen() == False:
                #pass
                return -1
        if iOrderId == -1:
            iOrderId = self.iOrderId
            self.iOrderId += 1
        self.placeOrder(iOrderId, contract, order)
        time.sleep(1)
        return iOrderId


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
        if self.fPrice is not None:
            fRatio = fPeerPrice/self.fPrice
            self.fTotal += fRatio
            self.iCount += 1
            if self.iCount > 100:
                fAvg = round(self.fTotal/self.iCount, 4)
                print(self.strDisplay, 'last', round(fRatio, 4), 'avg', fAvg)
                self.Reset()
                return fAvg
        return 0.0


# 2. 假设这是检测TQ断开的函数
def is_tq_disconnected():
    # 实际实现：尝试调用一个tq接口函数，捕获异常则返回True
    # 例如:
    # try:
    #     tq.get_market_data(...)
    #     return False
    # except Exception:
    #     return True
    return False # 占位

# 3. 监控线程函数
def monitor_tq_and_disconnect_ib(app):
    while not app.isConnected():
        time.sleep(1)  # 等待IB连接建立

    print("开始监控TQ连接状态...")
    while app.isConnected():
        if is_tq_disconnected():  # 检查TQ连接
            print("检测到TQ断开！正在结束IB连接...")
            app.disconnect()  # 断开IB，使app.run()循环退出
            break
        time.sleep(2)  # 每2秒检查一次

def main():
    app = MyEClient(MyEWrapper(None))
    app.wrapper = MyEWrapper(app)

    if IsChinaMarketOpen():
        app.wrapper.PalmmicroInit()

    # 启动IB连接
    app.connect('127.0.0.1', 7497, clientId=0)
    #app.run()

    # 将app.run()放入后台线程
    ib_thread = threading.Thread(target=app.run, daemon=True)
    ib_thread.start()
    print("IB API 运行线程已启动。")

    # 启动监控线程，监控TQ状态并控制app
    monitor_thread = threading.Thread(target=monitor_tq_and_disconnect_ib, args=(app,), daemon=True)
    monitor_thread.start()

    # === 主线程改为事件循环 ===
    try:
        # 监控线程是否还在运行，以及IB线程是否存活
        while monitor_thread.is_alive() and ib_thread.is_alive():
            time.sleep(1)  # 休眠让出CPU
            app.wrapper.PalmmicroRun()
            # 可选：检查是否有用户中断
            # 使用全局标志或信号处理来实现
        print("监控线程或IB线程已结束，退出主循环。")
    except KeyboardInterrupt:
        print("用户中断。")
    finally:
        # 清理资源
        app.disconnect()
        if ib_thread.is_alive():
            ib_thread.join(timeout=5)
            print("IB线程已结束。")

if __name__ == "__main__":
    main()
