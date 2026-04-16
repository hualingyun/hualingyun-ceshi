<?php
function testAPI($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

echo "=== 测试工单管理系统 API ===\n\n";

echo "1. 测试获取工单列表 (GET /api/work-orders)\n";
$result = testAPI('http://localhost:8000/api/work-orders');
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "2. 测试创建工单 (POST /api/work-orders)\n";
$createData = [
    'order_no' => 'WO2026A001',
    'subject' => '服务器维护工单',
    'category' => '日常工单',
    'description' => '定期对服务器进行维护检查',
    'planned_start_time' => '2026-04-17 09:00:00',
    'planned_end_time' => '2026-04-17 18:00:00',
    'executor' => '张三'
];
$result = testAPI('http://localhost:8000/api/work-orders', 'POST', $createData);
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "3. 再次获取工单列表\n";
$result = testAPI('http://localhost:8000/api/work-orders');
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "4. 测试创建第二个工单\n";
$createData2 = [
    'order_no' => 'EV2026B002',
    'subject' => '紧急故障处理',
    'category' => '事件工单',
    'description' => '数据库连接异常需要紧急处理',
    'planned_start_time' => '2026-04-17 10:00:00',
    'planned_end_time' => '2026-04-17 12:00:00',
    'executor' => '李四'
];
$result = testAPI('http://localhost:8000/api/work-orders', 'POST', $createData2);
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "5. 测试更新工单 (PUT /api/work-orders/1)\n";
$updateData = [
    'status' => '处理中'
];
$result = testAPI('http://localhost:8000/api/work-orders/1', 'PUT', $updateData);
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE) . "\n\n";

echo "6. 最终工单列表\n";
$result = testAPI('http://localhost:8000/api/work-orders');
echo "状态码: {$result['code']}\n";
echo "响应: " . json_encode($result['response'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

echo "=== 测试完成 ===\n";
