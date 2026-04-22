# 巨神兵AI辅助平台 - Docker镜像
FROM php:8.1-apache

# 维护者信息
LABEL maintainer="巨神兵AI辅助平台"
LABEL version="1.0.0"
LABEL description="巨神兵AI辅助平台 - 基于PHP和SQLite的AI服务平台"

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libzip-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    open-iscsi \
    curl \
    wget \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 安装PHP扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    sqlite3 \
    curl \
    mbstring \
    xml \
    zip \
    gd \
    opcache

# 启用Apache模块
RUN a2enmod rewrite
RUN a2enmod headers

# 配置PHP
RUN { \
    echo 'upload_max_filesize = 100M'; \
    echo 'post_max_size = 100M'; \
    echo 'max_execution_time = 300'; \
    echo 'memory_limit = 512M'; \
    echo 'date.timezone = Asia/Shanghai'; \
} > /usr/local/etc/php/conf.d/custom.ini

# 配置OPcache
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# 配置Apache
RUN { \
    echo '<VirtualHost *:80>'; \
    echo '    DocumentRoot /var/www/html'; \
    echo '    <Directory /var/www/html>'; \
    echo '        Options Indexes FollowSymLinks'; \
    echo '        AllowOverride All'; \
    echo '        Require all granted'; \
    echo '    </Directory>'; \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf

# 设置工作目录
WORKDIR /var/www/html

# 复制应用文件
COPY . /var/www/html/

# 创建数据目录并设置权限
RUN mkdir -p /var/www/html/data \
    && mkdir -p /var/www/html/storage/models \
    && mkdir -p /var/www/html/storage/knowledge \
    && mkdir -p /var/www/html/storage/temp \
    && mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/data \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/logs

# 健康检查
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# 暴露端口
EXPOSE 80

# 数据卷
VOLUME ["/var/www/html/data", "/var/www/html/storage", "/var/www/html/logs"]

# 启动命令
CMD ["apache2-foreground"]
