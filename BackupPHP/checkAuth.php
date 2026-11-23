<?php
// Detect HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowedOrigins = [
    "http://127.0.0.1:3000",
    "http://localhost:3000",
    "https://commandcenter.rivotmotors.com"
];

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_set_cookie_params([
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => $isHttps ? 'None' : 'Lax'
]);

session_start();

if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(["authenticated" => false]);
    exit;
}

echo json_encode(["authenticated" => true]);
