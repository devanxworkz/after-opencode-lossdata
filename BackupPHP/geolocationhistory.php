<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// ==== DB CONNECT ====
$conn = pg_pconnect("host=103.174.148.224 port=5432 dbname=rivotcommandcenterdb user=rivotcommandcenter password=123");

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB failed"]);
    exit;
}

// ==== INPUT ====
$vin   = $_GET['vin'] ?? '';
$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';
$page  = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 5000);

if (!$vin || !$start || !$end) {
    echo json_encode(["status" => "error", "message" => "Missing params"]);
    exit;
}

// ==== FORMAT TIME ====
$start = str_replace('T', ' ', $start);
$end   = str_replace('T', ' ', $end);

if (strlen($start) === 16) $start .= ':00';
if (strlen($end) === 16) $end   .= ':00';

$offset = ($page - 1) * $limit;

// ==== QUERY ====
$sql = "SELECT 
            time,
            lat_long,
            speed_kmph,
            soc,
            apusoc,
            tripkm,
            batvoltage,
            motortemp,
            controllermostemp,
            bmsmostemp,
            currentconsumption,
            inah,
            inah_by_charger,
            inah_by_regen,
            outah,
            remainingcapacity_ah,
            ntc,
            ev_power_state,
            currentrider,
            tirepressure
        FROM realtimedata
        WHERE vinnumber = $1
        AND time >= $2
        AND time <= $3
        ORDER BY time ASC
        LIMIT $limit OFFSET $offset";

$result = pg_query_params($conn, $sql, [$vin, $start, $end]);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => pg_last_error($conn)
    ]);
    exit;
}

// ==== DATA FORMAT ====
$data = [];

while ($row = pg_fetch_assoc($result)) {

    $lat = null;
    $lng = null;

    if (!empty($row["lat_long"])) {
        $parts = explode(',', $row["lat_long"]);
        if (count($parts) === 2) {
            $lat = (float) trim($parts[0]);
            $lng = (float) trim($parts[1]);
        }
    }

    $data[] = [
        "time" => $row["time"],

        "lat" => $lat,
        "lng" => $lng,

        "speed_kmph" => $row["speed_kmph"],
        "soc" => $row["soc"],
        "apusoc" => $row["apusoc"],
        "tripkm" => $row["tripkm"],

        "batvoltage" => $row["batvoltage"],

        "motortemp" => $row["motortemp"],
        "controllermostemp" => $row["controllermostemp"],
        "bmsmostemp" => $row["bmsmostemp"],

        "currentconsumption" => $row["currentconsumption"],

        "inah" => $row["inah"],
        "inah_by_charger" => $row["inah_by_charger"],
        "inah_by_regen" => $row["inah_by_regen"],
        "outah" => $row["outah"],
        "remainingcapacity_ah" => $row["remainingcapacity_ah"],

        "ntc" => $row["ntc"],

        "ev_power_state" => $row["ev_power_state"],
        "currentrider" => $row["currentrider"],
        "tirepressure" => $row["tirepressure"]
    ];
}

echo json_encode([
    "status" => "success",
    "page" => $page,
    "limit" => $limit,
    "count" => count($data),
    "data" => $data
]);
?>