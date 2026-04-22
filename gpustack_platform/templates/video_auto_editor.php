<?php
/**
 * 视频自动剪辑编辑器
 * 一键生成可直接发布的短视频
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

$config = require __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI视频自动剪辑 - <?php echo $config['app']['name'] ?? '巨神兵API辅助平台API辅助平台'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 24px;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .upload-area {
            border: 3px dashed #e2e8f0;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            background: #f8fafc;
        }
        
        .upload-area.has-file {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .upload-area i {
            font-size: 48px;
            color: #94a3b8;
            margin-bottom: 16px;
        }
        
        .upload-area.has-file i {
            color: #10b981;
        }
        
        .style-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .style-option {
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .style-option:hover {
            border-color: #667eea;
        }
        
        .style-option.active {
            border-color: #667eea;
            background: #eff6ff;
        }
        
        .style-option i {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .style-option h4 {
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .style-option p {
            font-size: 12px;
            color: #64748b;
        }
        
        .duration-slider {
            margin-bottom: 20px;
        }
        
        .duration-slider label {
            display: block;
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
        }
        
        .duration-slider input[type="range"] {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e2e8f0;
            outline: none;
            -webkit-appearance: none;
        }
        
        .duration-slider input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .progress-section {
            display: none;
            margin-top: 20px;
        }
        
        .progress-section.active {
            display: block;
        }
        
        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.3s;
            width: 0%;
        }
        
        .progress-text {
            text-align: center;
            margin-top: 8px;
            font-size: 14px;
            color: #64748b;
        }
        
        .result-section {
            display: none;
            margin-top: 20px;
        }
        
        .result-section.active {
            display: block;
        }
        
        .video-preview {
            width: 100%;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        
        .video-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .info-item {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }
        
        .info-item .value {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
        }
        
        .info-item .label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .advanced-options {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .toggle-advanced {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #667eea;
            cursor: pointer;
            font-size: 14px;
        }
        
        .advanced-panel {
            display: none;
            margin-top: 16px;
        }
        
        .advanced-panel.active {
            display: block;
        }
        
        .config-item {
            margin-bottom: 16px;
        }
        
        .config-item label {
            display: block;
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
        }
        
        .config-item input[type="range"] {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-magic"></i> AI视频自动剪辑</h1>
            <p>智能分析视频内容，一键生成可直接发布的精彩短视频</p>
        </div>
        
        <div class="main-grid">
            <div class="left-panel">
                <div class="card">
                    <h3 class="card-title">
                        <i class="fas fa-upload"></i>
                        上传视频
                    </h3>
                    
                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('videoFile').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h4>点击或拖拽上传视频</h4>
                        <p>支持 MP4, MOV, AVI, MKV 格式</p>
                        <input type="file" id="videoFile" accept="video/*" style="display: none;" onchange="handleFileSelect(event)">
                    </div>
                    
                    <div id="fileInfo" style="display: none; margin-top: 16px; padding: 12px; background: #f0fdf4; border-radius: 8px; color: #166534;">
                        <i class="fas fa-check-circle"></i> <span id="fileName"></span>
                    </div>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">
                        <i class="fas fa-sliders-h"></i>
                        剪辑风格
                    </h3>
                    
                    <div class="style-selector">
                        <div class="style-option active" data-style="auto" onclick="selectStyle('auto')">
                            <i class="fas fa-magic"></i>
                            <h4>智能自动</h4>
                            <p>自动分析最佳剪辑点</p>
                        </div>
                        <div class="style-option" data-style="fast" onclick="selectStyle('fast')">
                            <i class="fas fa-bolt"></i>
                            <h4>快节奏</h4>
                            <p>快速切换，适合短视频</p>
                        </div>
                        <div class="style-option" data-style="slow" onclick="selectStyle('slow')">
                            <i class="fas fa-feather"></i>
                            <h4>慢节奏</h4>
                            <p>舒缓流畅，适合长视频</p>
                        </div>
                        <div class="style-option" data-style="cinematic" onclick="selectStyle('cinematic')">
                            <i class="fas fa-film"></i>
                            <h4>电影感</h4>
                            <p>专业调色，转场效果</p>
                        </div>
                    </div>
                    
                    <div class="duration-slider">
                        <label>目标时长: <span id="durationValue">30</span> 秒</label>
                        <input type="range" id="durationSlider" min="15" max="120" value="30" oninput="updateDuration(this.value)">
                    </div>
                    
                    <div class="advanced-options">
                        <div class="toggle-advanced" onclick="toggleAdvanced()">
                            <i class="fas fa-cog"></i>
                            <span>高级设置（微调模式）</span>
                            <i class="fas fa-chevron-down" id="advancedIcon"></i>
                        </div>
                        
                        <div class="advanced-panel" id="advancedPanel">
                            <div class="config-item">
                                <label>场景变化敏感度: <span id="sceneThresholdValue">0.3</span></label>
                                <input type="range" min="0.1" max="0.8" step="0.1" value="0.3" oninput="updateConfig('scene_threshold', this.value)">
                            </div>
                            <div class="config-item">
                                <label>最小片段时长: <span id="minDurationValue">3</span>秒</label>
                                <input type="range" min="1" max="10" value="3" oninput="updateConfig('min_clip_duration', this.value)">
                            </div>
                            <div class="config-item">
                                <label>最大片段时长: <span id="maxDurationValue">15</span>秒</label>
                                <input type="range" min="5" max="30" value="15" oninput="updateConfig('max_clip_duration', this.value)">
                            </div>
                        </div>
                    </div>
                    
                    <button class="btn-primary" id="editBtn" onclick="startAutoEdit()" disabled>
                        <i class="fas fa-wand-magic-sparkles"></i> 开始自动剪辑
                    </button>
                    
                    <div class="progress-section" id="progressSection">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="progress-text" id="progressText">正在分析视频内容...</div>
                    </div>
                </div>
            </div>
            
            <div class="right-panel">
                <div class="card">
                    <h3 class="card-title">
                        <i class="fas fa-play-circle"></i>
                        预览结果
                    </h3>
                    
                    <div id="emptyState" style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                        <i class="fas fa-film" style="font-size: 64px; margin-bottom: 16px; display: block;"></i>
                        <p>上传视频并开始剪辑后，结果将显示在这里</p>
                    </div>
                    
                    <div class="result-section" id="resultSection">
                        <video class="video-preview" id="resultVideo" controls></video>
                        
                        <div class="video-info">
                            <div class="info-item">
                                <div class="value" id="resultDuration">0</div>
                                <div class="label">时长(秒)</div>
                            </div>
                            <div class="info-item">
                                <div class="value" id="resultClips">0</div>
                                <div class="label">片段数</div>
                            </div>
                            <div class="info-item">
                                <div class="value" id="resultSize">0</div>
                                <div class="label">文件大小</div>
                            </div>
                        </div>
                        
                        <a id="downloadBtn" class="btn-primary" style="display: inline-block; text-align: center; text-decoration: none;" download>
                            <i class="fas fa-download"></i> 下载视频
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let selectedFile = null;
        let selectedStyle = 'auto';
        let customConfig = {};
        
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            selectedFile = file;
            
            // 更新UI
            document.getElementById('uploadArea').classList.add('has-file');
            document.getElementById('fileInfo').style.display = 'block';
            document.getElementById('fileName').textContent = file.name + ' (' + formatFileSize(file.size) + ')';
            document.getElementById('editBtn').disabled = false;
        }
        
        function selectStyle(style) {
            selectedStyle = style;
            document.querySelectorAll('.style-option').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`[data-style="${style}"]`).classList.add('active');
        }
        
        function updateDuration(value) {
            document.getElementById('durationValue').textContent = value;
        }
        
        function toggleAdvanced() {
            const panel = document.getElementById('advancedPanel');
            const icon = document.getElementById('advancedIcon');
            panel.classList.toggle('active');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        }
        
        function updateConfig(key, value) {
            customConfig[key] = parseFloat(value);
            
            // 更新显示值
            if (key === 'scene_threshold') {
                document.getElementById('sceneThresholdValue').textContent = value;
            } else if (key === 'min_clip_duration') {
                document.getElementById('minDurationValue').textContent = value;
            } else if (key === 'max_clip_duration') {
                document.getElementById('maxDurationValue').textContent = value;
            }
        }
        
        async function startAutoEdit() {
            if (!selectedFile) {
                alert('请先上传视频');
                return;
            }
            
            const btn = document.getElementById('editBtn');
            const progressSection = document.getElementById('progressSection');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            
            btn.disabled = true;
            progressSection.classList.add('active');
            
            // 模拟进度
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressFill.style.width = progress + '%';
                
                if (progress < 30) {
                    progressText.textContent = '正在分析视频内容...';
                } else if (progress < 60) {
                    progressText.textContent = '正在检测精彩片段...';
                } else {
                    progressText.textContent = '正在生成剪辑视频...';
                }
            }, 1000);
            
            try {
                const formData = new FormData();
                formData.append('action', 'auto_edit');
                formData.append('video', selectedFile);
                formData.append('target_duration', document.getElementById('durationSlider').value);
                formData.append('style', selectedStyle);
                
                const response = await fetch('api/video_auto_edit.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                clearInterval(progressInterval);
                progressFill.style.width = '100%';
                progressText.textContent = '完成！';
                
                if (result.success) {
                    showResult(result);
                } else {
                    alert('剪辑失败: ' + result.error);
                }
            } catch (error) {
                clearInterval(progressInterval);
                alert('请求失败: ' + error.message);
            } finally {
                btn.disabled = false;
            }
        }
        
        function showResult(result) {
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('resultSection').classList.add('active');
            
            const video = document.getElementById('resultVideo');
            video.src = result.video.url;
            
            document.getElementById('resultDuration').textContent = Math.round(result.video.duration);
            document.getElementById('resultClips').textContent = result.video.clips_count;
            document.getElementById('resultSize').textContent = result.video.file_size;
            
            document.getElementById('downloadBtn').href = result.video.url;
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
