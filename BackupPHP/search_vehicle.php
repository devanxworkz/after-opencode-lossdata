<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// === PostgreSQL credentials ===
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$pass = "123";

// === Persistent DB Connection ===
$connStr = "host=$host port=$port dbname=$dbname user=$user password=$pass";
$conn = @pg_pconnect($connStr);

if (!$conn) {
    error_log("DB connection failed (search API): " . date("Y-m-d H:i:s"));
    echo json_encode([]);
    exit;
}

// === Input Search Query ===
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

// === Safe Query (Prevents SQL Injection) ===
$sql = "
    SELECT vinnumber, ownername, phonenumber
    FROM allvin
    WHERE vinnumber ILIKE $1
       OR ownername ILIKE $1
       OR phonenumber ILIKE $1
    LIMIT 10
";

$searchParam = "%" . $q . "%";
$result = @pg_query_params($conn, $sql, [$searchParam]);

if (!$result) {
    error_log("Search Query failed for q='$q'");
    echo json_encode([]);
    exit;
}

$data = pg_fetch_all($result) ?: [];

// === Output JSON ===
echo json_encode($data);

// ? Do NOT close persistent connections
// pg_close($conn);
?>
