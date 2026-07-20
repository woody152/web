import math
import pandas as pd
import requests
import time

from datetime import datetime, timezone, timedelta
from typing import Any, Dict, List, Optional, Union

from palmmicrostock import PalmmicroStock

def _get_floor_quantity(iQuantity: int) -> float:
    fQuantity = iQuantity / 100.0
    return math.floor(fQuantity) * 100.0

def _round_quantity(fQuantity: float) -> int:
	return int((fQuantity + 49.9) / 100.0) * 100

class TelegramAPI:
	@staticmethod
	def FetchData(strSymbols, strToken, strUrl = 'https://palmmicro.com/php/telegram.php', iChatId = 992671436, strFirstName = 'woody', strUserName = 'palmmicro'):
		ar = {'update_id': 886050244,
			  'message': {'message_id': 6620,
						  'from': {'id': iChatId,
								   'is_bot': False,
								   'first_name': strFirstName,
								   'username': strUserName,
								   'language_code': 'zh-Hans'
								  },
						  'chat': {'id': iChatId,
								   'first_name': strFirstName,
								   'username': strUserName,
								   'type': 'private'
								  },
						  'date': int(time.time()),
						  'text': strSymbols
						 }
			 }
		strUrl += '?token=' + strToken
		try:
			response = requests.post(strUrl, json = ar, headers = {'Content-Type': 'application/json'})
			response.raise_for_status()  # Raise an exception for HTTP errors
			if response.status_code == 200:
				response_data = response.json()  # Parse the JSON response data
                #print('Response data:', response_data)
				return response_data['text']
			else:
				print('Failed to send POST request. Status code:', response.status_code)
		except requests.exceptions.RequestException as e:
			print('FetchData error:', e)
		return None

	@staticmethod
	def SendMsg(strMsg, strToken, strUrl = 'https://api.telegram.org/', iChatId = -1001346320717):
		url = strUrl + 'bot' + strToken + '/sendMessage?text=' + strMsg + '&chat_id=' + str(iChatId)
		try:
			response = requests.get(url)
			response.raise_for_status()  # Raise an exception for HTTP errors
			if response.status_code == 200:
				...
				#data = response.json()  # Assuming the response is in JSON format
			else:
				print('Failed to retrieve data. Status code:', response.status_code)
		except requests.exceptions.RequestException as e:
			print('SendMsg Error occurred:', e)


class PalmmicroAPI(TelegramAPI):
	# 定义并初始化字典静态变量 arMultiplier，使用 strSymbol 作为键（整数倍率）
	arMultiplier: Dict[str, int] = {'hf_CL': 100,	# MCL:100, CL:1000
									'hf_ES': 5,		# MES
									'hf_GC': 10,	# MGC:10, GC:1000
									'hf_NQ': 2,		# MNQ
									'hf_SI': 5000,	# SI
									'nf_AG0': 15,
									'default': 1}	# 默认倍率
	DEFAULT_KEY_QUANTITY: int = 1000000
	DEFAULT_HEDGE_QUANTITY: int = 10000

	def __init__(self, config_dict):
		"""使用字典初始化API"""
		self.config = config_dict

	def get_config(self):
		"""返回当前配置字典"""
		return self.config

	def set_param(self, key: str, value):
		"""设置/更新配置参数"""
		self.config[key] = value

	def get_param(self, key: str):
		"""获取指定配置参数的值"""
		return self.config.get(key)

	@staticmethod
	def is_single(ar) -> bool:
		return 'calibration' in ar

	@staticmethod
	def get_next_symbol(ar) -> str:
		return ar['symbol_hedge']
	
	@staticmethod
	def is_holding_symbol(ar, strSymbol) -> bool:
		return strSymbol in ar['symbol_hedge']

	@staticmethod
	def get_holding_symbols(ar):
		return ar['symbol_hedge'].keys()

	def get_next_param(self, ar):
		return self.get_param(self.get_next_symbol(ar))

	@classmethod
	def get_multiplier(cls, strSymbol):
		"""根据 strSymbol 获取倍率"""
		return cls.arMultiplier.get(strSymbol, cls.arMultiplier.get('default', 1))

	@classmethod
	def set_multiplier(cls, strSymbol, value):
		"""设置指定 symbol 的倍率（确保为整数）"""
		if not isinstance(value, int):
			try:
				value = int(value)
			except ValueError:
				print(f"✗ 错误: {value} 无法转换为整数")
				return False
		cls.arMultiplier[strSymbol] = value
		print(f"✓ 已设置倍率 {strSymbol}: {value} (整数)")
		return True

	@staticmethod
	def _calc_hedge(ar) -> float:
		return float(ar['calibration']) / float(ar['position'])

	@staticmethod
	def _get_CNY(ar, arSrc):
		fCny = 1.0
		if arSrc is None:
			if 'CNYest' in ar:
				fCny = float(ar['CNYest'])
		else:
			if 'CNY' in arSrc:
				fCny = arSrc['CNY']
			elif 'CNY' in ar:
				fCny = float(ar['CNY'])
		return fCny

	@staticmethod
	def _reverse_calc_with_calibration(ar, strSymbol, arSrc):
		fCny = PalmmicroAPI._get_CNY(ar, arSrc)
		fPos = float(ar['position'])
		return (arSrc[strSymbol] - (1.0 - fPos) * float(ar['netvalue'])) * float(ar['calibration']) / fPos / fCny

	@staticmethod
	def _calc_with_calibration(ar, arSrc):
		fCny = PalmmicroAPI._get_CNY(ar, arSrc)
		fEst = 0.0
		if arSrc is None:
			if 'est_netvalue' in ar:
				fEst = float(ar['est_netvalue'])
		else:
			strHedgeSymbol = PalmmicroAPI.get_next_symbol(ar)
			if strHedgeSymbol in arSrc:
				fEst = arSrc[strHedgeSymbol]
			else:
				if 'est_netvalue' in ar:
					fEst = float(ar['est_netvalue'])
		fPos = float(ar['position'])
		return (1.0 - fPos) * float(ar['netvalue']) + fPos * fEst * fCny  / float(ar['calibration'])

	@staticmethod
	def _calc_with_holdings(ar, arSrc):
		fCny = PalmmicroAPI._get_CNY(ar, arSrc)
		fTotal = 0.0
		for strHolding, arHolding in ar['symbol_hedge'].items():
			if arSrc is None:
				fPrice = float(arHolding['est_price'])
			else:
				strReal = PalmmicroStock.ConvertYahooNetValueSymbol(strHolding)
				if strReal in arSrc:
					fPrice = arSrc[strReal]
				else:
					fPrice = float(arHolding['est_price'])
			fTotal += float(arHolding['ratio']) * fPrice / float(arHolding['price'])
		fTotal /= 100.0
		return float(ar['netvalue']) * (1.0 + float(ar['position']) * (fTotal * fCny / float(ar['CNYholdings']) - 1.0))
	
	def __est_calibration_netvalue(self, ar, arSrc):
		strHedgeSymbol = self.get_next_symbol(ar)
		arHedge = self.get_param(strHedgeSymbol)
		if arHedge is None:
			arCopy = arSrc
			if arSrc is not None:	# 检查是否有需要二次计算的杠杆ETF输入
				for strLevSymbol in arSrc:
					arLev = self.get_param(strLevSymbol)
					if arLev is not None:
						if strHedgeSymbol == self.get_next_symbol(arLev):
							arCopy = arSrc.copy()
							arCopy[strHedgeSymbol] = self._reverse_calc_with_calibration(arLev, strLevSymbol, {strLevSymbol: arSrc[strLevSymbol]})
							break
		else:
			arCopy = None
			if arSrc is not None:	# 需要二次计算
				arCopy = arSrc.copy()
				fIndex = None
				if strHedgeSymbol in arSrc:		# strHedgeSymbol in [SPY, QQQ]
					fIndex = self._reverse_calc_with_calibration(arHedge, strHedgeSymbol, {strHedgeSymbol:arSrc[strHedgeSymbol]})
				else:
					arIndex = self.get_next_param(arHedge)
					if arIndex is not None:
						strFutureSymbol = self.get_next_symbol(arIndex)
						if strFutureSymbol in arSrc:	# strFutureSymbol in [hf_ES, hf_NQ]
							fIndex = self._calc_with_calibration(arIndex, {strFutureSymbol: arSrc[strFutureSymbol]})
				if fIndex is not None:
					arCopy[strHedgeSymbol] = fIndex
		return self._calc_with_calibration(ar, arCopy)		# 直接算
	
	def __est_holdings_netvalue(self, ar, arSrc):
		arCopy = None
		if arSrc is not None:
			arCopy = arSrc.copy()
			for strSrc in arSrc:
				for strHolding in self.get_holding_symbols(ar):
					arHolding = self.get_param(strHolding)
					if arHolding is not None:
						if self.get_next_symbol(arHolding) == strSrc:
							arCopy[strHolding] = self._calc_with_calibration(arHolding, arSrc)
		return self._calc_with_holdings(ar, arCopy)		# 需要按持仓计算

	def EstNetValue(self, strSymbol: str, arSrc: Optional[Dict[str, float]] = None) -> float:
		fEst = 0.0
		ar = self.get_param(strSymbol)
		if ar is not None:
			if self.is_single(ar):
				fEst = self.__est_calibration_netvalue(ar, arSrc)
			else:
				fEst = self.__est_holdings_netvalue(ar, arSrc)
		return fEst

	def ReverseEst(self, arSrc: Dict[str, float]) -> float:
		for strSymbol in arSrc:
			if strSymbol != 'CNY':
				break
		fEst = 0.0
		ar = self.get_param(strSymbol)
		if ar is not None:
			if self.is_single(ar):
				fEst = self._reverse_calc_with_calibration(ar, strSymbol, arSrc)
				arHedge = self.get_next_param(ar)
				if arHedge is not None:
					fEst = self._calc_with_calibration(arHedge, {self.get_next_symbol(arHedge):fEst})
		return fEst

	def __calc_calibration_quantity(self, strSymbol, ar, arSrc):
		arDst = {}
		strHedgeSymbol = self.get_next_symbol(ar)
		fHedge = float(ar['hedge']) * self.get_multiplier(strHedgeSymbol)
		iHedgeQuantity = self.DEFAULT_HEDGE_QUANTITY	
		if strHedgeSymbol in arSrc:
			iHedgeQuantity = arSrc[strHedgeSymbol]
		else:
			arHedge = self.get_param(strHedgeSymbol)
			if arHedge is None:
				for strLevSymbol in arSrc:
					if strLevSymbol != strSymbol:
						arLev = self.get_param(strLevSymbol)
						if arLev is not None:
							if strHedgeSymbol == self.get_next_symbol(arLev):
								fHedge /= self._calc_hedge(arLev) * self.get_multiplier(strLevSymbol)
								iHedgeQuantity = arSrc[strLevSymbol]
								strHedgeSymbol = strLevSymbol
								break
			else:
				arIndex = self.get_next_param(arHedge)
				if arIndex is not None:
					strFutureSymbol = self.get_next_symbol(arIndex)
					if strFutureSymbol in arSrc:
						fHedge = self._calc_hedge(ar) * self.get_multiplier(strFutureSymbol)
						iHedgeQuantity = arSrc[strFutureSymbol]
						strHedgeSymbol = strFutureSymbol
		if strSymbol in arSrc:
			fQuantity = _get_floor_quantity(arSrc[strSymbol])
			iPeerQuantity = int(math.floor(fQuantity / fHedge))
		else:
			iPeerQuantity = self.DEFAULT_HEDGE_QUANTITY
		iHedgeQuantity = min(iHedgeQuantity, iPeerQuantity)
		arDst[strHedgeSymbol] = iHedgeQuantity
		arDst[strSymbol] = _round_quantity(iHedgeQuantity * fHedge)
		return arDst

	@staticmethod
	def _recalc_key_quantity(ar, arDst):
		fTotal = 0.0
		for strHolding, arHolding in ar['symbol_hedge'].items():
			fTotal += arDst[strHolding] * float(arHolding['price'])
		return _round_quantity(fTotal * float(ar['CNYholdings']) / float(ar['netvalue']) / float(ar['position']))

	def __calc_holdings_quantity(self, strSymbol, ar, arSrc):
		arDst = {}
		arQuantity = {}
		arReal = {}
		fAmount = _get_floor_quantity(arSrc.get(strSymbol, self.DEFAULT_KEY_QUANTITY)) * float(ar['netvalue']) * float(ar['position']) / float(ar['CNYholdings'])
		for strHolding, arHolding in ar['symbol_hedge'].items():
			fQuantity = fAmount * (float(arHolding['ratio']) / 100.0) / float(arHolding['price'])
			arQuantity[strHolding] = fQuantity
			strReal = PalmmicroStock.ConvertYahooNetValueSymbol(strHolding)
			if strReal in arReal:
				arReal[strReal] += fQuantity
			else:
				arReal[strReal] = fQuantity
		fMax = 0.0
		strMax = None
		strFutureSymbol = None
		for strReal, fQuantity in arReal.items():
			if strReal in arSrc:
				fRealQuantity = float(arSrc[strReal])
			else:
				fRealQuantity = self.DEFAULT_HEDGE_QUANTITY
				arHolding = self.get_param(strReal)
				if arHolding is not None:
					strNextSymbol = self.get_next_symbol(arHolding) 
					if strNextSymbol in arSrc:
						strFutureSymbol = strNextSymbol
						iFutureQuantity = arSrc[strFutureSymbol]
						arDst[strFutureSymbol] = arSrc[strFutureSymbol]
						fRealQuantity = self._calc_hedge(arHolding) * self.get_multiplier(strFutureSymbol) * iFutureQuantity
			if fRealQuantity > 0.000001:
				fCompare = fQuantity / fRealQuantity
				if strFutureSymbol is not None:
					fMax = fCompare
					strMax = strReal
					break					
				if fCompare > fMax:
					fMax = fCompare
					strMax = strReal
		if fMax < 1.0 and strFutureSymbol is not None and strMax is not None and strMax not in arSrc:
			fMaxTotal = fMax * arDst[strFutureSymbol]
			if fMaxTotal > 1.0:
				arDst[strFutureSymbol] = int(math.floor(fMaxTotal))
				fMax = fMaxTotal / arDst[strFutureSymbol]
			else:
				for strHolding in arQuantity: 
					arDst[strHolding] = 0
				arDst[strFutureSymbol] = 0
				arDst[strSymbol] = 0
				return arDst
		if fMax > 1.0:
			for strHolding, fQuantity in arQuantity.items():
				arQuantity[strHolding] = fQuantity / fMax
		for strHolding in self.get_holding_symbols(ar):
			if arQuantity[strHolding] < 0.0:
				arDst[strHolding] = 0
			else:
				if strFutureSymbol is None:
					arDst[strHolding] = int(math.floor(arQuantity[strHolding]))
				else:
					arDst[strHolding] = round(arQuantity[strHolding])
		arDst[strSymbol] = self._recalc_key_quantity(ar, arDst)
		return arDst

	def CalcQuantity(self, strSymbol: str, arSrc: Dict[str, int]) -> Dict[str, int]:
		ar = self.get_param(strSymbol)
		if ar is not None:
			if self.is_single(ar):
				return self.__calc_calibration_quantity(strSymbol, ar, arSrc)
			else:
				return self.__calc_holdings_quantity(strSymbol, ar, arSrc)
		return {}

	def GetNextSymbol(self, strSymbol: str) -> Union[str, None]:
		ar = self.get_param(strSymbol)
		if ar is not None and self.is_single(ar):
			return self.get_next_symbol(ar)
		return None
	
	def GetHedgeSymbol(self, strSymbol: str, strUnknown: str) -> Union[str, None]:
		strNextSymbol = self.GetNextSymbol(strSymbol)
		if strNextSymbol is not None:
			if strNextSymbol == strUnknown:							# in [SPY, QQQ, XOP]
				return strUnknown
			elif strNextSymbol == self.GetNextSymbol(strUnknown):	# in [GUSH]
				return strUnknown
			else:
				strIndex = self.GetNextSymbol(strNextSymbol)
				if strIndex is not None:
					if strUnknown == self.GetNextSymbol(strIndex):	# in [hf_ES, hf_NQ]
						return strUnknown
		return strNextSymbol
		
	def GetHoldingSymbols(self, strSymbol: str):
		ar = self.get_param(strSymbol)
		if ar is not None:
			return self.get_holding_symbols(ar)
		return None

	def IsHoldingSymbol(self, strSymbol: str, strUnknown: str) -> bool:
		ar = self.get_param(strSymbol)
		if ar is not None:
			return self.is_holding_symbol(ar, strUnknown)
		return False
	
	def IsFutureOfHoldingSymbol(self, strSymbol: str, strUnknown: str) -> Union[str, bool]:
		ar = self.get_param(strSymbol)
		if ar is not None:
			for strHoldingSymbol in self.get_holding_symbols(ar):
				if strUnknown == self.GetNextSymbol(strHoldingSymbol):	# in [hf_CL, hf_GC]
					return strHoldingSymbol
		return False

	def GetMapping(self) -> Dict[str, List[str]]:
		if self.config is None:
			return {}
		arMapping = {}
		for strSymbol in self.config.keys():
			if PalmmicroStock.IsSymbolA(strSymbol):
				arList = []
				strNextSymbol = self.GetNextSymbol(strSymbol)
				if strNextSymbol is None:
					arHolding = self.GetHoldingSymbols(strSymbol)
					if arHolding is not None:
						for strHoldingSymbol in arHolding:
							strRealSymbol = PalmmicroStock.ConvertYahooNetValueSymbol(strHoldingSymbol)
							if strRealSymbol not in arList:
								arList.append(strRealSymbol)			# in [GLD, SLV, USO]
							strFutureSymbol = self.GetNextSymbol(strHoldingSymbol)
							if strFutureSymbol is not None and strFutureSymbol not in arList:
								if strFutureSymbol != 'hf_SI' and strFutureSymbol != 'znb_SENSEX':
									arList.append(strFutureSymbol)		# in [hf_GC, hf_CL]
				else:
					arList.append(strNextSymbol)						# in [SPY, QQQ, XOP]
					strIndexSymbol = self.GetNextSymbol(strNextSymbol)
					if strIndexSymbol is not None:
						strFutureSymbol = self.GetNextSymbol(strIndexSymbol)
						if strFutureSymbol is not None:
							arList.append(strFutureSymbol)				# in [hf_ES, hf_NQ]
					for strOtherSymbol in self.config.keys():
						if strSymbol != strOtherSymbol and PalmmicroStock.IsSymbolA(strOtherSymbol) == False:
							if strNextSymbol == self.GetNextSymbol(strOtherSymbol):
								if strOtherSymbol != 'DRIP':
									arList.append(strOtherSymbol)		# in [GUSH]
				arMapping[strSymbol] = arList
		return arMapping


class PalmmicroDataFrame:
	def __init__(self, api):
		self.api = api
		self.index_names = ['Symbol', 'Hedge', 'Type']
		rows = []
		for symbol, hedges in api.GetMapping().items():
			for hedge in hedges:
				for side in PalmmicroStock.GetTypeList():
					row = {'Symbol': symbol,
						   'Hedge': hedge,
						   'Type': side}
					rows.append(row | self._build_row())
		df_flat = pd.DataFrame(data=rows)
		self.df = df_flat.set_index(self.index_names).sort_index()

	def GetDataFrame(self):
		return self.df
	
	@staticmethod
	def _build_row(time = '00:00:00', estprice = None, symbolqty = 0, symbolprice = 0.0, hedgeqty = 0, hedgeprice = 0.0, note = ''):
		if estprice is None:
			fPercent = 0.0
		else:
			fPercent = symbolprice / estprice - 1.0
		return {'Time': time,
				'Percent': fPercent,
				'SymbolSize': symbolqty,
				'SymbolPrice': symbolprice,
				'HedgeSize': hedgeqty,
				'HedgePrice': hedgeprice,
				'Note': note
			   }
	
	def GetData(self, symbol: str, hedge: str, side: str) -> pd.Series:
		return self.df.loc[(symbol, hedge, side)]  # type: ignore
	
	def UpdateData(self, strSymbol: str, strHedge: str, strType: str, arNewData: Dict[str, Any]):
		bChanged = False
		self.df.loc[(strSymbol, strHedge, strType), 'Time'] = arNewData.pop('Time', None)
		arOldData = self.GetData(strSymbol, strHedge, strType)
		for key, value in arNewData.items():
			if value is not None:
				if key in self.df.columns and arOldData[key] != value:
					self.df.loc[(strSymbol, strHedge, strType), key] = value
					bChanged = True
		return bChanged

	def _calcCalibrationArbitrage(self, mkt_stock, strMktType, strMktSymbol, stock, strType, usdcny_stock, strTime):
		(strSymbol, fPrice), = stock.GetSymbolPrice(strType).items()
		arQuantity = self.api.CalcQuantity(strSymbol, stock.GetSymbolSize(strType) | mkt_stock.GetSymbolSize(strMktType))
		iSize = arQuantity[strSymbol]
		if iSize > 0:
			arSrcPrice = mkt_stock.GetSymbolPrice(strMktType)
			if PalmmicroStock.IsLOF(strSymbol) == False:
				if usdcny_stock is not None:
					arSrcPrice |= usdcny_stock.GetSymbolPrice()
			fEst = self.api.EstNetValue(strSymbol, arSrcPrice)
			row = self._build_row(strTime, fEst, iSize, fPrice, arQuantity[strMktSymbol], arSrcPrice[strMktSymbol])
			return self.UpdateData(strSymbol, strMktSymbol, strType, row)
		return False
	
	@staticmethod
	def CombineSizeAndPrice(strSymbol, stock, iSize, strType):
		(strRealSymbol, fPrice), = stock.GetSymbolPrice(strType).items()
		strDebug = strSymbol + ' ' + str(iSize)
		if strRealSymbol == strSymbol:
			strDebug += '@' + str(fPrice)
		return strDebug

	def _calcHoldingArbitrage(self, arMktList, mkt_stock, strMktType, strMktSymbol, strMktHoldingSymbol, stock, strType, strTime):
		(strSymbol, fPrice), = stock.GetSymbolPrice(strType).items()
		arSrcPrice = mkt_stock.GetSymbolPrice(strMktType)
		arSrcQuantity = mkt_stock.GetSymbolSize(strMktType)
		for other_stock in arMktList:
			strOtherSymbol = other_stock.GetSymbol()
			if strOtherSymbol != strMktSymbol and strOtherSymbol != strMktHoldingSymbol and self.api.IsHoldingSymbol(strSymbol, strOtherSymbol):
				if other_stock.HasData(strMktType):
					arSrcPrice |= other_stock.GetSymbolPrice(strMktType)
					arSrcQuantity |= other_stock.GetSymbolSize(strMktType)
				else:
					return False
		arQuantity = self.api.CalcQuantity(strSymbol, stock.GetSymbolSize(strType) | arSrcQuantity)
		iSize = arQuantity[strSymbol]
		if iSize > 0:
			arReal = {}
			strAnd = ' + '
			strDebug = ''
			for strHoldingSymbol in self.api.GetHoldingSymbols(strSymbol):
				strRealSymbol = PalmmicroStock.ConvertYahooNetValueSymbol(strHoldingSymbol)
				for all_stock in arMktList:
					if all_stock.GetSymbol() == strRealSymbol:
						iHoldingSize = arQuantity[strHoldingSymbol]
						if iHoldingSize > 0 and strHoldingSymbol != strMktSymbol:
							strDebug += self.CombineSizeAndPrice(strHoldingSymbol, all_stock, iHoldingSize, strMktType) + strAnd
						if strRealSymbol in arReal:
							arReal[strRealSymbol] += iHoldingSize
						else:
							arReal[strRealSymbol] = iHoldingSize
						break
			strDebug = strDebug.rstrip(strAnd)
			if strMktSymbol in arReal:
				iMktSize = arReal[strMktSymbol]
			else:
				iMktSize = arQuantity[strMktSymbol]
			if iMktSize > 0:
				fEst = self.api.EstNetValue(strSymbol, arSrcPrice)
				row = self._build_row(strTime, fEst, iSize, fPrice, iMktSize, arSrcPrice[strMktSymbol], strDebug)
				return self.UpdateData(strSymbol, strMktSymbol, strType, row)
		return False
	
	@staticmethod
	def GetBeijingTime():
		return datetime.now(timezone(timedelta(hours=8))).strftime("%H:%M:%S")
	
	def ProcessPriceAndSize(self, stock, mkt_stock, strType, usdcny_stock = None, arMktList = []):
		strTime = self.GetBeijingTime()
		strSymbol = stock.GetSymbol()
		strMktType = stock.GetPeerType(strType)
		strMktSymbol = mkt_stock.GetSymbol()
		strHedgeSymbol = self.api.GetHedgeSymbol(strSymbol, strMktSymbol)
		if strHedgeSymbol is None:
			strMktHoldingSymbol = self.api.IsFutureOfHoldingSymbol(strSymbol, strMktSymbol)
			if self.api.IsHoldingSymbol(strSymbol, strMktSymbol) or strMktHoldingSymbol != False:
				return self._calcHoldingArbitrage(arMktList, mkt_stock, strMktType, strMktSymbol, strMktHoldingSymbol, stock, strType, strTime)
		else:
			if strHedgeSymbol == strMktSymbol:
				return self._calcCalibrationArbitrage(mkt_stock, strMktType, strMktSymbol, stock, strType, usdcny_stock, strTime)
		return False
