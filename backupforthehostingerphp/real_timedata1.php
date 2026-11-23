<?php
// Enable errors only for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// === DB credentials ===
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$password = "123";

// === Persistent DB connection (recommended) ===
$connStr = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = @pg_pconnect($connStr); // persistent connect

if (!$conn) {
    error_log("DB connection failed at " . date("Y-m-d H:i:s"));
    echo json_encode(["status" => "error", "message" => "Server error"]);
    exit;
}

// === Parameters ===
$vin    = trim($_GET['vin'] ?? '');
$latest = isset($_GET['latest']) && $_GET['latest'] == "1";
$start  = trim($_GET['start'] ?? '');
$end    = trim($_GET['end'] ?? '');

// Format timestamps safely
if ($start !== '') $start = date("Y-m-d H:i:s", strtotime($start));
if ($end !== '')   $end   = date("Y-m-d H:i:s", strtotime($end));

// === Query Builder ===
$params = [];
$query  = "";

// ? CASE 1: Latest record only
if ($latest) {
    if ($vin !== '') {
        $query = "SELECT * FROM realtimedata WHERE vinnumber = $1 ORDER BY time DESC LIMIT 1";
        $params = [$vin];
    } else {
        $query = "SELECT * FROM realtimedata ORDER BY time DESC LIMIT 1";
    }
}

// ? CASE 2: Range or last 50 records
else {
    $conditions = [];
    $i = 1;

    if ($vin !== '') {
        $conditions[] = "vinnumber = $" . $i++;
        $params[] = $vin;
    }

    if ($start !== '' && $end !== '') {
        $conditions[] = "time BETWEEN $" . $i++ . " AND $" . $i++;
        $params[] = $start;
        $params[] = $end;
    }

    $where = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    if ($start !== '' && $end !== '') {
        // Full range
        $query = "SELECT * FROM realtimedata $where ORDER BY time ASC";
    } else {
        // Last 50 records safely
        $query = "
            SELECT * FROM (
                SELECT * FROM realtimedata
                $where
                ORDER BY time DESC
                LIMIT 50
            ) t
            ORDER BY time ASC
        ";
    }
}

// === Execute Query ===
$result = empty($params)
    ? @pg_query($conn, $query)
    : @pg_query_params($conn, $query, $params);

if (!$result) {
    error_log("Query failed at " . date("Y-m-d H:i:s") . " | VIN=$vin");
    echo json_encode(["status" => "error", "message" => "Server error"]);
    exit;
}

// === Fetch rows ===
$data = [];
while ($row = pg_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);

// ? DO NOT close persistent connections
// pg_close($conn);
?>
