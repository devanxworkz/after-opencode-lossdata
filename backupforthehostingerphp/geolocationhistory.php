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
$conn = pg_pconnect($conn_string);

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// ==== Get Parameters ====
$vin   = $_GET['vin'] ?? '';
$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';

if (!$vin || !$start || !$end) {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit;
}

// ==== Normalize ISO8601 input ====
$start = str_replace('T', ' ', $start);
$end   = str_replace('T', ' ', $end);

if (strlen($start) === 16) $start .= ':00';
if (strlen($end) === 16) $end   .= ':00';

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
            lat_long
        FROM realtimedata
        WHERE vinnumber = $1
          AND time BETWEEN $2 AND $3
        ORDER BY time ASC";

$result = pg_query_params($conn, $sql, [$vin, $start, $end]);

if (!$result) {
    echo json_encode(["status" => "error", "message" => "Query failed"]);
    exit;
}

// ==== Fetch Data ====
$data = [];
while ($row = pg_fetch_assoc($result)) {
    if (!empty($row["lat_long"])) {
        $latlng = array_map('floatval', array_map('trim', explode(',', $row["lat_long"])));

        if (count($latlng) === 2) {
            $ntcArray = [];
            if (!empty($row["ntc"])) {
                $ntcArray = array_map('trim', explode(',', $row["ntc"]));
                $ntcArray = array_map(fn($v) => is_numeric($v) ? (float)$v : $v, $ntcArray);
            }

            $data[] = [
                "time" => $row["time"],
                "vin" => $row["vinnumber"],
                "lat" => $latlng[0],
                "lng" => $latlng[1],
                "speed_kmph" => $row["speed_kmph"],
                "currentconsumption" => $row["currentconsumption"],
                "soc" => $row["soc"],
                "tripkm" => $row["tripkm"],
                "motortemp" => $row["motortemp"],
                "controllermostemp" => $row["controllermostemp"],
                "bmsmostemp" => $row["bmsmostemp"],
                "batvoltage" => $row["batvoltage"],
                "inah" => $row["inah"],
                "outah" => $row["outah"],
                "ntc" => $ntcArray
            ];
        }
    }
}

echo json_encode(["status" => "success", "count" => count($data), "data" => $data]);

// ? REMOVE pg_close() — persistent connection must stay open
?>
