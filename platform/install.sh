#!/bin/bash

# 巨神兵AIAPI辅助平台 - Linux安装脚本
# 支持 Ubuntu/Debian/CentOS/RHEL

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 配置变量
APP_NAME="巨神兵AIAPI辅助平台"
APP_DIR="/opt/lingyue-ai"
APP_USER="lingyue"
APP_SERVICE="lingyue-ai"
PHP_MIN_VERSION="7.4"
DEFAULT_PORT="8000"

# 打印信息函数
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 检测Linux发行版
detect_distro() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        DISTRO=$ID
        DISTRO_VERSION=$VERSION_ID
    elif [ -f /etc/redhat-release ]; then
        DISTRO="centos"
    elif [ -f /etc/debian_version ]; then
        DISTRO="debian"
    else
        DISTRO="unknown"
    fi
    print_info "检测到Linux发行版: $DISTRO $DISTRO_VERSION"
}

# 检查root权限
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "请使用root用户或使用 sudo 运行此脚本"
        exit 1
    fi
}

# 检查并安装依赖
install_dependencies() {
    print_info "正在安装系统依赖..."
    
    case $DISTRO in
        ubuntu|debian)
            apt-get update
            apt-get install -y \
                php-cli \
                php-fpm \
                php-sqlite3 \
                php-curl \
                php-mbstring \
                php-xml \
                php-zip \
                php-gd \
                nginx \
                sqlite3 \
                curl \
                wget \
                unzip \
                openssl \
                isc-dhcp-client \
                open-iscsi
            ;;
        centos|rhel|fedora|rocky|almalinux)
            if command -v dnf &> /dev/null; then
                dnf install -y \
                    php-cli \
                    php-fpm \
                    php-pdo \
                    php-sqlite3 \
                    php-curl \
                    php-mbstring \
                    php-xml \
                    php-zip \
                    php-gd \
                    nginx \
                    sqlite \
                    curl \
                    wget \
                    unzip \
                    openssl \
                    iscsi-initiator-utils
            else
                yum install -y \
                    php-cli \
                    php-fpm \
                    php-pdo \
                    php-sqlite3 \
                    php-curl \
                    php-mbstring \
                    php-xml \
                    php-zip \
                    php-gd \
                    nginx \
                    sqlite \
                    curl \
                    wget \
                    unzip \
                    openssl \
                    iscsi-initiator-utils
            fi
            ;;
        *)
            print_error "不支持的Linux发行版: $DISTRO"
            print_info "请手动安装PHP 7.4+、SQLite3、Nginx/iSCSI等依赖"
            exit 1
            ;;
    esac
    
    print_success "系统依赖安装完成"
}

# 检查PHP版本
check_php_version() {
    print_info "检查PHP版本..."
    
    if ! command -v php &> /dev/null; then
        print_error "PHP未安装"
        exit 1
    fi
    
    PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9]+\.[0-9]+' | head -1)
    
    if [ "$(printf '%s\n' "$PHP_MIN_VERSION" "$PHP_VERSION" | sort -V | head -n1)" != "$PHP_MIN_VERSION" ]; then
        print_error "PHP版本过低，需要 >= $PHP_MIN_VERSION，当前版本: $PHP_VERSION"
        exit 1
    fi
    
    print_success "PHP版本检查通过: $PHP_VERSION"
    
    # 检查必需的扩展
    REQUIRED_EXTENSIONS=("pdo" "pdo_sqlite" "curl" "mbstring" "xml" "zip" "gd")
    MISSING_EXTENSIONS=()
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            MISSING_EXTENSIONS+=("$ext")
        fi
    done
    
    if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
        print_error "缺少必需的PHP扩展: ${MISSING_EXTENSIONS[*]}"
        exit 1
    fi
    
    print_success "PHP扩展检查通过"
}

# 创建应用用户
create_app_user() {
    print_info "创建应用用户..."
    
    if id "$APP_USER" &>/dev/null; then
        print_warning "用户 $APP_USER 已存在"
    else
        useradd -r -s /bin/false -d "$APP_DIR" -M "$APP_USER"
        print_success "用户 $APP_USER 创建完成"
    fi
}

# 安装应用程序
install_application() {
    print_info "安装应用程序..."
    
    # 创建应用目录
    mkdir -p "$APP_DIR"
    
    # 复制文件
    cp -r . "$APP_DIR/"
    
    # 创建数据目录
    mkdir -p "$APP_DIR/data"
    mkdir -p "$APP_DIR/storage"
    mkdir -p "$APP_DIR/storage/models"
    mkdir -p "$APP_DIR/storage/knowledge"
    mkdir -p "$APP_DIR/storage/temp"
    mkdir -p "$APP_DIR/logs"
    
    # 设置权限
    chown -R "$APP_USER:$APP_USER" "$APP_DIR"
    chmod -R 755 "$APP_DIR"
    chmod -R 775 "$APP_DIR/data"
    chmod -R 775 "$APP_DIR/storage"
    chmod -R 775 "$APP_DIR/logs"
    
    print_success "应用程序安装完成: $APP_DIR"
}

# 配置防火墙
configure_firewall() {
    print_info "配置防火墙..."
    
    if command -v ufw &> /dev/null; then
        ufw allow $DEFAULT_PORT/tcp
        ufw allow 80/tcp
        ufw allow 443/tcp
        print_success "UFW防火墙规则已添加"
    elif command -v firewall-cmd &> /dev/null; then
        firewall-cmd --permanent --add-port=$DEFAULT_PORT/tcp
        firewall-cmd --permanent --add-port=80/tcp
        firewall-cmd --permanent --add-port=443/tcp
        firewall-cmd --reload
        print_success "Firewalld防火墙规则已添加"
    else
        print_warning "未检测到支持的防火墙工具，请手动配置端口"
    fi
}

# 创建Systemd服务
create_systemd_service() {
    print_info "创建Systemd服务..."
    
    cat > "/etc/systemd/system/${APP_SERVICE}.service" << 'EOF'
[Unit]
Description=巨神兵AIAPI辅助平台
After=network.target

[Service]
Type=simple
User=lingyue
Group=lingyue
WorkingDirectory=/opt/lingyue-ai
ExecStart=/usr/bin/php -S 0.0.0.0:8000 -t /opt/lingyue-ai
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=lingyue-ai

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    systemctl enable "$APP_SERVICE"
    
    print_success "Systemd服务创建完成"
}

# 创建Nginx配置（可选）
create_nginx_config() {
    print_info "创建Nginx配置..."
    
    cat > "/etc/nginx/conf.d/lingyue-ai.conf" << 'EOF'
server {
    listen 80;
    server_name _;
    root /opt/lingyue-ai;
    index index.php index.html;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(ht|git|env) {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
EOF

    # 测试Nginx配置
    if nginx -t; then
        systemctl reload nginx
        print_success "Nginx配置创建完成"
    else
        print_warning "Nginx配置测试失败，请手动检查"
    fi
}

# 创建启动脚本
create_start_script() {
    print_info "创建启动脚本..."
    
    cat > "/usr/local/bin/lingyue-ai" << 'EOF'
#!/bin/bash

APP_DIR="/opt/lingyue-ai"
PID_FILE="/var/run/lingyue-ai.pid"

start() {
    if [ -f "$PID_FILE" ] && kill -0 $(cat "$PID_FILE") 2>/dev/null; then
        echo "服务已在运行"
        return 1
    fi
    
    echo "启动巨神兵AIAPI辅助平台..."
    cd "$APP_DIR"
    nohup php -S 0.0.0.0:8000 -t "$APP_DIR" > /dev/null 2>&1 &
    echo $! > "$PID_FILE"
    echo "服务已启动，PID: $(cat $PID_FILE)"
    echo "访问地址: http://localhost:8000"
}

stop() {
    if [ -f "$PID_FILE" ]; then
        kill $(cat "$PID_FILE") 2>/dev/null
        rm -f "$PID_FILE"
        echo "服务已停止"
    else
        echo "服务未运行"
    fi
}

restart() {
    stop
    sleep 2
    start
}

status() {
    if [ -f "$PID_FILE" ] && kill -0 $(cat "$PID_FILE") 2>/dev/null; then
        echo "服务正在运行，PID: $(cat $PID_FILE)"
    else
        echo "服务未运行"
    fi
}

case "$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    status)
        status
        ;;
    *)
        echo "用法: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac
EOF

    chmod +x "/usr/local/bin/lingyue-ai"
    print_success "启动脚本创建完成"
}

# 初始化数据库
init_database() {
    print_info "初始化数据库..."
    
    # 创建安装标记文件
    if [ ! -f "$APP_DIR/.installed" ]; then
        touch "$APP_DIR/.installed"
        print_success "数据库初始化准备完成"
        print_warning "首次访问请运行安装向导: http://your-server-ip:8000/install.php"
    fi
}

# 显示安装信息
show_install_info() {
    echo ""
    echo "=========================================="
    echo -e "${GREEN}$APP_NAME 安装完成！${NC}"
    echo "=========================================="
    echo ""
    echo "安装路径: $APP_DIR"
    echo "访问地址: http://your-server-ip:$DEFAULT_PORT"
    echo ""
    echo "管理命令:"
    echo "  启动服务: systemctl start $APP_SERVICE"
    echo "  停止服务: systemctl stop $APP_SERVICE"
    echo "  重启服务: systemctl restart $APP_SERVICE"
    echo "  查看状态: systemctl status $APP_SERVICE"
    echo "  查看日志: journalctl -u $APP_SERVICE -f"
    echo ""
    echo "或使用快捷命令:"
    echo "  lingyue-ai start|stop|restart|status"
    echo ""
    echo -e "${YELLOW}重要提示:${NC}"
    echo "1. 首次访问请访问 http://your-server-ip:$DEFAULT_PORT/install.php 完成初始化"
    echo "2. 建议配置Nginx作为反向代理以获得更好的性能"
    echo "3. 如需使用IP-SAN存储，请确保已安装open-iscsi"
    echo ""
    echo -e "${GREEN}安装日志已保存到: /var/log/lingyue-ai-install.log${NC}"
    echo "=========================================="
}

# 主函数
main() {
    # 记录日志
    exec > >(tee -a /var/log/lingyue-ai-install.log)
    exec 2>&1
    
    echo "=========================================="
    echo "$APP_NAME Linux安装程序"
    echo "=========================================="
    echo ""
    
    # 检查root权限
    check_root
    
    # 检测发行版
    detect_distro
    
    # 安装依赖
    install_dependencies
    
    # 检查PHP版本
    check_php_version
    
    # 创建应用用户
    create_app_user
    
    # 安装应用程序
    install_application
    
    # 配置防火墙
    configure_firewall
    
    # 创建Systemd服务
    create_systemd_service
    
    # 创建Nginx配置
    create_nginx_config
    
    # 创建启动脚本
    create_start_script
    
    # 初始化数据库
    init_database
    
    # 启动服务
    print_info "启动服务..."
    systemctl start "$APP_SERVICE"
    
    # 显示安装信息
    show_install_info
}

# 运行主函数
main "$@"
