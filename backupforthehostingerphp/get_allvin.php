<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// PostgreSQL credentials
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$password = "123";

// Get VIN from URL
$vin = $_GET['vin'] ?? '';

if (empty($vin)) {
    echo json_encode(["status"=>"error", "message"=>"VIN not provided"]);
    exit;
}

// Connect to PostgreSQL
$connStr = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = pg_pconnect($connStr);

if (!$conn) {
    echo json_encode(["status"=>"error", "message"=>"Failed to connect to database", "pg_error"=>pg_last_error()]);
    exit;
}

// Query the allvin table
$query = "SELECT * FROM allvin WHERE vinnumber = $1 LIMIT 1";
$result = pg_query_params($conn, $query, [$vin]);

if (!$result) {
    echo json_encode(["status"=>"error", "message"=>"Query failed", "pg_error"=>pg_last_error()]);
    exit;   // removed pg_close
}

// Fetch result
$data = pg_fetch_all($result) ?: [];

echo json_encode(["status"=>"success", "count"=>count($data), "data"=>$data]);

// ? Do NOT close persistent connection
?>
