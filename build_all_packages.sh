#!/bin/bash

# 巨神兵API辅助平台API辅助平台 - 多架构安装包打包脚本
# 支持 Linux x86_64, Linux ARM64, Windows x86

set -e

# 版本号
VERSION="1.0.0"
BUILD_DIR="./build"
SOURCE_DIR="./gpustack_platform"
COMFYUI_DIR="./comfyui-master"

echo "========================================"
echo "巨神兵API辅助平台API辅助平台 - 多架构安装包构建"
echo "版本: ${VERSION}"
echo "========================================"

# 检查源目录
if [ ! -d "$SOURCE_DIR" ]; then
    echo "错误: 源目录 $SOURCE_DIR 不存在"
    exit 1
fi

if [ ! -d "$COMFYUI_DIR" ]; then
    echo "错误: ComfyUI 目录 $COMFYUI_DIR 不存在"
    exit 1
fi

# 清理并创建构建目录
echo "清理构建目录..."
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# ============================================
# 构建 Linux x86_64 安装包
# ============================================
build_linux_x86_64() {
    echo ""
    echo "========================================"
    echo "构建 Linux x86_64 安装包..."
    echo "========================================"
    
    local PKG_NAME="lingyue-ai-linux-x86_64-${VERSION}"
    local PKG_DIR="$BUILD_DIR/$PKG_NAME"
    
    mkdir -p "$PKG_DIR"
    
    # 复制应用文件
    echo "复制应用文件..."
    cp -r "$SOURCE_DIR"/* "$PKG_DIR/"
    
    # 复制 ComfyUI
    echo "复制 ComfyUI..."
    mkdir -p "$PKG_DIR/comfyui"
    cp -r "$COMFYUI_DIR"/* "$PKG_DIR/comfyui/"
    
    # 确保脚本可执行
    echo "设置脚本权限..."
    chmod +x "$PKG_DIR/install.sh"
    chmod +x "$PKG_DIR/uninstall.sh"
    chmod +x "$PKG_DIR/start_server_external.sh"
    
    # 删除开发文件和Windows特定文件
    echo "清理开发文件..."
    rm -f "$PKG_DIR/.gitignore"
    rm -f "$PKG_DIR/INSTALL_README.md"
    rm -f "$PKG_DIR/UPDATES_SUMMARY.md"
    rm -f "$PKG_DIR/VISUAL_NODE_EDITOR_GUIDE.md"
    rm -f "$PKG_DIR/test_workflow_api.html"
    rm -f "$PKG_DIR/install.bat"
    rm -f "$PKG_DIR/start_server.bat"
    rm -f "$PKG_DIR/start_server_external.bat"
    rm -rf "$PKG_DIR/.git"
    rm -rf "$PKG_DIR/.vscode"
    rm -rf "$PKG_DIR/.idea"
    rm -rf "$PKG_DIR/bin/ffmpeg"
    
    # 创建架构特定说明
    cat > "$PKG_DIR/ARCHITECTURE.txt" << 'EOF'
架构: Linux x86_64 (AMD64)
支持系统: Ubuntu 18.04+, Debian 10+, CentOS 7+, RHEL 7+, Fedora 30+
依赖: PHP 7.4+, Nginx/Apache, SQLite3, Python 3.10+
EOF
    
    # 创建安装说明
    cat > "$PKG_DIR/INSTALL.txt" << 'EOF'
巨神兵API辅助平台API辅助平台 - Linux x86_64 安装说明
=============================================

系统要求:
- CPU: x86_64 架构
- 内存: 4GB+ (推荐 8GB+)
- 磁盘: 10GB+ 可用空间
- 系统: Ubuntu 18.04+, Debian 10+, CentOS 7+, RHEL 7+

快速安装:
----------
1. 解压安装包
   tar -xzf lingyue-ai-linux-x86_64-*.tar.gz
   cd lingyue-ai-linux-x86_64-*

2. 运行安装脚本
   sudo bash install.sh

3. 访问系统
   打开浏览器访问 http://your-server-ip:8000/install.php
   按照向导完成初始化

手动安装:
----------
请参考 README_LINUX.md 文件

卸载:
-----
   sudo bash uninstall.sh

更多帮助请参考 README_LINUX.md
EOF
    
    # 创建tar.gz包
    echo "创建 tar.gz 安装包..."
    cd "$BUILD_DIR"
    tar -czf "${PKG_NAME}.tar.gz" "$PKG_NAME"
    
    # 计算校验和
    echo "计算文件校验和..."
    if command -v sha256sum &> /dev/null; then
        sha256sum "${PKG_NAME}.tar.gz" > "${PKG_NAME}.tar.gz.sha256"
    elif command -v shasum &> /dev/null; then
        shasum -a 256 "${PKG_NAME}.tar.gz" > "${PKG_NAME}.tar.gz.sha256"
    fi
    
    cd ..
    echo "Linux x86_64 安装包构建完成: ${PKG_NAME}.tar.gz"
}

# ============================================
# 构建 Linux ARM64 安装包
# ============================================
build_linux_arm64() {
    echo ""
    echo "========================================"
    echo "构建 Linux ARM64 安装包..."
    echo "========================================"
    
    local PKG_NAME="lingyue-ai-linux-arm64-${VERSION}"
    local PKG_DIR="$BUILD_DIR/$PKG_NAME"
    
    mkdir -p "$PKG_DIR"
    
    # 复制应用文件
    echo "复制应用文件..."
    cp -r "$SOURCE_DIR"/* "$PKG_DIR/"
    
    # 复制 ComfyUI
    echo "复制 ComfyUI..."
    mkdir -p "$PKG_DIR/comfyui"
    cp -r "$COMFYUI_DIR"/* "$PKG_DIR/comfyui/"
    
    # 确保脚本可执行
    echo "设置脚本权限..."
    chmod +x "$PKG_DIR/install.sh"
    chmod +x "$PKG_DIR/uninstall.sh"
    chmod +x "$PKG_DIR/start_server_external.sh"
    
    # 删除开发文件和Windows特定文件
    echo "清理开发文件..."
    rm -f "$PKG_DIR/.gitignore"
    rm -f "$PKG_DIR/INSTALL_README.md"
    rm -f "$PKG_DIR/UPDATES_SUMMARY.md"
    rm -f "$PKG_DIR/VISUAL_NODE_EDITOR_GUIDE.md"
    rm -f "$PKG_DIR/test_workflow_api.html"
    rm -f "$PKG_DIR/install.bat"
    rm -f "$PKG_DIR/start_server.bat"
    rm -f "$PKG_DIR/start_server_external.bat"
    rm -rf "$PKG_DIR/.git"
    rm -rf "$PKG_DIR/.vscode"
    rm -rf "$PKG_DIR/.idea"
    rm -rf "$PKG_DIR/bin/ffmpeg"
    
    # 创建架构特定说明
    cat > "$PKG_DIR/ARCHITECTURE.txt" << 'EOF'
架构: Linux ARM64 (AArch64)
支持系统: Ubuntu 18.04+ ARM64, Debian 10+ ARM64, 
         CentOS 7+ ARM64, RHEL 7+ ARM64, 
         Raspberry Pi OS 64-bit, ARM服务器
依赖: PHP 7.4+, Nginx/Apache, SQLite3, Python 3.10+
注意: ARM架构需要安装ARM版本的PyTorch
EOF
    
    # 创建安装说明
    cat > "$PKG_DIR/INSTALL.txt" << 'EOF'
巨神兵API辅助平台API辅助平台 - Linux ARM64 安装说明
============================================

系统要求:
- CPU: ARM64 (AArch64) 架构
- 内存: 4GB+ (推荐 8GB+)
- 磁盘: 10GB+ 可用空间
- 系统: Ubuntu 18.04+ ARM64, Debian 10+ ARM64, 
       CentOS 7+ ARM64, RHEL 7+ ARM64, 
       Raspberry Pi OS 64-bit

ARM特定说明:
------------
1. PyTorch安装需要使用ARM版本:
   pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cpu

2. 某些模型可能需要重新编译或寻找ARM兼容版本

快速安装:
----------
1. 解压安装包
   tar -xzf lingyue-ai-linux-arm64-*.tar.gz
   cd lingyue-ai-linux-arm64-*

2. 运行安装脚本
   sudo bash install.sh

3. 访问系统
   打开浏览器访问 http://your-server-ip:8000/install.php
   按照向导完成初始化

手动安装:
----------
请参考 README_LINUX.md 文件

卸载:
-----
   sudo bash uninstall.sh

更多帮助请参考 README_LINUX.md
EOF
    
    # 创建tar.gz包
    echo "创建 tar.gz 安装包..."
    cd "$BUILD_DIR"
    tar -czf "${PKG_NAME}.tar.gz" "$PKG_NAME"
    
    # 计算校验和
    echo "计算文件校验和..."
    if command -v sha256sum &> /dev/null; then
        sha256sum "${PKG_NAME}.tar.gz" > "${PKG_NAME}.tar.gz.sha256"
    elif command -v shasum &> /dev/null; then
        shasum -a 256 "${PKG_NAME}.tar.gz" > "${PKG_NAME}.tar.gz.sha256"
    fi
    
    cd ..
    echo "Linux ARM64 安装包构建完成: ${PKG_NAME}.tar.gz"
}

# ============================================
# 构建 Windows x86 安装包
# ============================================
build_windows_x86() {
    echo ""
    echo "========================================"
    echo "构建 Windows x86 安装包..."
    echo "========================================"
    
    local PKG_NAME="lingyue-ai-windows-x86-${VERSION}"
    local PKG_DIR="$BUILD_DIR/$PKG_NAME"
    
    mkdir -p "$PKG_DIR"
    
    # 复制应用文件
    echo "复制应用文件..."
    cp -r "$SOURCE_DIR"/* "$PKG_DIR/"
    
    # 复制 ComfyUI
    echo "复制 ComfyUI..."
    mkdir -p "$PKG_DIR/comfyui"
    cp -r "$COMFYUI_DIR"/* "$PKG_DIR/comfyui/"
    
    # 删除Linux特定文件
    echo "清理Linux特定文件..."
    rm -f "$PKG_DIR/.gitignore"
    rm -f "$PKG_DIR/INSTALL_README.md"
    rm -f "$PKG_DIR/UPDATES_SUMMARY.md"
    rm -f "$PKG_DIR/VISUAL_NODE_EDITOR_GUIDE.md"
    rm -f "$PKG_DIR/test_workflow_api.html"
    rm -f "$PKG_DIR/install.sh"
    rm -f "$PKG_DIR/uninstall.sh"
    rm -f "$PKG_DIR/start_server_external.sh"
    rm -rf "$PKG_DIR/.git"
    rm -rf "$PKG_DIR/.vscode"
    rm -rf "$PKG_DIR/.idea"
    
    # 创建架构特定说明
    cat > "$PKG_DIR/ARCHITECTURE.txt" << 'EOF'
架构: Windows x86 (32/64位)
支持系统: Windows 10, Windows 11, Windows Server 2016+
依赖: PHP 7.4+, Nginx/Apache, SQLite3, Python 3.10+
EOF
    
    # 创建Windows安装说明
    cat > "$PKG_DIR/INSTALL.txt" << 'EOF'
巨神兵API辅助平台API辅助平台 - Windows x86 安装说明
==========================================

系统要求:
- CPU: x86/x64 架构
- 内存: 4GB+ (推荐 8GB+)
- 磁盘: 10GB+ 可用空间
- 系统: Windows 10, Windows 11, Windows Server 2016+
- 软件: PHP 7.4+, Python 3.10+, Nginx/Apache

快速安装:
----------
1. 解压安装包到目标目录
   例如: C:\lingyue-ai\

2. 安装依赖软件:
   - 安装 PHP: https://windows.php.net/download/
   - 安装 Python: https://www.python.org/downloads/windows/
   - 安装 Nginx: http://nginx.org/en/download.html

3. 运行安装脚本
   双击运行 install.bat
   或命令行运行: install.bat

4. 访问系统
   打开浏览器访问 http://localhost:8000/install.php
   按照向导完成初始化

手动启动:
---------
   双击 start_server.bat 启动服务
   或双击 start_server_external.bat 启动外部访问模式

卸载:
-----
   直接删除安装目录即可

更多帮助请参考 README.md
EOF
    
    # 创建zip包
    echo "创建 zip 安装包..."
    cd "$BUILD_DIR"
    if command -v zip &> /dev/null; then
        zip -r "${PKG_NAME}.zip" "$PKG_NAME"
        
        # 计算校验和
        echo "计算文件校验和..."
        if command -v sha256sum &> /dev/null; then
            sha256sum "${PKG_NAME}.zip" > "${PKG_NAME}.zip.sha256"
        elif command -v shasum &> /dev/null; then
            shasum -a 256 "${PKG_NAME}.zip" > "${PKG_NAME}.zip.sha256"
        fi
        
        echo "Windows x86 安装包构建完成: ${PKG_NAME}.zip"
    else
        echo "警告: 未找到 zip 命令，跳过创建 zip 包"
        echo "Windows x86 文件已准备: $PKG_DIR"
    fi
    
    cd ..
}

# ============================================
# 主程序
# ============================================

# 解析命令行参数
BUILD_LINUX_X86_64=true
BUILD_LINUX_ARM64=true
BUILD_WINDOWS_X86=true

while [[ $# -gt 0 ]]; do
    case $1 in
        --linux-x86_64-only)
            BUILD_LINUX_X86_64=true
            BUILD_LINUX_ARM64=false
            BUILD_WINDOWS_X86=false
            shift
            ;;
        --linux-arm64-only)
            BUILD_LINUX_X86_64=false
            BUILD_LINUX_ARM64=true
            BUILD_WINDOWS_X86=false
            shift
            ;;
        --windows-x86-only)
            BUILD_LINUX_X86_64=false
            BUILD_LINUX_ARM64=false
            BUILD_WINDOWS_X86=true
            shift
            ;;
        --all)
            BUILD_LINUX_X86_64=true
            BUILD_LINUX_ARM64=true
            BUILD_WINDOWS_X86=true
            shift
            ;;
        -v|--version)
            VERSION="$2"
            shift 2
            ;;
        -h|--help)
            echo "用法: $0 [选项]"
            echo ""
            echo "选项:"
            echo "  --linux-x86_64-only    仅构建 Linux x86_64 安装包"
            echo "  --linux-arm64-only     仅构建 Linux ARM64 安装包"
            echo "  --windows-x86-only     仅构建 Windows x86 安装包"
            echo "  --all                  构建所有架构安装包 (默认)"
            echo "  -v, --version VERSION  指定版本号 (默认: 1.0.0)"
            echo "  -h, --help             显示帮助信息"
            exit 0
            ;;
        *)
            echo "未知选项: $1"
            echo "使用 -h 或 --help 查看帮助"
            exit 1
            ;;
    esac
done

echo ""
echo "版本: $VERSION"
echo ""

# 构建选定的包
if [ "$BUILD_LINUX_X86_64" = true ]; then
    build_linux_x86_64
fi

if [ "$BUILD_LINUX_ARM64" = true ]; then
    build_linux_arm64
fi

if [ "$BUILD_WINDOWS_X86" = true ]; then
    build_windows_x86
fi

# 生成汇总信息
echo ""
echo "========================================"
echo "构建完成！"
echo "========================================"
echo ""
echo "输出目录: $BUILD_DIR"
echo ""
echo "生成的文件:"
ls -lh "$BUILD_DIR"/*.{tar.gz,zip,sha256} 2>/dev/null || ls -lh "$BUILD_DIR"
echo ""
echo "========================================"
