<?php
$cookieFile = __DIR__ . '/cookies_login.txt';

$ch = curl_init('http://127.0.0.1:8080/api/captcha.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$img = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "验证码HTTP状态: $code\n";
echo "验证码内容长度: " . strlen($img) . "\n";
echo "验证码前100字符: " . substr($img, 0, 100) . "\n\n";

$ch = curl_init('http://127.0.0.1:8080/api/auth.php?action=login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'Admin', 'password' => 'Admin.123', 'captcha' => 'wrong']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "登录HTTP状态: $httpCode\n";
echo "Content-Type: $contentType\n";
echo "登录响应: $response\n";
echo "\nJSON解析结果: ";
var_dump(json_decode($response, true));
