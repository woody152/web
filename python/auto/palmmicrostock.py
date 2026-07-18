import json
import os
import pandas as pd
import re
import requests
import sched
import sys
import time
import threading
from typing import Any, Callable, Dict, List, Optional

sys.path.append('C:/new_tdx64/PYPlugins/user')
from tqcenter import tq	# type: ignore

#单个定时任务类，封装任务及其调度信息
class PalmmicroTask:
	def __init__(self, name: str, func: Callable, interval: int, args: tuple = (), kwargs: Optional[Dict[str, Any]] = None, daemon: bool = True):
		self.name = name
		self.func = func
		self.interval = interval
		self.args = args
		self.kwargs = kwargs or {}
		self.daemon = daemon
		self.scheduler = sched.scheduler(time.time, time.sleep)
		self.thread: Optional[threading.Thread] = None
		self.is_running = False
		self.lock = threading.Lock()
		self.event_id: Optional[Any] = None
	
	def _execute_and_reschedule(self):
		#执行任务并重新调度自己
		try:
			self.func(*self.args, **self.kwargs)
		except Exception as e:
			print(f"任务 '{self.name}' 执行失败: {e}")
		finally:
			# 重新调度自己（如果任务仍在运行）
			with self.lock:
				if self.is_running:
					self.event_id = self.scheduler.enter(self.interval, 1, self._execute_and_reschedule)
	
	def _run_scheduler(self):
		#运行调度器循环
		while self.is_running:
			try:
				self.scheduler.run()
				# 当所有事件都执行完时，run()会返回，需要短暂休眠避免CPU空转
				if self.is_running and not self.scheduler.queue:
					time.sleep(0.1)
			except Exception as e:
				print(f"任务 '{self.name}' 调度器异常: {e}")
				break
	
	def start(self, delay: int = 0):
		#启动定时任务
		with self.lock:
			if self.is_running:
				print(f"任务 '{self.name}' 已经在运行中")
				return
			
			self.is_running = True
			# 首次调度，延迟delay秒后执行
			self.event_id = self.scheduler.enter(delay, 1, self._execute_and_reschedule)
			# 启动调度器线程
			self.thread = threading.Thread(target = self._run_scheduler, daemon = self.daemon, name = f"Scheduler-{self.name}")
			self.thread.start()
			print(f"任务 '{self.name}' 已启动，首次执行延迟 {delay} 秒")
	
	def stop(self, wait: bool = False):
		#停止定时任务
		with self.lock:
			if not self.is_running:
				print(f"任务 '{self.name}' 未在运行")
				return
			
			self.is_running = False
			# 从调度器队列中移除待执行的事件
			if self.event_id is not None:
				try:
					self.scheduler.cancel(self.event_id)
				except ValueError:
					pass  # 事件可能已经执行了
			
			print(f"任务 '{self.name}' 已停止")
		
		if wait and self.thread and self.thread.is_alive():
			self.thread.join(timeout=5)
	
	def is_alive(self) -> bool:
		#检查任务是否在运行
		if self.thread is None:
			return False
		return self.is_running and self.thread.is_alive()
	
	@staticmethod
	def GetThreadsDataFrame() -> pd.DataFrame:
		#获取线程信息并包含栈帧信息（需要 sys._current_frames()）
		threads = threading.enumerate()
		current_thread = threading.current_thread()
		main_thread = threading.main_thread()
	
		# 获取所有线程的栈帧
		frames = sys._current_frames()
		thread_data: Dict[str, Any] = {'name': [], 'ident': [], 'native_id': [], 'alive': [], 'daemon': [], 'main': [], 'current': [], 'filename': [], 'line': [], 'function': []}
		for t in threads:
			thread_data['name'].append(t.name)
			thread_data['ident'].append(t.ident)
			native_id = getattr(t, 'native_id', None)
			thread_data['native_id'].append(native_id)
			thread_data['alive'].append(t.is_alive())
			thread_data['daemon'].append(t.daemon)
			thread_data['main'].append(t is main_thread)
			thread_data['current'].append(t is current_thread)
		
			# 获取栈帧信息
			if t.ident in frames:
				frame = frames[t.ident]
				thread_data['filename'].append(os.path.basename(frame.f_code.co_filename))
				thread_data['line'].append(frame.f_lineno)
				thread_data['function'].append(frame.f_code.co_name)
			else:
				thread_data['filename'].append(None)
				thread_data['line'].append(None)
				thread_data['function'].append(None)
		return pd.DataFrame(thread_data)


class PalmmicroStock:
	def __init__(self, strSymbol: str, strName = None):
		self._data = {'symbol': strSymbol,
					  'LAST_price': None,
					  'VWAP_price': None,
					  'BUY_price': None,
					  'SELL_price': None,
					  'BUY_size': None,
					  'SELL_size': None,
					  'BUY_updated': False,
					  'SELL_updated': False
					 }
		if strName is None:
			self.strName = strSymbol
		else:
			self.strName = strName
	
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

	def GetName(self) -> str:
		return self.strName

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
		return self.get_value(strType + '_price') is not None and self.get_value(strType + '_size') is not None

	def IsUpdated(self, strType: str):
		return self.get_value(strType + '_updated')

	def SetUpdated(self, strType: str, bStatus: bool = True):
		if strType in self.GetTypeList():
			self.set_value(strType + '_updated', bStatus)
		
	def SetPrice(self, fPrice: float, strType: str = 'LAST') -> None:
		fOld = self.get_value(strType + '_price')
		if fOld is None or abs(fOld - fPrice) > 0.0001:
			self.set_value(strType + '_price', fPrice)
			self.SetUpdated(strType)
			
	def SetSize(self, iSize: int, strType: str) -> None:
		iOld = self.get_value(strType + '_size')
		if iOld is None or iOld != iSize:
			self.set_value(strType + '_size', iSize)
			self.SetUpdated(strType)

	@staticmethod
	def GetTypeList() -> List[str]:
		return ['BUY', 'SELL']

	@staticmethod
	def GetPeerType(strType: str) -> str:
		if strType == 'SELL':
			return 'BUY'
		return 'SELL'

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

	@staticmethod
	def JoinSymbols(arStock):
		return ','.join(arStock.keys())
	
		
#新浪股票类，继承自 PalmmicroStock, 使用新浪接口返回的原始数据字符串进行初始化, 格式如: 'var hq_str_sh600036="招商银行,36.50,36.48,...";'
class SinaStock(PalmmicroStock):
	arStock = {}

	def __init__(self, data_str):
		strSymbol, strName = self.ParseSymbol(data_str)
		super().__init__(strSymbol, strName)
		self.UpdateData(data_str)

	def Update(self, data_str: str) -> None:
		strSymbol, _ = self.ParseSymbol(data_str)
		if strSymbol == self.GetSymbol():
			self.UpdateData(data_str)

	@staticmethod
	def ParseSymbol(data_str):
		strSinaSymbol = 'unknown'
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
				elif code_part.startswith('hf_') or code_part.startswith('nf_'):
					strSymbol = code_part				# 'hf_CL, nf_AG0'
				elif code_part.startswith('gb_'):
					strSymbol = code_part[3:].upper()	# 'XOP'
				else:
					strSymbol = code_part.upper()		# 'SZ162411'
				strSinaSymbol = code_part
		return strSymbol, strSinaSymbol

	def __set_price_and_size(self, parts, last, buyp = None, sellp = None, buyv = None, sellv = None):
		self.SetPrice(float(parts[last]))
		if buyp is not None:
			self.SetPrice(float(parts[buyp]), 'BUY')
		if sellp is not None:
			self.SetPrice(float(parts[sellp]), 'SELL')
		if buyv is not None:
			self.SetSize(int(parts[buyv]), 'BUY')
		if sellv is not None:
			self.SetSize(int(parts[sellv]), 'SELL')

	def UpdateData(self, data_str):
		# 提取引号内的数据
		start = data_str.find('"')
		end = data_str.rfind('"')
		if start != -1 and end != -1 and start < end:
			data_content = data_str[start + 1:end]
			if data_content:
				# 分割数据
				strSinaSymbol = self.GetName()
				parts = data_content.split(',')
				if strSinaSymbol.startswith('fx_'):
					self.__set_price_and_size(parts, 8)
				elif strSinaSymbol.startswith('hf_'):
					self.__set_price_and_size(parts, 0, 2, 3, 10, 11)
				elif strSinaSymbol.startswith('nf_'):
					self.__set_price_and_size(parts, 8, 6, 7, 11, 12)
				elif strSinaSymbol.startswith('gb_'):
					self.__set_price_and_size(parts, 1)
				else:
					if len(parts) >= 32:
						self.__set_price_and_size(parts, 3, 6, 7, 10, 20)
	
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

	@classmethod
	def UpdateStock(cls, stock, strLine):
		if stock is None:
			stock = SinaStock(strLine)
		else:
			stock.Update(strLine)
		return stock

	@classmethod
	def TaskLoop(cls, strSymbols):
		arLine = cls.FetchData(strSymbols)
		if arLine:
			for strLine in arLine:
				strSymbol, strName = cls.ParseSymbol(strLine)
				if strSymbol not in cls.arStock.keys():
					cls.arStock[strSymbol] = SinaStock(strLine)
				cls.arStock[strSymbol].UpdateData(strLine)
		print(PalmmicroTask.GetThreadsDataFrame())

	@classmethod
	def TaskInit(cls, strSymbols: str = 'fx_susdcny,nf_AG0'):
		#cls.ThreadLoop(strSymbols)
		task = PalmmicroTask(cls.__name__, cls.TaskLoop, 19, (strSymbols, ))
		task.start()
		return cls.arStock


class IbkrStock(PalmmicroStock):
	def __init__(self, strName):
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
		super().__init__(strSymbol, strName)

	def GetNamePrice(self, strType: str = 'LAST') -> Dict[str, float]:
		(_, fPrice), = self.GetSymbolPrice(strType).items()
		return {self.GetName(): fPrice}


def _tdx_callback_func(data_str):
	#print('callback data ', data_str, int(time.time()))
	code_json = json.loads(data_str)
	TdxStock.GetData(code_json.get('Code'))

class TdxStock(PalmmicroStock):
	arStock = {}

	def __init__(self, strName):
		super().__init__(self.ConvertTdxSymbol(strName), strName)

	def Update(self) -> None:
		try:
			data_dict = tq.get_market_snapshot(self.GetName(), ['ErrorId', 'Now', 'Buyp', 'Buyv', 'Sellp', 'Sellv'])
		except Exception as e:
			print(f"tq.get_market_snapshot异常: {e}")
			return
		#print(data_dict)
		if data_dict['ErrorId'] == '0':	
			self.SetPrice(float(data_dict['Buyp'][0]), 'BUY')
			self.SetPrice(float(data_dict['Sellp'][0]), 'SELL')
			self.SetPrice(float(data_dict['Now']))
			iBuy = int(data_dict['Buyv'][0])
			iSell = int(data_dict['Sellv'][0])
			if self.IsSymbolA(self.GetSymbol()):
				iBuy *= 100
				iSell *= 100
				iBuy -= 50
				iSell -= 50
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

	""" 
	#从通达信拿汇率或者期货数据时才需要
	@staticmethod
	def ConvertToTdxName(strSymbol: str) -> str:
		#strSymbol = strSymbol.strip().upper()		# 去除前后空格
		if PalmmicroStock.IsSymbolA(strSymbol):
			market = strSymbol[:2]
			symbol = strSymbol[2:]
			return f"{symbol}.{market}"
		elif strSymbol == 'CNY':
			return 'USDCNY.OT'
		elif strSymbol == 'nf_AG0':
			return 'AGL8.SHF'
		return strSymbol							# 无法识别格式，返回原字符串
	"""

	@classmethod
	def TqInit(cls, strBlockCode: str = 'PLMM'):
		tq.initialize(__file__)

		#match_stkinfo = tq.get_match_stkinfo('USDCNY')
		#print(match_stkinfo)
		block_stocks = tq.get_stock_list_in_sector(strBlockCode, 1)
		#print(block_stocks)

		ar = []
		for strName in block_stocks:
			stock = TdxStock(strName)
			strSymbol = stock.GetSymbol()
			cls.arStock[strSymbol] = stock
			ar.append(strName)
		sub_hq = tq.subscribe_hq(ar, _tdx_callback_func)
		print(sub_hq)
		return cls.arStock

	@classmethod
	def TqDebug(cls, strDebug: str) -> None:
		try:
			tq.send_message(strDebug)
		except Exception as e:
			print(f"TqDebug异常: {e}")

	"""
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
	"""

	@classmethod
	def GetData(cls, strName):
		strSymbol = cls.ConvertTdxSymbol(strName)
		cls.arStock[strSymbol].Update()
		"""
		#cls._refresh_cache('AG')
		#cls._refresh_cache('QH')
		"""
