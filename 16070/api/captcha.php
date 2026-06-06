<?php
session_start();

$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

$bgColor = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bgColor);

$colors = [
    imagecolorallocate($image, 0, 0, 0),
    imagecolorallocate($image, 50, 50, 50),
    imagecolorallocate($image, 100, 100, 100),
    imagecolorallocate($image, 150, 50, 50),
    imagecolorallocate($image, 50, 100, 150)
];

$charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $charset[rand(0, strlen($charset) - 1)];
}

$_SESSION['captcha'] = strtolower($code);

for ($i = 0; $i < 4; $i++) {
    $fontSize = 5;
    $x = 20 + $i * 25;
    $y = rand(10, 20);
    $color = $colors[rand(0, count($colors) - 1)];
    imagestring($image, $fontSize, $x, $y, $code[$i], $color);
}

for ($i = 0; $i < 5; $i++) {
    $x1 = rand(0, $width);
    $y1 = rand(0, $height);
    $x2 = rand(0, $width);
    $y2 = rand(0, $height);
    $color = $colors[rand(0, count($colors) - 1)];
    imageline($image, $x1, $y1, $x2, $y2, $color);
}

for ($i = 0; $i < 100; $i++) {
    $x = rand(0, $width);
    $y = rand(0, $height);
    $color = $colors[rand(0, count($colors) - 1)];
    imagesetpixel($image, $x, $y, $color);
}

header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
