<?php
require '../vendor/autoload.php';
use Endroid\QrCode\QrCode;

$user_id = $_GET['user_id'] ?? 1; 
$qrCode = new QrCode("User ID: " . $user_id);

header('Content-Type: '.$qrCode->getContentType());
echo $qrCode->writeString();
?>
