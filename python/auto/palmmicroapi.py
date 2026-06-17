import math
import re
from typing import Any, Dict, Optional

def _get_floor_quantity(iQuantity: int) -> float:
    fQuantity = iQuantity / 100.0
    return math.floor(fQuantity) * 100.0

def _round_quantity(fQuantity: float) -> int:
	return int((fQuantity + 50.0) / 100.0) * 100

def convert_symbol(strSymbol: str) -> str:
	# 匹配 ^XXX-YY 格式，并提取 XXX 部分
	pattern = r'^\^([A-Z]+)-[A-Z]{2}$'
	match = re.match(pattern, strSymbol)
	if match:
		return match.group(1)  # 返回符号主体部分
	else:
		return strSymbol  # 不符合格式则返回原字符串

class PalmmicroAPI:
	DEFAULT_QUANTITY = 1000000
	DEFAULT_HEDGE_QUANTITY = 10000

	# 定义并初始化字典静态变量 arMultiplier，使用 strSymbol 作为键（整数倍率）
	arMultiplier: Dict[str, int] = {'hf_CL': 100,	# MCL:100, CL:1000
									'hf_ES': 5,		# MES
									'hf_GC': 10,	# MGC:10, GC:1000
									'hf_NQ': 2,		# MNQ
									'hf_SI': 5000,	# SI
									'nf_AG0': 15,
									'default': 1}	# 默认倍率

	def __init__(self, config_dict: Dict[str, Any]) -> None:
		"""
		使用字典初始化API

		参数:
			config_dict: 包含配置参数的字典
		"""
		self.config = config_dict

	def get_config(self):
		"""返回当前配置字典"""
		return self.config

	def get_param(self, key, default = None):
		"""获取指定配置参数的值"""
		return self.config.get(key, default)

	@staticmethod
	def is_single(ar):
		if 'calibration' in ar:
			return True
		return False

	@staticmethod
	def get_next_symbol(ar):
		return ar['symbol_hedge']

	def get_next_param(self, ar, default = None):
		return self.config.get(self.get_next_symbol(ar), default)

	def set_param(self, key, value):
		"""设置/更新配置参数"""
		self.config[key] = value

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
	def _get_CNY(ar, arSrc):
		fCny = 1.0
		if arSrc != None:
			if 'CNY' in arSrc:
				fCny = arSrc['CNY']
			elif 'CNY' in ar:
				fCny = float(ar['CNY'])
		else:
			if 'CNYest' in ar:
				fCny = float(ar['CNYest'])
		return fCny

	@staticmethod
	def _reverse_calc_with_calibration(ar, strSymbol, arSrc):
		fCny = PalmmicroAPI._get_CNY(ar, arSrc)
		fPos = float(ar['position'])
		return (arSrc[strSymbol] - (1.0 - fPos) * float(ar['netvalue'])) * float(ar['calibration']) / fPos / fCny

	@staticmethod
	def _calc_with_calibration(ar, arSrc = None):
		fCny = PalmmicroAPI._get_CNY(ar, arSrc)
		fEst = 0.0
		if arSrc != None:
			strHedgeSymbol = PalmmicroAPI.get_next_symbol(ar)
			if strHedgeSymbol in arSrc:
				fEst = arSrc[strHedgeSymbol]
			else:
				if 'est_netvalue' in ar:
					fEst = float(ar['est_netvalue'])
		else:
			if 'est_netvalue' in ar:
				fEst = float(ar['est_netvalue'])
		fPos = float(ar['position'])
		return (1.0 - fPos) * float(ar['netvalue']) + fPos * fEst * fCny  / float(ar['calibration'])

	@staticmethod
	def _calc_with_holdings(ar, arSrc = None):
		fCny = PalmmicroAPI._get_CNY(ar, arSrc)
		fTotal = 0.0
		for strHolding, arHolding in ar['symbol_hedge'].items():
			if arSrc != None:
				strReal = convert_symbol(strHolding)
				if strReal in arSrc:
					fPrice = arSrc[strReal]
				else:
					fPrice = float(arHolding['est_price'])
			else:
				fPrice = float(arHolding['est_price'])
			fTotal += float(arHolding['ratio']) * fPrice / float(arHolding['price'])
		fTotal /= 100.0
		return float(ar['netvalue']) * (1.0 + float(ar['position']) * (fTotal * fCny / float(ar['CNYholdings']) - 1.0))
	
	def __est_calibration_netvalue(self, ar, arSrc):
		arHedge = self.get_next_param(ar)
		if arHedge != None:
			arCopy = None
			if arSrc != None:	# 需要二次计算
				fIndex = None
				strHedgeSymbol = self.get_next_symbol(ar)
				if strHedgeSymbol in arSrc:		# strHedgeSymbol in [SPY, QQQ]
					fIndex = self._reverse_calc_with_calibration(arHedge, strHedgeSymbol, {strHedgeSymbol:arSrc[strHedgeSymbol]})
					# print(f"{strHedgeSymbol}: {fIndex:.2f}")
				else:
					arIndex = self.get_next_param(arHedge)
					if arIndex != None:
						strFutureSymbol = self.get_next_symbol(arIndex)
						if strFutureSymbol in arSrc:	# strFutureSymbol in [hf_ES, hf_NQ]
							fIndex = self._calc_with_calibration(arIndex, {strFutureSymbol:arSrc[strFutureSymbol]})
				if fIndex != None:
					arCopy = arSrc.copy()
					arCopy[strHedgeSymbol] = fIndex
		else:
			arCopy = arSrc
		return self._calc_with_calibration(ar, arCopy)		# 直接算
	
	def __est_holdings_netvalue(self, ar, arSrc):
		arCopy = None
		if arSrc != None:
			arCopy = arSrc.copy()
			for strSrc in arSrc:
				for strHolding in ar['symbol_hedge']:
					arHolding = self.get_param(strHolding)
					if arHolding != None:
						if self.get_next_symbol(arHolding) == strSrc:
							arCopy[strHolding] = self._calc_with_calibration(arHolding, arSrc)
		return self._calc_with_holdings(ar, arCopy)		# 需要按持仓计算

	def EstNetValue(self, strSymbol: str, arSrc: Optional[Dict[str, float]] = None) -> float:
		fEst = 0.0
		ar = self.get_param(strSymbol)
		if ar != None:
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
		if ar != None:
			if self.is_single(ar):
				fEst = self._reverse_calc_with_calibration(ar, strSymbol, arSrc)
				arHedge = self.get_next_param(ar)
				if arHedge != None:
					fEst = self._calc_with_calibration(arHedge, {self.get_next_symbol(arHedge):fEst})
		return fEst

	def __calc_calibration_quantity(self, strSymbol, ar, arSrc):
		arDst = {}
		arHedge = self.get_next_param(ar)
		strHedgeSymbol = self.get_next_symbol(ar)
		fHedge = float(ar['hedge']) * float(self.get_multiplier(strHedgeSymbol))
		iHedgeQuantity = self.DEFAULT_HEDGE_QUANTITY	
		if strHedgeSymbol in arSrc:
			iHedgeQuantity = arSrc[strHedgeSymbol]
		elif arHedge != None:
			arIndex = self.get_next_param(arHedge)
			if arIndex != None:
				strFutureSymbol = self.get_next_symbol(arIndex)
				if strFutureSymbol in arSrc:
					fHedge = float(ar['calibration']) * float(self.get_multiplier(strFutureSymbol)) / float(ar['position'])
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

	def __calc_holdings_quantity(self, strSymbol, ar, arSrc):
		arDst = {}
		arQuantity = {}
		arReal = {}
		fAmount = _get_floor_quantity(arSrc.get(strSymbol, self.DEFAULT_QUANTITY)) * float(ar['netvalue']) * float(ar['position']) / float(ar['CNYholdings'])
		for strHolding, arHolding in ar['symbol_hedge'].items():
			fQuantity = fAmount * (float(arHolding['ratio']) / 100.0) / float(arHolding['price'])
			arQuantity[strHolding] = fQuantity
			strReal = convert_symbol(strHolding)
			if strReal in arReal:
				arReal[strReal] += fQuantity
			else:
				arReal[strReal] = fQuantity
		fMax = 0.0
		for strReal, fQuantity in arReal.items():
			fCompare = fQuantity / arSrc.get(strReal, self.DEFAULT_HEDGE_QUANTITY)
			if fCompare > fMax:
				fMax = fCompare
		if fMax > 1.0:
			for strHolding, fQuantity in arQuantity.items():
				arQuantity[strHolding] = fQuantity / fMax
		fTotal = 0.0
		for strHolding, arHolding in ar['symbol_hedge'].items():
			iQuantity = math.floor(arQuantity[strHolding])
			arDst[strHolding] = iQuantity
			fTotal += iQuantity * float(arHolding['price'])
		arDst[strSymbol] = _round_quantity(fTotal * float(ar['CNYholdings']) / float(ar['netvalue']) / float(ar['position']))
		return arDst

	def CalcQuantity(self, strSymbol: str, arSrc: Dict[str, int]) -> Dict[str, int]:
		ar = self.get_param(strSymbol)
		if ar != None:
			if self.is_single(ar):
				return self.__calc_calibration_quantity(strSymbol, ar, arSrc)
			else:
				return self.__calc_holdings_quantity(strSymbol, ar, arSrc)
		return {}
