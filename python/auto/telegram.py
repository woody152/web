import requests
import json
import time
from typing import Union, List, Dict, Any

from _mytoken import BOT_TOKEN
from _mytoken import ROT_TOKEN

from palmmicroapi import PalmmicroAPI

def _handlePalmmicroData(arData):
	arCNY = {'CNY': 6.7587}
	arXOP = {'XOP': 141.07}
	arQuantity162411 = {'SZ162411':810506}
	arQuantityXOP = {'XOP':1000}
	arSPY = {'SPY': 618.9}
	arES = {'hf_ES': 6198.5}
	arQuantity161125 = {'SZ161125':1001045}
	arGLD = {'GLD': 373.7}
	arGC = {'hf_GC': 4091.51}
	arSLV = {'SLV': 56.21}
	arSI = {'hf_SI': 62.22}
	arUSO = {'USO': 85.81}
	arCL = {'hf_CL': 56.52}
	arINDA = {'INDA': 45.13}
	api = PalmmicroAPI(arData)
	#print(api.get_config())
    
	print(round(api.EstNetValue('SZ162411'), 3), '直接算162411官方估值')
	f162411 = api.EstNetValue('SZ162411', arXOP)
	fXOP = api.ReverseEst({'SZ162411':f162411})
	ar162411 = api.CalcQuantity('SZ162411', arQuantity162411 | arQuantityXOP)
	i162411 = ar162411['SZ162411']
	iXOP = ar162411['XOP']
	print(f"直接算162411: {i162411}@{f162411:.3f}, 反向算XOP: {iXOP}@{fXOP:.2f}, 对冲值: {i162411/iXOP:.0f}")
	ar162411 = api.CalcQuantity('SZ162411', arQuantity162411)
	print(f"只输入162411数量时建议: {ar162411['SZ162411']}, 对应XOP: {ar162411['XOP']}")
	ar162411 = api.CalcQuantity('SZ162411', arQuantityXOP)
	print(f"只输入XOP数量时建议: {ar162411['SZ162411']}, 对应XOP: {ar162411['XOP']}")
	ar162411 = api.CalcQuantity('SZ162411', {'XLE':200})
	print(f"无输入时建议: {ar162411['SZ162411']}, 对应XOP: {ar162411['XOP']}")
    
	print(round(api.EstNetValue('SZ159518', arCNY), 3), '直接算159518参考估值')
	f159518 = api.EstNetValue('SZ159518', arXOP | arCNY)
	fXOP = api.ReverseEst({'SZ159518':f159518} | arCNY)
	ar159518 = api.CalcQuantity('SZ159518', {'SZ159518':1415100} | arQuantityXOP)
	i159518 = ar159518['SZ159518']
	iXOP = ar159518['XOP']
	print(f"直接算159518: {i159518}@{f159518:.3f}, 反向算XOP: {iXOP}@{fXOP:.2f}, 对冲值: {i159518/iXOP:.0f}")
    
	print(round(api.EstNetValue('SZ161125'), 3), '直接算161125官方估值')
	f161125 = api.EstNetValue('SZ161125', arSPY)
	fSPY = api.ReverseEst({'SZ161125':f161125})
	ar161125 = api.CalcQuantity('SZ161125', arQuantity161125 | {'SPY':100})
	i161125 = ar161125['SZ161125']
	iSPY = ar161125['SPY']
	print(f"把SPY转换成^GSPC后二次计算161125: {i161125}@{f161125:.3f}, 反向算SPY: {iSPY}@{fSPY:.2f}, 对冲值: {i161125/iSPY:.0f}")
	f161125 = api.EstNetValue('SZ161125', arES)
	fSPY = api.ReverseEst({'SZ161125':f161125})
	ar161125 = api.CalcQuantity('SZ161125', arQuantity161125 | {'hf_ES': 1})
	i161125 = ar161125['SZ161125']
	iES = ar161125['hf_ES']
	print(f"把ES转换成^GSPC后二次计算161125: {i161125}@{f161125:.3f}, 反向算SPY: {fSPY:.2f}, 对应ES: {iES}, 对冲值: {i161125/iES/api.get_multiplier('hf_ES'):.0f}")
    
	f159612 = api.EstNetValue('SZ159612', arSPY | arCNY)
	fSPY = api.ReverseEst({'SZ159612':f159612} | arCNY)
	print(f"把SPY转换成^GSPC后二次计算159612: {f159612:.3f}, 反向算SPY: {fSPY:.2f}")
	f159612 = api.EstNetValue('SZ159612', arES | arCNY)
	fSPY = api.ReverseEst({'SZ159612':f159612} | arCNY)
	print(f"把ES转换成^GSPC后二次计算159612: {f159612:.3f}, 反向算SPY: {fSPY:.2f}")
	
	print(round(api.EstNetValue('SZ164701'), 3), '按持仓算164701官方估值')
	f164701 = api.EstNetValue('SZ164701', arGLD | arSLV)
	ar164701 = api.CalcQuantity('SZ164701', {'SZ164701': 133041, 'GLD': 100, 'SLV': 100})
	print(f"按持仓算164701: {ar164701['SZ164701']}@{f164701:.3f}, GLD: {ar164701['GLD']}, SLV: {ar164701['SLV']}")
	print(round(api.EstNetValue('SZ164701', arGC | arSI), 3), '把GC和SI转换成GLD和SLV后, 按持仓算164701')
    
	print(round(api.EstNetValue('SZ160723'), 3), '按持仓算160723官方估值')
	f160723 = api.EstNetValue('SZ160723', arUSO)
	ar160723 = api.CalcQuantity('SZ160723', {'SZ160723': 1006853, 'USO': 100})
	print(f"按持仓算160723: {ar160723['SZ160723']}@{f160723:.3f}, USO: {ar160723['USO']}, ^USO-EU: {ar160723['^USO-EU']}")
	print(round(api.EstNetValue('SZ160723', arCL), 3), '把CL转换成USO后, 按持仓算160723')

	print(round(api.EstNetValue('SZ164824'), 3), '按持仓算164824官方估值')
	print(round(api.EstNetValue('SZ164824', arINDA), 3), '按持仓算164824')
    
	print(round(api.EstNetValue('SZ161226'), 3), '直接算161226官方估值')
	f161226 = api.EstNetValue('SZ161226', {'nf_AG0':12842.3});
	fAG0 = api.ReverseEst({'SZ161226':f161226})
	ar161226 = api.CalcQuantity('SZ161226', {'SZ161226':576813, 'nf_AG0':10})
	i161226 = ar161226['SZ161226']
	iAG0 = ar161226['nf_AG0']
	print(f"直接算161226: {i161226}@{f161226:.3f}, 反向算AG0: {iAG0}@{fAG0:.2f}, 对冲值: {i161226/iAG0/api.get_multiplier('nf_AG0'):.0f}")

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
			_handlePalmmicroData(result['text'])
	else:
		print("函数执行失败, 请检查上面的错误信息。")
