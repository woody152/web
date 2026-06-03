class PalmmicroAPI:
	def __init__(self, config_dict):
		"""
        使用字典初始化API
        
        参数:
            config_dict: 包含配置参数的字典
        """
		self.config = config_dict
    
	def get_config(self):
		"""返回当前配置字典"""
		return self.config
    
	def get_param(self, key, default=None):
		"""获取指定配置参数的值"""
		return self.config.get(key, default)
    
	def set_param(self, key, value):
		"""设置/更新配置参数"""
		self.config[key] = value

	@staticmethod
	def _reverse_calc_with_calibration(ar, strSymbol, arSrc):
		if 'CNY' in arSrc:
			fCny = arSrc['CNY']
		elif 'CNY' in ar:
			fCny = float(ar['CNY'])
		else:
			fCny = 1.0
		fPos = float(ar['position'])
		return (arSrc[strSymbol] - (1.0 - fPos) * float(ar['netvalue'])) * float(ar['calibration']) / fPos / fCny

	@staticmethod
	def _calc_with_calibration(ar, arSrc = None):
		fCny = 1.0
		fEst = 0.0
		if arSrc != None:
			strHedgeSymbol = ar['symbol_hedge']
			if strHedgeSymbol in arSrc:
				fEst = arSrc[strHedgeSymbol]
			else:
				if 'est_netvalue' in ar:
					fEst = float(ar['est_netvalue'])
			if 'CNY' in arSrc:
				fCny = arSrc['CNY']
			elif 'CNY' in ar:
				fCny = float(ar['CNY'])
		else:
			if 'est_netvalue' in ar:
				fEst = float(ar['est_netvalue'])
			if 'CNYest' in ar:
				fCny = float(ar['CNYest'])
		fPos = float(ar['position'])
		return (1.0 - fPos) * float(ar['netvalue']) + fPos * fEst * fCny  / float(ar['calibration'])

	def EstNetValue(self, strSymbol, arSrc = None):
		if strSymbol not in self.config:
			return False  
		ar = self.config[strSymbol]
		if 'calibration' in ar:
			strHedgeSymbol = ar['symbol_hedge']
			if strHedgeSymbol in self.config:   # strSymbol in [SZ161125, SZ161130]
				if arSrc != None:	# 需要二次计算
					arHedge = self.config[strHedgeSymbol]
					if strHedgeSymbol in arSrc:	# strHedgeSymbol in [SPY, QQQ]
						fIndex = self._reverse_calc_with_calibration(arHedge, strHedgeSymbol, {strHedgeSymbol:arSrc[strHedgeSymbol]})
					else:	# strFutureSymbol in [hf_ES, hf_NQ]
						strIndexSymbol = arHedge['symbol_hedge']
						arIndex = self.config[strIndexSymbol]
						strFutureSymbol = arIndex['symbol_hedge']
						fIndex = self._calc_with_calibration(arIndex, {strFutureSymbol:arSrc[strFutureSymbol]})
					arCopy = arSrc.copy()
					arCopy[strHedgeSymbol] = fIndex
					return self._calc_with_calibration(ar, arCopy)
				else:
					return self._calc_with_calibration(ar)	# 直接算官方估值
			else:
				return self._calc_with_calibration(ar, arSrc)	# 直接算
		else:
			print(strSymbol, '需要按持仓计算')
		return True
        
	def ReverseEst(self, arSrc):
		for strSymbol in arSrc:
			if strSymbol != 'CNY':
				break
		return self._reverse_calc_with_calibration(self.config[strSymbol], strSymbol, arSrc)