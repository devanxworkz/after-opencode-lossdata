<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Database credentials
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$password = "123"; // fixed variable name

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Connect to PostgreSQL using PDO
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Validate VIN parameter
    if (!isset($_GET['vin']) || trim($_GET['vin']) === '') {
        echo json_encode(["error" => "VIN is required"]);
        exit();
    }

    $vin = trim($_GET['vin']);

    // Fetch data
    $stmt = $pdo->prepare("SELECT * FROM allvin WHERE vinnumber = :vin LIMIT 1");
    $stmt->execute(['vin' => $vin]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(["status" => "success", "data" => $result]);
    } else {
        echo json_encode(["status" => "error", "message" => "No data found for VIN"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    error_log($e->getMessage());
}

