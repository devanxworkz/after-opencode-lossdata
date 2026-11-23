<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// PostgreSQL connection
$conn = pg_connect(
    'host=103.174.148.224 port=5432 dbname=rivotcommandcenterdb user=rivotcommandcenter password=123'
);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

$vin  = trim($input['vin']  ?? '');
$note = trim($input['note'] ?? '');

if ($vin === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'VIN is required']);
    exit;
}

// Update ONLY that VIN row
$sql = 'UPDATE allvin
        SET note = $1
        WHERE vinnumber = $2';

$result = pg_query_params($conn, $sql, [$note, $vin]);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => pg_last_error($conn)
    ]);
    exit;
}

if (pg_affected_rows($result) === 0) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'VIN not found'
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'vin'    => $vin,
    'note'   => $note
]);

pg_close($conn);
?>