<?php

//生成二维码
require __DIR__ . '/includes/phpqrcode/phpqrcode.php';

$text = isset($_GET['text']) ? $_GET['text'] : '';
$outfile = isset($_GET['outfile']) ? urldecode($_GET['outfile']) : false;
$size = isset($_GET['size']) ? $_GET['size'] : 10;
$margin = isset($_GET['margin']) ? $_GET['margin'] : 2;
$saveandprint = isset($_GET['saveandprint']) ? (bool) $_GET['saveandprint'] : false;


QRcode::png($text, $outfile, QR_ECLEVEL_L, $size, $margin, $saveandprint);
exit;