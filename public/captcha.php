<?php
ob_start();
// check if php gd extension is loaded
if (!extension_loaded('gd')) {
    die("no GD installed");
}
$suppress_html_header=true;
include ("header.php");
$captcha_length = 5;
$available_letters = 'ABCDEFGHJKLMNPRTUVWXYZ2346789';
$captcha = '';
for ($i=0; $i < $captcha_length; $i++) {
    do { $ipos = mt_rand(0, strlen($available_letters) - 1); }
    while (stripos($captcha, $available_letters[$ipos]) !== false);
    $captcha .= $available_letters[$ipos];
}
$_SESSION['captcha_string'] = $captcha;
$im = imagecreatetruecolor($captcha_length*38, 70);
$bg = imagecolorallocate($im, 255, 255, 255);
imagefill($im, 0, 0, $bg);
for($i=0;$i<300;$i++) {
    $done = imagesetthickness($im, 3);
    $lines = imagecolorallocate($im, rand(150, 220), rand(150, 220), rand(150, 220));
    $start_x = rand(0,$captcha_length*38);
    $start_y = rand(0,70);
    $end_x = $start_x + rand(0,20);
    $end_y = $start_y + rand(0,20);
    imageline($im, $start_x, $start_y, $end_x, $end_y, $lines);
}
for ($i=0; $i < $captcha_length; $i++) {
    $text_color = imagecolorallocate($im, rand(0, 100), rand(10, 100), rand(0, 100));
    imagefttext($im, 35, rand(-10, 10), 20 + ($i * 30) + rand(-5, +5), 35 + rand(10, 30), $text_color, '../tagsets/fonts/FreeSerifBold.ttf', $captcha[$i]);
}
header('Content-type: image/png');
header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, proxy-revalidate');
imagepng($im);
imagedestroy($im);
?>
