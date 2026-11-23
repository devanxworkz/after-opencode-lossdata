<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// === DB config ===
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$pass = "123";

// === Use persistent connection ===
$connStr = "host=$host dbname=$dbname user=$user password=$pass port=$port";
$conn = @pg_pconnect($connStr);

if (!$conn) {
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// === Get VIN ===
$vin = trim($_GET['vin'] ?? '');

if ($vin === "") {
    echo json_encode(["error" => "VIN parameter required"]);
    exit;
}

// === Query latest row ===
$sql = "
    SELECT time, ntc
    FROM realtimedata
    WHERE vinnumber = $1
    ORDER BY time DESC
    LIMIT 1
";

$result = @pg_query_params($conn, $sql, [$vin]);

if (!$result) {
    echo json_encode(["error" => "Query failed"]);
    exit;
}

// === Fetch row ===
$row = pg_fetch_assoc($result);

if ($row) {
    // convert comma-string ? array
    $row['ntc_array'] = explode(",", $row['ntc'] ?? '');
    echo json_encode($row);
} else {
    echo json_encode([]);  // no data found
}

// DO NOT close persistent connection
// pg_close($conn);  // ? remove
?>
