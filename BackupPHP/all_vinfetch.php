<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// PostgreSQL credentials
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$password = "123";

// Build connection string
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";

// Persistent connection
$conn = pg_pconnect($conn_string);

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed", "pg_error" => pg_last_error()]);
    exit;
}

// Fetch all VINs
$query = "SELECT vinnumber FROM allvin ORDER BY vinnumber ASC";
$result = pg_query($conn, $query);

if (!$result) {
    echo json_encode(["status" => "error", "message" => "Query failed", "pg_error" => pg_last_error()]);
    exit;
}

$vins = [];
while ($row = pg_fetch_assoc($result)) {
    $vins[] = $row['vinnumber'];
}

// Return JSON response
echo json_encode([
    "status" => "success",
    "count" => count($vins),
    "vins"  => $vins
]);

// ? Do NOT close persistent connections
// pg_close($conn);
?>
