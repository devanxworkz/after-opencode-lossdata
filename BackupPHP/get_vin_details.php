<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// === PostgreSQL credentials ===
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$password = "123";

// === Get VIN ===
$vin = trim($_GET['vin'] ?? '');

if ($vin === '') {
    echo json_encode([
        "status" => "error",
        "message" => "VIN not provided"
    ]);
    exit;
}

// === Persistent Connection (pg_pconnect) ===
$connStr = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = @pg_pconnect($connStr);

if (!$conn) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error"
    ]);
    exit;
}

// === Query ===
$query = "SELECT * FROM allvin WHERE vinnumber = $1 LIMIT 1";
$result = @pg_query_params($conn, $query, [$vin]);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error"
    ]);
    exit;
}

// === Fetch Row ===
$row = pg_fetch_assoc($result);

echo json_encode([
    "status"  => $row ? "success" : "error",
    "data"    => $row ?: null,
    "message" => $row ? null : "VIN not found"
]);

// DO NOT CLOSE persistent connection
// pg_close($conn);  // ? REMOVE THIS
?>
