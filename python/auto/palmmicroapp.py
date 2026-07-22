import dtale
import threading
import time
import tkinter as tk

from tkinter import ttk, PhotoImage

from _mytoken import BOT_TOKEN

from palmmicrostock import PalmmicroStock, SinaStock, TdxStock, IbkrStock
from palmmicroapi import PalmmicroAPI, PalmmicroDataFrame

class PalmmicroApp:
	def __init__(self, root):
		self.root = root
		root.title('Palmmicro')
		self.running = True
		
		# 软件版本号
		self.version = '0.4'
		
		# 创建DataFrame
		self.df = self.create_dataframe()
		
		# 创建UI
		self.setup_ui()
		
		# 启动数据更新线程
		self.update_thread = threading.Thread(target = self.update_data_loop, daemon = True, name = f"{self.__class__.__name__}-{self.version}")
		self.update_thread.start()
		
		# 绑定窗口关闭事件
		root.protocol('WM_DELETE_WINDOW', self.on_closing)

		icon = PhotoImage(file = 'redfox.png')
		root.geometry('1024x768')  # 设置窗口大小
		root.resizable(True, True)
		root.iconphoto(True, icon)
		# 保持引用，防止被垃圾回收
		root.icon_image = icon	
	
	def create_dataframe(self):
		self.arSinaStock = SinaStock.TaskInit()
		self.arTdxStock = TdxStock.TqInit()
		api = PalmmicroAPI(PalmmicroAPI.FetchData(PalmmicroStock.JoinSymbols(self.arTdxStock), BOT_TOKEN))
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
		
		title_label = ttk.Label(header_frame, text = '企业微信数据本地部署软件', font = ('Arial', 12, 'bold'))
		title_label.pack(side=tk.LEFT)
		
		version_label = ttk.Label(header_frame, text = f"版本: {self.version}", font = ('Arial', 10))
		version_label.pack(side=tk.RIGHT)
		
		# 先创建状态栏（在Treeview之前）
		self.status_label = ttk.Label(main_frame, text = '就绪', relief = tk.SUNKEN, anchor = tk.W)
		self.status_label.pack(fill = tk.X, pady = (10, 0), side = tk.BOTTOM)
		
		# 创建Treeview来显示DataFrame
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
		columns = ['Symbol', 'Hedge', 'Type', 'Time', 'Percent', 'SymbolSize', 'SymbolPrice', 'HedgeSize', 'HedgePrice', 'Note']
		
		# 列显示名称
		display_names = ['代码', '对冲代码', '方向', '时间', '溢价', '数量', '价格', '对冲数量', '对冲价格', '补充内容']
		
		# 列宽度设置
		col_widths = [70, 70, 36, 60, 60, 80, 80, 80, 80, 300]
		
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
		
		# 存储列名用于数据更新
		self.column_names = columns
		
		# 初始化显示数据
		self.refresh_treeview()
	
	def refresh_treeview(self):
		"""刷新Treeview显示(保留三重索引, 与print一致)"""
		# 清空现有数据
		for item in self.tree.get_children():
			self.tree.delete(item)
		
		# 过滤掉SymbolSize为0的行
		filtered_df = self.df[self.df['SymbolSize'] != 0]
		
		# 用于跟踪已显示的Symbol和Hedge组合
		shown_symbols = set()
		shown_hedge_pairs = set()  # 记录(Symbol, Hedge)组合
		
		# 获取数据并格式化
		for row_tuple in filtered_df.itertuples():
			# 从索引中获取三重索引值
			idx = row_tuple.Index
			
			if isinstance(idx, tuple):
				symbol = idx[0]			# type: ignore
				hedge = idx[1]			# type: ignore
				type_val = str(idx[2])	# type: ignore
			else:
				symbol = str(idx)
				hedge = ''
				type_val = ''
			
			# 获取数据列
			time_val = row_tuple.Time
			percent_val = float(row_tuple.Percent)		# type: ignore
			symbol_size_val = int(row_tuple.SymbolSize)	# type: ignore
			symbol_price_val = row_tuple.SymbolPrice
			hedge_size_val = int(row_tuple.HedgeSize)	# type: ignore
			hedge_price_val = row_tuple.HedgePrice
			note_val = row_tuple.Note
			
			# 格式化各列
			percent_str = f"{percent_val * 100.0:.2f}%"
			symbol_price_str = f"{symbol_price_val:.3f}"
			hedge_price_str = f"{hedge_price_val:.2f}"
			
			# 决定是否显示Symbol（只在第一次出现时显示）
			show_symbol = symbol if symbol not in shown_symbols else ''
			if symbol not in shown_symbols:
				shown_symbols.add(symbol)
			
			# 决定是否显示Hedge（在同一个Symbol下，只在第一次出现时显示）
			hedge_key = (symbol, hedge)
			show_hedge = hedge if hedge_key not in shown_hedge_pairs else ''
			if hedge_key not in shown_hedge_pairs:
				shown_hedge_pairs.add(hedge_key)
			
			# 插入行
			item_id = self.tree.insert('', tk.END, values = (
				show_symbol, show_hedge, PalmmicroStock.GetTypeDisplay(type_val),
				time_val, percent_str, symbol_size_val,
				symbol_price_str, hedge_size_val, hedge_price_str,
				note_val
			))
			
			# 如果Percent为负数，设置该行为红色
			if percent_val < 0.0:		# type: ignore
				self.tree.tag_configure('red', foreground = 'red')
				self.tree.item(item_id, tags = ('red',))
		
		# 更新状态
		if hasattr(self, 'status_label'):
			filtered_count = len(filtered_df)
			total_count = len(self.df)
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
	
	def update_data(self):
		arMktList = list(self.arIbkrStock.values())
		arMktList.append(self.arSinaStock['nf_AG0'])
		bChanged = False
		for stock in self.arTdxStock.values():
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
	
	def on_closing(self):
		"""窗口关闭时的回调函数 - 释放资源"""
		print('正在关闭PalmmicroApp...')
		
		# 停止更新线程
		self.running = False
		if self.update_thread.is_alive():
			self.update_thread.join(timeout = 0.5)
		
		# 释放资源
		self.cleanup_resources()
		
		# 关闭窗口
		self.root.destroy()
		print('PalmmicroApp已关闭')
	
	def cleanup_resources(self):
		"""释放资源接口"""
		print('释放资源...')
		# 在这里可以添加需要释放的资源
		# 例如：关闭数据库连接、保存配置文件、释放大对象等
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
		
		print('资源已释放')
