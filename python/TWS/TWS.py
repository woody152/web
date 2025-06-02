import time

from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract
from ibapi.order import Order

from palmmicro import Palmmicro
from palmmicro import Calibration
from nyc_time import GetExchangeTime

def IsTesting():
    #return True
    return False

def IsChinaMarketOpen():
    if IsTesting():
        return True
    iTime = GetExchangeTime('SZSE')
    if iTime >= 915 and iTime < 1130:
        return True
    elif iTime >= 1300 and iTime < 1500:
        return True
    return False

def IsMarketOpen():
    iTime = GetExchangeTime()
    if iTime >= 930 and iTime < 1600:
        return True
    return False

def GetOrderArray(arPrice = [], iSize = 1, iBuyPos = -1, iSellPos = -1):
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

def GetMktDataArray(strSymbol):
    ar = {'symbol': strSymbol,
          'bid_price': None,
          'ask_price': None,
          'last_price': None,
          'bid_size': None,
          'ask_size': None
         }
    return ar


class MyEWrapper(EWrapper):
    def __init__(self, client):
        self.client = client
        self.strCurFuture = '202506'
        self.strNextFuture = '202509'
        self.arDebug = {}

    def nextValidId(self, orderId: int):
        self.arHedge = {'SZ161125', 'SZ161127', 'SZ161130', 'SZ162411', 'SZ162415', 'SZ162719', 'SZ164906'}
        #self.arQQQ = {'SH513100', 'SH513110', 'SH513390', 'SH513870', 'SZ159501', 'SZ159513', 'SZ159632', 'SZ159659', 'SZ159660', 'SZ159696', 'SZ159941'}
        #self.arXOP = {'SH513350', 'SZ159518'}
        self.arOrder = {}
        self.arOrder['KWEB'] = GetOrderArray([20.53, 28.53, 32.38, 33.3, 33.7, 33.87, 35.35, 38.45], 200, 2, 3)
        if IsChinaMarketOpen():
            self.arOrder['IEO'] = GetOrderArray()
            self.arOrder['QQQ'] = GetOrderArray()
            self.arOrder['SPY'] = GetOrderArray()
            self.arOrder['XBI'] = GetOrderArray()
            self.arOrder['XLY'] = GetOrderArray()
            self.arOrder['XOP'] = GetOrderArray()
        else:
        #if IsMarketOpen():
            #self.arOrder['TLT'] = GetOrderArray([80.42, 83.53, 83.65, 85.44, 85.81, 87.11, 90.57, 92.68, 98.05], 100, 0, 2)
            #self.arOrder['XOP'] = GetOrderArray([100.99, 105.55, 112.37, 114.17, 115.57, 119.18, 126.68, 152.37, 156.91], 100, 1, 6)
            self.arOrder['SPX'] = GetOrderArray([4472.35, 5089.4, 5555.24, 5828.34, 5891.94, 5908.49, 6101.45, 6402.44, 6469.53])
            self.arOrder['MES' + self.strCurFuture] = AdjustOrderArray(self.arOrder['SPX'], 1.0018, 4, -1)
            self.arOrder['MES' + self.strNextFuture] = AdjustOrderArray(self.arOrder['SPX'], 1.0107, -1, 6)
        self.palmmicro = Palmmicro()
        self.client.StartStreaming(orderId)
        self.data = {}
        self.spx_cal = {}
        for strSymbol in self.arOrder.keys():
            if strSymbol.startswith('MES'):
                self.spx_cal[strSymbol] = Calibration(strSymbol)
                iRequestId = self.client.FutureReqMktData('MES', strSymbol[3:])
            elif strSymbol == 'SPX':
                iRequestId = self.client.IndexReqMktData(strSymbol)
            else:
                iRequestId = self.client.StockReqMktData(strSymbol)
            self.data[iRequestId] = GetMktDataArray(strSymbol)

    def __get_buy_symbol(self, strSymbol):
        if strSymbol.startswith('MES'):
            #return 'MES' + self.strNextFuture
            return 'MES' + self.strCurFuture
        else:
            return strSymbol

    def __get_sell_symbol(self, strSymbol):
        if strSymbol.startswith('MES'):
            return 'MES' + self.strNextFuture
            #return 'MES' + self.strCurFuture
        else:
            return strSymbol

    def error(self, reqId, errorCode, errorString, contract):
        print('Error:', reqId, errorCode, errorString)

    def tickPrice(self, reqId, tickType, price, attrib):
        data = self.data[reqId]
        if tickType == 1:  # Bid price
            data['bid_price'] = price
            self.BidPriceTrade(data)
            self.CheckPriceAndSize(data)
        elif tickType == 2:  # Ask price
            data['ask_price'] = price
            self.AskPriceTrade(data)
            self.CheckPriceAndSize(data)
        elif tickType == 4:
            if IsMarketOpen():
                data['last_price'] = price
                self.LastPriceTrade(data)

    def tickSize(self, reqId, tickType, size):
        data = self.data[reqId]
        if tickType == 0:  # Bid size
            data['bid_size'] = size
        elif tickType == 3:  # Ask size
            data['ask_size'] = size
        self.CheckPriceAndSize(data)

    def orderStatus(self, orderId, status, filled, remaining, avgFillPrice, permId, parentId, lastFillPrice, clientId, whyHeld, mktCapPrice):
        print('Order Status - OrderId:', orderId, 'Status:', status, 'Filled:', filled, 'Remaining:', remaining, 'AvgFillPrice:', avgFillPrice)
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
                    arSellOrder['SELL_org_pos'] = arSellOrder['SELL_pos']
                    arOrder['BUY_pos'] -= 1
                    arOrder['BUY_org_pos'] = arOrder['BUY_pos']
                    if arSellOrder['SELL_id'] != -1 and arSellOrder['SELL_pos'] > -1 and arSellOrder['SELL_pos'] != iOldSellPos:
                        self.client.CallPlaceOrder(strSellSymbol, arSellOrder['price'][arSellOrder['SELL_pos']], arSellOrder['size'], 'SELL', arSellOrder['SELL_id'])
                elif status == 'Cancelled':
                    arOrder['BUY_id'] = -1
                    arOrder['BUY_pos'] = -1
                    arOrder['BUY_org_pos'] = -1
                    #print('BUY order cancelled ' + str(orderId))
                elif status != 'Submitted':
                    print('Unexpected BUY status ' + status)
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
                elif status != 'Submitted':
                    print('Unexpected SELL status ' + status)

    def IncSellPos(self, arOrder, iFrom, iLen):
        arOrder['SELL_pos'] = iFrom + 1
        if arOrder['SELL_pos'] >= iLen:
            arOrder['SELL_pos'] = -1

    def LastPriceTrade(self, data):
        strSymbol = data['symbol']
        if strSymbol.startswith('MES'):
            fAdjust = self.spx_cal[strSymbol].Calc(data['last_price'])
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
                self.spx_cal[key].SetPrice(data['last_price'])
    
    def AskPriceTrade(self, data):
        strSymbol = data['symbol']
        arOrder = self.arOrder[strSymbol]
        iPos = arOrder['BUY_org_pos']
        if iPos != -1:
            iSize = arOrder['size']
            arPrice = arOrder['price']
            fPrice = arPrice[iPos]
            if arOrder['BUY_id'] == -1:
                if data['ask_price'] > fPrice:
                    arOrder['BUY_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'BUY')
                elif iPos >= 1:
                    iActPos = iPos - 1
                    if data['ask_price'] > arPrice[iActPos]:
                        arOrder['BUY_id'] = self.client.CallPlaceOrder(strSymbol, arPrice[iActPos], iSize, 'BUY')
                        arOrder['BUY_pos'] = iActPos
            elif iPos != arOrder['BUY_pos']:
                if data['ask_price'] > fPrice:
                    self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'BUY', arOrder['BUY_id'])
                    arOrder['BUY_pos'] = iPos

    def BidPriceTrade(self, data):
        strSymbol = data['symbol']
        arOrder = self.arOrder[strSymbol]
        iPos = arOrder['SELL_org_pos']
        if iPos != -1:
            iSize = arOrder['size']
            arPrice = arOrder['price']
            fPrice = arPrice[iPos]
            if arOrder['SELL_id'] == -1:
                if data['bid_price'] < fPrice:
                    arOrder['SELL_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'SELL')
                elif iPos < len(arPrice) - 1 :
                    iActPos = iPos + 1
                    if data['bid_price'] < arPrice[iActPos]:
                        arOrder['SELL_id'] = self.client.CallPlaceOrder(strSymbol, arPrice[iActPos], iSize, 'SELL')
                        arOrder['SELL_pos'] = iActPos
            elif iPos != arOrder['SELL_pos']:
                if data['bid_price'] < fPrice:
                    self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'SELL', arOrder['SELL_id'])
                    arOrder['SELL_pos'] = iPos

    def CheckPriceAndSize(self, data):
        if IsChinaMarketOpen():
            if all(data[attr] is not None for attr in ['bid_price', 'ask_price', 'bid_size', 'ask_size']):
                self.ProcessPriceAndSize(data)

    def GetSellBuyStr(self, strType):
        if strType == 'ask':
            return 'Sell'
        elif strType == 'bid':
            return 'Buy'

    def DebugPriceAndSize(self, data, strSymbol, strHedge, arReply, arResult, strType):
        iSize = arResult['size']
        if iSize > 0:
            strPeerType = self.palmmicro.GetPeerStr(strType)
            fRatio = arResult['ratio']
            strDebug = str(round(fRatio * 100.0, 2)) + '% | '
            strDebug += self.GetSellBuyStr(strType) + ' ' + strSymbol + ' ' + str(iSize) + ' @' + str(data[strPeerType + '_price']) + ' | '
            strDebug += self.GetSellBuyStr(strPeerType) + ' ' + strHedge + ' ' + str(arResult['size_hedge']) + ' @' + arReply[strType + '_price']
            strHedgeType = strHedge + strType
            if strHedgeType not in self.arDebug or self.arDebug[strHedgeType] != strDebug:
                self.arDebug[strHedgeType] = strDebug
                if iSize >= 1 and ((fRatio > 0.001 and strType == 'ask') or (fRatio < -0.001 and strType == 'bid')):
                    print(strDebug)
                    self.palmmicro.SendSymbolMsg(strDebug, strSymbol)
                if strHedge in self.arHedge and iSize >= 100 and ((fRatio > 0.01 and strType == 'ask') or (fRatio < -0.005 and strType == 'bid')):
                    self.palmmicro.SendMsg(strDebug)
                elif strType == 'bid' and fRatio > -0.005:
                    self.palmmicro.SendSymbolMsg(strDebug, strSymbol)
        self.palmmicro.SendOldMsg()

    def ProcessPriceAndSize(self, data):
        strSymbol = data['symbol']
        #arAll = self.arHedge | self.arXOP
        #arPalmmicro = self.palmmicro.FetchData(arAll | self.arQQQ)
        arAll = self.arHedge
        arPalmmicro = self.palmmicro.FetchData(arAll)
        for strHedge in arAll:
            arReply = arPalmmicro[strHedge]
            if 'symbol_hedge' in arReply and arReply['symbol_hedge'] == strSymbol:
                for strType in ['ask', 'bid']:
                    arResult = self.palmmicro.GetArbitrageResult(strHedge, data, strType)
                    self.DebugPriceAndSize(data, strSymbol, strHedge, arReply, arResult, strType)
        #for strHedge in self.arQQQ:
            #self.palmmicro.SendMsg(strDebug, 'qqq')

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
        self.reqMktData(self.iRequestId, contract, '', False, False, [])
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
        if strSymbol.startswith('MES') or strSymbol == 'TLT' or strSymbol == 'XOP':
            if IsMarketOpen() == False:
                return -1
        else:
            if contract.exchange != 'OVERNIGHT':
                order.outsideRth = True
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
