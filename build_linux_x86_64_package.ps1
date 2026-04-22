# 巨神兵API辅助平台API辅助平台 - Linux x86_64 安装包打包脚本 (PowerShell)
# 支持构建 Linux x86_64 安装包

param(
    [string]$Version = "1.0.0",
    [switch]$Help
)

if ($Help) {
    Write-Host "Usage: .\build_linux_x86_64_package.ps1 [options]"
    Write-Host ""
    Write-Host "Options:"
    Write-Host "  -Version VERSION    Specify version (default: 1.0.0)"
    Write-Host "  -Help               Show help"
    exit 0
}

$BUILD_DIR = ".\build_linux_x86_64"
$SOURCE_DIR = ".\gpustack_platform"
$COMFYUI_DIR = ".\comfyui-master"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Lingyue AI Platform - Linux x86_64 Package Builder" -ForegroundColor Cyan
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
    Start-Sleep -Seconds 2
    Remove-Item -Path $BUILD_DIR -Recurse -Force -ErrorAction SilentlyContinue
}
New-Item -ItemType Directory -Path $BUILD_DIR -Force | Out-Null

# Build Linux x86_64 package
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Building Linux x86_64 package..." -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green

$PKG_NAME = "lingyue-ai-linux-x86_64-$Version"
$PKG_DIR = Join-Path $BUILD_DIR $PKG_NAME

New-Item -ItemType Directory -Path $PKG_DIR -Force | Out-Null

Write-Host "Copying application files..."
robocopy $SOURCE_DIR $PKG_DIR /E /NFL /NDL /NJH /NJS /nc /ns /np

Write-Host "Copying ComfyUI..."
$ComfyUIDest = "$PKG_DIR\comfyui"
New-Item -ItemType Directory -Path $ComfyUIDest -Force | Out-Null
robocopy $COMFYUI_DIR $ComfyUIDest /E /NFL /NDL /NJH /NJS /nc /ns /np

Write-Host "Cleaning Windows-specific files..."
$filesToRemove = @(
    "install.bat",
    "start_server.bat",
    "start_server_external.bat",
    ".gitignore",
    "INSTALL_README.md",
    "UPDATES_SUMMARY.md",
    "VISUAL_NODE_EDITOR_GUIDE.md",
    "test_workflow_api.html"
)

foreach ($file in $filesToRemove) {
    $filePath = Join-Path $PKG_DIR $file
    if (Test-Path $filePath) {
        Remove-Item -Path $filePath -Force -ErrorAction SilentlyContinue
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
        Remove-Item -Path $dirPath -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# Remove Windows ffmpeg binaries
$ffmpegDir = Join-Path $PKG_DIR "bin\ffmpeg"
if (Test-Path $ffmpegDir) {
    Remove-Item -Path $ffmpegDir -Recurse -Force -ErrorAction SilentlyContinue
}

# Create architecture info
$archFile = Join-Path $PKG_DIR "ARCHITECTURE.txt"
"Architecture: Linux x86_64 (AMD64)" | Out-File -FilePath $archFile -Encoding UTF8
"Supported Systems: Ubuntu 18.04+, Debian 10+, CentOS 7+, RHEL 7+, Fedora 30+" | Out-File -FilePath $archFile -Append -Encoding UTF8
"Dependencies: PHP 7.4+, Nginx/Apache, SQLite3, Python 3.10+" | Out-File -FilePath $archFile -Append -Encoding UTF8

# Create installation guide
$installFile = Join-Path $PKG_DIR "INSTALL.txt"
@"
巨神兵API辅助平台API辅助平台 - Linux x86_64 Installation Guide
======================================================

System Requirements:
- CPU: x86_64 (AMD64) Architecture
- Memory: 4GB+ (Recommended 8GB+)
- Disk: 10GB+ Free Space
- OS: Ubuntu 18.04+, Debian 10+, CentOS 7+, RHEL 7+, Fedora 30+

Quick Installation:
-------------------
1. Extract the package:
   tar -xzf lingyue-ai-linux-x86_64-*.tar.gz
   cd lingyue-ai-linux-x86_64-*

2. Run installation script:
   sudo bash install.sh

3. Access the system:
   Open browser and visit http://your-server-ip:8000/install.php
   Follow the wizard to complete initialization

Manual Installation:
--------------------
Please refer to README_LINUX.md

Uninstall:
----------
   sudo bash uninstall.sh

For more help, please refer to README_LINUX.md
"@ | Out-File -FilePath $installFile -Encoding UTF8

# Wait for file operations to complete
Write-Host "Waiting for file operations to complete..."
Start-Sleep -Seconds 3

# Create tar.gz package
Write-Host "Creating tar.gz package..."

# Check if tar is available (from Git Bash)
$tar = Get-Command "tar.exe" -ErrorAction SilentlyContinue
if (-not $tar) {
    $gitBashTar = "C:\Program Files\Git\usr\bin\tar.exe"
    if (Test-Path $gitBashTar) {
        $tar = @{ Source = $gitBashTar }
    } else {
        $gitBashTar = "C:\Program Files (x86)\Git\usr\bin\tar.exe"
        if (Test-Path $gitBashTar) {
            $tar = @{ Source = $gitBashTar }
        }
    }
}

$gzPath = Join-Path $BUILD_DIR "$PKG_NAME.tar.gz"

if ($tar) {
    Write-Host "Using tar to create tar.gz..."
    $tarExe = if ($tar.Source) { $tar.Source } else { "tar.exe" }
    & $tarExe -czf $gzPath -C $BUILD_DIR $PKG_NAME
} else {
    $sevenZip = Get-Command "7z.exe" -ErrorAction SilentlyContinue
    if ($sevenZip) {
        Write-Host "Using 7-Zip to create tar.gz..."
        $tarPath = Join-Path $BUILD_DIR "$PKG_NAME.tar"
        & 7z.exe a -ttar $tarPath $PKG_DIR
        & 7z.exe a -tgzip $gzPath $tarPath
        if (Test-Path $tarPath) {
            Remove-Item $tarPath -Force
        }
    } else {
        Write-Host "7-Zip and tar not found, creating zip package..."
        $zipPath = Join-Path $BUILD_DIR "$PKG_NAME.zip"
        Compress-Archive -Path $PKG_DIR -DestinationPath $zipPath -Force
        $gzPath = $zipPath
    }
}

Start-Sleep -Seconds 2

# Calculate checksum
if (Test-Path $gzPath) {
    Write-Host "Calculating checksum..."
    $gzFile = Get-Item $gzPath
    $hash = Get-FileHash -Path $gzPath -Algorithm SHA256
    $hashContent = "$($hash.Hash)  $($gzFile.Name)"
    Set-Content -Path "$gzPath.sha256" -Value $hashContent
    
    Write-Host ""
    Write-Host "Linux x86_64 package build completed: $($gzFile.Name)" -ForegroundColor Green
}

Write-Host ""
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
