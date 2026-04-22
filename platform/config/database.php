<?php
// 数据库配置文件 - 使用SQLite
return [
    'type' => 'sqlite',
    'path' => __DIR__ . '/../data/database.sqlite',
    
    // 数据库连接选项
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
