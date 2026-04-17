<?php
// 测试脚本 - 创建符合新规则的工单

$url = 'http://localhost:8000/api/tickets';

$tickets = [
    [
        'ticket_no' => 'Test01',
        'subject' => '测试工单',
        'category' => '日常工单',
        'description' => '这是一个测试工单',
        'assignee' => '张三'
    ],
    [
        'ticket_no' => 'Server02',
        'subject' => '服务器维护',
        'category' => '日常工单',
        'description' => '每月例行服务器维护',
        'assignee' => '李四',
        'planned_start_time' => '2026-04-20T09:00',
        'planned_end_time' => '2026-04-20T18:00'
    ],
    [
        'ticket_no' => 'NetWork3',
        'subject' => '网络故障处理',
        'category' => '事件工单',
        'description' => '办公室网络连接中断',
        'assignee' => '王五'
    ],
    [
        'ticket_no' => 'SysUpg04',
        'subject' => '系统升级',
        'category' => '日常工单',
        'description' => 'ERP系统版本升级',
        'assignee' => '赵六'
    ]
];

foreach ($tickets as $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    echo "Created: {$data['ticket_no']} - HTTP: $httpCode\n";
    if ($httpCode != 200) {
        echo "Response: $response\n";
    }
}

echo "\nAll tickets created!\n";
