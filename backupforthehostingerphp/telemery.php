<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Database config
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$password = "123";

// Connect (FIXED)
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = pg_pconnect($conn_string);
if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Get VIN from request
$vin = isset($_GET['vin']) ? trim($_GET['vin']) : '';
if ($vin === '') {
    echo json_encode(["error" => "VIN parameter is required"]);
    exit;
}

// Fetch last 500 records for the given VIN
$query = "SELECT
              time, 
              currentconsumption, 
              speed_kmph, 
              motortemp, 
              controllermostemp, 
              bmsmostemp,
              ntc,
              soc, 
              batvoltage,
              tripkm,
              inah,
              outah,
              lat_long
          FROM realtimedata 
          WHERE vinnumber = $1
          ORDER BY time DESC 
          LIMIT 500";

$result = pg_query_params($conn, $query, [$vin]);
if (!$result) {
    echo json_encode(["error" => "Query failed", "pg_error" => pg_last_error()]);
    exit;
}

// Convert rows to array
$data = [];
while ($row = pg_fetch_assoc($result)) {
    $ntcArray = [];
    if (!empty($row["ntc"])) {
        $ntcArray = array_map('trim', explode(',', $row["ntc"]));
        $ntcArray = array_map(function($v) {
            return is_numeric($v) ? (int)$v : $v;
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
        "lat_long" => !empty($row["lat_long"]) 
            ? array_map('floatval', array_map('trim', explode(',', $row["lat_long"])))
            : null
    ];
}

// Send JSON (oldest ? newest)
echo json_encode(array_reverse($data));

// Do NOT close persistent connection
// pg_close($conn); // ? remove this
?>
