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

	@staticmethod
	def _calc_with_holdings(ar, arSrc = None):
		fCny = 1.0
		fTotal = 0.0
		for strHolding, arHolding in ar['symbol_hedge'].items():
			if arSrc != None:
				fEst = arSrc[strHolding]
			else:
				fEst = float(arHolding['est_price'])
			fTotal += float(arHolding['ratio']) * fEst / float(arHolding['price'])
		fTotal /= 100.0
		return float(ar['netvalue']) * (1.0 + float(ar['position']) * (fTotal * float(ar['CNY']) / float(ar['CNYholdings']) - 1.0))

	def EstNetValue(self, strSymbol, arSrc = None):
		fEst = 0.0
		if strSymbol in self.config:
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
						fEst = self._calc_with_calibration(ar, arCopy)
					else:
						fEst = self._calc_with_calibration(ar)	# 直接算官方估值
				else:
					fEst = self._calc_with_calibration(ar, arSrc)	# 直接算
			else:
				fEst = self._calc_with_holdings(ar, arSrc)	# 需要按持仓计算
		return fEst
        
	def ReverseEst(self, arSrc):
		for strSymbol in arSrc:
			if strSymbol != 'CNY':
				break
		fEst = 0.0
		if strSymbol in self.config:
			ar = self.config[strSymbol]
			if 'calibration' in ar:
				fEst = self._reverse_calc_with_calibration(ar, strSymbol, arSrc)
				strHedgeSymbol = ar['symbol_hedge']
				if strHedgeSymbol in self.config:   # strSymbol in [SZ161125, SZ161130]
					arHedge = self.config[strHedgeSymbol]
					fEst = self._calc_with_calibration(arHedge, {arHedge['symbol_hedge']:fEst})
		return fEst