@echo off
chcp 65001 >nul
echo ==========================================
echo   巨神兵AIAPI辅助平台
echo ==========================================
echo.
echo 正在启动服务器...
cd /d "%~dp0"

REM 检查PHP是否可用
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误: 未找到PHP，请确保已安装PHP并添加到系统路径
    echo 可以从 https://windows.php.net/download/ 下载PHP
    pause
    exit /b 1
)

echo PHP已找到，启动服务器...
echo.
echo ==========================================
echo   本机访问: http://localhost:8000
echo   局域网访问: http://%COMPUTERNAME%:8000
echo   安装向导: http://localhost:8000/install.php
echo ==========================================
echo.
echo 按 Ctrl+C 停止服务器
echo.
php -d upload_max_filesize=500M -d post_max_size=500M -S 0.0.0.0:8000
pause