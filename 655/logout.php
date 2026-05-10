<?php
require_once 'config.php';

$current_user = app_get_current_user();
if ($current_user) {
    add_operation_log('用户退出登录', $current_user['id'], $current_user['username']);
}

session_destroy();
header('Location: login.html');
exit;
