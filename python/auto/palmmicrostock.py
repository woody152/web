import re
import requests
from typing import Dict

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

	def GetSymbolPrice(self, strType: str = 'BUY') -> Dict[str, float]:
		strSymbol = self.GetSymbol()
		fPrice = self.get_value(strType + '_price')
		if isinstance(fPrice, float):
			return {strSymbol: fPrice}
		return {strSymbol: 0.0}
	
	def GetSymbolSize(self, strType: str = 'BUY') -> Dict[str, int]:
		strSymbol = self.GetSymbol()
		iSize = self.get_value(strType + '_size')
		if isinstance(iSize, int):
			return {strSymbol: iSize}
		return {strSymbol: 0}
	
	def HasData(self, strType: str = 'BUY') -> bool:
		return self.get_value(strType + '_price') != None and self.get_value(strType + '_size') != None

	def SetPrice(self, fPrice: float, strType: str = 'BUY') -> None:
		self.set_value(strType + '_price', fPrice)
			
	def SetSize(self, iSize: int, strType: str = 'BUY') -> None:
		self.set_value(strType + '_size', iSize)
			
	@staticmethod
	def IsLOF(strSymbol: str) -> bool:
		return strSymbol.startswith(("SZ16", "SH50"))

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
					self.SetPrice(float(parts[8]), 'LAST')
				elif self.strSinaSymbol.startswith('nf_'):
					self.SetPrice(float(parts[6]), 'BUY')
					self.SetPrice(float(parts[7]), 'SELL')
					self.SetPrice(float(parts[8]), 'LAST')
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


class IbkrStock(PalmmicroStock):
	def __init__(self, strName):
		self.strName = strName
		if strName.startswith('MES'):
			strSymbol = 'hf_ES'
		elif strName.startswith('MNQ'):
			strSymbol = 'hf_NQ'
		else:
			strSymbol = strName
		super().__init__(strSymbol)

	def GetNamePrice(self, strType: str = 'BUY') -> Dict[str, float]:
		(strSymbol, fPrice), = self.GetSymbolPrice(strType).items()
		return {self.strName: fPrice}
