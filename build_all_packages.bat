@echo off
chcp 65001

:: 巨神兵API辅助平台API辅助平台 - 多架构安装包打包脚本 (Windows版本)
:: 支持构建 Linux x86_64, Linux ARM64, Windows x86 安装包

echo ========================================
echo 巨神兵API辅助平台API辅助平台 - 多架构安装包构建
echo ========================================
echo.

:: 检查是否存在 Git Bash
if exist "C:\Program Files\Git\bin\bash.exe" (
    echo 使用 Git Bash 运行打包脚本...
    "C:\Program Files\Git\bin\bash.exe" build_all_packages.sh %*
    goto :end
)

if exist "C:\Program Files (x86)\Git\bin\bash.exe" (
    echo 使用 Git Bash 运行打包脚本...
    "C:\Program Files (x86)\Git\bin\bash.exe" build_all_packages.sh %*
    goto :end
)

:: 检查是否存在 WSL
wsl --version >nul 2>&1
if %errorlevel% equ 0 (
    echo 使用 WSL 运行打包脚本...
    wsl bash build_all_packages.sh %*
    goto :end
)

:: 如果没有 Git Bash 或 WSL，使用 PowerShell 构建 Windows 包
echo 未找到 Git Bash 或 WSL，使用 PowerShell 构建 Windows 安装包...
echo.

:: 设置版本号
set VERSION=1.0.0
set BUILD_DIR=.\build
set SOURCE_DIR=.\gpustack_platform
set COMFYUI_DIR=.\comfyui-master

:: 解析参数
:parse_args
if "%~1"=="" goto :done_parsing
if "%~1"=="-v" (
    set VERSION=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="--version" (
    set VERSION=%~2
    shift
    shift
    goto :parse_args
)
if "%~1"=="--windows-x86-only" (
    set WINDOWS_ONLY=1
    shift
    goto :parse_args
)
if "%~1"=="-h" goto :show_help
if "%~1"=="--help" goto :show_help
shift
goto :parse_args
:done_parsing

echo 版本: %VERSION%
echo.

:: 清理并创建构建目录
echo 清理构建目录...
if exist "%BUILD_DIR%" rmdir /s /q "%BUILD_DIR%"
mkdir "%BUILD_DIR%"

:: 构建 Windows x86 安装包
echo.
echo ========================================
echo 构建 Windows x86 安装包...
echo ========================================

set PKG_NAME=lingyue-ai-windows-x86-%VERSION%
set PKG_DIR=%BUILD_DIR%\%PKG_NAME%

mkdir "%PKG_DIR%"

echo 复制应用文件...
xcopy /s /e /i "%SOURCE_DIR%\*" "%PKG_DIR%\" >nul 2>&1

echo 复制 ComfyUI...
mkdir "%PKG_DIR%\comfyui"
xcopy /s /e /i "%COMFYUI_DIR%\*" "%PKG_DIR%\comfyui\" >nul 2>&1

echo 清理Linux特定文件...
if exist "%PKG_DIR%\install.sh" del "%PKG_DIR%\install.sh"
if exist "%PKG_DIR%\uninstall.sh" del "%PKG_DIR%\uninstall.sh"
if exist "%PKG_DIR%\start_server_external.sh" del "%PKG_DIR%\start_server_external.sh"
if exist "%PKG_DIR%\.gitignore" del "%PKG_DIR%\.gitignore"
if exist "%PKG_DIR%\INSTALL_README.md" del "%PKG_DIR%\INSTALL_README.md"
if exist "%PKG_DIR%\UPDATES_SUMMARY.md" del "%PKG_DIR%\UPDATES_SUMMARY.md"
if exist "%PKG_DIR%\VISUAL_NODE_EDITOR_GUIDE.md" del "%PKG_DIR%\VISUAL_NODE_EDITOR_GUIDE.md"
if exist "%PKG_DIR%\test_workflow_api.html" del "%PKG_DIR%\test_workflow_api.html"

:: 创建架构特定说明
echo 架构: Windows x86 (32/64位) > "%PKG_DIR%\ARCHITECTURE.txt"
echo 支持系统: Windows 10, Windows 11, Windows Server 2016+ >> "%PKG_DIR%\ARCHITECTURE.txt"
echo 依赖: PHP 7.4+, Nginx/Apache, SQLite3, Python 3.10+ >> "%PKG_DIR%\ARCHITECTURE.txt"

:: 创建Windows安装说明
echo 巨神兵API辅助平台API辅助平台 - Windows x86 安装说明 > "%PKG_DIR%\INSTALL.txt"
echo ========================================== >> "%PKG_DIR%\INSTALL.txt"
echo. >> "%PKG_DIR%\INSTALL.txt"
echo 系统要求: >> "%PKG_DIR%\INSTALL.txt"
echo - CPU: x86/x64 架构 >> "%PKG_DIR%\INSTALL.txt"
echo - 内存: 4GB+ (推荐 8GB+) >> "%PKG_DIR%\INSTALL.txt"
echo - 磁盘: 10GB+ 可用空间 >> "%PKG_DIR%\INSTALL.txt"
echo - 系统: Windows 10, Windows 11, Windows Server 2016+ >> "%PKG_DIR%\INSTALL.txt"
echo - 软件: PHP 7.4+, Python 3.10+, Nginx/Apache >> "%PKG_DIR%\INSTALL.txt"
echo. >> "%PKG_DIR%\INSTALL.txt"
echo 快速安装: >> "%PKG_DIR%\INSTALL.txt"
echo ---------- >> "%PKG_DIR%\INSTALL.txt"
echo 1. 解压安装包到目标目录 >> "%PKG_DIR%\INSTALL.txt"
echo    例如: C:\lingyue-ai\ >> "%PKG_DIR%\INSTALL.txt"
echo. >> "%PKG_DIR%\INSTALL.txt"
echo 2. 安装依赖软件: >> "%PKG_DIR%\INSTALL.txt"
echo    - 安装 PHP: https://windows.php.net/download/ >> "%PKG_DIR%\INSTALL.txt"
echo    - 安装 Python: https://www.python.org/downloads/windows/ >> "%PKG_DIR%\INSTALL.txt"
echo    - 安装 Nginx: http://nginx.org/en/download.html >> "%PKG_DIR%\INSTALL.txt"
echo. >> "%PKG_DIR%\INSTALL.txt"
echo 3. 运行安装脚本 >> "%PKG_DIR%\INSTALL.txt"
echo    双击运行 install.bat >> "%PKG_DIR%\INSTALL.txt"
echo    或命令行运行: install.bat >> "%PKG_DIR%\INSTALL.txt"
echo. >> "%PKG_DIR%\INSTALL.txt"
echo 4. 访问系统 >> "%PKG_DIR%\INSTALL.txt"
echo    打开浏览器访问 http://localhost:8000/install.php >> "%PKG_DIR%\INSTALL.txt"
echo    按照向导完成初始化 >> "%PKG_DIR%\INSTALL.txt"
echo. >> "%PKG_DIR%\INSTALL.txt"
echo 手动启动: >> "%PKG_DIR%\INSTALL.txt"
echo --------- >> "%PKG_DIR%\INSTALL.txt"
echo    双击 start_server.bat 启动服务 >> "%PKG_DIR%\INSTALL.txt"
echo    或双击 start_server_external.bat 启动外部访问模式 >> "%PKG_DIR%\INSTALL.txt"
echo. >> "%PKG_DIR%\INSTALL.txt"
echo 卸载: >> "%PKG_DIR%\INSTALL.txt"
echo ----- >> "%PKG_DIR%\INSTALL.txt"
echo    直接删除安装目录即可 >> "%PKG_DIR%\INSTALL.txt"
echo. >> "%PKG_DIR%\INSTALL.txt"
echo 更多帮助请参考 README.md >> "%PKG_DIR%\INSTALL.txt"

:: 创建zip包
echo 创建 zip 安装包...
cd "%BUILD_DIR%"
powershell -Command "Compress-Archive -Path '%PKG_NAME%' -DestinationPath '%PKG_NAME%.zip' -Force"
cd ..

echo Windows x86 安装包构建完成: %PKG_NAME%.zip
echo.

:: 显示结果
echo ========================================
echo 构建完成！
echo ========================================
echo.
echo 输出目录: %BUILD_DIR%
echo.
echo 生成的文件:
dir /b "%BUILD_DIR%"
echo.
echo ========================================

if defined WINDOWS_ONLY goto :end

echo.
echo 注意: 要构建 Linux 安装包，请安装 Git for Windows 或 WSL:
echo   1. Git for Windows: https://git-scm.com/download/win
echo   2. WSL (Windows Subsystem for Linux)
echo.

:end
echo.
echo 按任意键退出...
pause >nul
exit /b 0

:show_help
echo 用法: %0 [选项]
echo.
echo 选项:
echo   --windows-x86-only     仅构建 Windows x86 安装包
echo   -v, --version VERSION  指定版本号 (默认: 1.0.0)
echo   -h, --help             显示帮助信息
echo.
echo 注意: 构建 Linux 安装包需要 Git Bash 或 WSL
echo   安装 Git for Windows: https://git-scm.com/download/win
echo.
pause
exit /b 0
