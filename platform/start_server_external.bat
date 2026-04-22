@echo off
chcp 65001 >nul
echo ==========================================
echo   巨神兵AIAPI辅助平台 - 外网访问模式
echo ==========================================
echo.

cd /d "%~dp0"

REM 检查PHP是否可用
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 未找到PHP，请确保已安装PHP并添加到系统路径
    pause
    exit /b 1
)

echo [信息] PHP已找到
echo.

REM 获取本机IP地址
for /f "tokens=2 delims=[]" %%a in ('ping -4 -n 1 %COMPUTERNAME% ^| findstr "["') do set LOCAL_IP=%%a
echo [信息] 本机IP地址: %LOCAL_IP%

REM 获取外网IP
echo [信息] 正在检测外网IP...
powershell -Command "try { $ip = (Invoke-WebRequest -Uri 'https://api.ipify.org' -TimeoutSec 5).Content; Write-Host '[信息] 外网IP地址: '$ip } catch { Write-Host '[警告] 无法获取外网IP' }"
echo.

echo ==========================================
echo   访问地址:
echo   本机:     http://localhost:8000
echo   局域网:   http://%LOCAL_IP%:8000
if not "%PUBLIC_IP%"=="" echo   外网:     http://%PUBLIC_IP%:8000
echo ==========================================
echo.

REM 检查防火墙
echo [信息] 检查Windows防火墙...
netsh advfirewall firewall show rule name="巨神兵AIAI平台" >nul 2>&1
if %errorlevel% neq 0 (
    echo [警告] 防火墙规则不存在，尝试添加...
    netsh advfirewall firewall add rule name="巨神兵AIAI平台" dir=in action=allow protocol=tcp localport=8000 >nul 2>&1
    if %errorlevel% equ 0 (
        echo [成功] 已添加防火墙规则，允许8000端口访问
    ) else (
        echo [警告] 无法自动添加防火墙规则，请手动允许8000端口
    )
) else (
    echo [信息] 防火墙规则已存在
)
echo.

echo [信息] 正在启动服务器...
echo [信息] 按 Ctrl+C 停止服务器
echo.

REM 使用0.0.0.0绑定所有接口
php -S 0.0.0.0:8000 -t . index.php

pause
