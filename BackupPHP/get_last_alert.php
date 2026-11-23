<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$password = "123";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Get latest alert
    $query = "SELECT vinnumber, alerts, alert_time, latlong 
              FROM alerts 
              ORDER BY alert_time DESC 
              LIMIT 1";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Create unique key
        $result['unique_key'] = md5(
            ($result['vinnumber'] ?? '') . 
            ($result['alerts'] ?? '') . 
            ($result['alert_time'] ?? '')
        );

        echo json_encode([
            "status" => "success",
            "data" => $result
        ]);
    } else {
        echo json_encode([
            "status" => "empty",
            "message" => "No alerts found"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
