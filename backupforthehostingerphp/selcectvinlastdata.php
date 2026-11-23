<?php
// latest_200_by_vin.php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // allow React fetch (adjust if needed)

// ? DB config
// $host = "localhost";
// $dbname = "NX100";
// $user = "postgres";
// $pass = "1234";


$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$pass = "123";

// ? Connect
$conn = pg_pconnect("host=$host dbname=$dbname user=$user password=$pass");
if (!$conn) {
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// ? VIN param
$vin = isset($_GET['vin']) ? $_GET['vin'] : "";
if ($vin === "") {
    echo json_encode(["error" => "VIN parameter required"]);
    exit;
}

// ? Query last 200 rows

$sql = "
    SELECT vinnumber,
           speed_kmph,
           controllerMosTemp,
           motorTemp,
           bmsMosTemp,
           ntc,
           lat_long,
           time,
           currentconsumption,
           batvoltage,
           inah,
           inah_by_charger,
           inah_by_regen,
           outah,
           tirepressure,
           soc,
           apusoc,
           ev_power_state,
           currentrider
    FROM    realtimedata
    WHERE vinnumber = $1
    ORDER BY time DESC
    LIMIT 200
";

$result = pg_query_params($conn, $sql, [$vin]);
if (!$result) {
    echo json_encode(["error" => "Query failed"]);
    exit;
}

// ? Collect rows
$data = [];
while ($row = pg_fetch_assoc($result)) {
    $data[] = $row;
}

// ? Return JSON
echo json_encode($data);
?>
