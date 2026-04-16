<?php
function testAPI($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'response' => json_decode($response, true)];
}

echo "=== 测试工单编号验证规则 ===\n\n";

echo "1. 测试缺少大写字母: Abc12345\n";
$result = testAPI('http://localhost:8000/api/work-orders', 'POST', [
    'order_no' => 'Abc12345',
    'subject' => '测试',
    'category' => '日常工单',
    'planned_start_time' => '2026-04-17 09:00:00',
    'planned_end_time' => '2026-04-17 18:00:00',
    'executor' => '测试'
]);
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "2. 测试缺少小写字母: ABC12345\n";
$result = testAPI('http://localhost:8000/api/work-orders', 'POST', [
    'order_no' => 'ABC12345',
    'subject' => '测试',
    'category' => '日常工单',
    'planned_start_time' => '2026-04-17 09:00:00',
    'planned_end_time' => '2026-04-17 18:00:00',
    'executor' => '测试'
]);
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "3. 测试缺少数字: Abcdefgh\n";
$result = testAPI('http://localhost:8000/api/work-orders', 'POST', [
    'order_no' => 'Abcdefgh',
    'subject' => '测试',
    'category' => '日常工单',
    'planned_start_time' => '2026-04-17 09:00:00',
    'planned_end_time' => '2026-04-17 18:00:00',
    'executor' => '测试'
]);
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "4. 测试正确格式: Abc123Xyz\n";
$result = testAPI('http://localhost:8000/api/work-orders', 'POST', [
    'order_no' => 'Abc123Xyz',
    'subject' => '测试工单',
    'category' => '日常工单',
    'description' => '测试验证规则',
    'planned_start_time' => '2026-04-17 09:00:00',
    'planned_end_time' => '2026-04-17 18:00:00',
    'executor' => '测试人员'
]);
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== 测试完成 ===\n";
