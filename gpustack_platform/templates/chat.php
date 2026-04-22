<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['logged_in']) {
    header('Location: ?route=login');
    exit;
}

// 引入权限管理
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PermissionManager.php';

// 检查是否有聊天权限
$userId = $_SESSION['user']['id'];
$isAdmin = $_SESSION['user']['role'] === 'admin';

if (!$isAdmin) {
    $db = Database::getInstance();
    $permManager = new PermissionManager($db);
    if (!$permManager->hasModulePermission($userId, 'chat')) {
        header('Location: ?route=home&error=permission_denied');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI聊天 - 巨神兵API辅助平台API辅助平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* 头部导航 */
        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #4c51bf;
        }

        .logo img {
            height: 36px;
        }

        .nav {
            display: flex;
            gap: 8px;
        }

        .nav-item {
            padding: 10px 20px;
            border-radius: 10px;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: #f3f4f6;
            color: #4c51bf;
        }

        .nav-item.active {
            background: #4c51bf;
            color: white;
        }

        /* 主内容区 */
        .main-card {
            background: white;
            height: calc(100vh - 73px);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
        }

        /* 模式切换标签 */
        .mode-tabs {
            display: flex;
            gap: 8px;
            background: #f3f4f6;
            padding: 4px;
            border-radius: 10px;
        }

        .mode-tab {
            padding: 8px 16px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .mode-tab:hover {
            color: #4c51bf;
        }

        .mode-tab.active {
            background: white;
            color: #4c51bf;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* 视频生成区域 */
        .video-gen-container {
            display: none;
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background: #f8fafc;
        }

        .video-gen-container.active {
            display: block;
        }

        .video-gen-panel {
            max-width: 800px;
            margin: 0 auto;
        }

        .video-input-area {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .video-input-area h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .video-prompt-input {
            width: 100%;
            min-height: 100px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            resize: vertical;
            font-family: inherit;
            margin-bottom: 16px;
        }

        .video-prompt-input:focus {
            outline: none;
            border-color: #4c51bf;
            box-shadow: 0 0 0 3px rgba(76, 81, 191, 0.1);
        }

        .video-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .video-option {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .video-option label {
            font-size: 13px;
            font-weight: 500;
            color: #374151;
        }

        .video-option select {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .generate-video-btn {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .generate-video-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .generate-video-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .video-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .video-result-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .video-preview {
            aspect-ratio: 16/9;
            background: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .video-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-info {
            padding: 16px;
        }

        .video-prompt-text {
            font-size: 14px;
            color: #374151;
            margin-bottom: 12px;
            overflow: hidden;
            display: -webkit-box;
            display: box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            box-orient: vertical;
        }

        .video-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #6b7280;
        }

        .video-actions {
            display: flex;
            gap: 8px;
        }

        .video-action-btn {
            padding: 6px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            font-size: 12px;
            color: #4b5563;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .video-action-btn:hover {
            background: #f3f4f6;
            border-color: #4c51bf;
            color: #4c51bf;
        }

        /* 生成进度 */
        .generating-status {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px;
            background: #f0fdf4;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .generating-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid #bbf7d0;
            border-top-color: #22c55e;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .generating-text {
            font-size: 14px;
            color: #166534;
        }

        .model-select {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            color: #4a5568;
        }

        /* 自定义模型选择下拉框 */
        .custom-model-select {
            position: relative;
        }

        .custom-select-trigger {
            background: white !important;
            transition: all 0.2s;
        }

        .custom-select-trigger:hover {
            border-color: #4c51bf;
        }

        .custom-select-dropdown {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .custom-select-option {
            padding: 10px 16px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.15s;
            border-bottom: 1px solid #f1f5f9;
        }

        .custom-select-option:last-child {
            border-bottom: none;
        }

        .custom-select-option:hover {
            background: #f8fafc;
        }

        .custom-select-option.selected {
            background: #eef2ff;
            color: #4c51bf;
        }

        .custom-select-option .model-name {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
        }

        .custom-select-option .model-remark {
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .custom-select-option:hover .model-remark {
            background: #e2e8f0;
        }

        .custom-select-option.selected .model-name {
            color: #4c51bf;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background: #f8fafc;
        }

        .message {
            display: flex;
            margin-bottom: 16px;
            gap: 12px;
        }

        .message.user {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .message.system .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .message.user .message-avatar {
            background: #4c51bf;
            color: white;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.5;
        }

        .message.system .message-content {
            background: white;
            border: 1px solid #e2e8f0;
            color: #1a202c;
        }

        .message.user .message-content {
            background: #4c51bf;
            color: white;
        }

        /* AI生成的多媒体内容 */
        .message-media {
            max-width: 70%;
            padding: 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .message-media img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .message-media img:hover {
            transform: scale(1.02);
        }

        .message-media .file-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .message-media .file-item:last-child {
            margin-bottom: 0;
        }

        .message-media .file-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .message-media .file-info {
            flex: 1;
        }

        .message-media .file-name {
            font-weight: 500;
            color: #1a202c;
            margin-bottom: 2px;
        }

        .message-media .file-size {
            font-size: 12px;
            color: #64748b;
        }

        .message-media .file-download {
            padding: 8px 16px;
            background: #4c51bf;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .message-media .file-download:hover {
            background: #434190;
        }

        /* 图片网格 */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
        }

        .image-grid img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* 内容类型标签 */
        .content-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            background: #ede9fe;
            color: #4c51bf;
            font-size: 11px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .chat-input-area {
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            background: white;
        }

        .input-wrapper {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            resize: none;
            min-height: 48px;
            max-height: 120px;
            font-family: inherit;
        }

        .chat-input:focus {
            outline: none;
            border-color: #4c51bf;
        }

        /* 图片上传区域 */
        .upload-preview-area {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
            padding: 8px;
            background: #f8fafc;
            border-radius: 8px;
            min-height: 0;
            max-height: 150px;
            overflow-y: auto;
        }

        .upload-preview-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            background: white;
        }

        .upload-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-preview-item.video,
        .upload-preview-item.document {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            flex-direction: column;
        }

        .upload-preview-item.video {
            background: #1a202c;
        }

        .upload-preview-item.video i,
        .upload-preview-item.document i {
            font-size: 28px;
            color: #4c51bf;
        }

        .upload-preview-item.video i {
            color: white;
        }

        .upload-preview-item.document i.fa-file-word {
            color: #2563eb;
        }

        .upload-preview-item.document i.fa-file-excel {
            color: #16a34a;
        }

        .upload-preview-item.document i.fa-file-powerpoint {
            color: #ea580c;
        }

        .upload-preview-item.document i.fa-file-alt {
            color: #64748b;
        }

        .upload-preview-item .file-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2px 4px;
            background: rgba(0,0,0,0.7);
            color: white;
            font-size: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .upload-preview-item .remove-btn {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            background: rgba(220, 38, 38, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upload-btn {
            padding: 10px;
            background: #f3f4f6;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .upload-btn:hover {
            background: #e5e7eb;
            border-color: #4c51bf;
            color: #4c51bf;
        }

        .input-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .send-btn {
            padding: 12px 20px;
            background: #4c51bf;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .send-btn:hover {
            background: #434190;
        }

        /* 工具栏 */
        .chat-toolbar {
            padding: 12px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .toolbar-tools {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tool-dropdown {
            position: relative;
        }

        .tool-btn {
            padding: 8px 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            color: #4a5568;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .tool-btn:hover {
            border-color: #4c51bf;
            color: #4c51bf;
        }

        .tool-btn.active {
            background: #4c51bf;
            color: white;
            border-color: #4c51bf;
        }

        .tool-menu {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 8px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            min-width: 180px;
            display: none;
            z-index: 100;
        }

        .tool-menu.show {
            display: block;
            animation: menuSlide 0.2s ease;
        }

        @keyframes menuSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        .tool-item {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 13px;
            color: #4a5568;
            transition: all 0.2s;
        }

        .tool-item:hover {
            background: #f3f4f6;
        }

        .tool-item:first-child {
            border-radius: 12px 12px 0 0;
        }

        .tool-item:last-child {
            border-radius: 0 0 12px 12px;
        }

        .tool-item i {
            width: 18px;
            text-align: center;
            color: #4c51bf;
        }

        .toolbar-divider {
            width: 1px;
            height: 24px;
            background: #e2e8f0;
        }

        /* 深度思考 & 联网搜索 */
        .feature-toggles {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toggle-btn {
            padding: 8px 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            color: #4a5568;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .toggle-btn:hover {
            border-color: #4c51bf;
        }

        .toggle-btn.active {
            background: #ede9fe;
            border-color: #4c51bf;
            color: #4c51bf;
        }

        .toggle-btn i {
            font-size: 14px;
        }

        /* 联网搜索下拉 */
        .search-dropdown {
            position: relative;
        }

        .search-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            min-width: 200px;
            display: none;
            z-index: 100;
        }

        .search-menu.show {
            display: block;
            animation: menuSlide 0.2s ease;
        }

        .search-mode-header {
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-mode-item {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-size: 13px;
            color: #4a5568;
            transition: all 0.2s;
        }

        .search-mode-item:hover {
            background: #f3f4f6;
        }

        .search-mode-item.selected {
            background: #ede9fe;
            color: #4c51bf;
        }

        .search-mode-item.selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 12px;
        }

        .search-mode-desc {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* 思考中指示器 */
        .thinking-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f0fdf4;
            border-radius: 8px;
            font-size: 12px;
            color: #166534;
            margin-bottom: 8px;
        }

        .thinking-indicator.show {
            display: flex;
        }

        .thinking-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid #bbf7d0;
            border-top-color: #22c55e;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 搜索结果标签 */
        .search-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            border-radius: 4px;
            margin-left: 8px;
        }

        .searching-indicator {
            display: none;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: #eff6ff;
            border-radius: 8px;
            font-size: 12px;
            color: #1d4ed8;
            margin-bottom: 8px;
        }

        .searching-indicator.show {
            display: flex;
        }

        /* 移动端响应式设计 */
        @media screen and (max-width: 768px) {
            body {
                padding: 0;
            }

            .container {
                max-width: 100%;
                padding: 0;
            }

            .header {
                padding: 12px 16px;
                flex-direction: column;
                gap: 10px;
            }

            .header h1 {
                font-size: 16px;
            }

            .chat-container {
                flex-direction: column;
            }

            .chat-sidebar {
                width: 100%;
                height: auto;
                min-height: 60px;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }

            .chat-sidebar.collapsed {
                display: none;
            }

            .chat-main {
                height: calc(100vh - 200px);
            }

            .chat-header {
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .chat-messages {
                padding: 16px;
            }

            .message-content {
                max-width: 85%;
                padding: 10px 12px;
                font-size: 14px;
            }

            .chat-input-area {
                padding: 12px 16px;
            }

            .toolbar {
                padding: 8px 16px;
                flex-wrap: wrap;
                gap: 8px;
            }

            .toolbar-tools {
                gap: 8px;
            }

            .mode-tabs {
                padding: 8px 16px;
                gap: 8px;
            }

            .mode-tab {
                padding: 8px 12px;
                font-size: 13px;
            }

            /* 视频生成区域移动端适配 */
            .video-gen-container {
                flex-direction: column;
            }

            .video-gen-panel {
                min-width: auto;
            }

            .video-options {
                grid-template-columns: 1fr;
            }

            .video-results {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 480px) {
            .chat-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .model-select {
                width: 100%;
                min-width: auto;
            }

            .toolbar .btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .toolbar-tools .tool-btn {
                width: 36px;
                height: 36px;
            }

            .input-wrapper {
                gap: 8px;
            }

            .chat-input {
                padding: 10px 12px;
                font-size: 16px; /* 防止iOS缩放 */
            }

            #sendBtn {
                width: 40px;
                height: 40px;
            }

            /* 简化顶部导航 */
            .header {
                padding: 10px 12px;
            }

            .header-actions {
                gap: 8px;
            }

            .header-actions .btn {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部导航 -->
        <header class="header">
            <a href="?route=home" class="logo" style="text-decoration: none;">
                <img src="assets/images/logo.png" alt="巨神兵API辅助平台AI" style="height: 32px; width: auto;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span style="display: none;"><i class="fas fa-robot"></i> 巨神兵API辅助平台API辅助平台</span>
            </a>
            <nav class="nav">
                <a href="?route=home" class="nav-item">
                    <i class="fas fa-home"></i> 首页
                </a>
                <?php
                // 获取当前用户权限
                $userPermissions = null;
                if (isset($_SESSION['user']['id']) && class_exists('PermissionManager')) {
                    $db = Database::getInstance();
                    $permManager = new PermissionManager($db);
                    $userPermissions = $permManager->getUserPermissions($_SESSION['user']['id']);
                }
                $isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
                
                function userCanAccess($module, $permissions, $isAdmin) {
                    if ($isAdmin) return true;
                    if (!$permissions) return true;
                    foreach ($permissions['modules'] as $perm) {
                        if ($perm['module'] === $module) return $perm['allowed'] == 1;
                    }
                    return true;
                }
                ?>
                <?php if (userCanAccess('scenarios', $userPermissions, $isAdmin)): ?>
                <a href="?route=scenarios" class="nav-item">
                    <i class="fas fa-magic"></i> 场景演示
                </a>
                <?php endif; ?>
                <a href="?route=chat" class="nav-item active">
                    <i class="fas fa-comments"></i> 聊天
                </a>
                <a href="?route=workflows_comfyui" class="nav-item">
                    <i class="fas fa-project-diagram"></i> 工作流
                </a>
                <a href="?route=agents" class="nav-item">
                    <i class="fas fa-robot"></i> 智能体
                </a>
                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                <a href="?route=admin" class="nav-item">
                    <i class="fas fa-cog"></i> 管理
                </a>
                <?php endif; ?>
                <a href="?route=logout" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> 退出
                </a>
                <a href="?route=about" class="nav-item">
                    <i class="fas fa-info-circle"></i> 关于
                </a>
            </nav>
        </header>

        <!-- 主内容 -->
        <div class="main-card">
            <div class="chat-header">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="mode-tabs">
                        <button class="mode-tab active" data-mode="chat" onclick="switchMode('chat')">
                            <i class="fas fa-comments"></i>
                            聊天
                        </button>
                        <button class="mode-tab" data-mode="video" onclick="switchMode('video')">
                            <i class="fas fa-video"></i>
                            视频生成
                        </button>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- API提供商选择 -->
                    <select class="model-select" id="providerSelect" onchange="onProviderChangeForCurrentMode()" style="min-width: 140px;">
                        <option value="">加载提供商...</option>
                    </select>
                    <!-- 模型选择 - 自定义下拉带备注 -->
                    <div class="custom-model-select" id="modelSelectContainer" style="position: relative; min-width: 320px;">
                        <div class="custom-select-trigger" id="modelSelectTrigger" onclick="toggleModelDropdown()" style="padding: 8px 12px; background: #f8f9fa; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                            <span id="modelSelectText">选择模型...</span>
                            <i class="fas fa-chevron-down" style="color: #64748b; font-size: 12px;"></i>
                        </div>
                        <div class="custom-select-dropdown" id="modelSelectDropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 8px; margin-top: 4px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                            <!-- 选项将通过JS动态生成 -->
                        </div>
                        <!-- 隐藏的原始select用于表单提交 -->
                        <select class="model-select" id="modelSelect" style="display: none;">
                            <option value="">选择模型...</option>
                        </select>
                    </div>
                    <button class="tool-btn" onclick="refreshModelsAndProviders()" title="刷新列表">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <!-- 工具栏 -->
            <div class="chat-toolbar">
                <div class="toolbar-tools">
                    <div class="tool-dropdown">
                        <button class="tool-btn" id="toolsBtn" onclick="toggleToolMenu()">
                            <i class="fas fa-toolbox"></i>
                            工具
                            <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                        </button>
                        <div class="tool-menu" id="toolMenu">
                            <div class="tool-item" onclick="selectTool('writing')">
                                <i class="fas fa-pen-fancy"></i>
                                <span>写作</span>
                            </div>
                            <div class="tool-item" onclick="selectTool('coding')">
                                <i class="fas fa-code"></i>
                                <span>编程</span>
                            </div>
                            <div class="tool-item" onclick="selectTool('problem')">
                                <i class="fas fa-calculator"></i>
                                <span>解题</span>
                            </div>
                            <div class="tool-item" onclick="selectTool('record')">
                                <i class="fas fa-microphone"></i>
                                <span>录音笔</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="toolbar-divider"></div>

                <div class="feature-toggles">
                    <button class="toggle-btn" id="deepThinkBtn" onclick="toggleDeepThink()">
                        <i class="fas fa-brain"></i>
                        深度思考
                    </button>

                    <div class="search-dropdown">
                        <button class="toggle-btn" id="webSearchBtn" onclick="toggleSearchMenu()">
                            <i class="fas fa-globe"></i>
                            联网搜索
                            <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                        </button>
                        <div class="search-menu" id="searchMenu">
                            <div class="search-mode-header">联网搜索模式</div>
                            <div class="search-mode-item selected" onclick="setSearchMode('auto')">
                                <div>
                                    <div>自动</div>
                                    <div class="search-mode-desc">自动判断是否联网</div>
                                </div>
                            </div>
                            <div class="search-mode-item" onclick="setSearchMode('manual')">
                                <div>
                                    <div>手动</div>
                                    <div class="search-mode-desc">手动控制联网状态</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="message system">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        你好！我是巨神兵API辅助平台AI助手。有什么可以帮助你的吗？
                    </div>
                </div>
            </div>

            <!-- 视频生成区域 -->
            <div class="video-gen-container" id="videoGenContainer">
                <div class="video-gen-panel">
                    <div class="video-input-area">
                        <h3><i class="fas fa-wand-magic-sparkles" style="color: #4c51bf;"></i> 描述你想生成的视频</h3>
                        <textarea class="video-prompt-input" id="videoPrompt" placeholder="例如：一只可爱的猫咪在草地上追逐蝴蝶，阳光明媚，画面温馨..."></textarea>
                        
                        <div class="video-options">
                            <div class="video-option">
                                <label>视频比例</label>
                                <select id="videoRatio">
                                    <option value="16:9">16:9 宽屏</option>
                                    <option value="9:16">9:16 竖屏</option>
                                    <option value="1:1">1:1 方形</option>
                                    <option value="4:3">4:3 标准</option>
                                </select>
                            </div>
                            <div class="video-option">
                                <label>视频时长</label>
                                <select id="videoDuration">
                                    <option value="5">5秒</option>
                                    <option value="10">10秒</option>
                                    <option value="15">15秒</option>
                                </select>
                            </div>
                            <div class="video-option">
                                <label>画质</label>
                                <select id="videoQuality">
                                    <option value="high">高清</option>
                                    <option value="medium">标准</option>
                                </select>
                            </div>
                        </div>
                        
                        <button class="generate-video-btn" id="generateVideoBtn" onclick="generateVideo()">
                            <i class="fas fa-video"></i>
                            生成视频
                        </button>
                    </div>
                    
                    <!-- 生成中状态 -->
                    <div class="generating-status" id="generatingStatus" style="display: none;">
                        <div class="generating-spinner"></div>
                        <span class="generating-text">正在生成视频，请稍候...</span>
                    </div>
                    
                    <!-- 视频结果列表 -->
                    <div class="video-results" id="videoResults"></div>
                </div>
            </div>

            <div class="chat-input-area" id="chatInputArea">
                <!-- 上传文件预览区域 -->
                <div class="upload-preview-area" id="uploadPreviewArea" style="display: none;"></div>

                <div class="input-wrapper">
                    <div style="flex: 1; display: flex; flex-direction: column;">
                        <div class="thinking-indicator" id="thinkingIndicator">
                            <div class="thinking-spinner"></div>
                            <span>深度思考中...</span>
                        </div>
                        <div class="searching-indicator" id="searchingIndicator">
                            <i class="fas fa-search"></i>
                            <span>正在联网搜索...</span>
                        </div>
                        <textarea class="chat-input" id="chatInput" placeholder="输入你的问题，或上传图片/视频/文档..." rows="1"></textarea>
                    </div>
                    <div class="input-actions">
                        <input type="file" id="fileUpload" accept="image/*,video/*,.txt,.doc,.docx,.wps,.et,.xls,.xlsx,.ppt,.pptx" multiple style="display: none;">
                        <button class="upload-btn" id="uploadBtn" title="上传图片、视频或文档">
                            <i class="fas fa-image"></i>
                        </button>
                        <button class="send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                            发送
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 状态管理
        let deepThinkEnabled = false;
        let webSearchEnabled = false;
        let searchMode = 'auto'; // 'auto' or 'manual'
        let currentTool = null;
        let isProcessing = false;

        // 工具配置 - 多模态AI助手
        const TOOL_CONFIGS = {
            writing: {
                name: '写作',
                icon: 'fa-pen-fancy',
                prompt: '你是一位专业的写作助手，擅长各种文体的写作。请帮助用户进行写作相关的工作。'
            },
            coding: {
                name: '编程',
                icon: 'fa-code',
                prompt: '你是一位资深程序员，精通多种编程语言。请帮助用户解决编程问题。'
            },
            problem: {
                name: '解题',
                icon: 'fa-calculator',
                prompt: '你是一位数学和理科专家，擅长解决各种题目。请帮助用户分析和解答问题。'
            },
            record: {
                name: '录音笔',
                icon: 'fa-microphone',
                prompt: '你是语音转文字助手，可以将录音内容整理成文字，并进行总结和分析。'
            },
            vision: {
                name: '视觉分析',
                icon: 'fa-eye',
                prompt: '你是一位视觉分析专家，擅长分析图像和视频内容。请详细描述你看到的视觉内容，包括场景、物体、人物、动作、颜色、构图等元素。'
            }
        };

        // 上传的文件列表
        let uploadedFiles = [];

        // 系统提示词 - 多模态识别与文档处理
        const MULTIMODAL_SYSTEM_PROMPT = `你是一位强大的多模态AI助手，能够理解和分析文本、图像、视频和各种文档内容。

## 能力范围
1. **文本理解**：理解自然语言，回答问题，进行对话
2. **图像识别**：分析图片内容，识别物体、场景、文字、人脸等
3. **视频理解**：理解视频内容，描述动作、场景变化、时间线等
4. **文档处理**：阅读和分析各类文档，包括：
   - 文本文件（TXT）
   - WPS Office文档（WPS、DOC、DOCX）
   - Excel表格（XLS、XLSX、ET）
   - PowerPoint演示文稿（PPT、PPTX）

## 响应规则
- 当用户上传图片时，详细描述图像内容，包括视觉元素、场景、物体等
- 当用户上传视频时，描述视频的主要内容和关键帧
- 当用户上传文档时，提取文档的主要内容，总结关键信息，回答文档相关问题
- 当用户同时提供文字和媒体内容时，结合所有信息给出综合回答
- 如果无法确定某些内容，请诚实说明
- 对于敏感内容，请委婉拒绝并说明原因

## 文档处理指南
- 对于文本类文档：提取主要观点、关键数据和结论
- 对于表格类文档：分析数据结构，提取重要统计信息
- 对于演示文稿：总结主题、主要内容和逻辑结构
- 如果文档内容较长，提供内容摘要和重点概括

## 输出格式
- 对图像：先给出整体描述，再详细说明关键元素
- 对视频：描述时间线、关键场景和动作
- 对文档：提供摘要、关键信息提取和详细内容分析
- 对混合内容：整合所有模态的信息给出统一回答`;

        // 获取当前模式对应的系统提示词
        function getSystemPrompt(mode) {
            let basePrompt = MULTIMODAL_SYSTEM_PROMPT;

            if (mode === 'deep_think') {
                basePrompt += '\n\n## 深度思考模式\n请深入分析问题，展示你的推理过程，然后给出全面、详细的回答。';
            } else if (mode === 'web_search') {
                basePrompt += '\n\n## 联网搜索模式\n请基于最新信息回答问题，如涉及实时信息请说明数据来源。';
            }

            if (currentTool && TOOL_CONFIGS[currentTool]) {
                basePrompt += '\n\n## 当前工具模式\n' + TOOL_CONFIGS[currentTool].prompt;
            }

            return basePrompt;
        }

        // 消息历史上下文
        let messageContext = [];

        // 初始化
        $(document).ready(function() {
            // 加载可用模型
            loadAvailableModels();

            // 绑定发送事件
            $('#chatInput').on('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            $('#sendBtn').on('click', sendMessage);

            // 绑定文件上传事件
            $('#uploadBtn').on('click', function() {
                $('#fileUpload').click();
            });

            $('#fileUpload').on('change', handleFileUpload);

            // 点击外部关闭菜单
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.tool-dropdown').length) {
                    $('#toolMenu').removeClass('show');
                }
                if (!$(e.target).closest('.search-dropdown').length) {
                    $('#searchMenu').removeClass('show');
                }
            });
        });

        // 当前模型信息
        let currentModelInfo = {};
        let currentProviders = [];
        let currentProviderId = '';

        // 图像生成专用模型列表（这些模型只用于生成图像，不能用于文本对话）
        const imageGenerationModels = [
            'qwen-image', 'wanx', 'wanx2.1', 'dall-e', 'dall-e-2', 'dall-e-3',
            'sd', 'stable-diffusion', 'midjourney', 'kandinsky', 'deepfloyd'
        ];

        // 检查是否是图像生成专用模型
        function isImageGenerationModel(modelName) {
            if (!modelName) return false;
            const lowerName = modelName.toLowerCase();
            return imageGenerationModels.some(imgModel => lowerName.includes(imgModel.toLowerCase()));
        }

        // 加载API提供商列表
        function loadProviders() {
            const providerSelect = $('#providerSelect');
            providerSelect.html('<option value="">加载提供商...</option>');

            $.ajax({
                url: 'api/providers_handler.php',
                method: 'GET',
                data: { action: 'get_providers', enabled: '1' },
                dataType: 'json',
                success: function(response) {
                    providerSelect.empty();
                    currentProviders = response.data || [];

                    if (currentProviders.length === 0) {
                        providerSelect.html('<option value="">无可用提供商</option>');
                        return;
                    }

                    // 添加提供商选项
                    currentProviders.forEach(provider => {
                        const isDefault = provider.is_default ? ' ⭐' : '';
                        const option = $(`<option value="${provider.id}">${provider.name}${isDefault}</option>`);
                        if (provider.is_default) {
                            option.attr('selected', 'selected');
                            currentProviderId = provider.id;
                            // 加载该提供商的模型
                            loadProviderModels(provider);
                        }
                        providerSelect.append(option);
                    });

                    console.log(`已加载 ${currentProviders.length} 个API提供商`);
                },
                error: function(xhr, status, error) {
                    providerSelect.html('<option value="">加载失败</option>');
                    console.error('加载提供商失败:', error);
                }
            });
        }

        // 提供商切换时更新模型列表
        function onProviderChange() {
            const providerId = $('#providerSelect').val();
            currentProviderId = providerId;

            if (!providerId) {
                $('#modelSelect').html('<option value="">选择模型...</option>');
                return;
            }

            const provider = currentProviders.find(p => p.id === providerId);
            if (provider) {
                loadProviderModels(provider);
            }
        }
        
        // 根据当前模式处理提供商切换
        function onProviderChangeForCurrentMode() {
            const providerType = $('#providerSelect').val();
            const currentMode = $('.mode-tab.active').data('mode');
            
            if (!providerType) {
                $('#modelSelect').html('<option value="">选择模型...</option>');
                return;
            }
            
            // 视频模式下使用专门的模型加载
            if (currentMode === 'video') {
                loadModelsForVideo(providerType);
            } else {
                // 聊天模式使用原有的逻辑
                onProviderChange();
            }
        }

        // 解析模型名称，生成用途说明
        function getModelRemark(modelName) {
            if (!modelName) return '';
            const lowerName = modelName.toLowerCase();
            
            // 解析模型类型和用途
            if (lowerName.includes('vl') || lowerName.includes('vision')) {
                return '图文理解';
            } else if (lowerName.includes('omni')) {
                return '全模态';
            } else if (lowerName.includes('ocr')) {
                return '文字识别';
            } else if (lowerName.includes('instruct') || lowerName.includes('chat')) {
                return '对话问答';
            } else if (lowerName.includes('turbo')) {
                return '快速响应';
            } else if (lowerName.includes('max') || lowerName.includes('pro')) {
                return '高性能';
            } else if (lowerName.includes('flash')) {
                return '轻量快速';
            } else if (lowerName.includes('audio')) {
                return '音频处理';
            } else if (lowerName.includes('embedding')) {
                return '向量嵌入';
            } else if (lowerName.includes('code')) {
                return '代码生成';
            } else {
                return '通用';
            }
        }

        // 切换模型下拉框显示
        function toggleModelDropdown() {
            const dropdown = document.getElementById('modelSelectDropdown');
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
        }

        // 选择模型
        function selectModel(modelName) {
            const hiddenSelect = document.getElementById('modelSelect');
            const triggerText = document.getElementById('modelSelectText');
            const currentMode = $('.mode-tab.active').data('mode');
            
            hiddenSelect.value = modelName;
            
            // 根据模式显示不同的文本
            if (currentMode === 'video') {
                const remark = getVideoModelRemark(modelName);
                triggerText.textContent = modelName + ' ' + remark || '选择模型...';
            } else {
                triggerText.textContent = modelName || '选择模型...';
            }
            
            // 更新选中样式
            document.querySelectorAll('.custom-select-option').forEach(opt => {
                opt.classList.toggle('selected', opt.dataset.value === modelName);
            });
            
            // 关闭下拉框
            document.getElementById('modelSelectDropdown').style.display = 'none';
        }

        // 渲染模型下拉选项
        function renderModelOptions(models, selectedModel) {
            const dropdown = document.getElementById('modelSelectDropdown');
            const hiddenSelect = document.getElementById('modelSelect');
            
            dropdown.innerHTML = '';
            hiddenSelect.innerHTML = '<option value="">选择模型...</option>';
            
            if (!models || models.length === 0) {
                dropdown.innerHTML = '<div class="custom-select-option"><span class="model-name">暂无可用模型</span></div>';
                return;
            }
            
            models.forEach(modelName => {
                const remark = getModelRemark(modelName);
                
                // 添加到下拉列表
                const option = document.createElement('div');
                option.className = 'custom-select-option';
                option.dataset.model = modelName;
                if (modelName === selectedModel) {
                    option.classList.add('selected');
                }
                option.innerHTML = `
                    <span class="model-name">${modelName}</span>
                    <span class="model-remark">${remark}</span>
                `;
                option.onclick = () => selectModel(modelName);
                dropdown.appendChild(option);
                
                // 同时更新隐藏的select
                const hiddenOption = document.createElement('option');
                hiddenOption.value = modelName;
                hiddenOption.textContent = modelName;
                if (modelName === selectedModel) {
                    hiddenOption.selected = true;
                }
                hiddenSelect.appendChild(hiddenOption);
            });
        }

        // 点击外部关闭下拉框
        document.addEventListener('click', function(e) {
            const container = document.getElementById('modelSelectContainer');
            if (container && !container.contains(e.target)) {
                const dropdown = document.getElementById('modelSelectDropdown');
                if (dropdown) dropdown.style.display = 'none';
            }
        });

        // 加载指定提供商的模型列表
        function loadProviderModels(provider) {
            const triggerText = document.getElementById('modelSelectText');
            if (triggerText) triggerText.textContent = '加载模型...';

            // 过滤图像生成模型
            const filteredModels = provider.models ? provider.models.filter(m => !isImageGenerationModel(m)) : [];

            // 如果有缓存的模型列表，直接使用
            if (filteredModels.length > 0) {
                const defaultModel = provider.default_model || provider.config?.default_model;
                renderModelOptions(filteredModels, defaultModel);
                if (triggerText && defaultModel) triggerText.textContent = defaultModel;
                return;
            }

            // 否则尝试从API获取
            $.ajax({
                url: 'api/providers_handler.php',
                method: 'GET',
                data: { action: 'fetch_models', provider_id: provider.id },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.models && response.models.length > 0) {
                        // 过滤图像生成模型
                        const filteredModels = response.models.filter(m => !isImageGenerationModel(m));
                        const defaultModel = provider.default_model || provider.config?.default_model;
                        renderModelOptions(filteredModels, defaultModel);
                        if (triggerText && defaultModel) triggerText.textContent = defaultModel;
                    } else {
                        // 使用默认模型
                        const defaultModel = provider.default_model || provider.config?.default_model || '';
                        if (defaultModel) {
                            renderModelOptions([defaultModel], defaultModel);
                            if (triggerText) triggerText.textContent = defaultModel;
                        } else {
                            renderModelOptions([], '');
                            if (triggerText) triggerText.textContent = '请输入模型名称';
                        }
                    }
                },
                error: function() {
                    // 使用默认模型
                    const defaultModel = provider.default_model || provider.config?.default_model || '';
                    if (defaultModel) {
                        renderModelOptions([defaultModel], defaultModel);
                        if (triggerText) triggerText.textContent = defaultModel;
                    }
                }
            });
        }

        // 刷新模型和提供商列表
        function refreshModelsAndProviders() {
            loadProviders();
            showToast('正在刷新列表...');
        }

        // 加载可用模型列表 - 兼容旧版本
        function loadAvailableModels() {
            loadProviders();
        }

        // 获取当前选中模型的详细信息
        function getCurrentModelInfo() {
            const selectedModel = $('#modelSelect').val();
            return currentModelInfo[selectedModel] || null;
        }

        // 处理文件上传
        function handleFileUpload(e) {
            const files = Array.from(e.target.files);
            const previewArea = $('#uploadPreviewArea');
            previewArea.show();

            files.forEach(file => {
                const fileId = 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                let fileType = 'file';
                
                // 判断文件类型
                if (file.type.startsWith('image/')) {
                    fileType = 'image';
                } else if (file.type.startsWith('video/')) {
                    fileType = 'video';
                } else if (isDocumentFile(file)) {
                    fileType = 'document';
                }

                const fileData = {
                    id: fileId,
                    file: file,
                    type: fileType,
                    name: file.name
                };
                uploadedFiles.push(fileData);

                // 创建预览
                if (fileType === 'image') {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        fileData.dataUrl = e.target.result;
                        const previewHtml = `
                            <div class="upload-preview-item" data-file-id="${fileId}">
                                <img src="${e.target.result}" alt="${file.name}">
                                <button class="remove-btn" onclick="removeUploadedFile('${fileId}')">×</button>
                            </div>
                        `;
                        previewArea.append(previewHtml);
                    };
                    reader.readAsDataURL(file);
                } else if (fileType === 'video') {
                    const previewHtml = `
                        <div class="upload-preview-item video" data-file-id="${fileId}">
                            <i class="fas fa-video"></i>
                            <span class="file-name">${file.name}</span>
                            <button class="remove-btn" onclick="removeUploadedFile('${fileId}')">×</button>
                        </div>
                    `;
                    previewArea.append(previewHtml);

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        fileData.dataUrl = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else if (fileType === 'document') {
                    // 文档类型预览
                    const iconClass = getDocumentIcon(file.name);
                    const previewHtml = `
                        <div class="upload-preview-item document" data-file-id="${fileId}">
                            <i class="fas ${iconClass}"></i>
                            <span class="file-name">${file.name}</span>
                            <button class="remove-btn" onclick="removeUploadedFile('${fileId}')">×</button>
                        </div>
                    `;
                    previewArea.append(previewHtml);

                    // 读取文档内容
                    readDocumentContent(file, fileData);
                }
            });

            $('#fileUpload').val('');
            showToast(`已上传 ${files.length} 个文件`);
        }

        // 判断是否为文档文件
        function isDocumentFile(file) {
            const docExtensions = ['.txt', '.doc', '.docx', '.wps', '.et', '.xls', '.xlsx', '.ppt', '.pptx'];
            const fileName = file.name.toLowerCase();
            return docExtensions.some(ext => fileName.endsWith(ext));
        }

        // 获取文档图标
        function getDocumentIcon(fileName) {
            const ext = fileName.toLowerCase().split('.').pop();
            const iconMap = {
                'txt': 'fa-file-alt',
                'doc': 'fa-file-word',
                'docx': 'fa-file-word',
                'wps': 'fa-file-word',
                'et': 'fa-file-excel',
                'xls': 'fa-file-excel',
                'xlsx': 'fa-file-excel',
                'ppt': 'fa-file-powerpoint',
                'pptx': 'fa-file-powerpoint'
            };
            return iconMap[ext] || 'fa-file';
        }

        // 读取文档内容
        async function readDocumentContent(file, fileData) {
            const ext = file.name.toLowerCase().split('.').pop();
            
            if (ext === 'txt') {
                // 直接读取文本文件
                const reader = new FileReader();
                reader.onload = function(e) {
                    fileData.content = e.target.result;
                    fileData.dataUrl = e.target.result;
                };
                reader.readAsText(file);
            } else {
                // 其他文档类型上传到服务器解析
                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'parseDocument');

                try {
                    const response = await $.ajax({
                        url: 'api/document_parser.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false
                    });

                    if (response.status === 'success') {
                        fileData.content = response.content;
                        fileData.dataUrl = response.content; // 用于发送到AI
                    } else {
                        fileData.content = `[文档: ${file.name} - 解析失败: ${response.message}]`;
                        showToast(`文档解析失败: ${response.message}`, 'error');
                    }
                } catch (error) {
                    fileData.content = `[文档: ${file.name} - 上传失败]`;
                    showToast(`文档上传失败`, 'error');
                }
            }
        }

        // 移除上传的文件
        function removeUploadedFile(fileId) {
            uploadedFiles = uploadedFiles.filter(f => f.id !== fileId);
            $(`.upload-preview-item[data-file-id="${fileId}"]`).remove();

            if (uploadedFiles.length === 0) {
                $('#uploadPreviewArea').hide();
            }
        }

        // 清空所有上传的文件
        function clearUploadedFiles() {
            uploadedFiles = [];
            $('#uploadPreviewArea').empty().hide();
        }

        // 切换工具菜单
        function toggleToolMenu() {
            $('#toolMenu').toggleClass('show');
            $('#searchMenu').removeClass('show');
        }

        // 选择工具
        function selectTool(tool) {
            currentTool = tool;
            const config = TOOL_CONFIGS[tool];
            
            $('#toolsBtn').html(`<i class="fas ${config.icon}"></i> ${config.name} <i class="fas fa-chevron-down" style="font-size: 10px;"></i>`);
            $('#toolsBtn').addClass('active');
            $('#toolMenu').removeClass('show');
            
            showToast(`已切换到${config.name}模式`);
        }

        // 切换深度思考
        function toggleDeepThink() {
            deepThinkEnabled = !deepThinkEnabled;
            $('#deepThinkBtn').toggleClass('active', deepThinkEnabled);
            
            showToast(deepThinkEnabled ? '已开启深度思考' : '已关闭深度思考');
        }

        // 切换联网搜索菜单
        function toggleSearchMenu() {
            $('#searchMenu').toggleClass('show');
            $('#toolMenu').removeClass('show');
        }

        // 设置搜索模式
        function setSearchMode(mode) {
            searchMode = mode;
            $('.search-mode-item').removeClass('selected');
            $(`.search-mode-item:contains('${mode === 'auto' ? '自动' : '手动'}')`).addClass('selected');
            
            if (mode === 'manual') {
                webSearchEnabled = false;
                $('#webSearchBtn').removeClass('active');
            }
            
            $('#searchMenu').removeClass('show');
            showToast(`已切换到${mode === 'auto' ? '自动' : '手动'}搜索模式`);
        }

        // 切换联网搜索
        function toggleWebSearch() {
            if (searchMode === 'auto') {
                showToast('当前为自动模式，无需手动切换');
                return;
            }
            
            webSearchEnabled = !webSearchEnabled;
            $('#webSearchBtn').toggleClass('active', webSearchEnabled);
            
            showToast(webSearchEnabled ? '已开启联网搜索' : '已关闭联网搜索');
        }

        // 发送消息
        async function sendMessage() {
            if (isProcessing) return;

            const input = $('#chatInput');
            const message = input.val().trim();
            const hasFiles = uploadedFiles.length > 0;
            const selectedProvider = String($('#providerSelect').val() || currentProviderId || '').trim();
            const selectedModel = String($('#modelSelect').val() || '').trim();

            // 如果没有文字且没有文件，不发送
            if (!message && !hasFiles) return;

            if (!selectedProvider) {
                showToast('请先选择AI提供商');
                return;
            }

            isProcessing = true;
            input.val('');

            // 构建用户消息内容
            let userContent = message;

            // 如果有上传的文件，添加文件信息
            if (hasFiles) {
                const fileInfo = uploadedFiles.map(f => {
                    let typeName = '文件';
                    if (f.type === 'image') typeName = '图片';
                    else if (f.type === 'video') typeName = '视频';
                    else if (f.type === 'document') typeName = '文档';
                    return `[${typeName}: ${f.name}]`;
                }).join('\n');

                // 构建包含文档内容的完整消息
                let fullContent = message || '';
                
                // 添加文档内容
                uploadedFiles.forEach(f => {
                    if (f.type === 'document' && f.content) {
                        fullContent += `\n\n【${f.name} 内容】\n${f.content}`;
                    }
                });

                if (message) {
                    userContent = `${fullContent}\n\n${fileInfo}`;
                } else {
                    userContent = `请分析以下内容：\n\n${fullContent}\n\n${fileInfo}`;
                }

                // 在消息中显示文件预览
                let filesHtml = '<div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px;">';
                uploadedFiles.forEach(f => {
                    if (f.type === 'image') {
                        filesHtml += `<img src="${f.dataUrl}" style="max-height: 100px; max-width: 150px; border-radius: 8px; border: 1px solid #e2e8f0;">`;
                    } else if (f.type === 'document') {
                        const iconClass = getDocumentIcon(f.name);
                        filesHtml += `<div style="padding: 8px 12px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; gap: 6px;"><i class="fas ${iconClass}"></i>${f.name}</div>`;
                    } else {
                        filesHtml += `<div style="padding: 8px 12px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; gap: 6px;"><i class="fas fa-video"></i>${f.name}</div>`;
                    }
                });
                filesHtml += '</div>';

                if (message) {
                    userContent = fullContent + '\n\n[已上传 ' + uploadedFiles.length + ' 个文件]';
                }
            }

            // 添加用户消息到上下文（包含文件信息）
            messageContext.push({ role: 'user', content: userContent });

            // 添加用户消息到界面
            let userMessageHtml = message || '[分析文件内容]';
            if (deepThinkEnabled) {
                userMessageHtml += '<span class="search-badge"><i class="fas fa-brain"></i> 深度思考</span>';
            }
            if (webSearchEnabled || (searchMode === 'auto' && shouldUseSearch(message))) {
                userMessageHtml += '<span class="search-badge"><i class="fas fa-globe"></i> 联网</span>';
            }
            if (hasFiles) {
                userMessageHtml += `<span class="search-badge"><i class="fas fa-paperclip"></i> ${uploadedFiles.length}个文件</span>`;
                // 添加文件预览
                let filesHtml = '<div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px;">';
                uploadedFiles.forEach(f => {
                    if (f.type === 'image') {
                        filesHtml += `<img src="${f.dataUrl}" style="max-height: 100px; max-width: 150px; border-radius: 8px; border: 1px solid #e2e8f0;">`;
                    } else if (f.type === 'document') {
                        const iconClass = getDocumentIcon(f.name);
                        filesHtml += `<div style="padding: 8px 12px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; gap: 6px;"><i class="fas ${iconClass}"></i>${f.name}</div>`;
                    } else {
                        filesHtml += `<div style="padding: 8px 12px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; gap: 6px;"><i class="fas fa-video"></i>${f.name}</div>`;
                    }
                });
                filesHtml += '</div>';
                userMessageHtml += filesHtml;
            }
            addMessage(userMessageHtml, 'user');

            // 清空上传的文件预览
            clearUploadedFiles();

            // 显示思考/搜索指示器
            if (deepThinkEnabled) {
                $('#thinkingIndicator').addClass('show');
            }
            if (webSearchEnabled || (searchMode === 'auto' && shouldUseSearch(message))) {
                $('#searchingIndicator').addClass('show');
            }

            // 确定模式
            let mode = 'normal';
            if (deepThinkEnabled) mode = 'deep_think';
            else if (webSearchEnabled) mode = 'web_search';
            // 如果有文件上传，使用视觉分析模式
            if (hasFiles) mode = 'vision_analysis';

            console.log('Sending chat request:', {
                provider_id: selectedProvider,
                model: selectedModel || '(provider default)',
                mode: mode
            });

            // 调用API（支持多提供商）
            $.ajax({
                url: 'api/api_handler.php',
                method: 'POST',
                data: {
                    request: 'chat',
                    input: message,
                    model: selectedModel,
                    mode: mode,
                    provider_id: selectedProvider,
                    context: JSON.stringify(messageContext.slice(-10)) // 保留最近10条消息
                },
                dataType: 'json',
                success: function(response) {
                    $('#thinkingIndicator').removeClass('show');
                    $('#searchingIndicator').removeClass('show');

                    console.log('API Response:', response);

                    if (response.status === 'success') {
                        // 添加AI回复到上下文
                        messageContext.push({ role: 'assistant', content: response.message });

                        // 检查内容类型，支持多媒体
                        const contentType = response.content_type || 'text';
                        const media = response.media;

                        if (contentType === 'text' || !media) {
                            // 纯文本消息
                            addMessage(response.message, 'system');
                        } else {
                            // 多媒体消息
                            let contentObj = {};

                            switch (contentType) {
                                case 'image':
                                    contentObj = { url: media.images[0] };
                                    break;
                                case 'images':
                                    contentObj = { urls: media.images };
                                    break;
                                case 'file':
                                    contentObj = media.files[0];
                                    break;
                                case 'files':
                                    contentObj = { files: media.files };
                                    break;
                                case 'mixed':
                                    contentObj = {
                                        text: response.message,
                                        images: media.images || [],
                                        files: media.files || []
                                    };
                                    break;
                            }

                            addMessage(contentObj, 'system', contentType);
                        }
                    } else {
                        let errorMsg = response.message || '请求失败，请检查Ollama服务是否运行';
                        let debugInfo = response.debug ? `<br><small style="color: #94a3b8;">调试: ${JSON.stringify(response.debug).substring(0, 200)}</small>` : '';
                        addMessage(`<span style="color: #dc2626;"><i class="fas fa-exclamation-circle"></i> ${errorMsg}</span>${debugInfo}`, 'system');
                    }

                    isProcessing = false;
                },
                error: function(xhr, status, error) {
                    $('#thinkingIndicator').removeClass('show');
                    $('#searchingIndicator').removeClass('show');

                    let errorMsg = '无法连接到Ollama服务';
                    if (xhr.status === 0) {
                        errorMsg = '无法连接到服务器，请检查：<br>1. Ollama是否已安装并运行<br>2. 默认地址 http://localhost:11434 是否正确<br>3. 如需修改地址，请编辑 config/config.php';
                    }
                    addMessage(`<span style="color: #dc2626;"><i class="fas fa-exclamation-circle"></i> ${errorMsg}</span>`, 'system');

                    isProcessing = false;
                }
            });
        }

        // 判断是否使用搜索（简单规则）
        function shouldUseSearch(message) {
            const searchKeywords = ['最新', '新闻', '今天', '天气', '股价', '价格', '多少钱', '什么时候', '谁', '哪里'];
            return searchKeywords.some(kw => message.includes(kw));
        }

        // 添加消息 - 支持文本、图片、文件等多种类型
        function addMessage(content, type, contentType = 'text') {
            let contentHtml = '';

            if (typeof content === 'string') {
                // 纯文本消息
                contentHtml = `<div class="message-content">${content}</div>`;
            } else if (typeof content === 'object') {
                // 多媒体消息对象
                switch (contentType) {
                    case 'image':
                        // 单张图片
                        contentHtml = `
                            <div class="message-media">
                                <div class="content-type-badge"><i class="fas fa-image"></i> AI生成图片</div>
                                <img src="${content.url}" alt="AI生成图片" onclick="previewImage('${content.url}')">
                                <div style="margin-top: 8px; text-align: right;">
                                    <a href="${content.url}" download class="file-download" style="display: inline-flex;">
                                        <i class="fas fa-download"></i> 下载
                                    </a>
                                </div>
                            </div>
                        `;
                        break;

                    case 'images':
                        // 多张图片
                        const imagesHtml = content.urls.map(url =>
                            `<img src="${url}" alt="AI生成图片" onclick="previewImage('${url}')">`
                        ).join('');
                        contentHtml = `
                            <div class="message-media">
                                <div class="content-type-badge"><i class="fas fa-images"></i> AI生成图片组</div>
                                <div class="image-grid">${imagesHtml}</div>
                            </div>
                        `;
                        break;

                    case 'file':
                        // 单个文件
                        contentHtml = `
                            <div class="message-media">
                                <div class="content-type-badge"><i class="fas fa-file"></i> AI生成文件</div>
                                <div class="file-item">
                                    <div class="file-icon"><i class="fas ${content.icon || 'fa-file'}"></i></div>
                                    <div class="file-info">
                                        <div class="file-name">${content.name}</div>
                                        <div class="file-size">${content.size || '未知大小'}</div>
                                    </div>
                                    <a href="${content.url}" download class="file-download">
                                        <i class="fas fa-download"></i> 下载
                                    </a>
                                </div>
                            </div>
                        `;
                        break;

                    case 'files':
                        // 多个文件
                        const filesHtml = content.files.map(file => `
                            <div class="file-item">
                                <div class="file-icon"><i class="fas ${file.icon || 'fa-file'}"></i></div>
                                <div class="file-info">
                                    <div class="file-name">${file.name}</div>
                                    <div class="file-size">${file.size || '未知大小'}</div>
                                </div>
                                <a href="${file.url}" download class="file-download">
                                    <i class="fas fa-download"></i> 下载
                                </a>
                            </div>
                        `).join('');
                        contentHtml = `
                            <div class="message-media">
                                <div class="content-type-badge"><i class="fas fa-folder"></i> AI生成文件组</div>
                                ${filesHtml}
                            </div>
                        `;
                        break;

                    case 'mixed':
                        // 混合内容（文本+媒体）
                        let mixedHtml = '<div class="message-media">';
                        if (content.text) {
                            mixedHtml += `<div style="margin-bottom: 12px; line-height: 1.6;">${content.text}</div>`;
                        }
                        if (content.images && content.images.length > 0) {
                            mixedHtml += '<div class="image-grid" style="margin-top: 8px;">';
                            mixedHtml += content.images.map(url =>
                                `<img src="${url}" alt="AI生成图片" onclick="previewImage('${url}')">`
                            ).join('');
                            mixedHtml += '</div>';
                        }
                        if (content.files && content.files.length > 0) {
                            mixedHtml += content.files.map(file => `
                                <div class="file-item" style="margin-top: 8px;">
                                    <div class="file-icon"><i class="fas ${file.icon || 'fa-file'}"></i></div>
                                    <div class="file-info">
                                        <div class="file-name">${file.name}</div>
                                        <div class="file-size">${file.size || '未知大小'}</div>
                                    </div>
                                    <a href="${file.url}" download class="file-download">
                                        <i class="fas fa-download"></i> 下载
                                    </a>
                                </div>
                            `).join('');
                        }
                        mixedHtml += '</div>';
                        contentHtml = mixedHtml;
                        break;

                    default:
                        contentHtml = `<div class="message-content">${JSON.stringify(content)}</div>`;
                }
            }

            const html = `
                <div class="message ${type}">
                    <div class="message-avatar">
                        <i class="fas fa-${type === 'user' ? 'user' : 'robot'}"></i>
                    </div>
                    ${contentHtml}
                </div>
            `;
            $('#chatMessages').append(html);
            $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
        }

        // 图片预览
        function previewImage(url) {
            const modal = $(`
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; cursor: zoom-out;" onclick="$(this).remove()">
                    <img src="${url}" style="max-width: 90%; max-height: 90%; border-radius: 8px;">
                </div>
            `);
            $('body').append(modal);
        }

        // Toast提示
        function showToast(message) {
            const toast = $(`
                <div style="
                    position: fixed;
                    top: 80px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #4c51bf;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 8px;
                    font-size: 13px;
                    z-index: 1000;
                    animation: fadeIn 0.3s;
                ">${message}</div>
            `);
            $('body').append(toast);
            setTimeout(() => toast.fadeOut(300, function() { $(this).remove(); }), 2000);
        }
        // 切换聊天/视频生成模式
        function switchMode(mode) {
            $('.mode-tab').removeClass('active');
            $(`.mode-tab[data-mode="${mode}"]`).addClass('active');
            
            if (mode === 'chat') {
                $('#chatMessages').show();
                $('#chatInputArea').show();
                $('#videoGenContainer').removeClass('active');
                $('.chat-toolbar').show();
                // 恢复所有提供商
                loadProviders();
            } else if (mode === 'video') {
                $('#chatMessages').hide();
                $('#chatInputArea').hide();
                $('#videoGenContainer').addClass('active');
                $('.chat-toolbar').hide();
                // 只加载支持图像/视频生成的提供商
                loadImageProviders();
            }
        }
        
        // 加载支持图像/视频生成的提供商
        function loadImageProviders() {
            const providerSelect = $('#providerSelect');
            providerSelect.html('<option value="">加载提供商...</option>');
            $('#modelSelect').html('<option value="">选择模型...</option>');

            $.ajax({
                url: 'api/model_handler.php',
                method: 'GET',
                data: { action: 'getImageProviders' },
                dataType: 'json',
                success: function(response) {
                    providerSelect.empty();
                    
                    if (!response.success || !response.providers || response.providers.length === 0) {
                        providerSelect.html('<option value="">无可用图像提供商</option>');
                        $('#modelSelect').html('<option value="">无可用模型</option>');
                        return;
                    }

                    // 添加提供商选项
                    response.providers.forEach((provider, index) => {
                        const option = $(`<option value="${provider.type}">${provider.name}</option>`);
                        if (index === 0) {
                            option.attr('selected', 'selected');
                        }
                        providerSelect.append(option);
                    });

                    // 加载第一个提供商的模型
                    if (response.providers.length > 0) {
                        loadModelsForVideo(response.providers[0].type);
                    }

                    console.log(`已加载 ${response.providers.length} 个图像提供商`);
                },
                error: function(xhr, status, error) {
                    providerSelect.html('<option value="">加载失败</option>');
                    console.error('加载图像提供商失败:', error);
                }
            });
        }
        
        // 为视频生成加载模型 - 只加载支持视频生成的模型
        function loadModelsForVideo(provider) {
            const modelSelect = $('#modelSelect');
            const dropdown = $('#modelSelectDropdown');
            const triggerText = $('#modelSelectText');
            
            modelSelect.html('<option value="">加载模型...</option>');
            dropdown.html('<div style="padding: 12px; color: #64748b;">加载模型...</div>');
            triggerText.text('加载模型...');

            $.ajax({
                url: 'api/model_handler.php',
                method: 'GET',
                data: { action: 'getModels', provider: provider },
                dataType: 'json',
                success: function(response) {
                    modelSelect.empty();
                    dropdown.empty();
                    
                    if (!response.success || !response.models || response.models.length === 0) {
                        modelSelect.html('<option value="">无可用模型</option>');
                        dropdown.html('<div style="padding: 12px; color: #64748b;">无可用模型</div>');
                        triggerText.text('无可用模型');
                        return;
                    }

                    console.log('原始模型列表:', response.models);
                    
                    // 过滤只支持视频生成的模型
                    const videoModels = [];
                    for (let i = 0; i < response.models.length; i++) {
                        const model = response.models[i];
                        // 尝试获取模型名称
                        let modelName = '';
                        if (model.id) modelName = model.id;
                        else if (model.name) modelName = model.name;
                        else if (typeof model === 'string') modelName = model;
                        
                        console.log('处理模型[' + i + ']:', model, '-> 名称:', modelName);
                        
                        if (isVideoGenerationModel(modelName)) {
                            videoModels.push(model);
                        }
                    }
                    
                    console.log('过滤后的视频模型数量:', videoModels.length);
                    
                    // 如果没有匹配到专门的视频模型，显示所有可用模型
                    if (videoModels.length === 0) {
                        console.log('未找到专门的视频模型，显示所有可用模型');
                        videoModels = response.models;
                    }

                    // 添加模型选项到隐藏的select
                    videoModels.forEach((model, index) => {
                        const modelName = model.name || model.id;
                        const remark = getVideoModelRemark(model.id || model.name);
                        const option = $(`<option value="${model.id}">${modelName} ${remark}</option>`);
                        if (index === 0) {
                            option.attr('selected', 'selected');
                        }
                        modelSelect.append(option);
                    });
                    
                    // 添加模型选项到自定义下拉框
                    videoModels.forEach((model, index) => {
                        const modelName = model.name || model.id;
                        const modelId = model.id || model.name;
                        const remark = getVideoModelRemark(modelId);
                        const isSelected = index === 0;
                        
                        const optionDiv = $(`
                            <div class="custom-select-option ${isSelected ? 'selected' : ''}" 
                                 data-value="${modelId}" 
                                 onclick="selectModel('${modelId}')">
                                <span class="model-name">${modelName}</span>
                                <span class="model-remark">${remark}</span>
                            </div>
                        `);
                        dropdown.append(optionDiv);
                    });
                    
                    // 更新触发器文本为第一个模型
                    const firstModel = videoModels[0];
                    const firstModelName = firstModel.name || firstModel.id;
                    const firstRemark = getVideoModelRemark(firstModel.id || firstModel.name);
                    triggerText.text(firstModelName + ' ' + firstRemark);
                },
                error: function(xhr, status, error) {
                    console.error('加载模型失败:', error);
                    modelSelect.html('<option value="">加载失败</option>');
                    dropdown.html('<div style="padding: 12px; color: #64748b;">加载失败</div>');
                    triggerText.text('加载失败');
                }
            });
        }
        
        // 判断是否为视频生成模型
        function isVideoGenerationModel(modelName) {
            if (!modelName || typeof modelName !== 'string') {
                console.log('无效的模型名称:', modelName, '类型:', typeof modelName);
                return false;
            }
            
            // 转换为字符串并小写化
            const strName = String(modelName).toLowerCase();
            console.log('检查模型:', strName);
            
            // 视频生成模型的关键词列表 - 扩展更多可能的多模态模型关键词
            const keywords = [
                'video', 'vl', 'vision', 'omni', 'gpt-4o', 'claude-3', 'gemini', 
                'glm-4v', 'llava', 'bakllava', 'moondream', 'qwen', 'wanx',
                'gpt-4', 'gpt4', 'multimodal', 'image', 'img', 'text-to-video', 't2v'
            ];
            
            for (let kw of keywords) {
                if (strName.includes(kw)) {
                    console.log('  ✓ 匹配关键词:', kw);
                    return true;
                }
            }
            
            console.log('  ✗ 未匹配');
            return false;
        }
        
        // 获取视频模型备注
        function getVideoModelRemark(modelName) {
            if (!modelName) return '';
            const lowerName = modelName.toLowerCase();
            if (lowerName.includes('video') || lowerName.includes('t2v')) return '【视频生成】';
            if (lowerName.includes('vl') || lowerName.includes('vision')) return '【图文理解】';
            if (lowerName.includes('omni')) return '【全模态】';
            if (lowerName.includes('gpt-4o')) return '【多模态】';
            if (lowerName.includes('claude-3')) return '【图文理解】';
            if (lowerName.includes('gemini')) return '【全模态】';
            if (lowerName.includes('wanx')) return '【万相视频】';
            if (lowerName.includes('qwen')) return '【通义千问】';
            if (lowerName.includes('gpt-4') || lowerName.includes('gpt4')) return '【通用模型】';
            return '【可用模型】';
        }
        
        // 生成视频
        function generateVideo() {
            const prompt = $('#videoPrompt').val().trim();
            if (!prompt) {
                showToast('请输入视频描述');
                return;
            }
            
            const ratio = $('#videoRatio').val();
            const duration = $('#videoDuration').val();
            const quality = $('#videoQuality').val();
            
            // 显示生成中状态
            $('#generatingStatus').show();
            $('#generateVideoBtn').prop('disabled', true);
            
            // 调用视频生成API
            $.ajax({
                url: 'api/video_handler.php',
                method: 'POST',
                data: {
                    action: 'generate',
                    prompt: prompt,
                    ratio: ratio,
                    duration: duration,
                    quality: quality,
                    provider: $('#providerSelect').val(),
                    model: $('#modelSelect').val()
                },
                success: function(response) {
                    $('#generatingStatus').hide();
                    $('#generateVideoBtn').prop('disabled', false);
                    
                    if (response.success) {
                        // 添加生成的视频到列表
                        addVideoResult(response.video);
                        $('#videoPrompt').val('');
                        showToast('视频生成成功！');
                    } else {
                        showToast(response.error || '生成失败，请重试');
                    }
                },
                error: function(xhr, status, error) {
                    $('#generatingStatus').hide();
                    $('#generateVideoBtn').prop('disabled', false);
                    console.error('Video generate error:', xhr, status, error);
                    let errorMsg = '请求失败，请检查网络连接';
                    if (xhr.status === 500) {
                        errorMsg = '服务器内部错误，请稍后重试';
                    } else if (xhr.status === 404) {
                        errorMsg = 'API接口不存在';
                    }
                    showToast(errorMsg);
                }
            });
        }
        
        // 添加视频结果到列表
        function addVideoResult(video) {
            const videoCard = `
                <div class="video-result-card" data-video-id="${video.id}">
                    <div class="video-preview">
                        <video controls poster="${video.thumbnail || ''}">
                            <source src="${video.url}" type="video/mp4">
                        </video>
                    </div>
                    <div class="video-info">
                        <div class="video-prompt-text">${video.prompt}</div>
                        <div class="video-meta">
                            <span>${video.created_at || '刚刚'}</span>
                            <div class="video-actions">
                                <button class="video-action-btn" onclick="downloadVideo('${video.url}')">
                                    <i class="fas fa-download"></i> 下载
                                </button>
                                <button class="video-action-btn" onclick="shareVideo('${video.id}')">
                                    <i class="fas fa-share"></i> 分享
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#videoResults').prepend(videoCard);
        }
        
        // 下载视频
        function downloadVideo(url) {
            const a = document.createElement('a');
            a.href = url;
            a.download = 'generated-video.mp4';
            a.click();
        }
        
        // 分享视频
        function shareVideo(videoId) {
            // 复制分享链接到剪贴板
            const shareUrl = `${window.location.origin}/api/video_handler.php?action=view&id=${videoId}`;
            navigator.clipboard.writeText(shareUrl).then(() => {
                showToast('分享链接已复制到剪贴板');
            });
        }
    </script>
</body>
</html>
