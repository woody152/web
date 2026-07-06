import json
import re
import requests
import sys
import time
from typing import Dict, List

#import pandas as pd

sys.path.append('C:/new_tdx64/PYPlugins/user')
from tqcenter import tq

class PalmmicroStock:
	def __init__(self, strSymbol: str):
		self._data = {'symbol': strSymbol,
					  'LAST_price': None,
					  'VWAP_price': None,
					  'BUY_price': None,
					  'SELL_price': None,
					  'BUY_size': None,
					  'SELL_size': None
					 }
	
	def set_value(self, key, value):
		self._data[key] = value
	
	def get_value(self, key):
		return self._data.get(key)
	
	def get_all_data(self):
		return self._data.copy()
	
	def update_data(self, new_data):
		if isinstance(new_data, dict):
			self._data.update(new_data)
	
	def delete_key(self, key):
		if key in self._data:
			del self._data[key]
			return True
		return False
	
	def clear_data(self):
		self._data.clear()

	def GetSymbol(self) -> str:
		strSymbol = self.get_value('symbol')
		if isinstance(strSymbol, str):
			return strSymbol
		return 'errorsymbol'

	def GetSymbolPrice(self, strType: str = 'LAST') -> Dict[str, float]:
		strSymbol = self.GetSymbol()
		fPrice = self.get_value(strType + '_price')
		if isinstance(fPrice, float):
			return {strSymbol: fPrice}
		return {strSymbol: 0.0}
	
	def GetSymbolSize(self, strType: str) -> Dict[str, int]:
		strSymbol = self.GetSymbol()
		iSize = self.get_value(strType + '_size')
		if isinstance(iSize, int):
			return {strSymbol: iSize}
		return {strSymbol: 0}
	
	def HasData(self, strType: str) -> bool:
		return self.get_value(strType + '_price') != None and self.get_value(strType + '_size') != None

	def SetPrice(self, fPrice: float, strType: str = 'LAST') -> None:
		self.set_value(strType + '_price', fPrice)
			
	def SetSize(self, iSize: int, strType: str) -> None:
		self.set_value(strType + '_size', iSize)
			
	@staticmethod
	def IsLOF(strSymbol: str) -> bool:
		return strSymbol.startswith(("SZ16", "SH50"))
	
	@staticmethod
	def IsSymbolA(strSymbol: str) -> bool:
		pattern = r'^(SH|SZ|BJ)\d{6}$'
		if re.match(pattern, strSymbol):
			return True
		return False

	@staticmethod
	def ConvertYahooNetValueSymbol(strSymbol: str) -> str:
		# 匹配 ^XXX-YY 格式，并提取 XXX 部分
		pattern = r'^\^([A-Z]+)-[A-Z]{2}$'
		match = re.match(pattern, strSymbol)
		if match:
			return match.group(1)  # 返回符号主体部分
		else:
			return strSymbol  # 不符合格式则返回原字符串
		"""
		if symbol.startswith('^'):
			symbol = symbol[1:]
			if '-' in symbol:
				symbol = symbol.split('-')[0]
		return symbol
		"""

class IbkrStock(PalmmicroStock):
	def __init__(self, strName):
		self.strName = strName
		if strName.startswith('MCL'):
			strSymbol = 'hf_CL'
		elif strName.startswith('MES'):
			strSymbol = 'hf_ES'
		elif strName.startswith('MGC'):
			strSymbol = 'hf_GC'
		elif strName.startswith('MNQ'):
			strSymbol = 'hf_NQ'
		else:
			strSymbol = strName
		super().__init__(strSymbol)

	def GetNamePrice(self, strType: str = 'LAST') -> Dict[str, float]:
		#(strSymbol, fPrice), = self.GetSymbolPrice(strType).items()
		fPrice = next(iter(self.GetSymbolPrice(strType).values()))
		return {self.strName: fPrice}


class SinaStock(PalmmicroStock):
	#新浪股票类，继承自 PalmmicroStock, 使用新浪接口返回的原始数据字符串进行初始化, 格式如: 'var hq_str_sh600036="招商银行,36.50,36.48,...";'
	def __init__(self, data_str):
		super().__init__(self._parse_symbol(data_str))
		self._update_data(data_str)

	def Update(self, data_str: str) -> None:
		if self._parse_symbol(data_str) == self.GetSymbol():
			self._update_data(data_str)

	def _parse_symbol(self, data_str):
		self.strSinaSymbol = 'unknown'
		strSymbol = 'errorsymbol'
		# 提取股票代码（从 var hq_str_ 后面截取）
		strCodeStart = 'var hq_str_'
		iCodeStart = data_str.find(strCodeStart)
		iStart = data_str.find('"')
		if iCodeStart != -1 and iStart != -1:
			code_part = data_str[iCodeStart + len(strCodeStart):iStart - 1]
			if code_part:
				if code_part.startswith('fx_'):
					strSymbol = code_part[-3:].upper()	# 'CNY'
				elif code_part.startswith('nf_'):
					strSymbol = code_part				# 'nf_AG0'
				elif code_part.startswith('gb_'):
					strSymbol = code_part[3:].upper()	# 'XOP'
				else:
					strSymbol = code_part.upper()		# 'SZ162411'
				self.strSinaSymbol = code_part
		return strSymbol

	def _update_data(self, data_str):
		# 提取引号内的数据
		start = data_str.find('"')
		end = data_str.rfind('"')
		if start != -1 and end != -1 and start < end:
			data_content = data_str[start + 1:end]
			if data_content:
				# 分割数据
				parts = data_content.split(',')
				if self.strSinaSymbol.startswith('fx_'):
					self.SetPrice(float(parts[8]))
				elif self.strSinaSymbol.startswith('nf_'):
					self.SetPrice(float(parts[6]), 'BUY')
					self.SetPrice(float(parts[7]), 'SELL')
					self.SetPrice(float(parts[8]))
					self.SetSize(int(parts[11]), 'BUY')
					self.SetSize(int(parts[12]), 'SELL')
				elif self.strSinaSymbol.startswith('gb_'):
					pass
				else:
					if len(parts) >= 32:
						self.SetPrice(float(parts[6]), 'BUY')
						self.SetPrice(float(parts[7]), 'SELL')
						self.SetSize(int(parts[10]), 'BUY')
						self.SetSize(int(parts[20]), 'SELL')
	
	@staticmethod
	def FetchData(strSymbols: str):
		strUrl = f'http://hq.sinajs.cn/list={strSymbols}'
		try:
			response = requests.get(strUrl, headers={'Referer': 'https://finance.sina.com.cn'})
			if response.status_code == 200:
				return response.text.split("\n")
			else:
				print('Failed to send sina request. Status code:', response.status_code)
		except requests.exceptions.RequestException as e:
			print('FetchSinaData error:', e)
		return False

def _tdx_callback_func(data_str):
	#print('callback data ', data_str, int(time.time()))
	code_json = json.loads(data_str)
	TdxStock.GetData(code_json.get('Code'))

class TdxStock(PalmmicroStock):
	iTimer = 0
	ar_stock = {}

	def __init__(self, strSymbol):
		super().__init__(strSymbol)
		self.strName = self.ConvertToTdxSymbol(strSymbol)

	def __del__(self):
		#un_sub_ptr = tq.unsubscribe_hq([self.strName])
		#print(un_sub_ptr)
		self.OnDel(self.GetSymbol())

	def GetName(self) -> str:
		return self.strName

	def Update(self) -> None:
		if tq._initialized:
			data_dict = tq.get_market_snapshot(self.strName, ['Now', 'Buyp', 'Buyv', 'Sellp', 'Sellv'])
			#print(data_dict)
			self.SetPrice(float(data_dict['Buyp'][0]), 'BUY')
			self.SetPrice(float(data_dict['Sellp'][0]), 'SELL')
			self.SetPrice(float(data_dict['Now']))
			iBuy = int(data_dict['Buyv'][0])
			iSell = int(data_dict['Sellv'][0])
			if self.IsSymbolA(self.GetSymbol()):
				iBuy *= 99
				iSell *= 99
			self.SetSize(iBuy, 'BUY')
			self.SetSize(iSell, 'SELL')
		
	@staticmethod
	def ConvertTdxSymbol(strSymbol: str) -> str:
		strSymbol = strSymbol.strip()				# 去除前后空格
		if '.' in strSymbol:						# 如果包含 '.' 则分割
			parts = strSymbol.split('.')
			if len(parts) == 2:
				symbol = parts[0]					# 股票代码部分
				market = parts[1].upper()			# 市场部分，转为大写
				if market in ['SH', 'SZ', 'BJ']:	# 如果市场代码是 SH 或 SZ，返回市场+代码格式
					return f"{market}{symbol}"
				elif strSymbol == 'AGL8.SHF':
					return 'nf_AG0'
				elif strSymbol == 'USDCNY.OT':
					return 'CNY'
		return strSymbol							# 无法识别格式，返回原字符串

	@staticmethod
	def ConvertToTdxSymbol(strSymbol: str) -> str:
		#strSymbol = strSymbol.strip().upper()		# 去除前后空格
		#if strSymbol[:2] in ['SH', 'SZ', 'BJ']:		# 如果以 SH、SZ、BJ 开头
		if PalmmicroStock.IsSymbolA(strSymbol):
			market = strSymbol[:2]
			symbol = strSymbol[2:]
			return f"{symbol}.{market}"
		elif strSymbol == 'CNY':
			return 'USDCNY.OT'
		elif strSymbol == 'nf_AG0':
			return 'AGL8.SHF'
		return strSymbol							# 无法识别格式，返回原字符串

	@classmethod
	def Init(cls, arSymbol: List):
		tq.initialize(__file__)
		#match_stkinfo = tq.get_match_stkinfo('USDCNY')
		#print(match_stkinfo)
		ar = []
		for strSymbol in arSymbol:
			stock = TdxStock(strSymbol)
			cls.ar_stock[strSymbol] = stock
			ar.append(stock.GetName())
		sub_hq = tq.subscribe_hq(ar, _tdx_callback_func)
		print(sub_hq)
		return cls.ar_stock

	@classmethod
	def OnDel(cls, strSymbol: str):
		del cls.ar_stock[strSymbol]
		if len(cls.ar_stock) == 0:
			print('closing...')
			#tq.close()

	@classmethod
	def TqDebug(cls, strDebug: str) -> None:
		try:
			tq.send_message(str)
		except Exception as e:
			print(f"TqDebug异常: {e}")

	@classmethod
	def _refresh_cache(cls, strMarket):
		try:
			cache = tq.refresh_cache(strMarket, True)
		except Exception as e:
			print(f"_refresh_cache异常: {e}")
			return
		cache_json = json.loads(cache)
		if cache_json['ErrorId'] != '0':
			print(strMarket, cache_json['Error'])
		else:
			pass
			#cls.TqDebug(f'{strMarket} refresh cache')

	@classmethod
	def GetData(cls, strName):
		strSymbol = cls.ConvertTdxSymbol(strName)
		cls.ar_stock[strSymbol].Update()
		iCur = int(time.time())
		if iCur - cls.iTimer >= 6:
			cls.iTimer = iCur
			#cls._refresh_cache('AG')
			#cls._refresh_cache('QH')
