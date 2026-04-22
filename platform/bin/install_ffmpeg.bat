@echo off
chcp 65001 >nul
title FFmpeg 安装程序
color 0A

echo ========================================
echo    FFmpeg 自动安装程序
echo ========================================
echo.
echo 正在检查 PHP 环境...

php -v >nul 2>&1
if errorlevel 1 (
    echo.
    echo [错误] 未检测到 PHP，请确保 PHP 已安装并添加到系统 PATH
echo.
    pause
    exit /b 1
)

echo [OK] PHP 已安装
echo.
echo 开始下载和安装 FFmpeg...
echo 这可能需要几分钟时间，请耐心等待
echo.

php "%~dp0install_ffmpeg.php"

echo.
pause
