#!/bin/bash

# 巨神兵API辅助平台API辅助平台 - Linux卸载脚本

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

APP_NAME="巨神兵API辅助平台API辅助平台"
APP_DIR="/opt/lingyue-ai"
APP_USER="lingyue"
APP_SERVICE="lingyue-ai"

print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 确认卸载
confirm_uninstall() {
    echo "=========================================="
    echo "$APP_NAME 卸载程序"
    echo "=========================================="
    echo ""
    echo -e "${RED}警告: 此操作将删除以下数据:${NC}"
    echo "  - 应用程序文件 ($APP_DIR)"
    echo "  - 数据库文件 (位于 $APP_DIR/data/)"
    echo "  - 系统服务配置"
    echo ""
    read -p "确定要卸载吗？输入 'yes' 确认: " confirm
    
    if [ "$confirm" != "yes" ]; then
        echo "卸载已取消"
        exit 0
    fi
}

# 备份数据
backup_data() {
    print_info "正在备份数据..."
    
    BACKUP_DIR="/root/lingyue-ai-backup-$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    if [ -d "$APP_DIR/data" ]; then
        cp -r "$APP_DIR/data" "$BACKUP_DIR/"
    fi
    
    if [ -d "$APP_DIR/storage" ]; then
        cp -r "$APP_DIR/storage" "$BACKUP_DIR/"
    fi
    
    if [ -d "$APP_DIR/config" ]; then
        cp -r "$APP_DIR/config" "$BACKUP_DIR/"
    fi
    
    print_info "数据已备份到: $BACKUP_DIR"
}

# 停止服务
stop_services() {
    print_info "停止服务..."
    
    if systemctl is-active --quiet "$APP_SERVICE" 2>/dev/null; then
        systemctl stop "$APP_SERVICE"
        systemctl disable "$APP_SERVICE"
    fi
    
    # 删除服务文件
    rm -f "/etc/systemd/system/${APP_SERVICE}.service"
    systemctl daemon-reload
    
    print_info "服务已停止并移除"
}

# 删除应用文件
remove_files() {
    print_info "删除应用文件..."
    
    if [ -d "$APP_DIR" ]; then
        rm -rf "$APP_DIR"
    fi
    
    # 删除启动脚本
    rm -f "/usr/local/bin/lingyue-ai"
    
    print_info "应用文件已删除"
}

# 删除Nginx配置
remove_nginx_config() {
    print_info "移除Nginx配置..."
    
    if [ -f "/etc/nginx/conf.d/lingyue-ai.conf" ]; then
        rm -f "/etc/nginx/conf.d/lingyue-ai.conf"
        if systemctl is-active --quiet nginx 2>/dev/null; then
            systemctl reload nginx
        fi
    fi
}

# 删除用户
remove_user() {
    print_info "删除应用用户..."
    
    if id "$APP_USER" &>/dev/null; then
        userdel "$APP_USER" 2>/dev/null || true
    fi
}

# 清理防火墙规则
cleanup_firewall() {
    print_info "清理防火墙规则..."
    
    if command -v ufw &> /dev/null; then
        ufw delete allow 8000/tcp 2>/dev/null || true
    elif command -v firewall-cmd &> /dev/null; then
        firewall-cmd --permanent --remove-port=8000/tcp 2>/dev/null || true
        firewall-cmd --reload 2>/dev/null || true
    fi
}

# 主函数
main() {
    if [ "$EUID" -ne 0 ]; then
        print_error "请使用root用户或使用 sudo 运行此脚本"
        exit 1
    fi
    
    confirm_uninstall
    
    # 备份数据
    backup_data
    
    # 停止服务
    stop_services
    
    # 删除文件
    remove_files
    
    # 删除Nginx配置
    remove_nginx_config
    
    # 删除用户
    remove_user
    
    # 清理防火墙
    cleanup_firewall
    
    echo ""
    echo "=========================================="
    echo -e "${GREEN}$APP_NAME 已完全卸载${NC}"
    echo "=========================================="
    echo ""
    echo "数据备份位于: /root/lingyue-ai-backup-*"
    echo "如需彻底删除备份，请手动删除"
    echo ""
}

main "$@"
