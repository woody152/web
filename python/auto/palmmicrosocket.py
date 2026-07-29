import asyncio
import json
import pandas as pd
import threading
import websockets
from typing import Callable

class PalmmicroSocket:
	def __init__(self, render_url: str, callback: Callable[[], pd.DataFrame], interval: float = 2.0):
		self.render_url = render_url
		self.interval = interval
		self.running = False
		self.thread = None
		self.connection_retry_delay = 3
		self.websocket = None
		self.loop = None
		self.data_callback = callback
		
	def start(self):
		"""启动发送线程"""
		if self.data_callback is None:
			raise ValueError("请先设置数据回调函数")
		
		self.running = True
		self.thread = threading.Thread(target=self._run, daemon=True)
		self.thread.start()
		print("发送器已启动")
	
	def _run(self):
		"""在线程中运行"""
		self.loop = asyncio.new_event_loop()
		asyncio.set_event_loop(self.loop)
		self.loop.run_until_complete(self._send_loop())
	
	async def _send_loop(self):
		"""发送循环 - 保持长连接"""
		while self.running:
			try:
				# 建立连接
				self.websocket = await websockets.connect(
					self.render_url,
					ping_interval=10,
					ping_timeout=30,
					close_timeout=5,
					max_size=10**7
				)
				print("✓ WebSocket连接成功")
				
				# 启动心跳任务
				ping_task = asyncio.create_task(self._keep_alive())
				
				# 主发送循环
				try:
					while self.running:
						try:
							# 检查连接状态 - 使用state属性
							if self.websocket.state.name in ['CLOSED', 'CLOSING']:
								print("⚠ 连接已断开，等待重连...")
								break
							
							# 获取数据
							df = self.data_callback()
							
							if df is not None and not df.empty:
								await self._send_data(df)
							
							await asyncio.sleep(self.interval)
							
						except websockets.exceptions.ConnectionClosed:
							print("⚠ 连接被关闭，等待重连...")
							break
						except asyncio.TimeoutError:
							print("⚠ 发送超时，继续...")
							continue
						except Exception as e:
							print(f"⚠ 发送循环异常: {e}")
							await asyncio.sleep(1)
							
				finally:
					# 清理心跳任务
					if ping_task:
						ping_task.cancel()
						try:
							await ping_task
						except asyncio.CancelledError:
							pass
					
					# 关闭连接
					if self.websocket:
						try:
							await self.websocket.close()
						except:
							pass
						self.websocket = None
					
			except Exception as e:
				print(f"⚠ 连接失败: {e}")
			
			# 重连延迟
			if self.running:
				print(f"⏳ {self.connection_retry_delay}秒后重连...")
				await asyncio.sleep(self.connection_retry_delay)
	
	async def _keep_alive(self):
		"""保持连接活跃 - 定期ping"""
		try:
			while True:
				try:
					if self.websocket and self.websocket.state.name == 'OPEN':
						await asyncio.wait_for(self.websocket.ping(), timeout=5)
						await asyncio.sleep(15)
					else:
						break
				except asyncio.CancelledError:
					break
				except Exception:
					break
		except asyncio.CancelledError:
			pass
	
	async def _send_data(self, df: pd.DataFrame):
		"""发送DataFrame数据"""
		try:
			if not self.websocket or self.websocket.state.name != 'OPEN':
				return
			
			message = {
				"type": "dataframe",
				"data": df.to_dict(orient='records')
			}
			
			await asyncio.wait_for(
				self.websocket.send(json.dumps(message)), 
				timeout=10
			)
			#print(f"✓ 发送: {len(df)}行")
			
		except asyncio.TimeoutError:
			print("⚠ 发送超时")
			raise
		except Exception as e:
			print(f"⚠ 发送失败: {e}")
			raise
	
	def stop(self):
		"""停止发送"""
		self.running = False
		if self.thread:
			self.thread.join(timeout=3)
		print("发送器已停止")
