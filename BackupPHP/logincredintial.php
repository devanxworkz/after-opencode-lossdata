
<?php

// Detect environment
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
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Session cookie settings (ENV-AWARE)
session_set_cookie_params([
    'secure'   => $isHttps,        // true on HTTPS, false locally
    'httponly' => true,
    'samesite' => $isHttps ? 'None' : 'Lax'
]);

session_start();

header("Content-Type: application/json");
require_once "db.php";

// Read JSON
$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing credentials"]);
    exit;
}

// DB lookup
$stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// Verify password
if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];

    echo json_encode([
        "success" => true,
        "message" => "Login successful"
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Invalid credentials"
    ]);
}
