import time

# python -m pip install setuptools
# python setup.py install
from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract
from ibapi.order import Order

from palmmicro import Palmmicro
from palmmicro import Calibration
from palmmicroapi import PalmmicroAPI
from palmmicrostock import IbkrStock

from nyc_time import GetExchangeTime

def IsChinaMarketOpen():
    iTime = GetExchangeTime('SZSE')
    if iTime >= 915 and iTime < 1130:
        return True
    elif iTime >= 1300 and iTime < 1500:
        return True
    return False
    #return True

def IsMarketOpen():
    iTime = GetExchangeTime()
    if iTime >= 930 and iTime < 1600:
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
        self.strCurFuture = '202609'
        self.strNextFuture = '202612'
        #PalmmicroAPI.set_multiplier('hf_ES', 1)
        #PalmmicroAPI.set_multiplier('hf_NQ', 1)

    def nextValidId(self, orderId: int):
        self.arOrder = {}
        self.arOrder['SPY'] = GetOrderArray()
        if IsChinaMarketOpen():
            self.arOrder['KWEB'] = GetOrderArray()
            self.arOrder['GLD'] = GetOrderArray()
            self.arOrder['GUSH'] = GetOrderArray()
            #self.arOrder['IEO'] = GetOrderArray()
            self.arOrder['INDA'] = GetOrderArray()
            self.arOrder['QQQ'] = GetOrderArray()
            self.arOrder['SLV'] = GetOrderArray()
            self.arOrder['USO'] = GetOrderArray()
            self.arOrder['XBI'] = GetOrderArray()
            self.arOrder['XLE'] = GetOrderArray()
            self.arOrder['XLY'] = GetOrderArray()
            self.arOrder['XOP'] = GetOrderArray()
            self.arOrder['MES' + self.strCurFuture] = GetOrderArray()
            self.arOrder['MNQ' + self.strCurFuture] = GetOrderArray()
        else:
            #self.arOrder['TLT'] = GetOrderArray([80.90, 84.19, 85.21, 86.40, 86.62, 86.72, 87.59, 89.76, 91.88], 100, 1, 8)
            self.arOrder['SPX'] = GetOrderArray([5177.26, 6215.66, 7078.29, 7251.65, 7425.73, 7430.03, 7444.26, 7608.40, 7940.92])
            self.arOrder['MES' + self.strCurFuture] = AdjustOrderArray(self.arOrder['SPX'], 1.0071, 5, 7)
            self.arOrder['MES' + self.strNextFuture] = AdjustOrderArray(self.arOrder['SPX'], 1.0182, -1, -1)
        self.palmmicro = Palmmicro()
        self.client.StartStreaming(orderId)
        self.arMkt = {}
        self.spx_cal = {}
        for strSymbol in self.arOrder.keys():
            if strSymbol.startswith('MES'):
                self.spx_cal[strSymbol] = Calibration(strSymbol)
                iRequestId = self.client.FutureReqMktData('MES', strSymbol[3:])
            elif strSymbol.startswith('MNQ'):
                iRequestId = self.client.FutureReqMktData('MNQ', strSymbol[3:])
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

    def error(self, reqId, errorCode, errorString, *args):
        print('Error:', reqId, errorCode, errorString)
        if args:
            print(args[0])

    def tickPrice(self, reqId, tickType, price, attrib):
        if price > 0.0:
            mkt_stock = self.arMkt[reqId]
            if tickType == 1:  # Bid price
                mkt_stock.SetPrice(price, 'BUY')
                self.BidPriceTrade(mkt_stock)
                self._CheckPriceAndSize(mkt_stock)
            elif tickType == 2:  # Ask price
                mkt_stock.SetPrice(price, 'SELL')
                self.AskPriceTrade(mkt_stock)
                self._CheckPriceAndSize(mkt_stock)
            elif tickType == 4: # Last price
                if IsMarketOpen():
                    mkt_stock.SetPrice(price, 'LAST')
                    self.LastPriceTrade(mkt_stock)
            else:
                if IsMarketOpen():
                    print(mkt_stock.GetSymbol(), price, tickType)

    def tickSize(self, reqId, tickType, size):
        mkt_stock = self.arMkt[reqId]
        iSize = int(size)
        if tickType == 0:  # Bid size
            mkt_stock.SetSize(iSize, 'BUY')
        elif tickType == 3:  # Ask size
            mkt_stock.SetSize(iSize, 'SELL')
        self._CheckPriceAndSize(mkt_stock)

    def tickString(self, reqId, tickType, value):
        mkt_stock = self.arMkt[reqId]
        if tickType == 48:  # RT_VOLUME
            arParts = value.split(';')
            if len(arParts) >= 6 and arParts[4] != '': 
                fPrice = float(arParts[4])
                (strSymbol, fOld), = mkt_stock.GetNamePrice('VWAP').items()
                if strSymbol.startswith('MES'):
                    fPrice = round(4.0 * fPrice) / 4.0
                fPrice = round(fPrice, 2)
                if fOld == None or abs(fPrice - fOld) > 0.005:
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
        (strSymbol, fLast), = mkt_stock.GetNamePrice('LAST').items()
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

    def _CheckPriceAndSize(self, mkt_stock):
        if IsChinaMarketOpen():
            self.palmmicro.CheckPriceAndSize(mkt_stock, self.arMkt)


def GetContractExchange():
    iTime = GetExchangeTime()
    if iTime >= 350 and iTime < 2000:
        return 'SMART'
    return 'OVERNIGHT'


class MyEClient(EClient):
    def __init__(self, wrapper):
        EClient.__init__(self, wrapper)
        self.wrapper = wrapper

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

    def FutureReqMktData(self, strSymbol, strYearMonth):
        contract = Contract()
        contract.secType = 'FUT'
        contract.exchange = 'CME'
        contract.lastTradeDateOrContractMonth = strYearMonth
        return self.callReqMktData(strSymbol, contract, strYearMonth)

    def StockReqMktData(self, strSymbol):
        contract = Contract()
        contract.secType = 'STK'
        contract.exchange = GetContractExchange()
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


app = MyEClient(MyEWrapper(None))
app.wrapper = MyEWrapper(app)
app.connect('127.0.0.1', 7497, clientId=0)
time.sleep(1)
app.run()
