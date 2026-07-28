<?php
require('../_tgprivate.php');

// display.php - 彻底解决Chrome转圈问题
// https://palmmicro.com/php/test/dfdisplay.php?api_key=your-secret-key-2026

// ==========================================
// API Key 配置
// ==========================================
// define('API_KEY', 'your-secret-key-2026');

// ==========================================
// 验证 API Key
// ==========================================
$apiKey = '';

if (isset($_GET['api_key'])) {
    $apiKey = $_GET['api_key'];
}

if (empty($apiKey)) {
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
}

if (empty($apiKey) && isset($_COOKIE['api_key'])) {
    $apiKey = $_COOKIE['api_key'];
}

if (empty($apiKey)) {
    showErrorPage('缺少API Key', '请在URL中添加 ?api_key=您的密钥 参数');
    exit;
}

//if ($apiKey !== API_KEY) {
if ($apiKey !== WECHAT_QMT_KEY) {
    showErrorPage('API Key无效', '您提供的API Key不正确，请检查后重试');
    exit;
}

function showErrorPage($title, $message) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>访问被拒绝</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: #f5f7fa;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                padding: 50px 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
            }
            .error-icon { font-size: 64px; margin-bottom: 20px; }
            .error-title { color: #e74c3c; font-size: 24px; margin-bottom: 15px; }
            .error-message { color: #7f8c8d; font-size: 16px; line-height: 1.6; margin-bottom: 30px; }
            .error-details {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
                text-align: left;
                font-size: 13px;
                color: #2c3e50;
                border-left: 4px solid #e74c3c;
                margin-bottom: 25px;
            }
            .error-details code {
                background: #e9ecef;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 12px;
                word-break: break-all;
            }
            .btn {
                display: inline-block;
                padding: 10px 30px;
                background: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                transition: background 0.3s;
                font-size: 14px;
                border: none;
                cursor: pointer;
            }
            .btn:hover { background: #2980b9; }
            .btn-secondary { background: #95a5a6; }
            .btn-secondary:hover { background: #7f8c8d; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">🔒</div>
            <div class="error-title"><?php echo htmlspecialchars($title); ?></div>
            <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
            <div>
                <button class="btn" onclick="location.href='?'">🔄 重试</button>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!isset($_COOKIE['api_key'])) {
    setcookie('api_key', $apiKey, time() + 3600, '/', '', true, true);
}

$apiKeyForJS = $apiKey;

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>实时数据监控</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 24px;
        }
        
        .security-badge {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .status-bar {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-dot.online { background: #2ecc71; }
        .status-dot.offline { background: #e74c3c; }
        
        .control-bar {
            background: white;
            padding: 15px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .control-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .control-group label {
            color: #34495e;
            font-size: 14px;
        }
        
        .control-group select, .control-group button {
            padding: 6px 12px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        
        .table-wrapper {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }
        
        .table-container {
            overflow-x: auto;
            padding: 0;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        thead th {
            background: #34495e;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }
        
        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        tbody tr:hover {
            background: #e8f4f8;
        }
        
        tbody td {
            padding: 10px 15px;
            border-bottom: 1px solid #ecf0f1;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
            font-size: 16px;
        }
        
        .no-data .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .loading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.7);
            justify-content: center;
            align-items: center;
            z-index: 20;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            border: 4px solid #ecf0f1;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .stats {
            padding: 10px 20px;
            background: #ecf0f1;
            color: #2c3e50;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stats span {
            margin-right: 20px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            .control-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .control-group {
                justify-content: center;
            }
            tbody td, thead th {
                padding: 8px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部 -->
        <div class="header">
            <h1>
                📊 Pandas DataFrame 实时数据
                <span class="security-badge">🔒 安全连接</span>
            </h1>
            <div class="status-bar">
                <span>
                    <span class="status-dot online" id="statusDot"></span>
                    <span id="statusText">在线</span>
                </span>
                <span>最后更新: <span id="lastUpdate">-</span></span>
                <span>数据行数: <span id="rowCount">0</span></span>
            </div>
        </div>
        
        <!-- 控制栏 -->
        <div class="control-bar">
            <div class="control-group">
                <label>刷新间隔:</label>
                <select id="refreshInterval">
                    <option value="1000">1秒</option>
                    <option value="2000" selected>2秒</option>
                    <option value="3000">3秒</option>
                    <option value="5000">5秒</option>
                    <option value="10000">10秒</option>
                </select>
                <button class="btn" id="btnRefresh">🔄 立即刷新</button>
            </div>
            <div class="control-group">
                <button class="btn btn-success" id="btnAutoScroll">📌 自动滚动</button>
                <button class="btn btn-danger" id="btnClear">🗑️ 清空缓存</button>
            </div>
        </div>
        
        <!-- 表格 -->
        <div class="table-wrapper">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner"></div>
            </div>
            <div class="table-container" id="tableContainer">
                <div class="no-data" id="noData">
                    <div class="icon">📭</div>
                    <p>暂无数据</p>
                    <p style="font-size:12px;margin-top:10px;">请确保receiver.php已收到POST数据</p>
                </div>
                <table id="dataTable" style="display:none;">
                    <thead id="tableHead">
                        <tr id="headerRow">
                            <th style="text-align:center;color:#95a5a6;">等待数据...</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
            <div class="stats">
                <div>
                    <span>📋 列数: <span id="colCount">0</span></span>
                    <span>📏 显示行数: <span id="displayRowCount">0</span></span>
                </div>
                <div>
                    <span id="dataSize">数据大小: 0 KB</span>
                    <span style="margin-left:15px;color:#7f8c8d;">
                        🔑 API Key: <?php echo substr($apiKeyForJS, 0, 8) . '***'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ==========================================
        // 配置
        // ==========================================
        const API_KEY = <?php echo json_encode($apiKeyForJS); ?>;
        const API_KEY_PARAM = 'api_key=' + encodeURIComponent(API_KEY);
        
        let refreshTimer = null;
        let refreshInterval = 2000;
        let autoScroll = true;
        let isRefreshing = false;
        let currentColumns = [];
        
        // DOM 引用
        const dataTable = document.getElementById('dataTable');
        const tableBody = document.getElementById('tableBody');
        const headerRow = document.getElementById('headerRow');
        const noData = document.getElementById('noData');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');
        const lastUpdate = document.getElementById('lastUpdate');
        const rowCount = document.getElementById('rowCount');
        const colCount = document.getElementById('colCount');
        const displayRowCount = document.getElementById('displayRowCount');
        const dataSize = document.getElementById('dataSize');
        const tableContainer = document.getElementById('tableContainer');
        
        // ==========================================
        // 核心功能：使用 iframe 方式获取数据（彻底避免转圈）
        // ==========================================
        
        // 创建一个隐藏的 iframe 用于加载数据
        let dataIframe = null;
        let iframeReady = false;
        
        function initIframe() {
            if (dataIframe) return;
            
            dataIframe = document.createElement('iframe');
            dataIframe.style.display = 'none';
            dataIframe.id = 'dataLoader';
            // 设置 sandbox 可以进一步减少浏览器行为
            dataIframe.sandbox = 'allow-scripts allow-same-origin';
            document.body.appendChild(dataIframe);
            
            // 监听 iframe 加载完成
            dataIframe.addEventListener('load', function() {
                iframeReady = true;
                console.log('📦 数据加载器已就绪');
            });
            
            // 监听 iframe 内的消息
            window.addEventListener('message', function(event) {
                // 只接受来自 iframe 的消息
                if (event.source !== dataIframe.contentWindow) return;
                
                try {
                    const data = JSON.parse(event.data);
                    updateTableData(data);
                    updateStatus('online', '在线');
                    const now = new Date();
                    lastUpdate.textContent = now.toLocaleString('zh-CN');
                    
                    // 隐藏加载状态
                    loadingOverlay.classList.remove('active');
                    isRefreshing = false;
                } catch (e) {
                    console.error('解析数据失败:', e);
                    loadingOverlay.classList.remove('active');
                    isRefreshing = false;
                }
            });
        }
        
        function fetchDataWithIframe() {
            if (isRefreshing) return;
            isRefreshing = true;
            loadingOverlay.classList.add('active');
            
            if (!dataIframe) {
                initIframe();
            }
            
            // 构建数据获取 URL
            const url = 'data.json?_=' + Date.now() + '&' + API_KEY_PARAM;
            
            // 在 iframe 中加载数据
            try {
                // 方法：在 iframe 中执行 fetch
                const script = `
                    fetch('${url}', {
                        cache: 'no-store',
                        headers: {
                            'Cache-Control': 'no-cache, no-store, must-revalidate'
                        }
                    })
                    .then(response => response.text())
                    .then(data => {
                        // 将数据发送回父窗口
                        window.parent.postMessage(data, '*');
                    })
                    .catch(error => {
                        window.parent.postMessage(JSON.stringify({error: error.message}), '*');
                    });
                `;
                
                // 在 iframe 中执行脚本
                const iframeWindow = dataIframe.contentWindow;
                if (iframeWindow) {
                    // 清除 iframe 内容
                    iframeWindow.document.open();
                    iframeWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <script>
                                ${script}
                            <\/script>
                        </head>
                        <body></body>
                        </html>
                    `);
                    iframeWindow.document.close();
                } else {
                    throw new Error('iframe 未就绪');
                }
            } catch (error) {
                console.error('iframe 加载失败:', error);
                loadingOverlay.classList.remove('active');
                isRefreshing = false;
                
                // 降级方案：直接使用 fetch
                fetchDataFallback();
            }
        }
        
        // ==========================================
        // 降级方案：使用传统的 fetch（但加了额外处理）
        // ==========================================
        
        let fetchAbortController = null;
        
        function fetchDataFallback() {
            if (fetchAbortController) {
                fetchAbortController.abort();
            }
            fetchAbortController = new AbortController();
            
            const url = 'data.json?_=' + Date.now() + '&' + API_KEY_PARAM;
            
            fetch(url, {
                signal: fetchAbortController.signal,
                cache: 'no-store',
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.text();
            })
            .then(text => {
                if (!text || text.trim() === '') {
                    showNoData('数据文件为空');
                    updateStatus('offline', '无数据');
                    return;
                }
                const data = JSON.parse(text);
                updateTableData(data);
                updateStatus('online', '在线');
                const sizeKB = (text.length / 1024).toFixed(2);
                dataSize.textContent = `数据大小: ${sizeKB} KB`;
                const now = new Date();
                lastUpdate.textContent = now.toLocaleString('zh-CN');
            })
            .catch(error => {
                if (error.name === 'AbortError') return;
                console.error('获取数据失败:', error);
                showNoData('数据加载失败');
                updateStatus('offline', '加载失败');
            })
            .finally(() => {
                loadingOverlay.classList.remove('active');
                isRefreshing = false;
                fetchAbortController = null;
            });
        }
        
        // ==========================================
        // 主获取函数：优先使用 iframe，降级使用 fetch
        // ==========================================
        
        function fetchData() {
            // 尝试使用 iframe 方式（彻底避免转圈）
            try {
                if (dataIframe && iframeReady) {
                    fetchDataWithIframe();
                } else {
                    // iframe 未初始化，使用降级方案
                    fetchDataFallback();
                    // 同时初始化 iframe
                    if (!dataIframe) {
                        initIframe();
                        // 标记为就绪，下次使用
                        setTimeout(() => {
                            iframeReady = true;
                        }, 100);
                    }
                }
            } catch (e) {
                console.warn('iframe 方式失败，使用降级方案:', e);
                fetchDataFallback();
            }
        }
        
        // ==========================================
        // 更新表格数据
        // ==========================================
        
        function updateTableData(data) {
            let rows = [];
            let columns = [];
            
            // 解析数据
            if (data.columns && data.data) {
                columns = data.columns;
                rows = data.data;
            } else if (Array.isArray(data) && data.length > 0 && typeof data[0] === 'object') {
                columns = Object.keys(data[0]);
                rows = data.map(row => columns.map(col => row[col] !== undefined ? row[col] : null));
            } else if (typeof data === 'object' && !Array.isArray(data)) {
                const keys = Object.keys(data);
                if (keys.length > 0 && typeof data[keys[0]] === 'object') {
                    const firstRow = data[keys[0]];
                    columns = Object.keys(firstRow);
                    rows = keys.map(key => {
                        const row = data[key];
                        return columns.map(col => row[col] !== undefined ? row[col] : null);
                    });
                }
            }
            
            if (columns.length > 0 && JSON.stringify(columns) !== JSON.stringify(currentColumns)) {
                currentColumns = columns;
                headerRow.innerHTML = columns.map(col => 
                    `<th>${escapeHtml(String(col))}</th>`
                ).join('');
                colCount.textContent = columns.length;
            }
            
            if (rows.length === 0 || columns.length === 0) {
                showNoData('数据为空');
                updateTableMeta(0, 0);
                return;
            }
            
            dataTable.style.display = '';
            noData.style.display = 'none';
            
            const maxRows = 1000;
            const displayRows = rows.slice(0, maxRows);
            
            const fragment = document.createDocumentFragment();
            displayRows.forEach((row) => {
                const tr = document.createElement('tr');
                for (let i = 0; i < columns.length; i++) {
                    const td = document.createElement('td');
                    let value = row[i] !== undefined ? row[i] : null;
                    td.textContent = formatValue(value);
                    tr.appendChild(td);
                }
                fragment.appendChild(tr);
            });
            
            tableBody.innerHTML = '';
            tableBody.appendChild(fragment);
            
            if (rows.length > maxRows) {
                const existing = document.getElementById('truncateMsg');
                if (!existing) {
                    const msg = document.createElement('div');
                    msg.id = 'truncateMsg';
                    msg.style.cssText = 'padding: 10px; text-align: center; background: #fff3cd; color: #856404; font-size: 13px;';
                    msg.textContent = `⚠️ 数据超过${maxRows}行，仅显示前${maxRows}行 (总计${rows.length}行)`;
                    const statsDiv = document.querySelector('.stats');
                    statsDiv.parentNode.insertBefore(msg, statsDiv);
                }
            } else {
                const existing = document.getElementById('truncateMsg');
                if (existing) existing.remove();
            }
            
            updateTableMeta(rows.length, columns.length);
            rowCount.textContent = rows.length;
            
            if (autoScroll) {
                setTimeout(() => {
                    tableContainer.scrollTop = tableContainer.scrollHeight;
                }, 50);
            }
        }
        
        // ==========================================
        // 工具函数
        // ==========================================
        
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }
        
        function formatValue(value) {
            if (value === null || value === undefined) return '-';
            if (typeof value === 'number') {
                if (Number.isInteger(value)) return value.toString();
                return value.toFixed(4);
            }
            if (typeof value === 'boolean') return value ? '✓' : '✗';
            /*
            if (typeof value === 'string' && !isNaN(Date.parse(value))) {
                const d = new Date(value);
                if (!isNaN(d.getTime())) {
                    return d.toLocaleString('zh-CN');
                }
            }*/
            return String(value);
        }

        function showNoData(message) {
            dataTable.style.display = 'none';
            noData.style.display = 'block';
            noData.innerHTML = `
                <div class="icon">📭</div>
                <p>${message}</p>
            `;
            updateTableMeta(0, 0);
        }
        
        function updateTableMeta(rows, cols) {
            rowCount.textContent = rows;
            displayRowCount.textContent = rows > 1000 ? 1000 : rows;
            if (cols > 0) {
                colCount.textContent = cols;
            }
        }
        
        function updateStatus(status, text) {
            statusDot.className = `status-dot ${status}`;
            statusText.textContent = text;
        }
        
        // ==========================================
        // 控制功能
        // ==========================================
        
        function startAutoRefresh() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
            }
            refreshTimer = setInterval(fetchData, refreshInterval);
        }
        
        function stopAutoRefresh() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
                refreshTimer = null;
            }
        }
        
        // ==========================================
        // 事件绑定
        // ==========================================
        
        document.getElementById('refreshInterval').addEventListener('change', function() {
            refreshInterval = parseInt(this.value);
            startAutoRefresh();
        });
        
        document.getElementById('btnRefresh').addEventListener('click', function() {
            fetchData();
        });
        
        document.getElementById('btnAutoScroll').addEventListener('click', function() {
            autoScroll = !autoScroll;
            this.textContent = autoScroll ? '📌 自动滚动' : '📌 手动滚动';
            this.classList.toggle('btn-success', autoScroll);
            this.classList.toggle('btn', true);
        });
        
        document.getElementById('btnClear').addEventListener('click', function() {
            if (confirm('确定要清空缓存并重新加载吗？')) {
                // 使用 fetch 发送清空请求，然后重新加载数据
                fetch('clear.php?' + API_KEY_PARAM, { 
                    method: 'POST',
                    cache: 'no-store'
                })
                .then(() => {
                    // 清空表格
                    tableBody.innerHTML = '';
                    headerRow.innerHTML = '<th style="text-align:center;color:#95a5a6;">等待数据...</th>';
                    showNoData('数据已清空，等待新数据...');
                    updateStatus('offline', '已清空');
                    // 重新获取数据
                    setTimeout(fetchData, 500);
                })
                .catch(() => {
                    // 如果清空失败，直接重新加载数据
                    fetchData();
                });
            }
        });
        
        // ==========================================
        // 初始化
        // ==========================================
        
        // 初始化 iframe
        initIframe();
        
        // 首次加载数据
        setTimeout(fetchData, 100);
        startAutoRefresh();
        
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
                fetchData();
            }
        });
        
        console.log('📊 DataFrame 数据监控已启动');
        console.log(`⏱️ 刷新间隔: ${refreshInterval}ms`);
        console.log(`🔑 API Key: ${API_KEY.substring(0, 8)}*** (已认证)`);
        console.log('📦 使用 iframe 加载器，浏览器不会显示转圈');
    </script>
</body>
</html>