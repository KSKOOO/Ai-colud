#!/bin/bash

echo "=========================================="
echo "  巨神兵API辅助平台API辅助平台 - 外网访问模式"
echo "=========================================="
echo ""

cd "$(dirname "$0")"

# 检查PHP是否可用
if ! command -v php &> /dev/null; then
    echo "[错误] 未找到PHP，请确保已安装PHP"
    exit 1
fi

echo "[信息] PHP已找到"
echo ""

# 获取本机IP
LOCAL_IP=$(hostname -I | awk '{print $1}')
echo "[信息] 本机IP地址: $LOCAL_IP"

# 获取外网IP
PUBLIC_IP=$(curl -s https://api.ipify.org 2>/dev/null || echo "无法获取")
if [ "$PUBLIC_IP" != "无法获取" ]; then
    echo "[信息] 外网IP地址: $PUBLIC_IP"
fi
echo ""

echo "=========================================="
echo "  访问地址:"
echo "  本机:     http://localhost:8000"
echo "  局域网:   http://$LOCAL_IP:8000"
if [ "$PUBLIC_IP" != "无法获取" ]; then
    echo "  外网:     http://$PUBLIC_IP:8000"
fi
echo "=========================================="
echo ""

# 检查防火墙
echo "[信息] 检查防火墙状态..."
if command -v ufw &> /dev/null; then
    ufw status | grep -q "8000"
    if [ $? -ne 0 ]; then
        echo "[警告] UFW防火墙可能阻止8000端口"
        echo "[提示] 如需允许访问，请运行: sudo ufw allow 8000/tcp"
    else
        echo "[信息] UFW已允许8000端口"
    fi
elif command -v firewall-cmd &> /dev/null; then
    firewall-cmd --list-ports | grep -q "8000"
    if [ $? -ne 0 ]; then
        echo "[警告] FirewallD可能阻止8000端口"
        echo "[提示] 如需允许访问，请运行: sudo firewall-cmd --permanent --add-port=8000/tcp && sudo firewall-cmd --reload"
    else
        echo "[信息] FirewallD已允许8000端口"
    fi
fi
echo ""

echo "[信息] 正在启动服务器..."
echo "[信息] 按 Ctrl+C 停止服务器"
echo ""

# 使用0.0.0.0绑定所有接口
php -S 0.0.0.0:8000 -t . index.php
