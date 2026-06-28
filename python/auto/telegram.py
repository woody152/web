import json
import requests
import time
from typing import Union, List, Dict, Any

from _mytoken import BOT_TOKEN
#from _mytoken import ROT_TOKEN

from palmmicroapi import PalmmicroAPI
from palmmicrostock import PalmmicroStock, SinaStock

def __printHedge(api, ar: Dict[str, int], strSymbol, strSymbolUS):
	if ar[strSymbolUS] != 0:
		print(f"{strSymbolUS}对冲值: {ar[strSymbol]/ar[strSymbolUS]/api.get_multiplier(strSymbolUS):.0f}")

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
	print(f"直接算{strSymbol}: {ar[strSymbol]}@{fNetValue:.3f}, 对应{strSymbolUS}: {ar[strSymbolUS]}, 反向算XOP@{fPriceUS:.2f}")
	__printHedge(api, ar, strSymbol, strSymbolUS)
	ar = api.CalcQuantity(strSymbol, {strSymbolUS: iQuantityUS})
	print(f"只输入{strSymbolUS}数量时建议: {ar[strSymbol]}, 对应{strSymbolUS}: {ar[strSymbolUS]}")
	__printHedge(api, ar, strSymbol, strSymbolUS)
	ar = api.CalcQuantity(strSymbol, {strSymbol: iQuantity})
	print(f"只输入{strSymbol}数量时建议: {ar[strSymbol]}, 对应XOP: {ar['XOP']}")
	__printHedge(api, ar, strSymbol, 'XOP')
	ar = api.CalcQuantity(strSymbol, {})
	print(f"无输入数量或者出错时建议: {ar[strSymbol]}, 对应XOP: {ar['XOP']}")
	__printHedge(api, ar, strSymbol, 'XOP')

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
	print(f"把{strSymbolUS}转换成^GSPC后二次计算{strSymbol}: {ar[strSymbol]}@{fNetValue:.3f}, 对应{strSymbolUS}: {ar[strSymbolUS]}, 反向算SPY@{fPriceUS:.2f}")
	__printHedge(api, ar, strSymbol, strSymbolUS)

def __getSize(arStock, arSymbol, strType = 'SELL'):
	arQuantity = {}
	for strSymbol in arSymbol:
		arQuantity |= arStock[strSymbol].GetSize(strType)
	return arQuantity

def _handlePalmmicroData(arData, strSymbols):
	arLine = SinaStock.FetchData('fx_susdcny,nf_AG0,' + strSymbols.lower())
	if arLine == False:
		print('无法获得新浪数据')
		return
	
	usdcny_stock = SinaStock(arLine[0])
	ag0_stock = SinaStock(arLine[1])
	arSymbol = strSymbols.split(',')
	iIndex = 2
	arStock = {}
	for strSymbol in arSymbol:
		arStock[strSymbol] = SinaStock(arLine[iIndex])
		iIndex += 1

	arCNY = usdcny_stock.GetPrice()
	api = PalmmicroAPI(arData)
	
	arQuantity = __getSize(arStock, {'SZ162411', 'SZ159518'})
	arQuantityUS = {'XOP': 1000, 'GUSH': 10000}
	arPriceUS = {'XOP': 144.29, 'GUSH': 26.77}
	for strSymbol, iQuantity in arQuantity.items():
		for strSymbolUS, iQuantityUS in arQuantityUS.items():
			__testXOP(api, strSymbol, strSymbolUS, iQuantity, iQuantityUS, arPriceUS[strSymbolUS], arCNY)

	arQuantity = __getSize(arStock, {'SZ161125', 'SZ159612'})
	arQuantityUS = {'SPY': 100, 'hf_ES': 2}
	arPriceUS = {'SPY': 619.19, 'hf_ES': 6267.25}
	for strSymbol, iQuantity in arQuantity.items():
		for strSymbolUS, iQuantityUS in arQuantityUS.items():
			__testSPY(api, strSymbol, strSymbolUS, iQuantity, iQuantityUS, arPriceUS[strSymbolUS], arCNY)
    
	arQuantity = arStock['SZ164701'].GetSize('SELL')
	f164701 = api.EstNetValue('SZ164701')
	__printHoldingEst('SZ164701', f164701)
	f164701 = api.EstNetValue('SZ164701', {'GLD': 357.37, 'SLV': 56.22})
	ar164701 = api.CalcQuantity('SZ164701', arQuantity | {'GLD': 100, 'SLV': 100})
	print(f"按持仓算SZ164701: {ar164701['SZ164701']}@{f164701:.3f}, GLD: {ar164701['GLD']}, SLV: {ar164701['SLV']}")
	__printHedge(api, ar164701, 'SZ164701', 'GLD')
	f164701 = api.EstNetValue('SZ164701', {'hf_GC': 3909.92, 'hf_SI': 60.42})
	ar164701 = api.CalcQuantity('SZ164701', arQuantity | {'hf_GC': 1, 'SLV': 100})
	print(f"把hf_GC和hf_SI转换成GLD和SLV后, 按持仓算SZ164701: {ar164701['SZ164701']}@{f164701:.3f}, GLD: {ar164701['GLD']}, SLV: {ar164701['SLV']}, hf_GC: {ar164701['hf_GC']}")
	__printHedge(api, ar164701, 'SZ164701', 'GLD')
    
	arQuantity = arStock['SZ160723'].GetSize('SELL')
	f160723 = api.EstNetValue('SZ160723')
	__printHoldingEst('SZ160723', f160723)
	f160723 = api.EstNetValue('SZ160723', {'USO': 93.47})
	ar160723 = api.CalcQuantity('SZ160723', arQuantity | {'USO': 100})
	i160723 = ar160723['SZ160723']
	iUSO = ar160723['USO']
	iUSOEU = ar160723['^USO-EU']
	iUSOJP = ar160723['^USO-JP']
	print(f"按持仓算SZ160723: {i160723}@{f160723:.3f}, USO: {iUSO}, ^USO-EU: {iUSOEU}, ^USO-JP: {iUSOJP}")
	print(f"USO对冲值: {i160723/(iUSO + iUSOEU + iUSOJP):.0f}")
	f160723 = api.EstNetValue('SZ160723', {'hf_CL': 61.53})
	ar160723 = api.CalcQuantity('SZ160723', arQuantity | {'hf_CL': 10})
	i160723 = ar160723['SZ160723']
	iUSO = ar160723['USO']
	iUSOEU = ar160723['^USO-EU']
	iUSOJP = ar160723['^USO-JP']
	print(f"把hf_CL转换成USO后, 按持仓算SZ160723: {i160723}@{f160723:.3f}, USO: {iUSO}, ^USO-EU: {iUSOEU}, ^USO-JP: {iUSOJP}, hf_CL: {ar160723['hf_CL']}")
	print(f"USO对冲值: {i160723/(iUSO + iUSOEU + iUSOJP):.0f}")

	f164824 = api.EstNetValue('SZ164824')
	__printHoldingEst('SZ164824', f164824)
	f164824 = api.EstNetValue('SZ164824', {'INDA': 44.72})
	__printHoldingEst('SZ164824', f164824, '')
    
	f161226 = api.EstNetValue('SZ161226')
	__printEst('SZ161226', f161226)
	#f161226 = api.EstNetValue('SZ161226', {'nf_AG0':12842.3});
	f161226 = api.EstNetValue('SZ161226', ag0_stock.GetPrice());
	fAG0 = api.ReverseEst({'SZ161226':f161226})
	ar161226 = api.CalcQuantity('SZ161226', {'SZ161226':576813, 'nf_AG0':10})
	print(f"直接算161226: {ar161226['SZ161226']}@{f161226:.3f}, 反向算nf_AG0: {ar161226['nf_AG0']}@{fAG0:.2f}")
	__printHedge(api, ar161226, 'SZ161226', 'nf_AG0')


def post_json_array_to_telegram(
	data_array: Dict[str, Any], 
	bot_token: str, 
	timeout: int = 30
) -> Union[List[Any], Dict[str, Any], None]:
	"""
	向Telegram风格的Webhook端点发送JSON数组, 并返回解析后的响应数据。

	参数:
		data_array: 要发送的Python列表(数组), 例如 [{"key": "value"}, 123, "text"]
		bot_token: 您的TG_TOKEN, 用于替换URL中的占位符
		timeout: 请求超时时间(秒), 默认30秒

	返回:
		成功时返回服务器响应解析后的Python对象(通常为列表或字典)
		失败时返回None, 并打印错误信息
	"""
	# 1. 构建完整的URL
	url = f"https://palmmicro.com/php/telegram.php?token={bot_token}"

	# 2. 将Python数组转换为JSON字符串
	# ensure_ascii=False 使中文等非ASCII字符保持原样，更易读
	try:
		json_payload = json.dumps(data_array, ensure_ascii=False)
		print(f"发送的JSON数据: {json_payload}")
	except TypeError as e:
		print(f"数据序列化失败: {e}, 请检查data_array是否包含不可序列化的对象")
		return None

	# 3. 设置请求头
	headers = {'Content-Type': 'application/json'}

	# 4. 发送POST请求并处理响应
	try:
		response = requests.post(
			url, 
			data=json_payload,      # 发送JSON字符串
			headers=headers, 
			timeout=timeout
		)

		# 检查HTTP状态码
		response.raise_for_status()

		# 5. 解析返回的JSON数据为Python对象
		# 注意：response.json() 可以自动处理数组、对象等
		received_data = response.json()

		print(f"服务器返回的原始内容: {response.text}")
		print(f"解析后的Python对象类型: {type(received_data)}")

		# 可选：验证返回的数据是否为列表
		if isinstance(received_data, list):
			print("成功: 服务器返回了一个JSON数组, 已转换为Python列表。")
		elif isinstance(received_data, dict):
			print("注意: 服务器返回了一个JSON对象(字典), 而非数组。")
		else:
			print(f"注意：服务器返回了其他类型: {type(received_data)}")

		return received_data

	except requests.exceptions.Timeout:
		print(f"请求超时({timeout}秒), 请检查网络或增加timeout参数")
		return None
	except requests.exceptions.HTTPError as e:
		print(f"HTTP错误: {e}，状态码: {response.status_code}")
		print(f"服务器响应内容: {response.text}")
		return None
	except requests.exceptions.RequestException as e:
		print(f"网络请求失败: {e}")
		return None
	except json.JSONDecodeError as e:
		print(f"解析服务器返回的JSON时出错: {e}")
		print(f"服务器返回的原始内容(非JSON): {response.text}")
		return None

# 使用示例
def FetchPalmmicroData(strSymbols):
	ar = {'update_id': 886050244,
		  'message': {'message_id': 6620,
					  'from': {'id': 992671436,
							   'is_bot': False,
							   'first_name': 'woody',
							   'username': 'palmmicro',
							   'language_code': 'zh-Hans'
					 		  },
					  'chat': {'id': 992671436,
							   'first_name': 'woody',
							   'username': 'palmmicro',
							   'type': 'private'
							  },
					  'date': 0,
					  'text': ''
                     }
		 }
	arMessage = ar['message']
	arMessage['date'] = int(time.time())
	arMessage['text'] = strSymbols
	result = post_json_array_to_telegram(ar, BOT_TOKEN)
	#result = post_json_array_to_telegram(ar, ROT_TOKEN)

	if result is not None:
		# 可以进一步处理result
		if isinstance(result, dict):
			_handlePalmmicroData(result['text'], strSymbols)
	else:
		print("函数执行失败, 请检查上面的错误信息。")
