<?php

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// ================================
// CORS
// ================================
$allowedOrigins = [
    'http://127.0.0.1:3000',
    'http://localhost:3000',
    'https://commandcenter.rivotmotors.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ================================
// HANDLE CORS PREFLIGHT REQUEST
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ================================
// NEW PostgreSQL DATABASE
// ================================
$host = '127.0.0.1';
$port = '5432';
$dbname = 'rivotdb';
$user = 'springapp';
$password = '1234';

// ================================
// CONNECT TO DATABASE
// ================================
$conn = pg_connect(
    "host=$host port=$port dbname=$dbname user=$user password=$password"
);

if (!$conn) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);

    exit;
}

// ================================
// FETCH ALL COLUMNS
// ================================
$sql = "
    SELECT
        vinnumber,
        ownername,
        odo,
        time,
        soc,
        batvoltage,
        recoengine,
        today_km,
        last_day_km,
        uptime,
        lat_long,
        updated_at
    FROM vehicle_latest_status
    ORDER BY vinnumber
";

$result = pg_query($conn, $sql);

if (!$result) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch vehicle data',
        'error' => pg_last_error($conn)
    ]);

    pg_close($conn);
    exit;
}

// ================================
// BUILD RESPONSE
// ================================
$vehicles = [];

while ($row = pg_fetch_assoc($result)) {

    $vehicles[] = [
        'vinnumber'   => $row['vinnumber'],
        'ownername'   => $row['ownername'],
        'odo'         => $row['odo'] !== null ? (float)$row['odo'] : null,
        'time'        => $row['time'],
        'soc'         => $row['soc'] !== null ? (float)$row['soc'] : null,
        'batvoltage'  => $row['batvoltage'] !== null ? (float)$row['batvoltage'] : null,
        'recoengine'  => $row['recoengine'] !== null ? (float)$row['recoengine'] : null,
        'today_km'    => $row['today_km'] !== null ? (float)$row['today_km'] : null,
        'last_day_km' => $row['last_day_km'] !== null ? (float)$row['last_day_km'] : null,
        'uptime'      => $row['uptime'],
        'lat_long'    => $row['lat_long'],
        'updated_at'  => $row['updated_at']
    ];
}

// ================================
// RETURN JSON
// ================================
echo json_encode([
    'success' => true,
    'count'   => count($vehicles),
    'data'    => $vehicles
], JSON_UNESCAPED_SLASHES);

// ================================
// CLOSE CONNECTION
// ================================
pg_free_result($result);
pg_close($conn);
?>
