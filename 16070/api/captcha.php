<?php
session_start();
header('Content-Type: image/svg+xml');

$width = 120;
$height = 40;

$colors = ['#000000', '#323232', '#646464', '#963232', '#326496', '#e74c3c', '#3498db', '#2ecc71', '#f39c12'];
$charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $charset[rand(0, strlen($charset) - 1)];
}

$_SESSION['captcha'] = strtolower($code);

$svg = '<?xml version="1.0" encoding="UTF-8"?>';
$svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
$svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';

for ($i = 0; $i < 8; $i++) {
    $x1 = rand(0, $width);
    $y1 = rand(0, $height);
    $x2 = rand(0, $width);
    $y2 = rand(0, $height);
    $color = $colors[rand(0, count($colors) - 1)];
    $opacity = rand(10, 30) / 100;
    $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $color . '" stroke-width="1" opacity="' . $opacity . '"/>';
}

for ($i = 0; $i < 4; $i++) {
    $x = 20 + $i * 25;
    $y = 28 + rand(-5, 5);
    $rotate = rand(-20, 20);
    $color = $colors[rand(0, count($colors) - 1)];
    $fontSize = 20 + rand(0, 6);
    $svg .= '<text x="' . $x . '" y="' . $y . '" font-family="Arial, sans-serif" font-size="' . $fontSize . '" font-weight="bold" fill="' . $color . '" transform="rotate(' . $rotate . ' ' . $x . ' ' . $y . ')">' . $code[$i] . '</text>';
}

for ($i = 0; $i < 30; $i++) {
    $x = rand(0, $width);
    $y = rand(0, $height);
    $color = $colors[rand(0, count($colors) - 1)];
    $r = rand(1, 2);
    $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $r . '" fill="' . $color . '" opacity="0.5"/>';
}

$svg .= '</svg>';
echo $svg;
