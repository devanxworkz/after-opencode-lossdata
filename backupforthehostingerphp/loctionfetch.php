<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// ==== Database Config ====
$host     = "103.174.148.224";
$port     = "5432";
$dbname   = "rivotcommandcenterdb";
$user     = "rivotcommandcenter";
$password = "123";

// ==== Use Persistent PostgreSQL Connection ====
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = pg_pconnect($conn_string);

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed (pg_pconnect)"
    ]);
    exit;
}

// ==== Get Input Parameters ====
$vin   = $_GET['vin']   ?? '';
$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';

if (!$vin || !$start || !$end) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Missing required parameters: vin, start, end"
    ]);
    exit;
}

// ==== Fix timestamp formats ====
$start = str_replace('T', ' ', $start);
$end   = str_replace('T', ' ', $end);

// If time is in "YYYY-MM-DD HH:MM", add seconds
if (strlen($start) === 16) $start .= ":00";
if (strlen($end) === 16)   $end   .= ":00";

$start_ts = strtotime($start);
$end_ts   = strtotime($end);

if ($start_ts === false || $end_ts === false) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid date format"]);
    exit;
}

if ($end_ts <= $start_ts) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "End time must be greater than start time"]);
    exit;
}

// Format for SQL
$start = date("Y-m-d H:i:s", $start_ts);
$end   = date("Y-m-d H:i:s", $end_ts);

// ==== SQL ====
$sql = "
    SELECT time, lat_long
    FROM realtimedata
    WHERE vinnumber = $1
      AND time BETWEEN $2 AND $3
    ORDER BY time ASC
";

$result = pg_query_params($conn, $sql, [$vin, $start, $end]);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Query failed"
    ]);
    exit;
}

// ==== Process Records ====
$data = [];
while ($row = pg_fetch_assoc($result)) {
    if (!empty($row["lat_long"])) {
        $coords = array_map('floatval', array_map('trim', explode(',', $row["lat_long"])));

        if (count($coords) === 2) {
            $data[] = [
                "time" => $row["time"],
                "lat"  => $coords[0],
                "lng"  => $coords[1]
            ];
        }
    }
}

// ==== Output ====
echo json_encode([
    "status" => "success",
    "vin"    => $vin,
    "count"  => count($data),
    "data"   => $data
]);
?>
