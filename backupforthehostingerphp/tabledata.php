<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Database credentials
$host     = "103.174.148.224";
$port     = "5432";
$dbname   = "rivotcommandcenterdb";
$user     = "rivotcommandcenter";
$password = "123";

// Persistent connection (SAFE)
$conn = pg_pconnect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$conn) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Input
$vin    = $_GET['vin'] ?? '';
$start  = $_GET['start'] ?? null;
$end    = $_GET['end'] ?? null;

// Sanitize limit/offset
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if (empty($vin)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "VIN number is required"]);
    exit;
}

// ==== Fetch allvin info ====
$sqlAllvin = "SELECT * FROM allvin WHERE vinNumber = $1";
$resAllvin = pg_query_params($conn, $sqlAllvin, [$vin]);

if (!$resAllvin || pg_num_rows($resAllvin) === 0) {
    echo json_encode(["status" => "error", "message" => "VIN not found"]);
    exit;
}

$allvin = pg_fetch_assoc($resAllvin);

// ==== Build realtime query ====
$params = [$vin];
$timeCondition = "";

if ($start && $end) {
    $timeCondition = "AND time BETWEEN $2 AND $3";
    $params[] = $start;
    $params[] = $end;
}

// SAFE limit-offset
$sqlRealtime = "
    SELECT *
    FROM realtimedata
    WHERE vinnumber = $1
    $timeCondition
    ORDER BY time DESC
    LIMIT $limit OFFSET $offset
";

$resRealtime = pg_query_params($conn, $sqlRealtime, $params);

$dataRealtime = [];
while ($row = pg_fetch_assoc($resRealtime)) {
    if (!empty($row['ntc'])) {
        $row['ntc'] = array_map('trim', explode(',', $row['ntc']));
    }
    if (!empty($row['lat_long'])) {
        $row['lat_long'] = array_map('floatval', explode(',', $row['lat_long']));
    }
    $dataRealtime[] = $row;
}

echo json_encode([
    "status" => "success",
    "allvin" => $allvin,
    "realtimedata" => $dataRealtime
]);

// DO NOT CLOSE persistent connection
// pg_close($conn);
?>
