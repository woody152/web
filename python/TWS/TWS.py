import time

from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract
from ibapi.order import Order

from palmmicro import Palmmicro
from palmmicro import Calibration
from palmmicro import GetMktDataArray

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
        self.strCurFuture = '202603'
        self.strNextFuture = '202606'
        self.arDebug = {}

    def nextValidId(self, orderId: int):
        self.arQDII = {'SH501018', 'SZ160719', 'SZ160723', 'SZ161116', 'SZ161125', 'SZ161127', 'SZ161129', 'SZ161130', 'SZ161226', 'SZ162411', 'SZ162415', 'SZ162719', 'SZ164701', 'SZ164906'}
        #self.arQQQ = {'SH513100', 'SH513110', 'SH513390', 'SH513870', 'SZ159501', 'SZ159513', 'SZ159632', 'SZ159659', 'SZ159660', 'SZ159696', 'SZ159941'}
        self.arXOPETF = {'SH513350', 'SZ159518'}
        self.arOrder = {}
        self.arOrder['KWEB'] = GetOrderArray([19.82, 30.55, 32.76, 33.53, 34.35, 34.56, 34.84, 36.15, 36.79, 40.82, 41.28], 200, 4, -1, 0)
        #self.arOrder['TLT'] = GetOrderArray([81.71, 84.44, 86.76, 87.70], 100, 2, -1)
        #self.arOrder['SPY'] = GetOrderArray([612.11, 653.01, 653.17], 50, 1, -1)
        #self.arOrder['XOP'] = GetOrderArray([135.08, 135.15, 138.48, 139.43, 150.32], 100, -1, 3)
        if IsChinaMarketOpen():
            self.arOrder['GLD'] = GetOrderArray()
            self.arOrder['IEO'] = GetOrderArray()
            self.arOrder['QQQ'] = GetOrderArray()
            self.arOrder['SLV'] = GetOrderArray()
            self.arOrder['SPY'] = GetOrderArray()
            self.arOrder['USO'] = GetOrderArray()
            self.arOrder['XBI'] = GetOrderArray()
            self.arOrder['XLY'] = GetOrderArray()
            self.arOrder['XOP'] = GetOrderArray()
        else:
        #if IsMarketOpen():
            self.arOrder['SPX'] = GetOrderArray([4936.03, 5987.40, 6274.99, 6348.08, 6671.70, 6733.82, 6832.80, 6844.12, 6888.71, 6954.41, 6995.33, 7038.77])
            self.arOrder['MES' + self.strCurFuture] = AdjustOrderArray(self.arOrder['SPX'], 1.0074, 3, 9)
            self.arOrder['MES' + self.strNextFuture] = AdjustOrderArray(self.arOrder['SPX'], 1.0188, -1, -1)
        self.palmmicro = Palmmicro()
        self.client.StartStreaming(orderId)
        self.arMkt = {}
        self.spx_cal = {}
        for strSymbol in self.arOrder.keys():
            if strSymbol.startswith('MES'):
                self.spx_cal[strSymbol] = Calibration(strSymbol)
                iRequestId = self.client.FutureReqMktData('MES', strSymbol[3:])
            elif strSymbol == 'SPX':
                iRequestId = self.client.IndexReqMktData(strSymbol)
            else:
                iRequestId = self.client.StockReqMktData(strSymbol)
            self.arMkt[iRequestId] = GetMktDataArray(strSymbol)

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

    def error(self, reqId, errorCode, errorString, contract):
        print('Error:', reqId, errorCode, errorString)

    def tickPrice(self, reqId, tickType, price, attrib):
        if price > 0.0:
            arMktData = self.arMkt[reqId]
            if tickType == 1:  # Bid price
                arMktData['BUY_price'] = price
                self.BidPriceTrade(arMktData)
                self._CheckPriceAndSize(arMktData)
            elif tickType == 2:  # Ask price
                arMktData['SELL_price'] = price
                self.AskPriceTrade(arMktData)
                self._CheckPriceAndSize(arMktData)
            elif tickType == 4: # Last price
                if IsMarketOpen():
                    arMktData['LAST_price'] = price
                    self.LastPriceTrade(arMktData)
            #elif tickType == 59: # Avg volume price 21
            else:
                if IsMarketOpen():
                    print(arMktData['symbol'], price, tickType)

    def tickSize(self, reqId, tickType, size):
        arMktData = self.arMkt[reqId]
        if tickType == 0:  # Bid size
            arMktData['BUY_size'] = size
        elif tickType == 3:  # Ask size
            arMktData['SELL_size'] = size
        self._CheckPriceAndSize(arMktData)

    def tickString(self, reqId, tickType, value):
        arMktData = self.arMkt[reqId]
        if tickType == 48:  # RT_VOLUME
            arParts = value.split(';')
            if len(arParts) >= 6 and arParts[4] != '': 
                fPrice = float(arParts[4])
                strSymbol = arMktData['symbol']
                if strSymbol.startswith('MES'):
                    fPrice = round(4.0 * fPrice) / 4.0
                fPrice = round(fPrice, 2)
                if arMktData['VWAP_price'] == None or abs(fPrice - arMktData['VWAP_price']) > 0.005:
                    arMktData['VWAP_price'] = fPrice
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

    def LastPriceTrade(self, arMktData):
        strSymbol = arMktData['symbol']
        if strSymbol.startswith('MES'):
            fAdjust = self.spx_cal[strSymbol].Calc(arMktData['LAST_price'])
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
                self.spx_cal[key].SetPrice(arMktData['LAST_price'])
    
    def AskPriceTrade(self, arMktData):
        strSymbol = arMktData['symbol']
        arOrder = self.arOrder[strSymbol]
        iPos = arOrder['BUY_org_pos']
        if iPos != -1:
            iSize = arOrder['size']
            arPrice = arOrder['price']
            fPrice = arPrice[iPos]
            if arOrder['BUY_id'] == -1:
                if arMktData['SELL_price'] > fPrice:
                    arOrder['BUY_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'BUY')
                elif iPos >= 1:
                    iActPos = iPos - 1
                    if arMktData['SELL_price'] > arPrice[iActPos]:
                        arOrder['BUY_id'] = self.client.CallPlaceOrder(strSymbol, arPrice[iActPos], iSize, 'BUY')
                        arOrder['BUY_pos'] = iActPos
            elif iPos != arOrder['BUY_pos']:
                if arMktData['SELL_price'] > fPrice:
                    self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'BUY', arOrder['BUY_id'])
                    arOrder['BUY_pos'] = iPos

    def BidPriceTrade(self, arMktData):
        strSymbol = arMktData['symbol']
        arOrder = self.arOrder[strSymbol]
        iPos = arOrder['SELL_org_pos']
        if iPos != -1:
            iSize = arOrder['size']
            arPrice = arOrder['price']
            fPrice = arPrice[iPos]
            if arOrder['SELL_id'] == -1:
                if arMktData['BUY_price'] < fPrice:
                    arOrder['SELL_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'SELL')
                elif iPos < len(arPrice) - 1 :
                    iActPos = iPos + 1
                    if arMktData['BUY_price'] < arPrice[iActPos]:
                        arOrder['SELL_id'] = self.client.CallPlaceOrder(strSymbol, arPrice[iActPos], iSize, 'SELL')
                        arOrder['SELL_pos'] = iActPos
            elif iPos != arOrder['SELL_pos']:
                if arMktData['BUY_price'] < fPrice:
                    self.client.CallPlaceOrder(strSymbol, fPrice, iSize, 'SELL', arOrder['SELL_id'])
                    arOrder['SELL_pos'] = iPos

    def __debugPriceAndSize(self, arSymData, strSymbol, strType, strDebug):
        iSize = arSymData['quantity']
        if iSize >= 10:
            fRatio = arSymData['discount']
            strDebug = str(round(fRatio * 100.0, 2)) + '% | ' + strDebug
            strSymbolType = strSymbol + strType
            if strSymbolType not in self.arDebug or self.arDebug[strSymbolType] != strDebug:
                self.arDebug[strSymbolType] = strDebug
                if (fRatio > 0.001 and strType == 'SELL') or (fRatio < -0.001 and strType == 'BUY'):
                    print(strDebug)
                    self.palmmicro.SendSymbolMsg(strDebug, strSymbol)
                if strSymbol in self.arQDII and iSize >= 100 and ((fRatio > 0.01 and strType == 'SELL') or (fRatio < -0.005 and strType == 'BUY')):
                    self.palmmicro.SendMsg(strDebug)
                elif strType == 'BUY' and fRatio > -0.005:
                    self.palmmicro.SendSymbolMsg(strDebug, strSymbol)

    def _processPriceAndSize(self, arMktData, arSym):
        strMktSymbol = arMktData['symbol']
        for strType in ['SELL', 'BUY']:
            if strType == 'SELL':
                strMktType = 'BUY'
            else:
                strMktType = 'SELL'
            if all(arMktData[attr] is not None for attr in [strMktType + '_price', strMktType + '_size']):
                for strSymbol, arSymData in arSym.items():
                    strDebug = False
                    if 'calibration' in arSymData:
                        if arSymData['symbol_hedge'] == strMktSymbol:
                            strDebug = self.palmmicro.CalcCalibrationArbitrage(arMktData, strMktSymbol, strMktType, strSymbol, strType)
                    else:
                        if strMktSymbol in arSymData['symbol_hedge']:
                            strDebug = self.palmmicro.CalcHoldingsArbitrage(self.arMkt, strMktSymbol, strMktType, arMktData[strMktType + '_size'], strSymbol, strType)
                    if strDebug:
                        self.__debugPriceAndSize(arSymData, strSymbol, strType, strDebug)
            else:
                for strSymbol, arSymData in arSym.items():
                    strDebug = strMktSymbol + ' ' + strMktType + ' streaming missing'
                    if 'calibration' in arSymData:
                        if arSymData['symbol_hedge'] != strMktSymbol:
                            strDebug = False
                    else:
                        if strMktSymbol not in arSymData['symbol_hedge']:
                            strDebug = False
                    if strDebug:
                        self.palmmicro.SendSymbolMsg(strDebug, strSymbol)

    def _CheckPriceAndSize(self, arMktData):
        if IsChinaMarketOpen():
            arSym = self.palmmicro.FetchData(sorted(self.arQDII | self.arXOPETF))
            self._processPriceAndSize(arMktData, arSym)
            if self.palmmicro.CheckNewSinaData() == True:
                for reqId, arOtherMktData in self.arMkt.items():
                    if arOtherMktData['symbol'] != arMktData['symbol']:
                        self._processPriceAndSize(arOtherMktData, arSym)
                self._processPriceAndSize(self.palmmicro.GetAG0(), arSym)
            #for strHedge in self.arQQQ:
                #self.palmmicro.SendMsg(strDebug, 'qqq')
            self.palmmicro.SendOldMsg()

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
        if strSymbol == 'KWEB' or strSymbol == 'TLT' or strSymbol == 'XOP':
            if contract.exchange != 'OVERNIGHT':
                order.outsideRth = True
        else:
            if IsMarketOpen() == False:
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
