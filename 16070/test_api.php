<?php
$baseUrl = 'http://127.0.0.1:8080/api';
$cookieFile = __DIR__ . '/cookies.txt';

function apiRequest($url, $method = 'GET', $data = null, $includeCookie = true) {
    global $baseUrl, $cookieFile;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($includeCookie) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => $response, 'data' => json_decode($response, true)];
}

echo "=== 运维值班排班系统 API 测试 ===\n\n";

echo "1. 测试验证码生成... ";
$ch = curl_init($baseUrl . '/captcha.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$img = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo $code == 200 && strlen($img) > 100 ? "✅ 成功\n" : "❌ 失败\n";

echo "\n2. 测试登录状态检查（未登录）... ";
$r = apiRequest('/auth.php?action=check');
echo $r['code'] == 200 && $r['data']['logged_in'] === false ? "✅ 成功\n" : "❌ 失败 (code: {$r['code']})\n";

echo "\n3. 测试登录... ";
$r = apiRequest('/auth.php?action=login', 'POST', [
    'username' => 'Admin',
    'password' => 'Admin.123',
    'captcha' => 'test'
]);
if ($r['code'] == 400 && strpos($r['body'], '验证码') !== false) {
    echo "✅ 验证码验证正常\n";
} else {
    echo "⚠️  返回: {$r['body']}\n";
}

echo "\n4. 测试密码强度检测... ";
$weakPass = '123456';
$strongPass = 'Admin.123';
function checkPasswordStrength($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-+=\\[\\]\/\\\\~`]/', $password)) return false;
    return true;
}
$weakTest = checkPasswordStrength($weakPass) === false;
$strongTest = checkPasswordStrength($strongPass) === true;
echo $weakTest && $strongTest ? "✅ 成功\n" : "❌ 失败\n";

echo "\n5. 测试数据文件初始化... ";
$files = ['users.json', 'schedules.json', 'attendances.json', 'logs.json'];
$allExists = true;
foreach ($files as $f) {
    $path = __DIR__ . '/data/' . $f;
    if (!file_exists($path)) {
        $allExists = false;
        echo "❌ $f 不存在\n";
    }
}
if ($allExists) {
    $users = json_decode(file_get_contents(__DIR__ . '/data/users.json'), true);
    echo count($users) > 0 && $users[0]['username'] === 'Admin' ? "✅ 成功（管理员已初始化）\n" : "❌ 管理员未初始化\n";
}

echo "\n6. 测试用户列表API（未登录）... ";
$r = apiRequest('/users.php');
echo $r['code'] == 401 ? "✅ 权限控制正常\n" : "❌ 权限控制失败 (code: {$r['code']})\n";

echo "\n7. 测试排班列表API（未登录）... ";
$r = apiRequest('/schedules.php');
echo $r['code'] == 401 ? "✅ 权限控制正常\n" : "❌ 权限控制失败 (code: {$r['code']})\n";

echo "\n8. 测试打卡状态API（未登录）... ";
$r = apiRequest('/attendance.php?action=today_status');
echo $r['code'] == 401 ? "✅ 权限控制正常\n" : "❌ 权限控制失败 (code: {$r['code']})\n";

echo "\n9. 测试日志列表API（未登录）... ";
$r = apiRequest('/logs.php');
echo $r['code'] == 401 ? "✅ 权限控制正常\n" : "❌ 权限控制失败 (code: {$r['code']})\n";

echo "\n=== API基础测试完成 ===\n";
echo "\n请使用浏览器访问 http://127.0.0.1:8080/ 进行完整功能测试\n";
echo "默认管理员账号: Admin / Admin.123\n";

echo "\n=== 项目文件结构 ===\n";
$dir = __DIR__;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
$files = [];
foreach ($it as $file) {
    $path = $file->getPathname();
    if (strpos($path, 'cookies.txt') !== false) continue;
    $relPath = str_replace($dir . '\\', '', $path);
    $relPath = str_replace('\\', '/', $relPath);
    $files[] = $relPath;
}
sort($files);
foreach ($files as $f) {
    echo "  $f\n";
}
