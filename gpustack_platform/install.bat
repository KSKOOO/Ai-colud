@echo off
chcp 65001 >nul
title 巨神兵API辅助平台API辅助平台 - Windows安装程序
color 0A

set INSTALL_DIR=%~dp0
cd /d "%INSTALL_DIR%"

echo ================================================
echo   巨神兵API辅助平台API辅助平台 - Windows安装程序
echo ================================================
echo.

:: 检查管理员权限
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [警告] 建议以管理员身份运行此安装程序
    echo [警告] 某些功能可能需要管理员权限
    echo.
    pause
    cls
)

echo [1/5] 检查系统环境...

:: 检查PHP
where php >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=*" %%a in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%a
    echo [✓] PHP已安装: %PHP_VERSION%
    goto PHP_OK
)

:: 检查是否自带PHP
echo [*] 系统未安装PHP，检查本地环境...
if exist "%INSTALL_DIR%\php\php.exe" (
    set PHP_PATH=%INSTALL_DIR%\php
    set PATH=%PHP_PATH%;%PATH%
    for /f "tokens=*" %%a in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%a
    echo [✓] 使用本地PHP: %PHP_VERSION%
    goto PHP_OK
)

:: 需要下载PHP
echo [!] 未检测到PHP，正在准备下载...
echo [*] PHP是运行此程序必需的组件
echo.
choice /C YN /M "是否自动下载并配置PHP"
if %errorlevel% neq 1 (
    echo [x] 安装已取消。请手动安装PHP 7.4或更高版本
    pause
    exit /b 1
)

echo [*] 正在下载PHP...
echo [*] 这可能需要几分钟，请耐心等待...

:: 创建PHP目录
mkdir "%INSTALL_DIR%\php" 2>nul

:: 下载PHP (使用精简版)
powershell -Command "& {Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/php-8.2.12-Win32-vs16-x64.zip' -OutFile '%INSTALL_DIR%\php.zip' -UseBasicParsing}"
if %errorlevel% neq 0 (
    echo [x] 下载PHP失败，请检查网络连接
    echo [*] 您可以手动从 https://windows.php.net/download/ 下载PHP
    pause
    exit /b 1
)

echo [*] 正在解压PHP...
powershell -Command "& {Expand-Archive -Path '%INSTALL_DIR%\php.zip' -DestinationPath '%INSTALL_DIR%\php' -Force}"
del "%INSTALL_DIR%\php.zip" 2>nul

:: 复制PHP配置文件
copy /Y "%INSTALL_DIR%\config\php.ini" "%INSTALL_DIR%\php\php.ini" >nul 2>&1
if not exist "%INSTALL_DIR%\php\php.ini" (
    copy "%INSTALL_DIR%\php\php.ini-development" "%INSTALL_DIR%\php\php.ini" >nul
)

:: 启用必要扩展
powershell -Command "& {(Get-Content '%INSTALL_DIR%\php\php.ini') -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite' -replace ';extension=sqlite3', 'extension=sqlite3' -replace ';extension=mbstring', 'extension=mbstring' -replace ';extension=curl', 'extension=curl' -replace ';extension=fileinfo', 'extension=fileinfo' -replace ';extension=gd', 'extension=gd' -replace ';extension=openssl', 'extension=openssl' -replace ';extension=zip', 'extension=zip' | Set-Content '%INSTALL_DIR%\php\php.ini'}"

set PHP_PATH=%INSTALL_DIR%\php
set PATH=%PHP_PATH%;%PATH%
echo [✓] PHP配置完成

:PHP_OK
echo.

:: 检查必要扩展
echo [2/5] 检查PHP扩展...
php -r "if (!extension_loaded('pdo_sqlite')) { exit(1); }"
if %errorlevel% neq 0 (
    echo [x] 缺少必需的PHP扩展: pdo_sqlite
    echo [*] 请确保PHP安装了SQLite支持
    pause
    exit /b 1
)
echo [✓] 所有必需扩展已就绪
echo.

:: 创建数据目录
echo [3/5] 初始化数据目录...
if not exist "%INSTALL_DIR%\data" mkdir "%INSTALL_DIR%\data"
if not exist "%INSTALL_DIR%\logs" mkdir "%INSTALL_DIR%\logs"
if not exist "%INSTALL_DIR%\uploads" mkdir "%INSTALL_DIR%\uploads"
if not exist "%INSTALL_DIR%\storage" mkdir "%INSTALL_DIR%\storage"
echo [✓] 数据目录已创建
echo.

:: 检查并创建数据库
echo [4/5] 初始化数据库...
if not exist "%INSTALL_DIR%\data\database.sqlite" (
    echo [*] 创建数据库...
    php "%INSTALL_DIR%\install.php" --cli
    if %errorlevel% neq 0 (
        echo [x] 数据库初始化失败
        pause
        exit /b 1
    )
) else (
    echo [✓] 数据库已存在
)
echo.

:: 创建启动脚本
echo [5/5] 创建启动脚本...
(
echo @echo off
echo chcp 65001 ^>nul
echo cd /d "%%~dp0"
echo set PHP_PATH=%PHP_PATH%
echo set PATH=%%PHP_PATH%%;%%PATH%%
echo.
echo echo ================================================
echo echo   巨神兵API辅助平台API辅助平台
echo echo ================================================
echo echo.
echo echo 正在启动服务...
echo echo 默认访问地址: http://localhost:8080
echo echo.
echo start http://localhost:8080
echo php -S 0.0.0.0:8080 -t . index.php
echo pause
) > "%INSTALL_DIR%\启动服务.bat"

(
echo @echo off
echo chcp 65001 ^>nul
echo cd /d "%%~dp0"
echo set PHP_PATH=%PHP_PATH%
echo set PATH=%%PHP_PATH%%;%%PATH%%
echo.
echo echo 正在打开管理后台...
echo start http://localhost:8080/?route=admin
echo php -S 0.0.0.0:8080 -t . index.php ^>nul 2^>^&1
) > "%INSTALL_DIR%\打开后台.bat"

echo [✓] 启动脚本已创建
echo.

echo ================================================
echo   安装完成！
echo ================================================
echo.
echo 安装目录: %INSTALL_DIR%
echo PHP路径: %PHP_PATH%
echo.
echo 使用说明:
echo   1. 运行 "启动服务.bat" 启动Web服务
echo   2. 浏览器访问 http://localhost:8080
echo   3. 默认管理员账号: admin / admin123
echo.
echo 提示:
echo   - 首次使用请先配置AI模型提供商
echo   - 如需外网访问，请确保防火墙开放8080端口
echo.
echo 是否立即启动服务?
choice /C YN /M "立即启动"
if %errorlevel% equ 1 (
    start "" "%INSTALL_DIR%\启动服务.bat"
)

exit /b 0
