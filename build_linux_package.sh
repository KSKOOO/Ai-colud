#!/bin/bash

# 巨神兵API辅助平台API辅助平台 - Linux安装包打包脚本
# 在Windows上可使用 Git Bash 或 WSL 运行

set -e

# 版本号
VERSION="1.0.0"
PACKAGE_NAME="lingyue-ai-linux-${VERSION}"
BUILD_DIR="./build"
SOURCE_DIR="./gpustack_platform"

echo "========================================"
echo "巨神兵API辅助平台API辅助平台 - Linux安装包构建"
echo "版本: ${VERSION}"
echo "========================================"

# 检查源目录
if [ ! -d "$SOURCE_DIR" ]; then
    echo "错误: 源目录 $SOURCE_DIR 不存在"
    exit 1
fi

# 清理并创建构建目录
echo "清理构建目录..."
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/$PACKAGE_NAME"

# 复制应用文件
echo "复制应用文件..."
cp -r "$SOURCE_DIR"/* "$BUILD_DIR/$PACKAGE_NAME/"

# 确保脚本可执行
echo "设置脚本权限..."
chmod +x "$BUILD_DIR/$PACKAGE_NAME/install.sh"
chmod +x "$BUILD_DIR/$PACKAGE_NAME/uninstall.sh"

# 删除开发文件
echo "清理开发文件..."
rm -f "$BUILD_DIR/$PACKAGE_NAME/.gitignore"
rm -f "$BUILD_DIR/$PACKAGE_NAME/INSTALL_README.md"
rm -f "$BUILD_DIR/$PACKAGE_NAME/UPDATES_SUMMARY.md"
rm -f "$BUILD_DIR/$PACKAGE_NAME/VISUAL_NODE_EDITOR_GUIDE.md"
rm -f "$BUILD_DIR/$PACKAGE_NAME/test_workflow_api.html"
rm -rf "$BUILD_DIR/$PACKAGE_NAME/.git"
rm -rf "$BUILD_DIR/$PACKAGE_NAME/.vscode"
rm -rf "$BUILD_DIR/$PACKAGE_NAME/.idea"

# 创建安装说明
cat > "$BUILD_DIR/$PACKAGE_NAME/INSTALL.txt" << 'EOF'
巨神兵API辅助平台API辅助平台 - Linux安装说明
======================================

快速安装：
----------
1. 解压安装包
   tar -xzf lingyue-ai-linux-*.tar.gz
   cd lingyue-ai-linux-*

2. 运行安装脚本
   sudo bash install.sh

3. 访问系统
   打开浏览器访问 http://your-server-ip:8000/install.php
   按照向导完成初始化

手动安装：
----------
请参考 README_LINUX.md 文件

卸载：
-----
   sudo bash uninstall.sh

更多帮助请参考 README_LINUX.md
EOF

# 创建tar.gz包
echo "创建tar.gz安装包..."
cd "$BUILD_DIR"
tar -czf "${PACKAGE_NAME}.tar.gz" "$PACKAGE_NAME"

# 创建zip包（兼容Windows）
if command -v zip &> /dev/null; then
    echo "创建zip安装包..."
    zip -r "${PACKAGE_NAME}.zip" "$PACKAGE_NAME"
fi

# 计算校验和
echo "计算文件校验和..."
cd ..
if command -v sha256sum &> /dev/null; then
    sha256sum "$BUILD_DIR/${PACKAGE_NAME}.tar.gz" > "$BUILD_DIR/${PACKAGE_NAME}.tar.gz.sha256"
    [ -f "$BUILD_DIR/${PACKAGE_NAME}.zip" ] && sha256sum "$BUILD_DIR/${PACKAGE_NAME}.zip" > "$BUILD_DIR/${PACKAGE_NAME}.zip.sha256"
elif command -v shasum &> /dev/null; then
    shasum -a 256 "$BUILD_DIR/${PACKAGE_NAME}.tar.gz" > "$BUILD_DIR/${PACKAGE_NAME}.tar.gz.sha256"
    [ -f "$BUILD_DIR/${PACKAGE_NAME}.zip" ] && shasum -a 256 "$BUILD_DIR/${PACKAGE_NAME}.zip" > "$BUILD_DIR/${PACKAGE_NAME}.zip.sha256"
fi

# 显示结果
echo ""
echo "========================================"
echo "构建完成！"
echo "========================================"
echo ""
echo "安装包位置:"
echo "  $BUILD_DIR/${PACKAGE_NAME}.tar.gz"
[ -f "$BUILD_DIR/${PACKAGE_NAME}.zip" ] && echo "  $BUILD_DIR/${PACKAGE_NAME}.zip"
echo ""
echo "文件大小:"
ls -lh "$BUILD_DIR/${PACKAGE_NAME}".*
echo ""
echo "校验和文件:"
[ -f "$BUILD_DIR/${PACKAGE_NAME}.tar.gz.sha256" ] && cat "$BUILD_DIR/${PACKAGE_NAME}.tar.gz.sha256"
echo ""
echo "安装包内容预览:"
ls -la "$BUILD_DIR/$PACKAGE_NAME/"
echo ""
echo "========================================"
echo "分发说明:"
echo "========================================"
echo ""
echo "1. 将安装包上传到服务器:"
echo "   scp $BUILD_DIR/${PACKAGE_NAME}.tar.gz user@server:/tmp/"
echo ""
echo "2. 在服务器上解压并安装:"
echo "   ssh user@server"
echo "   cd /tmp"
echo "   tar -xzf ${PACKAGE_NAME}.tar.gz"
echo "   cd ${PACKAGE_NAME}"
echo "   sudo bash install.sh"
echo ""
echo "========================================"
