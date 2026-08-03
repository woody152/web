import dtale
import pandas as pd
import threading
import time
import tkinter as tk

from tkinter import ttk, PhotoImage

from palmmicrostock import PalmmicroStock, SinaStock, TdxStock, IbkrStock
from palmmicroapi import PalmmicroAPI, PalmmicroDataFrame
from palmmicrosocket import PalmmicroSocket

class PalmmicroApp:
	def __init__(self, root):
		self.root = root
		root.title('Palmmicro')
		self.running = True
		
		# 软件版本号
		self.version = '0.74'
		
		# 创建DataFrame
		self.df = self.create_dataframe()
		if self.df is not None:
			# 启动数据更新线程
			self.update_thread = threading.Thread(target = self.update_data_loop, daemon = True, name = f"{self.__class__.__name__}-{self.version}")
			self.update_thread.start()

			#查找并且打开self.sender的注释语句把DataFrame数据发送到公网, 后面还有一个self.sender.stop()
			#self.sender = PalmmicroSocket("wss://palmmicro.onrender.com/ws", self.get_current_data)
			#self.sender.start()
						
			# 绑定窗口关闭事件
			root.protocol('WM_DELETE_WINDOW', self.on_closing)
		
		# 创建UI
		self.setup_ui()

		icon = PhotoImage(file = 'redfox.png')
		root.geometry('1024x768')  # 设置窗口大小
		root.resizable(True, True)
		root.iconphoto(True, icon)
		# 保持引用，防止被垃圾回收
		root.icon_image = icon	

	def get_current_data(self) -> pd.DataFrame:
		"""回调函数 - 返回当前最新数据"""
		return self.pdf.GetDisplayDataFrame()

	def _debug(self, strDebug: str):
		self.strError = strDebug
		TdxStock.TqDebug(strDebug)
						
	def create_dataframe(self):
		self.arTdxStock = TdxStock.TqInit()
		if self.arTdxStock is None:
			self.strError = '没有找到通达信Python软件, 请先安装运行支持Python接口的64位通达信程序。'
			return None
		else:
			if len(self.arTdxStock) == 0:
				self._debug('没有找到通达信自定义板块PLMM, 请先在自定义板块设置中导入Palmmicro.EBK文件。')
				return None

		self.arSinaStock = SinaStock.TaskInit()

		try:
			from _mytoken import BOT_TOKEN
			strToken = BOT_TOKEN
		except (ImportError, AttributeError):
			# 模块不存在或没有 BOT_TOKEN 变量
			strToken = 'palmmicro'
		config_dict = PalmmicroAPI.FetchData(PalmmicroStock.JoinSymbols(self.arTdxStock), strToken)
		if config_dict is None:
			self._debug('连接PalmmicroAPI接口失败, 请重新运行程序。多次失败的话可以尝试换一个网络和IP地址后再连接。')
			return None
		else:
			if isinstance(config_dict, dict) == False:
				self._debug('没有正确获得PalmmicroAPI接口数据, 请附带错误原因联系woody@palmmicro.com。原因是: ' + config_dict)
				return None

		api = PalmmicroAPI(config_dict)
		self.pdf = PalmmicroDataFrame(api)
		self.arIbkrStock = IbkrStock.InitAPI(api.GetMapping())

		df = self.pdf.GetDataFrame()
		self.d_column_formats = {'Percent': {'fmt': '0.00%'}, 'SymbolPrice': {'fmt': '0.000'}}
		self.d = dtale.show(df,
							host = '127.0.0.1',
							port = 40005,
							column_formats = self.d_column_formats,
							reaper_on = False
					   	   )
		self.d.open_browser()
		return df
	
	def setup_ui(self):
		"""设置UI界面"""
		# 主框架
		main_frame = ttk.Frame(self.root, padding = '10')
		main_frame.pack(fill = tk.BOTH, expand = True)
		
		# 标题和版本号
		header_frame = ttk.Frame(main_frame)
		header_frame.pack(fill = tk.X, pady = (0, 10))
		
		title_label = ttk.Label(header_frame, text = '企业微信消息本地化部署软件', font = ('Arial', 12, 'bold'))
		title_label.pack(side=tk.LEFT)
		
		version_label = ttk.Label(header_frame, text = f"版本: {self.version}", font = ('Arial', 10))
		version_label.pack(side=tk.RIGHT)

		# 先创建状态栏（在Treeview之前）
		strStatus = self.strError if self.df is None else '请在通达信TQ策略管理器中查看更多状态信息'
		self.status_label = ttk.Label(main_frame, text = strStatus, relief = tk.SUNKEN, anchor = tk.W)
		self.status_label.pack(fill = tk.X, pady = (10, 0), side = tk.BOTTOM)

		# 创建Treeview来显示DataFrame
		if self.df is not None:
			self.create_treeview(main_frame)
	
	def create_treeview(self, parent):
		"""创建Treeview显示DataFrame(带三重索引, 显示方式与print一致)"""
		# 创建容器
		tree_frame = ttk.Frame(parent)
		tree_frame.pack(fill = tk.BOTH, expand = True)
		
		# 添加滚动条
		v_scrollbar = ttk.Scrollbar(tree_frame, orient = tk.VERTICAL)
		h_scrollbar = ttk.Scrollbar(tree_frame, orient = tk.HORIZONTAL)
		
		# 定义列 - 与print(DataFrame)一致，不重复显示索引
		# 索引列只显示一次：Symbol, Hedge, Type 作为前3列
		#columns = ['Symbol', 'Hedge', 'Type', 'Time', 'Percent', 'SymbolSize', 'SymbolPrice', 'HedgeSize', 'HedgePrice', 'Note']
		df_reset = self.df.reset_index()	# type: ignore
		columns = df_reset.columns.to_list()
		
		# 列显示名称
		#display_names = ['代码', '对冲代码', '方向', '时间', '溢价', '数量', '价格', '对冲数量', '对冲价格', '补充内容']
		display_df = self.pdf.GetDisplayDataFrame()
		display_names = display_df.columns.to_list()
		display_names.remove('折价')
		
		# 列宽度设置
		col_widths = [70, 74, 36, 60, 60, 80, 80, 80, 80, 300]
		
		# 创建Treeview
		self.tree = ttk.Treeview(tree_frame, columns = columns, show = 'headings', 
								 yscrollcommand = v_scrollbar.set, 
								 xscrollcommand = h_scrollbar.set,
								 height = 15)
		
		# 配置滚动条
		v_scrollbar.config(command = self.tree.yview)
		h_scrollbar.config(command = self.tree.xview)
		
		# 设置列标题和宽度，所有列左对齐
		for col, name, width in zip(columns, display_names, col_widths):
			self.tree.heading(col, text = name, anchor = 'w')
			self.tree.column(col, width = width, anchor = 'w', stretch = False)
		
		# 布局
		self.tree.grid(row = 0, column = 0, sticky = 'nsew')
		v_scrollbar.grid(row = 0, column = 1, sticky = 'ns')
		h_scrollbar.grid(row = 1, column = 0, sticky = 'ew')
		
		tree_frame.grid_rowconfigure(0, weight = 1)
		tree_frame.grid_columnconfigure(0, weight = 1)
		
		# 初始化显示数据
		self.refresh_treeview()
	
	def refresh_treeview(self):
		"""刷新Treeview显示(保留三重索引, 与print一致)"""
		# 清空现有数据
		for item in self.tree.get_children():
			self.tree.delete(item)

		filtered_df = self.pdf.GetDisplayDataFrame()
		for row_num, (index, row) in enumerate(filtered_df.iterrows()):			
			item_id = self.tree.insert('', tk.END, values = (row.iloc[0], row.iloc[1], row.iloc[2], row.iloc[3], row.iloc[4], row.iloc[6], row.iloc[7], row.iloc[8], row.iloc[9], row.iloc[10]))

			# 如果Percent为负数，设置该行为红色
			if row.iloc[5]:
				self.tree.tag_configure('red', foreground = 'red')
				self.tree.item(item_id, tags = ('red',))

		# 更新状态
		if hasattr(self, 'status_label'):
			filtered_count = len(filtered_df)
			total_count = len(self.df)	# type: ignore
			self.status_label.config(text = f"显示行数: {filtered_count} (共{total_count}行，过滤{total_count - filtered_count}行)")
	
	def update_data_loop(self):
		"""数据更新循环 - 每秒更新一次"""
		while self.running:
			if self.update_data():
				# 在主线程中刷新UI
				self.root.after(0, self.refresh_treeview)
				self.d.data = self.pdf.GetDataFrame()
				self.d.update_settings(column_formats = self.d_column_formats)
			time.sleep(1)

	def _lock_and_update_data(self, arMktList):
		with PalmmicroStock._global_lock:
			bChanged = False
			for stock in self.arTdxStock.values():	# type: ignore
				for strType in stock.GetTypeList():
					strMktType = stock.GetPeerType(strType)
					for mkt_stock in arMktList:
						if stock.IsUpdated(strType) or mkt_stock.IsUpdated(strMktType):
							bChanged |= self.pdf.ProcessPriceAndSize(stock, mkt_stock, strType, self.arSinaStock.get('CNY'), arMktList)
					stock.SetUpdated(strType, False)
			for strMktType in PalmmicroStock.GetTypeList():
				for mkt_stock in arMktList:
					mkt_stock.SetUpdated(strMktType, False)
			return bChanged
		
	def update_data(self):
		arMktList = list(self.arIbkrStock.values())
		ag0_stock = self.arSinaStock.get('nf_AG0')
		if ag0_stock is not None:
			arMktList.append(ag0_stock)
		return self._lock_and_update_data(arMktList)
	
	def on_closing(self):
		"""窗口关闭时的回调函数 - 释放资源"""
		TdxStock.TqDebug('正在关闭PalmmicroApp...')
		
		# 停止更新线程
		self.running = False
		if self.update_thread.is_alive():
			self.update_thread.join(timeout = 1)
		
		# 释放资源
		self.cleanup_resources()
		
		# 关闭窗口
		self.root.destroy()
	
	def cleanup_resources(self):
		# 在这里可以添加需要释放的资源例如：关闭数据库连接、保存配置文件、释放大对象等
		TdxStock.TqDebug('释放资源...')
		#self.sender.stop()
		IbkrStock.FreeAPI()
		SinaStock.TaskFree()
		#TdxStock.TqFree()
		
		# 清理DataFrame
		if hasattr(self, 'df'):
			del self.df
		
		# 清理Treeview
		if hasattr(self, 'tree'):
			for item in self.tree.get_children():
				self.tree.delete(item)
