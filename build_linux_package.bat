@echo off
chcp 65001

:: 巨神兵API辅助平台API辅助平台 - Linux安装包打包脚本 (Windows版本)
:: 需要安装 Git for Windows 或 WSL

echo ========================================
echo 巨神兵API辅助平台API辅助平台 - Linux安装包构建
echo ========================================
echo.

:: 检查是否存在 Git Bash
if exist "C:\Program Files\Git\bin\bash.exe" (
    echo 使用 Git Bash 运行打包脚本...
    "C:\Program Files\Git\bin\bash.exe" build_linux_package.sh
    goto :end
)

if exist "C:\Program Files (x86)\Git\bin\bash.exe" (
    echo 使用 Git Bash 运行打包脚本...
    "C:\Program Files (x86)\Git\bin\bash.exe" build_linux_package.sh
    goto :end
)

:: 检查是否存在 WSL
wsl --version >nul 2>&1
if %errorlevel% equ 0 (
    echo 使用 WSL 运行打包脚本...
    wsl bash build_linux_package.sh
    goto :end
)

echo.
echo 错误: 未找到 Git Bash 或 WSL。
echo 请安装以下之一：
echo   1. Git for Windows: https://git-scm.com/download/win
echo   2. WSL (Windows Subsystem for Linux)
echo.
pause
exit /b 1

:end
echo.
echo 按任意键退出...
pause >nul
