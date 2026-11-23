<?php
header("Content-Type: application/json");

// CORS (adjust if your React app runs on a different port)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// --- DB connection ---
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$pass = "123";

// Connect
$conn = pg_pconnect("host=$host port=$port dbname=$dbname user=$user password=$pass");
if (!$conn) {
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// --- Input ---
$vin = isset($_GET['vin']) ? trim($_GET['vin']) : '';
if ($vin === '') {
    echo json_encode(["error" => "VIN is required"]);
    exit;
}

// --- Query ---
$sql = "
  SELECT
    vinnumber,
    speed_kmph,
    controllerMosTemp AS controllermostemp,
    motorTemp         AS motortemp,
    bmsMosTemp        AS bmsmostemp,
    ntc,
    lat_long,
    time,
    currentconsumption,
    batvoltage,
    soc,
    apuSoc            AS apusoc,
    tirepressure,
    inAH              AS inah,
    inAH_by_charger   AS inah_by_charger,
    inAH_by_regen     AS inah_by_regen,
    outAH             AS outah,
    ev_power_state,
    currentrider,
    tripkm,
    remainingcapacity_ah
  FROM realtimedata
  WHERE vinnumber = $1
  ORDER BY time DESC
  LIMIT 1
";

$res = pg_query_params($conn, $sql, [$vin]);
if (!$res || pg_num_rows($res) === 0) {
    echo json_encode(["error" => "No data found"]);
    exit;
}

$row = pg_fetch_assoc($res);

// Optional: force numeric for known numeric fields
$numericKeys = [
    "speed_kmph","controllermostemp","motortemp","bmsmostemp","currentconsumption",
    "batvoltage","soc","apusoc","tirepressure","inah","inah_by_charger","inah_by_regen","outah"
];
foreach ($numericKeys as $k) {
    if (isset($row[$k])) $row[$k] = is_numeric($row[$k]) ? 0 + $row[$k] : null;
}

echo json_encode($row);
pg_close($conn);
?>
