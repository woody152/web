import dtale
import time
from datetime import datetime, timezone, timedelta

from _mytoken import BOT_TOKEN
#from _mytoken import ROT_TOKEN

from palmmicrostock import PalmmicroStock, SinaStock, TdxStock
from palmmicroapi import PalmmicroAPI, PalmmicroDataFrame

def __printHedge(api, ar, strSymbol, strSymbolUS, iSizeUS = None):
	if iSizeUS is None:
		iSizeUS = ar[strSymbolUS]
	if iSizeUS != 0:
		print(f"{strSymbolUS}对冲值: {ar[strSymbol]/iSizeUS/api.get_multiplier(strSymbolUS):.0f}")

def __printEst(strSymbol, fNetValue, strType = '官方', strMethod = '直接'):
	print(f"{strMethod}算{strSymbol}{strType}估值: {fNetValue:.3f}")

def __printHoldingEst(strSymbol, fNetValue, strType = '官方'):
	__printEst(strSymbol, fNetValue, strType, '按持仓')

def __testXOP(api, strSymbol, strSymbolUS, iQuantity, iQuantityUS, fPriceUS, arUSDCNY):
	fNetValue = api.EstNetValue(strSymbol)
	__printEst(strSymbol, fNetValue)
	if PalmmicroStock.IsLOF(strSymbol):
		fNetValue = api.EstNetValue(strSymbol, {strSymbolUS: fPriceUS})
		fPriceUS = api.ReverseEst({strSymbol: fNetValue})
	else:
		fNetValue = api.EstNetValue(strSymbol, arUSDCNY)
		__printEst(strSymbol, fNetValue, '参考')
		fNetValue = api.EstNetValue(strSymbol, {strSymbolUS: fPriceUS} | arUSDCNY)
		fPriceUS = api.ReverseEst({strSymbol: fNetValue} | arUSDCNY)
	ar = api.CalcQuantity(strSymbol, {strSymbol: iQuantity, strSymbolUS: iQuantityUS})
	__printHedge(api, ar, strSymbol, strSymbolUS)
	print(f"直接算{strSymbol}: {ar[strSymbol]}@{fNetValue:.3f}, 对应{strSymbolUS}: {ar[strSymbolUS]}, 反向算XOP@{fPriceUS:.2f}")
	ar = api.CalcQuantity(strSymbol, {strSymbolUS: iQuantityUS})
	__printHedge(api, ar, strSymbol, strSymbolUS)
	print(f"只输入{strSymbolUS}数量时建议: {ar[strSymbol]}, 对应{strSymbolUS}: {ar[strSymbolUS]}")
	ar = api.CalcQuantity(strSymbol, {strSymbol: iQuantity})
	__printHedge(api, ar, strSymbol, 'XOP')
	print(f"只输入{strSymbol}数量时建议: {ar[strSymbol]}, 对应XOP: {ar['XOP']}")
	ar = api.CalcQuantity(strSymbol, {})
	__printHedge(api, ar, strSymbol, 'XOP')
	print(f"无输入数量或者出错时建议: {ar[strSymbol]}, 对应XOP: {ar['XOP']}")

def __testSPY(api, strSymbol, strSymbolUS, iQuantity, iQuantityUS, fPriceUS, arUSDCNY):
	fNetValue = api.EstNetValue(strSymbol)
	__printEst(strSymbol, fNetValue)
	if PalmmicroStock.IsLOF(strSymbol):
		fNetValue = api.EstNetValue(strSymbol, {strSymbolUS: fPriceUS})
		fPriceUS = api.ReverseEst({strSymbol: fNetValue})
	else:
		fNetValue = api.EstNetValue(strSymbol, arUSDCNY)
		__printEst(strSymbol, fNetValue, '参考')
		fNetValue = api.EstNetValue(strSymbol, {strSymbolUS: fPriceUS} | arUSDCNY)
		fPriceUS = api.ReverseEst({strSymbol: fNetValue} | arUSDCNY)
	ar = api.CalcQuantity(strSymbol, {strSymbol: iQuantity, strSymbolUS: iQuantityUS})
	__printHedge(api, ar, strSymbol, strSymbolUS)
	print(f"把{strSymbolUS}转换成^GSPC后二次计算{strSymbol}: {ar[strSymbol]}@{fNetValue:.3f}, 对应{strSymbolUS}: {ar[strSymbolUS]}, 反向算SPY@{fPriceUS:.2f}")

def __debugUSO(api, strSymbol, arQuantity):
	strDebug = ''
	iSum = 0
	arHolding = api.GetHoldingSymbols(strSymbol)
	if arHolding is not None:
		for strHolding in arHolding:
			iSize = arQuantity[strHolding]
			strDebug += f", {strHolding}: {iSize}"
			iSum += iSize
		__printHedge(api, arQuantity, strSymbol, 'USO', iSum)
	return strDebug

def __getSize(arStock, arSymbol, strType = 'SELL'):
	arQuantity = {}
	for strSymbol in arSymbol:
		arQuantity |= arStock[strSymbol].GetSymbolSize(strType)
	return arQuantity

def FetchPalmmicroData():
	arSinaStock = SinaStock.TaskInit('fx_susdcny,hf_ES,hf_CL,hf_GC,hf_NQ,nf_AG0')
	arTdxStock = TdxStock.TqInit()
	api = PalmmicroAPI(PalmmicroAPI.FetchData(PalmmicroStock.JoinSymbols(arTdxStock), BOT_TOKEN))
	pdf = PalmmicroDataFrame(api)

	d_column_formats = {'Percent': {'fmt': '0.00%'}, 'SymbolPrice': {'fmt': '0.000'}}
	d = dtale.show(pdf.GetDataFrame(),
				   host = '127.0.0.1',
				   port = 40005,
				   column_formats = d_column_formats,
				   reaper_on = False  # <--- 添加这一行，禁用闲置清理
				   )
	d.open_browser()
	
	while True:
		if all(key in arSinaStock for key in ['CNY', 'nf_AG0']):
			usdcny_stock = arSinaStock['CNY']
			#cl_stock = arSinaStock['hf_CL']
			ag0_stock = arSinaStock['nf_AG0']
			break
		time.sleep(1)

	while True:
		time.sleep(1)
		bHasData = True
		for stock in arTdxStock.values():
			if (stock.HasData('BUY') and stock.HasData('SELL')) == False:
				print(stock.GetSymbol(), ' has no data')
				bHasData = False
				break
		if bHasData:
			break
	
	arCNY = usdcny_stock.GetSymbolPrice()
	arQuantity = __getSize(arTdxStock, {'SZ162411', 'SZ159518'})
	arQuantityUS = {'XOP': 1000, 'GUSH': 10000}
	arPriceUS = {'XOP': 136.47, 'GUSH': 23.32}
	for strSymbol, iQuantity in arQuantity.items():
		for strSymbolUS, iQuantityUS in arQuantityUS.items():
			__testXOP(api, strSymbol, strSymbolUS, iQuantity, iQuantityUS, arPriceUS[strSymbolUS], arCNY)

	arQuantity = __getSize(arTdxStock, {'SZ161125', 'SZ159612'})
	arQuantityUS = {'SPY': 100, 'hf_ES': 2}
	arPriceUS = {'SPY': 515.89, 'hf_ES': 5210.0}
	for strSymbol, iQuantity in arQuantity.items():
		for strSymbolUS, iQuantityUS in arQuantityUS.items():
			__testSPY(api, strSymbol, strSymbolUS, iQuantity, iQuantityUS, arPriceUS[strSymbolUS], arCNY)
    
	arQuantity = arTdxStock['SZ164701'].GetSymbolSize('BUY')
	f164701 = api.EstNetValue('SZ164701')
	__printHoldingEst('SZ164701', f164701)
	f164701 = api.EstNetValue('SZ164701', {'GLD': 349.23, 'SLV': 46.69})
	ar164701 = api.CalcQuantity('SZ164701', arQuantity | {'GLD': 100, 'SLV': 100})
	__printHedge(api, ar164701, 'SZ164701', 'GLD')
	print(f"按持仓算SZ164701: {ar164701['SZ164701']}@{f164701:.3f}, GLD: {ar164701['GLD']}, SLV: {ar164701['SLV']}")
	f164701 = api.EstNetValue('SZ164701', {'hf_GC': 3816.76, 'hf_SI': 52.03})
	ar164701 = api.CalcQuantity('SZ164701', arQuantity | {'hf_GC': 1, 'SLV': 100})
	__printHedge(api, ar164701, 'SZ164701', 'GLD')
	print(f"把hf_GC和hf_SI转换成GLD和SLV后, 按持仓算SZ164701: {ar164701['SZ164701']}@{f164701:.3f}, GLD: {ar164701['GLD']}, SLV: {ar164701['SLV']}, hf_GC: {ar164701['hf_GC']}")
    
	arQuantity = arTdxStock['SZ160723'].GetSymbolSize('BUY')
	f160723 = api.EstNetValue('SZ160723')
	__printHoldingEst('SZ160723', f160723)
	f160723 = api.EstNetValue('SZ160723', {'USO': 60.03})
	ar160723 = api.CalcQuantity('SZ160723', arQuantity | {'USO': 100})
	str = __debugUSO(api, 'SZ160723', ar160723)
	print(f"按持仓算SZ160723: {ar160723['SZ160723']}@{f160723:.3f}{str}")
	f160723 = api.EstNetValue('SZ160723', {'hf_CL': 39.61})
	ar160723 = api.CalcQuantity('SZ160723', arQuantity | {'hf_CL': 10})
	str = __debugUSO(api, 'SZ160723', ar160723)
	print(f"把hf_CL转换成USO后, 按持仓算SZ160723: {ar160723['SZ160723']}@{f160723:.3f}{str}, hf_CL: {ar160723['hf_CL']}")

	f164824 = api.EstNetValue('SZ164824')
	__printHoldingEst('SZ164824', f164824)
	f164824 = api.EstNetValue('SZ164824', {'INDA': 45.44})
	__printHoldingEst('SZ164824', f164824, '')
    
	f161226 = api.EstNetValue('SZ161226')
	__printEst('SZ161226', f161226)
	f161226 = api.EstNetValue('SZ161226', ag0_stock.GetSymbolPrice());
	fAG0 = api.ReverseEst({'SZ161226':f161226})
	ar161226 = api.CalcQuantity('SZ161226', arTdxStock['SZ161226'].GetSymbolSize('SELL') | ag0_stock.GetSymbolSize('SELL'))
	__printHedge(api, ar161226, 'SZ161226', 'nf_AG0')
	print(f"直接算161226: {ar161226['SZ161226']}@{f161226:.3f}, 反向算nf_AG0: {ar161226['nf_AG0']}@{fAG0:.2f}")

	arMktList = list(arSinaStock.values())
	print("按 Ctrl+C 退出...")
	try:
		while True:
			bChanged = False
			strHMS = datetime.now(timezone(timedelta(hours=8))).strftime("%H:%M:%S")
			for stock in arTdxStock.values():
				for strType in stock.GetTypeList():
					if stock.HasData(strType):
						for mkt_stock in arMktList:
							bChanged |= pdf.ProcessPriceAndSize(arMktList, mkt_stock, stock, strType, usdcny_stock, strHMS)
						stock.SetUpdated(strType, False)
			for strMktType in PalmmicroStock.GetTypeList():
				for mkt_stock in arMktList:
					mkt_stock.SetUpdated(strMktType, False)
			if bChanged:
				d.data = pdf.GetDataFrame()
				d.update_settings(column_formats = d_column_formats)
				TdxStock.TqDebug(strHMS + ' D-Tale update ...')
			#print(strHMS)
			time.sleep(1)
	except KeyboardInterrupt:
		print("已退出")		

def calculate_annualized_return(principal, total_return, years):
    # 计算年化收益率公式：final_amount = principal * (1 + rate)^years
    # 解方程：rate = (final_amount / principal)^(1/years) - 1
    rate = ((principal + total_return) / principal) ** (1 / years) - 1
    return rate * 100  # 转换为百分比

def main():
	import sys
	print(f"Hello, World! {sys.version}")
	result = calculate_annualized_return(350, 168, 10)
	print(f"总结: 无敌哥10年赚168万, 本金350万, 年化收益率为: {result:.2f}%")
	FetchPalmmicroData()

main()
