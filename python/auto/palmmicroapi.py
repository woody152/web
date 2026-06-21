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

	def set_param(self, key: str, value):
		"""设置/更新配置参数"""
		self.config[key] = value

	def get_param(self, key: str, default = None):
		"""获取指定配置参数的值"""
		return self.config.get(key, default)

	@staticmethod
	def IsLOF(strSymbol: str) -> bool:
		return strSymbol.startswith(("SZ16", "SH50"))
	
	@staticmethod
	def is_single(ar) -> bool:
		return 'calibration' in ar

	@staticmethod
	def get_next_symbol(ar) -> str:
		return ar['symbol_hedge']

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
	def _get_hedge(ar):
		return float(ar['calibration']) / float(ar['position'])

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
	def _calc_with_calibration(ar, arSrc):
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
	def _calc_with_holdings(ar, arSrc):
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
		strHedgeSymbol = self.get_next_symbol(ar)
		arHedge = self.get_param(strHedgeSymbol)
		if arHedge != None:
			arCopy = None
			if arSrc != None:	# 需要二次计算
				arCopy = arSrc.copy()
				fIndex = None
				if strHedgeSymbol in arSrc:		# strHedgeSymbol in [SPY, QQQ]
					fIndex = self._reverse_calc_with_calibration(arHedge, strHedgeSymbol, {strHedgeSymbol:arSrc[strHedgeSymbol]})
					# print(f"{strHedgeSymbol}: {fIndex:.2f}")
				else:
					arIndex = self.get_next_param(arHedge)
					if arIndex != None:
						strFutureSymbol = self.get_next_symbol(arIndex)
						if strFutureSymbol in arSrc:	# strFutureSymbol in [hf_ES, hf_NQ]
							fIndex = self._calc_with_calibration(arIndex, {strFutureSymbol: arSrc[strFutureSymbol]})
				if fIndex != None:
					arCopy[strHedgeSymbol] = fIndex
		else:
			arCopy = arSrc
			if arSrc != None:	# 检查是否有需要二次计算的杠杆ETF输入
				for strLevSymbol in arSrc:
					arLev = self.get_param(strLevSymbol)
					if arLev != None:
						if strHedgeSymbol == self.get_next_symbol(arLev):
							arCopy = arSrc.copy()
							arCopy[strHedgeSymbol] = self._reverse_calc_with_calibration(arLev, strLevSymbol, {strLevSymbol: arSrc[strLevSymbol]})
							break
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
		strHedgeSymbol = self.get_next_symbol(ar)
		fHedge = float(ar['hedge']) * float(self.get_multiplier(strHedgeSymbol))
		iHedgeQuantity = self.DEFAULT_HEDGE_QUANTITY	
		if strHedgeSymbol in arSrc:
			iHedgeQuantity = arSrc[strHedgeSymbol]
		else:
			arHedge = self.get_param(strHedgeSymbol)
			if arHedge != None:
				arIndex = self.get_next_param(arHedge)
				if arIndex != None:
					strFutureSymbol = self.get_next_symbol(arIndex)
					if strFutureSymbol in arSrc:
						fHedge = self._get_hedge(ar) * self.get_multiplier(strFutureSymbol)
						iHedgeQuantity = arSrc[strFutureSymbol]
						strHedgeSymbol = strFutureSymbol
			else:
				for strLevSymbol in arSrc:
					if strLevSymbol != strSymbol:
						arLev = self.get_param(strLevSymbol)
						if arLev != None:
							if strHedgeSymbol == self.get_next_symbol(arLev):
								fHedge /= self._get_hedge(arLev) * self.get_multiplier(strLevSymbol)
								iHedgeQuantity = arSrc[strLevSymbol]
								strHedgeSymbol = strLevSymbol
								break
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
			strReal = convert_symbol(strHolding)
			if strReal in arReal:
				arReal[strReal] += fQuantity
			else:
				arReal[strReal] = fQuantity
		fMax = 0.0
		strMax = False
		for strReal, fQuantity in arReal.items():
			if strReal in arSrc:
				fRealQuantity = float(arSrc[strReal])
			else:
				fRealQuantity = self.DEFAULT_HEDGE_QUANTITY
				arHolding = self.get_param(strReal)
				if arHolding != None:
					strNextSymbol = self.get_next_symbol(arHolding) 
					if strNextSymbol in arSrc:
						strFutureSymbol = strNextSymbol
						iFutureQuantity = arSrc[strFutureSymbol]
						arDst[strFutureSymbol] = arSrc[strFutureSymbol]
						fRealQuantity = self._get_hedge(arHolding) * self.get_multiplier(strFutureSymbol) * iFutureQuantity
						#print(f"RealQuantity {strFutureSymbol}: {fRealQuantity:.2f}")
			fCompare = fQuantity / fRealQuantity
			if fCompare > fMax:
				fMax = fCompare
				strMax = strReal
		#print(f"Max {strMax}: {fMax:.2f}")
		if fMax < 1.0 and strMax not in arSrc:
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
		#print(f"float: {arQuantity}")			
		for strHolding in ar['symbol_hedge']:
			arDst[strHolding] = int(math.floor(arQuantity[strHolding]))
		arDst[strSymbol] = self._recalc_key_quantity(ar, arDst)
		#print(f"int: {arDst}")			
		return arDst

	def CalcQuantity(self, strSymbol: str, arSrc: Dict[str, int]) -> Dict[str, int]:
		ar = self.get_param(strSymbol)
		if ar != None:
			if self.is_single(ar):
				return self.__calc_calibration_quantity(strSymbol, ar, arSrc)
			else:
				return self.__calc_holdings_quantity(strSymbol, ar, arSrc)
		return {}
