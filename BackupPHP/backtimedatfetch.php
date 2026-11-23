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

// ==== Connect to PostgreSQL ====
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = pg_pconnect($conn_string);   // persistent connection

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

// ==== Get Parameters ====
$vin   = $_GET['vin']   ?? '';
$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';

if (!$vin || !$start || !$end) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Missing required parameters: vin, start, end"
    ]);
    exit;  // ? removed pg_close()
}

// ==== Normalize ISO8601 ====
$start = str_replace('T', ' ', $start);
$end   = str_replace('T', ' ', $end);

if (strlen($start) === 16) $start .= ':00';  
if (strlen($end) === 16)   $end   .= ':00';

// ==== Validate timestamps ====
$start_ts = strtotime($start);
$end_ts   = strtotime($end);

if ($start_ts === false || $end_ts === false) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid date format. Expected: YYYY-MM-DDTHH:MM"
    ]);
    exit;  // ? removed pg_close()
}

$start = date("Y-m-d H:i:s", $start_ts);
$end   = date("Y-m-d H:i:s", $end_ts);

// ==== SQL Query ====
$sql = "SELECT 
            vinnumber,
            time, 
            speed_kmph, 
            motortemp, 
            controllermostemp, 
            bmsmostemp, 
            currentconsumption, 
            ntc,
            soc,
            batvoltage,
            tripkm,
            inah,
            outah,
            inah_by_charger,
            inah_by_regen,
            remainingcapacity_ah,
            currentrider,
            lat_long,
            data
        FROM realtimedata
        WHERE vinnumber = $1
          AND time BETWEEN $2 AND $3
        ORDER BY time DESC";

// ==== Execute ====
$result = pg_query_params($conn, $sql, [$vin, $start, $end]);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Query execution failed"
    ]);
    exit;  // ? removed pg_close()
}

// ==== Build Response ====
$data = [];
while ($row = pg_fetch_assoc($result)) {
    $ntcArray = [];
    if (!empty($row["ntc"])) {
        $ntcArray = array_map('trim', explode(',', $row["ntc"]));
        $ntcArray = array_map(function($v) {
            return is_numeric($v) ? (float)$v : $v;
        }, $ntcArray);
    }

  $data[] = [
    "time" => $row["time"],
    "currentconsumption" => isset($row["currentconsumption"]) ? (float)$row["currentconsumption"] : null,
    "speed_kmph" => isset($row["speed_kmph"]) ? (float)$row["speed_kmph"] : null,
    "motortemp" => isset($row["motortemp"]) ? (float)$row["motortemp"] : null,
    "controllermostemp" => isset($row["controllermostemp"]) ? (float)$row["controllermostemp"] : null,
    "bmsmostemp" => isset($row["bmsmostemp"]) ? (float)$row["bmsmostemp"] : null,
    "ntc" => $ntcArray,
    "soc" => isset($row["soc"]) ? (float)$row["soc"] : null,
    "batvoltage" => isset($row["batvoltage"]) ? (float)$row["batvoltage"] : null,
    "tripkm" => isset($row["tripkm"]) ? (float)$row["tripkm"] : null,
    "inah" => isset($row["inah"]) ? (float)$row["inah"] : null,
    "outah" => isset($row["outah"]) ? (float)$row["outah"] : null,
    "inah_by_charger" => isset($row["inah_by_charger"]) ? (float)$row["inah_by_charger"] : null,
    "inah_by_regen" => isset($row["inah_by_regen"]) ? (float)$row["inah_by_regen"] : null,
    "remainingcapacity_ah" => isset($row["remainingcapacity_ah"]) ? (float)$row["remainingcapacity_ah"] : null,
    "currentrider" => $row["currentrider"] ?? null,
    "lat_long" => !empty($row["lat_long"]) 
        ? array_map('floatval', array_map('trim', explode(',', $row["lat_long"])))
        : null,
    "data" => $row["data"]
];
}

// ==== Return JSON ====
echo json_encode([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);

// ? Do NOT close persistent connections
// pg_close($conn);
?>
