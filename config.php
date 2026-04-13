<?php
/**
 * 工单管理系统配置文件
 */

// 数据存储方式: 'file' 或 'redis'
define('STORAGE_TYPE', 'file');

// 文件存储配置
define('DATA_DIR', __DIR__ . '/data/');
define('WORKORDER_FILE', DATA_DIR . 'workorders.json');

// Redis配置
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_DB', 0);
define('REDIS_KEY_PREFIX', 'workorder:');

// 工单状态定义
$WORKORDER_STATUS = [
    'pending' => '待处理',
    'processing' => '处理中',
    'completed' => '已完成',
    'closed' => '已关闭'
];

// 工单类别定义
$WORKORDER_CATEGORIES = [
    'daily' => '日常工单',
    'incident' => '事件工单',
    'maintenance' => '维护工单',
    'emergency' => '紧急工单'
];

// 确保数据目录存在
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// 确保数据文件存在
if (!file_exists(WORKORDER_FILE)) {
    file_put_contents(WORKORDER_FILE, json_encode([]));
}
