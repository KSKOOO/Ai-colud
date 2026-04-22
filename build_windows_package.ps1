# 巨神兵API辅助平台API辅助平台 - Windows x86 安装包打包脚本 (PowerShell)
# 支持构建 Windows x86 安装包

param(
    [string]$Version = "1.0.0",
    [switch]$Help
)

if ($Help) {
    Write-Host "Usage: .\build_windows_package.ps1 [options]"
    Write-Host ""
    Write-Host "Options:"
    Write-Host "  -Version VERSION    Specify version (default: 1.0.0)"
    Write-Host "  -Help               Show help"
    exit 0
}

$BUILD_DIR = ".\build"
$SOURCE_DIR = ".\gpustack_platform"
$COMFYUI_DIR = ".\comfyui-master"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Lingyue AI Platform - Windows x86 Package Builder" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Version: $Version"
Write-Host ""

# Check source directories
if (-not (Test-Path $SOURCE_DIR)) {
    Write-Host "Error: Source directory $SOURCE_DIR does not exist" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $COMFYUI_DIR)) {
    Write-Host "Error: ComfyUI directory $COMFYUI_DIR does not exist" -ForegroundColor Red
    exit 1
}

# Clean and create build directory
Write-Host "Cleaning build directory..."
if (Test-Path $BUILD_DIR) {
    Remove-Item -Path $BUILD_DIR -Recurse -Force
}
New-Item -ItemType Directory -Path $BUILD_DIR | Out-Null

# Build Windows x86 package
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Building Windows x86 package..." -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green

$PKG_NAME = "lingyue-ai-windows-x86-$Version"
$PKG_DIR = Join-Path $BUILD_DIR $PKG_NAME

New-Item -ItemType Directory -Path $PKG_DIR | Out-Null

Write-Host "Copying application files..."
Copy-Item -Path "$SOURCE_DIR\*" -Destination $PKG_DIR -Recurse -Force

Write-Host "Copying ComfyUI..."
New-Item -ItemType Directory -Path "$PKG_DIR\comfyui" | Out-Null
Copy-Item -Path "$COMFYUI_DIR\*" -Destination "$PKG_DIR\comfyui" -Recurse -Force

Write-Host "Cleaning Linux-specific files..."
$filesToRemove = @(
    "install.sh",
    "uninstall.sh",
    "start_server_external.sh",
    ".gitignore",
    "INSTALL_README.md",
    "UPDATES_SUMMARY.md",
    "VISUAL_NODE_EDITOR_GUIDE.md",
    "test_workflow_api.html"
)

foreach ($file in $filesToRemove) {
    $filePath = Join-Path $PKG_DIR $file
    if (Test-Path $filePath) {
        Remove-Item -Path $filePath -Force
    }
}

# Clean directories
$dirsToRemove = @(
    ".git",
    ".vscode",
    ".idea"
)

foreach ($dir in $dirsToRemove) {
    $dirPath = Join-Path $PKG_DIR $dir
    if (Test-Path $dirPath) {
        Remove-Item -Path $dirPath -Recurse -Force
    }
}

# Create architecture info
$archFile = Join-Path $PKG_DIR "ARCHITECTURE.txt"
"Architecture: Windows x86 (32/64-bit)" | Out-File -FilePath $archFile -Encoding UTF8
"Supported Systems: Windows 10, Windows 11, Windows Server 2016+" | Out-File -FilePath $archFile -Append -Encoding UTF8
"Dependencies: PHP 7.4+, Nginx/Apache, SQLite3, Python 3.10+" | Out-File -FilePath $archFile -Append -Encoding UTF8

# Create installation guide
$installFile = Join-Path $PKG_DIR "INSTALL.txt"
@"
Lingyue AI Platform - Windows x86 Installation Guide
====================================================

System Requirements:
- CPU: x86/x64 Architecture
- Memory: 4GB+ (Recommended 8GB+)
- Disk: 10GB+ Free Space
- OS: Windows 10, Windows 11, Windows Server 2016+
- Software: PHP 7.4+, Python 3.10+, Nginx/Apache

Quick Installation:
-------------------
1. Extract the package to target directory
   Example: C:\lingyue-ai\

2. Install Dependencies:
   - Install PHP: https://windows.php.net/download/
   - Install Python: https://www.python.org/downloads/windows/
   - Install Nginx: http://nginx.org/en/download.html

3. Run Installation Script:
   Double-click install.bat
   Or run in command line: install.bat

4. Access the System:
   Open browser and visit http://localhost:8000/install.php
   Follow the wizard to complete initialization

Manual Start:
-------------
   Double-click start_server.bat to start service
   Or double-click start_server_external.bat for external access mode

Uninstall:
----------
   Simply delete the installation directory

For more help, please refer to README.md
"@ | Out-File -FilePath $installFile -Encoding UTF8

# Create zip package
Write-Host "Creating zip package..."
$zipPath = Join-Path $BUILD_DIR "$PKG_NAME.zip"
Compress-Archive -Path $PKG_DIR -DestinationPath $zipPath -Force

# Calculate checksum
Write-Host "Calculating checksum..."
$zipFile = Get-Item $zipPath
$hash = Get-FileHash -Path $zipPath -Algorithm SHA256
$hashContent = "$($hash.Hash)  $($zipFile.Name)"
Set-Content -Path "$zipPath.sha256" -Value $hashContent

Write-Host ""
Write-Host "Windows x86 package build completed: $PKG_NAME.zip" -ForegroundColor Green
Write-Host ""

# Show results
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Build Completed!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Output Directory: $BUILD_DIR"
Write-Host ""
Write-Host "Generated Files:"
Get-ChildItem -Path $BUILD_DIR | ForEach-Object {
    $size = if ($_.Length -gt 1GB) { "{0:N2} GB" -f ($_.Length / 1GB) }
            elseif ($_.Length -gt 1MB) { "{0:N2} MB" -f ($_.Length / 1MB) }
            elseif ($_.Length -gt 1KB) { "{0:N2} KB" -f ($_.Length / 1KB) }
            else { "$($_.Length) B" }
    Write-Host "  $($_.Name) - $size"
}
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
